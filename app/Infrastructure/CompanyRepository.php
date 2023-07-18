<?php

namespace JobMarket\Infrastructure;

use JobMarket\Domain\Company\Company;
use JobMarket\Domain\Company\CompanyRepositoryInterface;
use JobMarket\Facades\Config;
use PDO;

class CompanyRepository implements CompanyRepositoryInterface
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
            "SELECT * FROM companies"
        );

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(Company $company): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO companies (id, user_id, name, description, location, website) VALUES (?, ?, ?, ?, ?, ?)"
        );

        $stmt->execute([
            $company->getId(),
            $company->getUserId(),
            $company->getName(),
            $company->getDescription(),
            $company->getLocation(),
            $company->getWebsite()
        ]);
    }

    public function getById(string $id): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM companies WHERE id = ?"
        );
        $stmt->execute([$id]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function update(Company $company): void
    {
        $stmt = $this->db->prepare(
            "UPDATE companies SET user_id = ?, name = ?, description = ?, location = ?, location = ?, website = ? WHERE id = ?"
        );

        $stmt->execute([
            $company->getUserId(),
            $company->getName(),
            $company->getDescription(),
            $company->getLocation(),
            $company->getWebsite(),
            $company->getId()
        ]);
    }

    public function delete(string $id): void
    {
        $stmt = $this->db->prepare(
            "DELETE FROM companies WHERE id = ?"
        );

        $stmt->execute([$id]);
    }
}
