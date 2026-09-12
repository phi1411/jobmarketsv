<?php

namespace JobMarket\Infrastructure;

use JobMarket\Facades\Config;
use JobMarket\Support\Pagination;
use PDO;

class JobReportRepository
{
    private PDO $db;

    public function __construct()
    {
        $config = Config::env();
        $this->db = new PDO(
            "mysql:dbname={$config['dbname']};host={$config['host']};charset=utf8mb4",
            $config["user"],
            $config["password"],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    }

    public function findExisting(string $userId, string $jobId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM `job_reports` WHERE `user_id` = ? AND `job_id` = ? LIMIT 1");
        $stmt->execute([$userId, $jobId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function create(array $report): array
    {
        $stmt = $this->db->prepare(
            "INSERT INTO `job_reports` (`id`, `user_id`, `job_id`, `reason`, `description`)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([$report["id"], $report["user_id"], $report["job_id"], $report["reason"], $report["description"]]);
        return $this->findById($report["id"]);
    }

    public function findById(string $id): ?array
    {
        $stmt = $this->db->prepare($this->baseSelect() . " WHERE r.id = ? LIMIT 1");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function getAll(array $filters, ?Pagination $pagination): array
    {
        [$where, $params] = $this->filters($filters);
        $sql = $this->baseSelect() . $where . " ORDER BY FIELD(r.status, 'pending','reviewing','resolved','dismissed'), r.created_at DESC";
        if ($pagination) {
            $sql .= " LIMIT " . $pagination->getLimit() . " OFFSET " . $pagination->getOffset();
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countAll(array $filters): int
    {
        [$where, $params] = $this->filters($filters);
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM `job_reports` r" . $where);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    public function updateResolution(string $id, string $status, string $action, ?string $note, string $adminId): void
    {
        $handled = in_array($status, ["resolved", "dismissed"], true);
        $stmt = $this->db->prepare(
            "UPDATE `job_reports`
             SET `status` = ?, `resolution_action` = ?, `admin_note` = ?, `handled_by` = ?,
                 `handled_at` = " . ($handled ? "NOW()" : "NULL") . ", `updated_at` = NOW()
             WHERE `id` = ?"
        );
        $stmt->execute([$status, $action, $note, $adminId, $id]);
    }

    private function baseSelect(): string
    {
        return "SELECT r.*, j.title AS job_title, j.status AS job_status,
                       c.name AS company_name, u.name AS reporter_name, u.email AS reporter_email,
                       h.name AS handler_name
                FROM `job_reports` r
                JOIN `jobs` j ON j.id = r.job_id
                JOIN `companies` c ON c.id = j.company_id
                JOIN `users` u ON u.id = r.user_id
                LEFT JOIN `users` h ON h.id = r.handled_by";
    }

    private function filters(array $filters): array
    {
        $where = " WHERE 1=1";
        $params = [];
        $status = is_scalar($filters["status"] ?? null) ? trim((string)$filters["status"]) : "";
        if (in_array($status, ["pending", "reviewing", "resolved", "dismissed"], true)) {
            $where .= " AND r.status = ?";
            $params[] = $status;
        }
        $reason = is_scalar($filters["reason"] ?? null) ? trim((string)$filters["reason"]) : "";
        if (in_array($reason, ["scam", "salary_mismatch", "fee_required", "inappropriate", "other"], true)) {
            $where .= " AND r.reason = ?";
            $params[] = $reason;
        }
        return [$where, $params];
    }
}
