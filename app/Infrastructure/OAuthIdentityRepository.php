<?php

namespace JobMarket\Infrastructure;

use JobMarket\Domain\Authentication\OAuthIdentity;
use JobMarket\Domain\Authentication\OAuthIdentityRepositoryInterface;
use JobMarket\Exceptions\ValidationException;
use JobMarket\Facades\Config;
use InvalidArgumentException;
use PDO;
use Throwable;

class OAuthIdentityRepository implements OAuthIdentityRepositoryInterface
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        if ($db !== null) {
            $this->db = $db;
        } else {
            $config = Config::env();
            $this->db = new PDO(
                "mysql:dbname={$config['dbname']};host={$config['host']};charset=utf8mb4",
                $config["user"],
                $config["password"],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
        }
    }

    public function getDB(): PDO
    {
        return $this->db;
    }

    public function findIdentity(string $provider, string $providerSubject): ?OAuthIdentity
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM oauth_identities WHERE provider = ? AND provider_subject = ? LIMIT 1"
        );
        $stmt->execute([$provider, $providerSubject]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return new OAuthIdentity(
            $row["user_id"],
            $row["provider"],
            $row["provider_subject"],
            $row["email_at_link"] ?? null,
            $row["id"],
            $row["created_at"] ?? null,
            $row["updated_at"] ?? null
        );
    }

    public function findIdentityRecord(string $provider, string $providerSubject): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM oauth_identities WHERE provider = ? AND provider_subject = ? LIMIT 1"
        );
        $stmt->execute([$provider, $providerSubject]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function findIdentityByUserId(string $userId, string $provider): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM oauth_identities WHERE user_id = ? AND provider = ? LIMIT 1"
        );
        $stmt->execute([$userId, $provider]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function createIdentity(OAuthIdentity $identity): void
    {
        if ($identity->getProvider() !== "google") {
            throw new InvalidArgumentException("Chỉ hỗ trợ nhà cung cấp 'google'.");
        }

        $stmt = $this->db->prepare(
            "INSERT INTO oauth_identities (id, user_id, provider, provider_subject, email_at_link)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $identity->getId(),
            $identity->getUserId(),
            $identity->getProvider(),
            $identity->getProviderSubject(),
            $identity->getEmailAtLink()
        ]);
    }

    public function findLocalUserByEmail(string $email): ?array
    {
        $normalizedEmail = strtolower(trim($email));
        if (empty($normalizedEmail)) {
            return null;
        }

        $stmt = $this->db->prepare(
            "SELECT id, name, email, role, status, created_at FROM users WHERE LOWER(email) = ? LIMIT 1"
        );
        $stmt->execute([$normalizedEmail]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function createOAuthUserWithIdentity(array $userData, array $identityData): array
    {
        // 1. Consistent normalization of email and email_at_link
        $email = strtolower(trim($userData["email"] ?? ""));
        $name = trim($userData["name"] ?? "");
        $role = $userData["role"] ?? "student";
        $provider = trim($identityData["provider"] ?? "");
        $providerSubject = trim($identityData["provider_subject"] ?? "");
        $rawEmailAtLink = $identityData["email_at_link"] ?? $email;
        $emailAtLink = !empty($rawEmailAtLink) ? strtolower(trim($rawEmailAtLink)) : $email;

        // Validation
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("Email không hợp lệ.");
        }
        if (empty($name)) {
            throw new InvalidArgumentException("Tên người dùng không được để trống.");
        }
        if (!in_array($role, ["student", "company"], true)) {
            throw new InvalidArgumentException("Vai trò '{$role}' không được phép đăng ký qua Google.");
        }
        // Restrict provider exclusively to 'google' (no generic multi-provider pathway in P0-01)
        if ($provider !== "google") {
            throw new InvalidArgumentException("Chỉ hỗ trợ nhà cung cấp 'google'.");
        }
        if (empty($providerSubject)) {
            throw new InvalidArgumentException("Thông tin provider_subject không hợp lệ.");
        }

        $this->db->beginTransaction();
        try {
            // 1. Collision check: Local user with same normalized email must not exist (no auto-linking)
            $stmtUserCheck = $this->db->prepare("SELECT id FROM users WHERE LOWER(email) = ? LIMIT 1 FOR UPDATE");
            $stmtUserCheck->execute([$email]);
            if ($stmtUserCheck->fetch()) {
                throw new ValidationException(
                    ["email" => ["Email này đã tồn tại trên hệ thống. Vui lòng đăng nhập bằng mật khẩu."]],
                    "Email collision: tài khoản đã tồn tại"
                );
            }

            // 2. Collision check: Identity (provider, provider_subject) must not already exist
            $stmtIdCheck = $this->db->prepare(
                "SELECT id, user_id FROM oauth_identities WHERE provider = ? AND provider_subject = ? LIMIT 1 FOR UPDATE"
            );
            $stmtIdCheck->execute([$provider, $providerSubject]);
            if ($stmtIdCheck->fetch()) {
                throw new ValidationException(
                    ["identity" => ["Định danh Google này đã được liên kết với một tài khoản khác."]],
                    "Identity collision: định danh Google đã tồn tại"
                );
            }

            // 3. Step A: Insert User
            $userId = "usr-" . uniqid();
            // Secure random password hash: users.password column is NOT NULL
            $randomPasswordHash = password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT);

            $insertUserStmt = $this->db->prepare(
                "INSERT INTO users (id, name, email, password, role, status) VALUES (?, ?, ?, ?, ?, 'active')"
            );
            $insertUserStmt->execute([
                $userId,
                $name,
                $email,
                $randomPasswordHash,
                $role
            ]);

            // 4. Step B: If role === 'company', atomically create company skeleton pending
            $companyRecord = null;
            if ($role === "company") {
                $companyId = "comp-" . uniqid();
                $companyName = !empty($userData["company_name"]) ? trim($userData["company_name"]) : $name;
                $insertCompStmt = $this->db->prepare(
                    "INSERT INTO companies (id, user_id, name, verification_status) VALUES (?, ?, ?, 'pending')"
                );
                $insertCompStmt->execute([
                    $companyId,
                    $userId,
                    $companyName
                ]);
                $companyRecord = [
                    "id" => $companyId,
                    "user_id" => $userId,
                    "name" => $companyName,
                    "verification_status" => "pending"
                ];
            }

            // 5. Step C: Insert OAuth Identity
            $identityId = "oid-" . uniqid();
            $insertIdStmt = $this->db->prepare(
                "INSERT INTO oauth_identities (id, user_id, provider, provider_subject, email_at_link) VALUES (?, ?, ?, ?, ?)"
            );
            $insertIdStmt->execute([
                $identityId,
                $userId,
                $provider,
                $providerSubject,
                $emailAtLink
            ]);

            $this->db->commit();

            return [
                "user" => [
                    "id" => $userId,
                    "name" => $name,
                    "email" => $email,
                    "role" => $role,
                    "status" => "active"
                ],
                "identity" => [
                    "id" => $identityId,
                    "user_id" => $userId,
                    "provider" => $provider,
                    "provider_subject" => $providerSubject,
                    "email_at_link" => $emailAtLink
                ],
                "company" => $companyRecord
            ];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }
}