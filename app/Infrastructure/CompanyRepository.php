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
        
    }

    public function getById(string $id): array
    {

    }

    public function update(Company $company): void
    {

    }

    public function delete(string $id): void
    {

    }
}