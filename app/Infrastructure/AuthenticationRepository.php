<?php

namespace JobMarket\Infrastructure;

use JobMarket\Domain\Authentication\Authentication;
use JobMarket\Domain\Authentication\AuthenticationRepositoryInterface;
use JobMarket\Facades\Config;
use JobMarket\Facades\JWT;
use PDO;

class AuthenticationRepository implements AuthenticationRepositoryInterface
{
    private PDO $db;

    public function __construct()
    {
        $config = Config::env();
        $this->db = new PDO(
            "mysql:dbname={$config['dbname']};host={$config['host']};charset=utf8mb4",
            $config["user"],
            $config["password"]
        );
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public function register(Authentication $user): void
    {
        // Check if email already exists
        $checkStmt = $this->db->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $checkStmt->execute([$user->getEmail()]);
        if ($checkStmt->fetch()) {
            throw new \JobMarket\Exceptions\ValidationException(
                ["email" => ["Email này đã được sử dụng trên hệ thống."]],
                "Email đã tồn tại"
            );
        }

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO users (id, name, email, password, password_set_at, role, token, token_expires_at)
                 VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP, ?, ?, ?);"
            );

            $stmt->execute([
                $user->getId(),
                $user->getName(),
                $user->getEmail(),
                $user->getPassword(),
                $user->getRole(),
                $user->getToken(),
                date('Y-m-d H:i:s', $user->getExpire())
            ]);

            // TASK-P0-03: Atomically create company profile skeleton for employer/company accounts
            if ($user->getRole() === "company") {
                $companyId = "comp-" . uniqid();
                $compStmt = $this->db->prepare(
                    "INSERT INTO companies (id, user_id, name, verification_status) VALUES (?, ?, ?, 'pending')"
                );
                $compStmt->execute([
                    $companyId,
                    $user->getId(),
                    $user->getName()
                ]);
            }

            $this->db->commit();
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function login(Authentication $user): string
    {
        $res = $this->loginWithDetails($user);
        return $res["token"];
    }

    public function loginWithDetails(Authentication $user): array
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$user->getEmail()]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$userData || !password_verify($user->getRawPassword() ?? "", $userData["password"])) {
            throw new \JobMarket\Exceptions\AuthenticationException("Email hoặc mật khẩu không chính xác.");
        }

        if (isset($userData["status"])) {
            if ($userData["status"] === "banned") {
                throw new \JobMarket\Exceptions\AuthenticationException("Tài khoản của bạn đã bị khóa.");
            }
            if ($userData["status"] === "suspended") {
                throw new \JobMarket\Exceptions\AuthenticationException("Tài khoản của bạn đang bị tạm khóa.");
            }
            if ($userData["status"] !== "active") {
                throw new \JobMarket\Exceptions\AuthenticationException("Tài khoản chưa được kích hoạt hoặc không hợp lệ.");
            }
        }

        $token = JWT::encode([
            "id"    => $userData["id"],
            "email" => $userData["email"],
            "role"  => $userData["role"]
        ]);

        $updateStmt = $this->db->prepare(
            "UPDATE users SET token = ?, token_expires_at = ? WHERE email = ?"
        );

        $updateStmt->execute([
            $token,
            date('Y-m-d H:i:s', $user->getExpire()),
            $user->getEmail()
        ]);

        return [
            "token" => $token,
            "user"  => [
                "id"    => $userData["id"],
                "name"  => $userData["name"],
                "email" => $userData["email"],
                "role"  => $userData["role"]
            ]
        ];
    }

    public function logout(string $email): void
    {
        $stmt = $this->db->prepare(
            "UPDATE users SET token = 'revoked', token_expires_at = null WHERE email = ? OR id = ?"
        );

        $stmt->execute([
            $email,
            $email
        ]);
    }

    public function getById(string $id): ?Authentication
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM users WHERE id = ?"
        );

        $stmt->execute([$id]);

        $userData = $stmt->fetch(PDO::FETCH_ASSOC);

        if(!$userData) return null;

        return Authentication::create(
            $userData["name"],
            $userData["email"],
            $userData["password"],
            $userData["role"],
        );
    }

    public function getByEmail(string $email): ?Authentication
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM users WHERE email = ?"
        );

        $stmt->execute([$email]);

        $userData = $stmt->fetch(PDO::FETCH_ASSOC);

        if(!$userData) return null;

        return Authentication::create(
            $userData["name"],
            $userData["email"],
            $userData["password"],
            $userData["role"],
        );
    }

    public function getByToken(string $token): ?Authentication
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM users WHERE token = ?"
        );

        $stmt->execute([$token]);

        $userData = $stmt->fetch(PDO::FETCH_ASSOC);

        if(!$userData) return null;

        return Authentication::create(
            $userData["name"],
            $userData["email"],
            $userData["password"],
            $userData["role"],
        );
    }

    public function findUserRecordByIdOrEmail(?string $id, ?string $email): ?array
    {
        if (!empty($id)) {
            $stmt = $this->db->prepare("SELECT id, name, email, role, status, token, token_expires_at FROM users WHERE id = ? LIMIT 1");
            $stmt->execute([$id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($user) {
                return $user;
            }
        }

        if (!empty($email)) {
            $stmt = $this->db->prepare("SELECT id, name, email, role, status, token, token_expires_at FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($user) {
                return $user;
            }
        }

        return null;
    }
}
