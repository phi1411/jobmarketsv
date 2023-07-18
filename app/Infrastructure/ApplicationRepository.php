<?php

namespace JobMarket\Infrastructure;

use JobMarket\Domain\Application\Application;
use JobMarket\Domain\Application\ApplicationRepositoryInterface;
use JobMarket\Facades\Config;
use PDO;

class ApplicationRepository implements ApplicationRepositoryInterface
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
    public function getAll(string $id): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM applications WHERE job_id = ?"
        );
        $stmt->execute([$id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function create(string $id, Application $application): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO applications (id, job_id, developer_id, cover_letter, resume, status) VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $application->getId(),
            $application->getJobId(),
            $application->getDeveloperId(),
            $application->getCoverLetter(),
            $application->getResume(),
            $application->getStatus()
        ]);
    }
    public function findById(string $id): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM applications WHERE id = ?"
        );
        $stmt->execute([$id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function update(Application $application): void
    {
        $stmt = $this->db->prepare(
            "UPDATE applications SET job_id = ?, developer_id = ?, cover_letter = ?, resume = ?, status = ? WHERE id = ?"
        );
        $stmt->execute([
            $application->getJobId(),
            $application->getDeveloperId(),
            $application->getCoverLetter(),
            $application->getResume(),
            $application->getStatus(),
            $application->getId()
        ]);
    }
    public function delete(string $id): void
    {
        $stmt = $this->db->prepare(
            "DELETE FROM applications WHERE id = ?"
        );
        $stmt->execute([$id]);
    }
}
