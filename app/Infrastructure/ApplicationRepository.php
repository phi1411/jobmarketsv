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
            "mysql:dbname={$config['dbname']};host={$config['host']};charset=utf8mb4",
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
                `preferred_shift`, `status`, `employer_note`, `applied_at`,
                `cv_storage_path`, `cv_original_name`, `cv_file_size`, `cv_mime_type`,
                `ai_match_consent`, `ai_match_consented_at`, `ai_match_consent_revoked_at`, `ai_match_notice_version`
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
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
            $application->getAppliedAt() ?? date("Y-m-d H:i:s"),
            $application->getCvStoragePath(),
            $application->getCvOriginalName(),
            $application->getCvFileSize(),
            $application->getCvMimeType(),
            $application->getAiMatchConsent() ? 1 : 0,
            $application->getAiMatchConsentedAt(),
            $application->getAiMatchConsentRevokedAt(),
            $application->getAiMatchNoticeVersion(),
        ]);

        $this->insertStatusHistory(
            $application->getId(),
            $application->getStatus(),
            $application->getDeveloperId(),
            "student",
            "Đã nộp đơn ứng tuyển"
        );
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
                       sp.phone AS student_phone, sp.university AS student_university, sp.major AS student_major,
                       sp.skills AS student_skills, sp.skill_ids AS student_skill_ids,
                       (
                           SELECT GROUP_CONCAT(s.name ORDER BY s.name SEPARATOR '||')
                           FROM `skills` s
                           WHERE JSON_CONTAINS(COALESCE(sp.skill_ids, '[]'), JSON_QUOTE(s.id))
                       ) AS student_skill_names,
                       ma.status AS match_analysis_status,
                       CASE WHEN " . $this->visibleMatchPredicate() . " THEN ma.overall_score ELSE NULL END AS match_score,
                       CASE WHEN " . $this->visibleMatchPredicate() . " THEN ma.coverage_percent ELSE NULL END AS match_coverage,
                       CASE WHEN " . $this->visibleMatchPredicate() . " THEN ma.classification ELSE NULL END AS match_classification
                FROM `applications` a
                JOIN `jobs` j ON a.job_id = j.id
                JOIN `users` u ON a.developer_id = u.id
                LEFT JOIN `student_profiles` sp ON a.developer_id = sp.user_id
                LEFT JOIN `job_match_analyses` ma ON ma.id = (
                    SELECT ma_latest.id
                    FROM `job_match_analyses` ma_latest
                    WHERE ma_latest.application_id = a.id
                    ORDER BY ma_latest.created_at DESC, ma_latest.id DESC
                    LIMIT 1
                )
                WHERE j.company_id = ?";
        $params = [$companyId];

        $this->appendCompanyApplicationFilters($sql, $params, $filters);
        $sql .= $this->companyApplicationOrder($filters);

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
                LEFT JOIN `student_profiles` sp ON a.developer_id = sp.user_id
                LEFT JOIN `job_match_analyses` ma ON ma.id = (
                    SELECT ma_latest.id
                    FROM `job_match_analyses` ma_latest
                    WHERE ma_latest.application_id = a.id
                    ORDER BY ma_latest.created_at DESC, ma_latest.id DESC
                    LIMIT 1
                )
                WHERE j.company_id = ?";
        $params = [$companyId];

        $this->appendCompanyApplicationFilters($sql, $params, $filters);

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (int)$stmt->fetchColumn();
    }

    private function visibleMatchPredicate(): string
    {
        return "a.ai_match_consent = 1
            AND a.ai_match_consent_revoked_at IS NULL
            AND ma.status IN ('completed', 'partial')
            AND ma.overall_score IS NOT NULL
            AND ma.coverage_percent >= 60";
    }

    private function appendCompanyApplicationFilters(string &$sql, array &$params, array $filters): void
    {
        if (!empty($filters["job_id"]) && is_scalar($filters["job_id"])) {
            $sql .= " AND a.job_id = ?";
            $params[] = trim((string)$filters["job_id"]);
        }

        $allowedStatuses = ["pending", "interview", "accepted", "rejected", "withdrawn"];
        $status = is_scalar($filters["status"] ?? null) ? trim((string)$filters["status"]) : "";
        if (in_array($status, $allowedStatuses, true)) {
            $sql .= " AND a.status = ?";
            $params[] = $status;
        }

        foreach (["university" => "sp.university", "major" => "sp.major"] as $filter => $column) {
            $value = is_scalar($filters[$filter] ?? null) ? trim((string)$filters[$filter]) : "";
            if ($value !== "") {
                $sql .= " AND {$column} LIKE ? ESCAPE '\\\\'";
                $params[] = "%" . QueryHelper::escapeLike(mb_substr($value, 0, 100)) . "%";
            }
        }

        $allowedShifts = ["morning", "afternoon", "evening", "night", "weekend", "flexible"];
        $shift = is_scalar($filters["preferred_shift"] ?? null) ? trim((string)$filters["preferred_shift"]) : "";
        if (in_array($shift, $allowedShifts, true)) {
            $sql .= " AND a.preferred_shift = ?";
            $params[] = $shift;
        }

        $rawSkillIds = $filters["skill_ids"] ?? "";
        $skillIds = is_array($rawSkillIds) ? $rawSkillIds : explode(",", (string)$rawSkillIds);
        $skillIds = array_slice(array_values(array_unique(array_filter(array_map(
            fn(mixed $id): string => is_scalar($id) ? trim((string)$id) : "",
            $skillIds
        ), fn(string $id): bool => (bool)preg_match('/^[a-zA-Z0-9_-]{1,64}$/', $id)))), 0, 10);
        foreach ($skillIds as $skillId) {
            $sql .= " AND JSON_CONTAINS(COALESCE(sp.skill_ids, '[]'), JSON_QUOTE(?))";
            $params[] = $skillId;
        }

        $matchFilter = is_scalar($filters["match_filter"] ?? null) ? trim((string)$filters["match_filter"]) : "";
        if (in_array($matchFilter, ["50", "65", "80"], true)) {
            $sql .= " AND " . $this->visibleMatchPredicate() . " AND ma.overall_score >= ?";
            $params[] = (int)$matchFilter;
        } elseif ($matchFilter === "unscored") {
            $sql .= " AND COALESCE((" . $this->visibleMatchPredicate() . "), 0) = 0";
        }
    }

    private function companyApplicationOrder(array $filters): string
    {
        $sortBy = is_scalar($filters["sort_by"] ?? null) ? trim((string)$filters["sort_by"]) : "newest";

        return match ($sortBy) {
            "oldest" => " ORDER BY a.applied_at ASC, a.id ASC",
            "match_desc" => " ORDER BY (match_score IS NULL) ASC, match_score DESC, a.applied_at DESC",
            "match_asc" => " ORDER BY (match_score IS NULL) ASC, match_score ASC, a.applied_at DESC",
            default => " ORDER BY a.applied_at DESC, a.id DESC",
        };
    }

    public function updateStatus(string $id, string $status, ?string $studentMessage = null, ?string $actorId = null, string $actorRole = "system", ?string $historyNote = null): void
    {
        $started = !$this->db->inTransaction();
        if ($started) $this->db->beginTransaction();
        try {
            $current = $this->db->prepare("SELECT `status` FROM `applications` WHERE `id` = ? FOR UPDATE");
            $current->execute([$id]);
            $oldStatus = $current->fetchColumn();
            if ($studentMessage !== null) {
                $stmt = $this->db->prepare("UPDATE `applications` SET `status` = ?, `student_message` = ?, `updated_at` = NOW() WHERE `id` = ?");
                $stmt->execute([$status, $studentMessage, $id]);
            } else {
                $stmt = $this->db->prepare("UPDATE `applications` SET `status` = ?, `updated_at` = NOW() WHERE `id` = ?");
                $stmt->execute([$status, $id]);
            }
            if ($oldStatus !== false && $oldStatus !== $status) {
                $this->insertStatusHistory($id, $status, $actorId, $actorRole, $historyNote);
            }
            if ($started) $this->db->commit();
        } catch (\Throwable $e) {
            if ($started && $this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
    }

    public function withdraw(string $id, ?string $actorId = null, string $actorRole = "student"): void
    {
        $this->updateStatus($id, "withdrawn", null, $actorId, $actorRole, "Sinh viên đã rút đơn");
    }

    public function getStatusHistory(string $applicationId): array
    {
        $stmt = $this->db->prepare(
            "SELECT `id`,`application_id`,`status`,`actor_role`,`note`,`created_at`
             FROM `application_status_history` WHERE `application_id` = ? ORDER BY `created_at` ASC, `id` ASC"
        );
        $stmt->execute([$applicationId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function insertStatusHistory(string $applicationId, string $status, ?string $actorId, string $actorRole, ?string $note): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO `application_status_history` (`id`,`application_id`,`status`,`actor_id`,`actor_role`,`note`)
             VALUES (?,?,?,?,?,?)"
        );
        $stmt->execute(["hist-" . bin2hex(random_bytes(12)), $applicationId, $status, $actorId, $actorRole, $note]);
    }

    public function updateConsent(string $id, bool $consent, ?string $revokedAt = null): void
    {
        $stmt = $this->db->prepare(
            "UPDATE `applications`
             SET `ai_match_consent` = ?, `ai_match_consent_revoked_at` = ?, `updated_at` = NOW()
             WHERE `id` = ?"
        );
        $stmt->execute([$consent ? 1 : 0, $revokedAt, $id]);
    }
}
