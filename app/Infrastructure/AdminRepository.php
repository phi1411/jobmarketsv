<?php

namespace JobMarket\Infrastructure;

use JobMarket\Domain\Admin\AdminRepositoryInterface;
use JobMarket\Facades\Config;
use JobMarket\Support\Pagination;
use JobMarket\Support\QueryHelper;
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
            $config["password"],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    }

    // --- USERS MANAGEMENT ---

    public function getUsers(array $filters = [], ?Pagination $pagination = null): array
    {
        [$whereSql, $params] = $this->buildUserWhereClause($filters);

        $sortBy = $filters["sort_by"] ?? "newest";
        $sortSql = match ($sortBy) {
            "name_asc"  => "ORDER BY `name` ASC",
            "name_desc" => "ORDER BY `name` DESC",
            default     => "ORDER BY `created_at` DESC"
        };

        $sql = "SELECT `id`, `name`, `email`, `role`, `status`, `created_at`, `updated_at` 
                FROM `users` 
                WHERE {$whereSql} {$sortSql}";

        if ($pagination !== null) {
            $sql .= " LIMIT " . $pagination->getLimit() . " OFFSET " . $pagination->getOffset();
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countUsers(array $filters = []): int
    {
        [$whereSql, $params] = $this->buildUserWhereClause($filters);
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM `users` WHERE {$whereSql}");
        $stmt->execute($params);

        return (int)$stmt->fetchColumn();
    }

    public function getUserById(string $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT `id`, `name`, `email`, `role`, `status`, `created_at`, `updated_at` 
             FROM `users` WHERE `id` = ? LIMIT 1"
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function updateUserStatus(string $id, string $status): void
    {
        $stmt = $this->db->prepare(
            "UPDATE `users` SET `status` = ?, `updated_at` = NOW() WHERE `id` = ?"
        );
        $stmt->execute([$status, $id]);
    }

    public function countActiveAdmins(): int
    {
        $stmt = $this->db->query("SELECT COUNT(*) FROM `users` WHERE `role` = 'admin' AND `status` = 'active'");
        return (int)$stmt->fetchColumn();
    }

    private function buildUserWhereClause(array $filters): array
    {
        $sql = "1=1";
        $params = [];

        if (!empty($filters["role"])) {
            $sql .= " AND `role` = ?";
            $params[] = $filters["role"];
        }

        if (!empty($filters["status"])) {
            $sql .= " AND `status` = ?";
            $params[] = $filters["status"];
        }

        if (!empty($filters["keyword"])) {
            $kw = "%" . QueryHelper::escapeLike($filters["keyword"]) . "%";
            $sql .= " AND (`name` LIKE ? OR `email` LIKE ?)";
            $params[] = $kw;
            $params[] = $kw;
        }

        return [$sql, $params];
    }

    // --- COMPANIES MANAGEMENT ---

    public function getCompanies(array $filters = [], ?Pagination $pagination = null): array
    {
        [$whereSql, $params] = $this->buildCompanyWhereClause($filters);

        $sortBy = $filters["sort_by"] ?? "newest";
        $sortSql = match ($sortBy) {
            "name_asc" => "ORDER BY c.name ASC",
            default    => "ORDER BY c.created_at DESC"
        };

        $sql = "SELECT c.*, u.email as owner_email, u.name as owner_name, u.status as owner_status
                FROM `companies` c
                LEFT JOIN `users` u ON c.user_id = u.id
                WHERE {$whereSql} {$sortSql}";

        if ($pagination !== null) {
            $sql .= " LIMIT " . $pagination->getLimit() . " OFFSET " . $pagination->getOffset();
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countCompanies(array $filters = []): int
    {
        [$whereSql, $params] = $this->buildCompanyWhereClause($filters);
        $sql = "SELECT COUNT(*) FROM `companies` c LEFT JOIN `users` u ON c.user_id = u.id WHERE {$whereSql}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (int)$stmt->fetchColumn();
    }

    public function getCompanyById(string $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT c.*, u.email as owner_email, u.name as owner_name 
             FROM `companies` c
             LEFT JOIN `users` u ON c.user_id = u.id
             WHERE c.id = ? LIMIT 1"
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function updateCompanyVerification(string $id, string $verificationStatus, ?string $rejectionReason): void
    {
        $stmt = $this->db->prepare(
            "UPDATE `companies` 
             SET `verification_status` = ?, `rejection_reason` = ?, `updated_at` = NOW() 
             WHERE `id` = ?"
        );
        $stmt->execute([$verificationStatus, $rejectionReason, $id]);
    }

    private function buildCompanyWhereClause(array $filters): array
    {
        $sql = "1=1";
        $params = [];

        if (!empty($filters["verification_status"])) {
            $sql .= " AND c.verification_status = ?";
            $params[] = $filters["verification_status"];
        }

        if (!empty($filters["keyword"])) {
            $kw = "%" . QueryHelper::escapeLike($filters["keyword"]) . "%";
            $sql .= " AND (c.name LIKE ? OR c.contact_person LIKE ? OR c.city LIKE ? OR u.email LIKE ?)";
            $params[] = $kw;
            $params[] = $kw;
            $params[] = $kw;
            $params[] = $kw;
        }

        return [$sql, $params];
    }

    // --- JOBS MODERATION ---

    public function getJobs(array $filters = [], ?Pagination $pagination = null): array
    {
        [$whereSql, $params] = $this->buildJobWhereClause($filters);

        $sortBy = $filters["sort_by"] ?? "newest";
        $sortSql = match ($sortBy) {
            "salary_desc" => "ORDER BY j.salary_max DESC, j.salary_min DESC",
            "salary_asc"  => "ORDER BY j.salary_min ASC",
            default       => "ORDER BY j.created_at DESC"
        };

        $sql = "SELECT j.*, c.name as company_name, c.logo_url as company_logo, cat.name as category_name
                FROM `jobs` j
                LEFT JOIN `companies` c ON j.company_id = c.id
                LEFT JOIN `categories` cat ON j.category_id = cat.id
                WHERE {$whereSql} {$sortSql}";

        if ($pagination !== null) {
            $sql .= " LIMIT " . $pagination->getLimit() . " OFFSET " . $pagination->getOffset();
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countJobs(array $filters = []): int
    {
        [$whereSql, $params] = $this->buildJobWhereClause($filters);
        $sql = "SELECT COUNT(*) FROM `jobs` j LEFT JOIN `companies` c ON j.company_id = c.id WHERE {$whereSql}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (int)$stmt->fetchColumn();
    }

    public function getJobById(string $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT j.*, c.name as company_name, c.user_id as company_user_id 
             FROM `jobs` j
             LEFT JOIN `companies` c ON j.company_id = c.id
             WHERE j.id = ? LIMIT 1"
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function updateJobModeration(string $id, string $status, ?string $rejectionReason): void
    {
        $stmt = $this->db->prepare(
            "UPDATE `jobs` 
             SET `status` = ?, `rejection_reason` = ?, `updated_at` = NOW() 
             WHERE `id` = ?"
        );
        $stmt->execute([$status, $rejectionReason, $id]);
    }

    private function buildJobWhereClause(array $filters): array
    {
        $sql = "j.deleted_at IS NULL";
        $params = [];

        if (!empty($filters["status"])) {
            $sql .= " AND j.status = ?";
            $params[] = $filters["status"];
        }

        if (!empty($filters["company_id"])) {
            $sql .= " AND j.company_id = ?";
            $params[] = $filters["company_id"];
        }

        if (!empty($filters["category_id"])) {
            $sql .= " AND j.category_id = ?";
            $params[] = $filters["category_id"];
        }

        if (!empty($filters["keyword"])) {
            $kw = "%" . QueryHelper::escapeLike($filters["keyword"]) . "%";
            $sql .= " AND (j.title LIKE ? OR c.name LIKE ?)";
            $params[] = $kw;
            $params[] = $kw;
        }

        return [$sql, $params];
    }

    // --- AUDIT LOGS ---

    public function logAudit(string $actorId, string $action, string $targetType, string $targetId, ?array $metadata = null): void
    {
        $id = "audit-" . uniqid();
        $metaJson = $metadata !== null ? json_encode($metadata, JSON_UNESCAPED_UNICODE) : null;

        $stmt = $this->db->prepare(
            "INSERT INTO `audit_logs` (`id`, `actor_id`, `action`, `target_type`, `target_id`, `metadata`, `created_at`)
             VALUES (?, ?, ?, ?, ?, ?, NOW())"
        );
        $stmt->execute([$id, $actorId, $action, $targetType, $targetId, $metaJson]);
    }

    public function getAuditLogs(array $filters = [], ?Pagination $pagination = null): array
    {
        $sql = "SELECT a.*, u.name as actor_name, u.email as actor_email 
                FROM `audit_logs` a
                LEFT JOIN `users` u ON a.actor_id = u.id
                ORDER BY a.created_at DESC";

        if ($pagination !== null) {
            $sql .= " LIMIT " . $pagination->getLimit() . " OFFSET " . $pagination->getOffset();
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countAuditLogs(array $filters = []): int
    {
        $stmt = $this->db->query("SELECT COUNT(*) FROM `audit_logs`");
        return (int)$stmt->fetchColumn();
    }
}
