<?php

namespace JobMarket\Infrastructure;

use JobMarket\Domain\Application\Application;
use JobMarket\Domain\Application\ApplicationRepositoryInterface;
use JobMarket\Facades\Config;
use JobMarket\Support\Pagination;
use JobMarket\Support\QueryHelper;
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
            $config["password"],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    }

    public function getDb(): PDO
    {
        return $this->db;
    }

    public function create(Application $application): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO `applications` (
                `id`, `job_id`, `developer_id`, `cover_letter`, `resume`, 
                `preferred_shift`, `status`, `employer_note`, `applied_at`
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );

        $stmt->execute([
            $application->getId(),
            $application->getJobId(),
            $application->getDeveloperId(),
            $application->getCoverLetter(),
            $application->getResume(),
            $application->getPreferredShift(),
            $application->getStatus(),
            $application->getEmployerNote(),
            $application->getAppliedAt() ?? date("Y-m-d H:i:s")
        ]);
    }

    public function findById(string $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT a.*, 
                    j.title AS job_title, j.company_id, c.name AS company_name,
                    u.name AS student_name, u.email AS student_email,
                    sp.phone AS student_phone, sp.university AS student_university, sp.major AS student_major
             FROM `applications` a
             JOIN `jobs` j ON a.job_id = j.id
             JOIN `companies` c ON j.company_id = c.id
             JOIN `users` u ON a.developer_id = u.id
             LEFT JOIN `student_profiles` sp ON a.developer_id = sp.user_id
             WHERE a.id = ? LIMIT 1"
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function findByJobAndStudent(string $jobId, string $studentUserId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM `applications` WHERE `job_id` = ? AND `developer_id` = ? LIMIT 1"
        );
        $stmt->execute([$jobId, $studentUserId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function getByStudent(string $studentUserId, array $filters = [], ?Pagination $pagination = null): array
    {
        $sql = "SELECT a.*, 
                       j.title AS job_title, j.company_id, c.name AS company_name
                FROM `applications` a
                JOIN `jobs` j ON a.job_id = j.id
                JOIN `companies` c ON j.company_id = c.id
                WHERE a.developer_id = ?";
        $params = [$studentUserId];

        if (!empty($filters["status"])) {
            $sql .= " AND a.status = ?";
            $params[] = $filters["status"];
        }

        $sort = QueryHelper::sanitizeSort(
            $filters["sort_by"] ?? "created_at",
            $filters["sort_dir"] ?? "DESC",
            ["created_at", "applied_at", "status"]
        );
        $sql .= " " . $sort["sql"];

        if ($pagination !== null) {
            $sql .= " LIMIT " . $pagination->getLimit() . " OFFSET " . $pagination->getOffset();
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countByStudent(string $studentUserId, array $filters = []): int
    {
        $sql = "SELECT COUNT(*) FROM `applications` a WHERE a.developer_id = ?";
        $params = [$studentUserId];

        if (!empty($filters["status"])) {
            $sql .= " AND a.status = ?";
            $params[] = $filters["status"];
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (int)$stmt->fetchColumn();
    }

    public function getByJob(string $jobId, array $filters = [], ?Pagination $pagination = null): array
    {
        $sql = "SELECT a.*, 
                       j.title AS job_title,
                       u.name AS student_name, u.email AS student_email,
                       sp.phone AS student_phone, sp.university AS student_university, sp.major AS student_major
                FROM `applications` a
                JOIN `jobs` j ON a.job_id = j.id
                JOIN `users` u ON a.developer_id = u.id
                LEFT JOIN `student_profiles` sp ON a.developer_id = sp.user_id
                WHERE a.job_id = ?";
        $params = [$jobId];

        if (!empty($filters["status"])) {
            $sql .= " AND a.status = ?";
            $params[] = $filters["status"];
        }

        $sort = QueryHelper::sanitizeSort(
            $filters["sort_by"] ?? "created_at",
            $filters["sort_dir"] ?? "DESC",
            ["created_at", "applied_at", "status"]
        );
        $sql .= " " . $sort["sql"];

        if ($pagination !== null) {
            $sql .= " LIMIT " . $pagination->getLimit() . " OFFSET " . $pagination->getOffset();
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countByJob(string $jobId, array $filters = []): int
    {
        $sql = "SELECT COUNT(*) FROM `applications` a WHERE a.job_id = ?";
        $params = [$jobId];

        if (!empty($filters["status"])) {
            $sql .= " AND a.status = ?";
            $params[] = $filters["status"];
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (int)$stmt->fetchColumn();
    }

    public function getByCompany(string $companyId, array $filters = [], ?Pagination $pagination = null): array
    {
        $sql = "SELECT a.*, 
                       j.title AS job_title,
                       u.name AS student_name, u.email AS student_email,
                       sp.phone AS student_phone, sp.university AS student_university, sp.major AS student_major
                FROM `applications` a
                JOIN `jobs` j ON a.job_id = j.id
                JOIN `users` u ON a.developer_id = u.id
                LEFT JOIN `student_profiles` sp ON a.developer_id = sp.user_id
                WHERE j.company_id = ?";
        $params = [$companyId];

        if (!empty($filters["job_id"])) {
            $sql .= " AND a.job_id = ?";
            $params[] = $filters["job_id"];
        }

        if (!empty($filters["status"])) {
            $sql .= " AND a.status = ?";
            $params[] = $filters["status"];
        }

        $sort = QueryHelper::sanitizeSort(
            $filters["sort_by"] ?? "created_at",
            $filters["sort_dir"] ?? "DESC",
            ["created_at", "applied_at", "status"]
        );
        $sql .= " " . $sort["sql"];

        if ($pagination !== null) {
            $sql .= " LIMIT " . $pagination->getLimit() . " OFFSET " . $pagination->getOffset();
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countByCompany(string $companyId, array $filters = []): int
    {
        $sql = "SELECT COUNT(*) 
                FROM `applications` a
                JOIN `jobs` j ON a.job_id = j.id
                WHERE j.company_id = ?";
        $params = [$companyId];

        if (!empty($filters["job_id"])) {
            $sql .= " AND a.job_id = ?";
            $params[] = $filters["job_id"];
        }

        if (!empty($filters["status"])) {
            $sql .= " AND a.status = ?";
            $params[] = $filters["status"];
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (int)$stmt->fetchColumn();
    }

    public function updateStatus(string $id, string $status, ?string $employerNote = null): void
    {
        if ($employerNote !== null) {
            $stmt = $this->db->prepare(
                "UPDATE `applications` SET `status` = ?, `employer_note` = ?, `updated_at` = NOW() WHERE `id` = ?"
            );
            $stmt->execute([$status, $employerNote, $id]);
        } else {
            $stmt = $this->db->prepare(
                "UPDATE `applications` SET `status` = ?, `updated_at` = NOW() WHERE `id` = ?"
            );
            $stmt->execute([$status, $id]);
        }
    }

    public function withdraw(string $id): void
    {
        $stmt = $this->db->prepare(
            "UPDATE `applications` SET `status` = 'withdrawn', `updated_at` = NOW() WHERE `id` = ?"
        );
        $stmt->execute([$id]);
    }
}
