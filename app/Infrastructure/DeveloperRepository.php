<?php

namespace JobMarket\Infrastructure;

use JobMarket\Domain\Developer\Developer;
use JobMarket\Domain\Developer\DeveloperRepositoryInterface;
use JobMarket\Facades\Config;
use PDO;

class DeveloperRepository implements DeveloperRepositoryInterface
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
    public function getAll(): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM users WHERE role = developer"
        );
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function create(Developer $developer): void
    {
        // $stmt = $this->db->prepare(
        //     "INSERT INTO users (id, name, email, password, role, token) VALUES (?, ?, ?, ?, ?, ?)"
        // );
        // $stmt->execute([
        //     $developer->getId(),
        //     $developer->getName(),
        //     $developer->getEmail(),
        //     $developer->getPassword(),

        // ]);
    }
    public function getById(string $id): array
    {
    }
    public function update(Developer $developer): void
    {

    }
    public function delete(string $id): void
    {
        
    }
}
