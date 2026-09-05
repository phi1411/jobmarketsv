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

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    public function findByUserId(string $userId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM `companies` WHERE `user_id` = ? LIMIT 1"
        );
        $stmt->execute([$userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function update(Company $company): void
    {
        $stmt = $this->db->prepare(
            "UPDATE companies SET user_id = ?, name = ?, description = ?, location = ?, website = ? WHERE id = ?"
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

    public function updateProfile(string $id, array $data): void
    {
        $fields = [
            "name"           => $data["name"] ?? "",
            "description"    => $data["description"] ?? null,
            "contact_person" => $data["contact_person"] ?? null,
            "contact_phone"  => $data["contact_phone"] ?? null,
            "address"        => $data["address"] ?? null,
            "city"           => $data["city"] ?? null,
            "district"       => $data["district"] ?? null,
            "website"        => $data["website"] ?? null,
        ];

        $stmt = $this->db->prepare(
            "UPDATE `companies`
             SET `name` = ?,
                 `description` = ?,
                 `contact_person` = ?,
                 `contact_phone` = ?,
                 `address` = ?,
                 `city` = ?,
                 `district` = ?,
                 `website` = ?
             WHERE `id` = ?"
        );

        $stmt->execute([
            $fields["name"],
            $fields["description"],
            $fields["contact_person"],
            $fields["contact_phone"],
            $fields["address"],
            $fields["city"],
            $fields["district"],
            $fields["website"],
            $id
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
