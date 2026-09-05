<?php

namespace JobMarket\Infrastructure;

use JobMarket\Domain\Authentication\OAuthStateStoreInterface;

class OAuthStateStore implements OAuthStateStoreInterface
{
    private string $storageDir;

    public function __construct(?string $storageDir = null)
    {
        $this->storageDir = $storageDir ?? (sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'jobmarket_oauth_states');
        if (!is_dir($this->storageDir)) {
            @mkdir($this->storageDir, 0700, true);
        }
    }

    public function save(string $state, array $data, int $ttl = 300): void
    {
        $this->clearExpired();

        $filePath = $this->getFilePath($state);
        $payload = [
            "state"      => $state,
            "nonce"      => $data["nonce"] ?? "",
            "role"       => array_key_exists("role", $data) ? $data["role"] : null,
            "return_url" => $data["return_url"] ?? null,
            "created_at" => $data["created_at"] ?? time(),
            "expires_at" => time() + $ttl
        ];

        // Write to a temporary file first, then atomically rename to target
        $tempPath = $this->storageDir . DIRECTORY_SEPARATOR . "tmp_save_" . bin2hex(random_bytes(16)) . ".tmp";
        file_put_contents($tempPath, json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), LOCK_EX);
        @rename($tempPath, $filePath);
    }

    /**
     * Atomically consume state data.
     * Uses atomic rename() to ensure exactly one consumer obtains the state.
     * All concurrent or subsequent callers receive null.
     */
    public function consume(string $state): ?array
    {
        $filePath = $this->getFilePath($state);
        if (!file_exists($filePath)) {
            return null;
        }

        // Process-unique target name in the same filesystem directory for atomic rename
        $consumerPath = $this->storageDir . DIRECTORY_SEPARATOR . "consuming_" . hash("sha256", $state) . "_" . bin2hex(random_bytes(16)) . ".tmp";

        // Atomic operation: exactly one concurrent caller succeeds in renaming
        if (!@rename($filePath, $consumerPath)) {
            return null;
        }

        // We are the exclusive owner of this consumed file
        $content = @file_get_contents($consumerPath);
        @unlink($consumerPath);

        if (!$content) {
            return null;
        }

        $data = json_decode($content, true);
        if (!is_array($data)) {
            return null;
        }

        if (($data["expires_at"] ?? 0) <= time()) {
            return null; // Expired
        }

        return $data;
    }

    public function clearExpired(): void
    {
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
            // Clean orphan tmp files older than 10 minutes
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
