<?php

namespace JobMarket\Infrastructure;

use JobMarket\Domain\Authentication\Authentication;
use JobMarket\Domain\Authentication\AuthenticationRepositoryInterface;
use JobMarket\Facades\Config;
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
    }

    public function login(Authentication $user): string
    {
    }

    public function logout(Authentication $user): void
    {
    }

    public function getById(string $id): ?Authentication
    {
    }

    public function getByEmail(string $email): ?Authentication
    {
    }

    public function getByToken(string $token): ?Authentication
    {
    }
}
