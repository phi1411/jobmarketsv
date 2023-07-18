<?php

namespace JobMarket\Infrastructure;

use JobMarket\Domain\Job\Job;
use JobMarket\Domain\Job\JobRepositoryInterface;
use JobMarket\Facades\Config;
use PDO;

class JobRepository implements JobRepositoryInterface
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
            "SELECT * FROM jobs"
        );

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(Job $job): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO jobs (id, company_id, title, description, requirements, location, category, type) VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );

        $stmt->execute([
            $job->getId(),
            $job->getCompanyId(),
            $job->getTitle(),
            $job->getDescription(),
            $job->getRequirements(),
            $job->getLocation(),
            $job->getCategory(),
            $job->getType()
        ]);
    }

    public function findById(string $id): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM jobs WHERE id = ?"
        );
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function update(Job $job): void
    {
        $stmt = $this->db->prepare(
            "UPDATE jobs SET company_id = ?, title = ?, description = ?, requirements = ?, location = ?, category = ?, type = ? WHERE id = ?"
        );
        $stmt->execute([
            $job->getCompanyId(),
            $job->getTitle(),
            $job->getDescription(),
            $job->getRequirements(),
            $job->getLocation(),
            $job->getCategory(),
            $job->getType(),
            $job->getId()
        ]);
    }

    public function delete(string $id): void
    {
        $stmt = $this->db->prepare(
            "DELETE FROM jobs WHERE id = ?"
        );
        $stmt->execute([$id]);
    }
}
