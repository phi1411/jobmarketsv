<?php

namespace JobMarket\Infrastructure;

use JobMarket\Domain\Admin\Admin;
use JobMarket\Domain\Admin\AdminRepositoryInterface;
use JobMarket\Facades\Config;
use PDO;

class AdminRepository implements AdminRepositoryInterface
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
    public function getJobs(): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM jobs"
        );
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function getCompanies(): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM companies"
        );
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function getUsers(): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM users"
        );
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function update(Admin $admin): void
    {
        $stmt = $this->db->prepare(
            "UPDATE admin_users SET user_id = ? WHERE id = ?"
        );
        $stmt->execute([
            $admin->getUserId(),
            $admin->getId()
        ]);
    }
    public function delete(string $id): void
    {
        $stmt = $this->db->prepare(
            "DELETE FROM admin_users WHERE id = ?"
        );
        $stmt->execute([$id]);
    }
}
