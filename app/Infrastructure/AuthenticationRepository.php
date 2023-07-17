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
            "mysql:dbname={$config['dbname']};host={$config['host']}",
            $config["user"],
            $config["password"]
        );
    }
    public function register(Authentication $user): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO users (id, name, email, password, role, token, token_expires_at) VALUES (?, ?, ?, ?, ?, ?, ?);"
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
    }

    public function login(Authentication $user): string
    {
        $token = JWT::encode([
            "email" => $user->getEmail(),
            "password" => $user->getPassword()
        ]);
        $stmt = $this->db->prepare(
            "UPDATE users SET token = ?, token_expires_at = ? WHERE email = ?"
        );

        $stmt->execute([
            $token,
            date('Y-m-d H:i:s', $user->getExpire()),
            $user->getEmail()
        ]);

        return $token;
    }

    public function logout(string $email): void
    {
        $stmt = $this->db->prepare(
            "UPDATE users SET token_expires_at = null WHERE email = ?"
        );

        $stmt->execute([
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
}
