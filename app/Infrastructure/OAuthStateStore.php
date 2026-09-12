<?php

namespace JobMarket\Infrastructure;

use JobMarket\Domain\Authentication\OAuthStateStoreInterface;
use JobMarket\Facades\Config;
use PDO;
use Throwable;

class OAuthStateStore implements OAuthStateStoreInterface
{
    private string $storageDir;
    private ?PDO $db = null;

    public function __construct(?string $storageDir = null, ?PDO $db = null)
    {
        $base = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 2);
        $this->storageDir = $storageDir ?? ($base . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'oauth_states');
        if (!is_dir($this->storageDir)) {
            @mkdir($this->storageDir, 0777, true);
        }

        if ($db !== null) {
            $this->db = $db;
        } else {
            try {
                if (class_exists(Config::class)) {
                    $config = Config::env();
                    $this->db = new PDO(
                        "mysql:dbname={$config['dbname']};host={$config['host']};charset=utf8mb4",
                        $config["user"],
                        $config["password"],
                        [
                            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                            PDO::ATTR_TIMEOUT => 5
                        ]
                    );
                    // Ensure table exists
                    $this->db->exec("CREATE TABLE IF NOT EXISTS `oauth_states` (
                        `state` VARCHAR(128) NOT NULL PRIMARY KEY,
                        `data` TEXT NOT NULL,
                        `expires_at` INT UNSIGNED NOT NULL,
                        INDEX `idx_oauth_expires` (`expires_at`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
                }
            } catch (Throwable $e) {
                // Silently fallback to filesystem / cookie if DB unavailable
                $this->db = null;
            }
        }
    }

    public function save(string $state, array $data, int $ttl = 300): void
    {
        $this->clearExpired();

        $payload = [
            "state"      => $state,
            "nonce"      => $data["nonce"] ?? "",
            "role"       => array_key_exists("role", $data) ? $data["role"] : null,
            "return_url" => $data["return_url"] ?? null,
            "created_at" => $data["created_at"] ?? time(),
            "expires_at" => time() + $ttl
        ];

        $encodedPayload = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        // 1. Primary: Save to Database (shared across all cluster nodes)
        if ($this->db !== null) {
            try {
                $stmt = $this->db->prepare("REPLACE INTO `oauth_states` (`state`, `data`, `expires_at`) VALUES (?, ?, ?)");
                $stmt->execute([$state, $encodedPayload, $payload["expires_at"]]);
            } catch (Throwable $e) {
                // Continue to secondary stores
            }
        }

        // 2. Secondary: Save to local storage file
        if (is_dir($this->storageDir)) {
            $filePath = $this->getFilePath($state);
            $tempPath = $this->storageDir . DIRECTORY_SEPARATOR . "tmp_save_" . bin2hex(random_bytes(16)) . ".tmp";
            @file_put_contents($tempPath, $encodedPayload, LOCK_EX);
            @rename($tempPath, $filePath);
        }

        // 3. Tertiary: Save to cookie fallback
        $cookieName = "oauth_st_" . substr(hash("sha256", $state), 0, 16);
        $cookieVal = base64_encode($encodedPayload);
        if (!headers_sent()) {
            @setcookie($cookieName, $cookieVal, [
                'expires'  => time() + $ttl,
                'path'     => '/',
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
        }
    }

    public function consume(string $state): ?array
    {
        $data = null;

        // 1. Primary: Consume from Database
        if ($this->db !== null) {
            try {
                $stmt = $this->db->prepare("SELECT `data`, `expires_at` FROM `oauth_states` WHERE `state` = ? LIMIT 1");
                $stmt->execute([$state]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($row) {
                    $delStmt = $this->db->prepare("DELETE FROM `oauth_states` WHERE `state` = ?");
                    $delStmt->execute([$state]);

                    if ((int)$row["expires_at"] > time()) {
                        $decoded = json_decode($row["data"], true);
                        if (is_array($decoded)) {
                            $data = $decoded;
                        }
                    }
                }
            } catch (Throwable $e) {
                // Continue to fallback stores
            }
        }

        // 2. Secondary: Consume from local file
        $filePath = $this->getFilePath($state);
        if (file_exists($filePath)) {
            $consumerPath = $this->storageDir . DIRECTORY_SEPARATOR . "consuming_" . hash("sha256", $state) . "_" . bin2hex(random_bytes(16)) . ".tmp";
            if (@rename($filePath, $consumerPath)) {
                $content = @file_get_contents($consumerPath);
                @unlink($consumerPath);
                if ($data === null && $content) {
                    $decoded = json_decode($content, true);
                    if (is_array($decoded) && ($decoded["expires_at"] ?? 0) > time()) {
                        $data = $decoded;
                    }
                }
            }
        }

        // 3. Tertiary: Cookie fallback if server cluster has non-shared local disk
        $cookieName = "oauth_st_" . substr(hash("sha256", $state), 0, 16);
        if ($data === null && isset($_COOKIE[$cookieName])) {
            $raw = base64_decode($_COOKIE[$cookieName], true);
            if ($raw) {
                $decoded = json_decode($raw, true);
                if (is_array($decoded) && ($decoded["expires_at"] ?? 0) > time() && ($decoded["state"] ?? "") === $state) {
                    $data = $decoded;
                }
            }
        }

        // Clear cookie
        if (isset($_COOKIE[$cookieName]) && !headers_sent()) {
            @setcookie($cookieName, "", [
                'expires'  => time() - 3600,
                'path'     => '/',
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
        }

        return $data;
    }

    public function clearExpired(): void
    {
        if ($this->db !== null) {
            try {
                $stmt = $this->db->prepare("DELETE FROM `oauth_states` WHERE `expires_at` <= ?");
                $stmt->execute([time()]);
            } catch (Throwable $e) {}
        }

        if (!is_dir($this->storageDir)) {
            return;
        }

        $files = @scandir($this->storageDir);
        if (!$files) {
            return;
        }

        $now = time();
        foreach ($files as $file) {
            if ($file === "." || $file === ".." || (!str_ends_with($file, ".json") && !str_ends_with($file, ".tmp"))) {
                continue;
            }
            $fullPath = $this->storageDir . DIRECTORY_SEPARATOR . $file;
            if (str_ends_with($file, ".tmp")) {
                if ($now - @filemtime($fullPath) > 600) {
                    @unlink($fullPath);
                }
                continue;
            }
            $content = @file_get_contents($fullPath);
            if ($content) {
                $data = json_decode($content, true);
                if (is_array($data) && ($data["expires_at"] ?? 0) <= $now) {
                    @unlink($fullPath);
                }
            }
        }
    }

    private function getFilePath(string $state): string
    {
        $hash = hash("sha256", $state);
        return $this->storageDir . DIRECTORY_SEPARATOR . "state_" . $hash . ".json";
    }
}
