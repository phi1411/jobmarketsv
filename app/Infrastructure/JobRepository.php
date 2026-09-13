<?php

namespace JobMarket\Infrastructure;

use JobMarket\Domain\Job\Job;
use JobMarket\Domain\Job\JobRepositoryInterface;
use JobMarket\Facades\Config;
use JobMarket\Support\Pagination;
use JobMarket\Support\QueryHelper;
use PDO;

class JobRepository implements JobRepositoryInterface
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

    public function getAll(): array
    {
        return $this->search([], null);
    }

    public function search(array $filters = [], ?Pagination $pagination = null): array
    {
        [$whereSql, $params] = $this->buildWhereClause($filters);

        $sql = "SELECT j.*, 
                       c.name AS company_name, 
                       c.logo_url AS company_logo, 
                       c.verification_status,
                       c.contact_person,
                       c.contact_phone,
                       c.address AS company_address,
                       cat.name AS category_name
                FROM `jobs` j
                LEFT JOIN `companies` c ON j.company_id = c.id
                LEFT JOIN `categories` cat ON (j.category_id = cat.id OR j.category = cat.id)
                WHERE {$whereSql}";

        // Sort Whitelisting
        $sortBy = $filters["sort_by"] ?? "newest";
        $sortDir = $filters["sort_dir"] ?? "DESC";

        $sortSql = match ($sortBy) {
            "newest"      => "ORDER BY j.created_at DESC",
            "salary_desc" => "ORDER BY j.salary_max DESC, j.salary_min DESC",
            "salary_asc"  => "ORDER BY j.salary_min ASC",
            default       => QueryHelper::sanitizeSort($sortBy, $sortDir, ["created_at", "salary_min", "salary_max", "title"])["sql"]
        };
        $sql .= " " . $sortSql;

        // Pagination
        if ($pagination !== null) {
            $sql .= " LIMIT " . $pagination->getLimit() . " OFFSET " . $pagination->getOffset();
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$r) {
            $this->hydrateSkills($r);
        }
        return $rows;
    }

    public function count(array $filters = []): int
    {
        [$whereSql, $params] = $this->buildWhereClause($filters);

        $sql = "SELECT COUNT(*) FROM `jobs` j WHERE {$whereSql}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (int)$stmt->fetchColumn();
    }

    private function buildWhereClause(array $filters): array
    {
        $sql = "j.deleted_at IS NULL";
        $params = [];

        // Public queries only show published, non-expired, non-closed jobs
        $isPublic = !empty($filters["is_public"]) || !isset($filters["is_public"]);

        if ($isPublic) {
            $sql .= " AND j.status = 'published'";
            $sql .= " AND (j.application_deadline IS NULL OR j.application_deadline >= CURDATE())";
            $sql .= " AND (j.deadline IS NULL OR j.deadline >= CURDATE())";
        } else {
            // Non-public (company managing their own jobs)
            if (!empty($filters["status"]) && $filters["status"] !== "all") {
                $sql .= " AND j.status = ?";
                $params[] = $filters["status"];
            }
        }

        // Filter: Company ID
        if (!empty($filters["company_id"])) {
            $sql .= " AND j.company_id = ?";
            $params[] = $filters["company_id"];
        }

        // Filter: Category ID
        if (!empty($filters["category_id"])) {
            $sql .= " AND (j.category_id = ? OR j.category = ?)";
            $params[] = $filters["category_id"];
            $params[] = $filters["category_id"];
        }

        // Filter: Location ID
        if (!empty($filters["location_id"])) {
            $sql .= " AND (j.location_id = ? OR j.location = ?)";
            $params[] = $filters["location_id"];
            $params[] = $filters["location_id"];
        }

        // Filter: City
        if (!empty($filters["city"])) {
            $sql .= " AND j.city LIKE ?";
            $params[] = "%" . QueryHelper::escapeLike($filters["city"]) . "%";
        }

        // Filter: District
        if (!empty($filters["district"])) {
            $sql .= " AND j.district LIKE ?";
            $params[] = "%" . QueryHelper::escapeLike($filters["district"]) . "%";
        }

        // Canonical work-location filters. District remains a legacy display/search field only.
        if (!empty($filters["province"])) {
            $sql .= " AND EXISTS (SELECT 1 FROM `job_locations` jl_province WHERE jl_province.job_id = j.id AND jl_province.province = ?)";
            $params[] = $filters["province"];
        }
        if (!empty($filters["commune"])) {
            $sql .= " AND EXISTS (SELECT 1 FROM `job_locations` jl_commune WHERE jl_commune.job_id = j.id AND jl_commune.commune = ?)";
            $params[] = $filters["commune"];
        }

        // Filter: Work Type (e.g. part_time, internship, freelance)
        if (!empty($filters["work_type"])) {
            $normalizedWorkType = str_replace("-", "_", $filters["work_type"]);
            $legacyWorkType = str_replace("_", "-", $filters["work_type"]);
            $sql .= " AND (j.work_type = ? OR j.type = ?)";
            $params[] = $normalizedWorkType;
            $params[] = $legacyWorkType;
        }

        // Filter: Work Mode (e.g. onsite, remote, hybrid)
        if (!empty($filters["work_mode"])) {
            $normalizedWorkMode = str_replace("-", "", $filters["work_mode"]);
            $legacyWorkMode = ($filters["work_mode"] === "onsite" ? "on-site" : $filters["work_mode"]);
            $sql .= " AND (j.work_mode = ? OR j.work_format = ?)";
            $params[] = $normalizedWorkMode;
            $params[] = $legacyWorkMode;
        }

        // Filter: Shift Type
        if (!empty($filters["shift_type"])) {
            $sql .= " AND j.shift_type = ?";
            $params[] = $filters["shift_type"];
        }

        // Filter: Salary Type
        if (!empty($filters["salary_type"])) {
            $sql .= " AND j.salary_type = ?";
            $params[] = $filters["salary_type"];
        }

        // Filter: Minimum Salary
        if (!empty($filters["salary_min"]) && is_numeric($filters["salary_min"])) {
            $sql .= " AND (j.salary_max >= ? OR j.salary_min >= ?)";
            $params[] = (int)$filters["salary_min"];
            $params[] = (int)$filters["salary_min"];
        }

        // Filter: Maximum Salary
        if (!empty($filters["salary_max"]) && is_numeric($filters["salary_max"])) {
            $sql .= " AND (j.salary_min <= ?)";
            $params[] = (int)$filters["salary_max"];
        }

        // Filter: Skill ID (inside required_skills)
        if (!empty($filters["skill_id"])) {
            $sql .= " AND j.required_skills LIKE ?";
            $params[] = "%" . QueryHelper::escapeLike($filters["skill_id"]) . "%";
        }

        // Filter: Search Keyword
        if (!empty($filters["keyword"])) {
            $keyword = "%" . QueryHelper::escapeLike($filters["keyword"]) . "%";
            $sql .= " AND (j.title LIKE ? OR j.description LIKE ? OR j.requirements LIKE ? OR j.benefits LIKE ?)";
            $params[] = $keyword;
            $params[] = $keyword;
            $params[] = $keyword;
            $params[] = $keyword;
        }

        return [$sql, $params];
    }

    public function findById(string $id): array
    {
        $stmt = $this->db->prepare(
            "SELECT j.*, 
                    c.name AS company_name, 
                    c.logo_url AS company_logo, 
                    c.address AS company_address,
                    c.contact_person, 
                    c.contact_phone, 
                    c.verification_status,
                    cat.name AS category_name
             FROM `jobs` j
             LEFT JOIN `companies` c ON j.company_id = c.id
             LEFT JOIN `categories` cat ON (j.category_id = cat.id OR j.category = cat.id)
             WHERE j.id = ? AND j.deleted_at IS NULL LIMIT 1"
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return [];
        }

        $this->hydrateSkills($row);
        return $row;
    }

    private function hydrateSkills(array &$jobRow): void
    {
        $skillsArray = [];
        if (!empty($jobRow["required_skills"])) {
            $decoded = json_decode($jobRow["required_skills"], true);
            if (is_array($decoded)) {
                $skillsArray = $decoded;
            } else {
                $skillsArray = array_values(array_filter(array_map('trim', explode(',', $jobRow["required_skills"])), fn($s) => $s !== ''));
            }
        }
        $jobRow["skills"] = $skillsArray;
    }

    public function findByCompany(string $companyId, array $filters = [], ?Pagination $pagination = null): array
    {
        $filters["company_id"] = $companyId;
        return $this->search($filters, $pagination);
    }

    public function countByCompany(string $companyId, array $filters = []): int
    {
        $filters["company_id"] = $companyId;
        return $this->count($filters);
    }

    public function create(Job $job): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO `jobs` (
                `id`, `company_id`, `category_id`, `location_id`, `title`, 
                `description`, `requirements`, `benefits`, `location`, `city`, 
                `district`, `address`, `status`, `work_type`, `work_mode`, 
                `salary_type`, `salary_min`, `salary_max`, `currency`, `shift_type`, 
                `shift_information`, `working_schedule`, `required_skills`, `minimum_age`, `maximum_age`, `quantity`, 
                `application_deadline`, `rejection_reason`, `published_at`, `type`, 
                `work_format`, `deadline`, `category`
            ) VALUES (
                ?, ?, ?, ?, ?, 
                ?, ?, ?, ?, ?, 
                ?, ?, ?, ?, ?, 
                ?, ?, ?, ?, ?, 
                ?, ?, ?, ?, ?, ?, 
                ?, ?, ?, ?, 
                ?, ?, ?
            )"
        );

        $legacyType = str_replace("_", "-", $job->getWorkType());
        if (!in_array($legacyType, ["full-time", "part-time", "contract", "freelance"], true)) {
            $legacyType = "part-time";
        }

        $stmt->execute([
            $job->getId(),
            $job->getCompanyId(),
            $job->getCategoryId(),
            $job->getLocationId(),
            $job->getTitle(),
            $job->getDescription(),
            $job->getRequirements(),
            $job->getBenefits(),
            $job->getLocation(),
            $job->getCity(),
            $job->getDistrict(),
            $job->getAddress(),
            $job->getStatus(),
            $job->getWorkType(),
            $job->getWorkMode(),
            $job->getSalaryType(),
            $job->getSalaryMin(),
            $job->getSalaryMax(),
            $job->getCurrency(),
            $job->getShiftType(),
            $job->getShiftInformation(),
            $job->getWorkingSchedule(),
            $job->getRequiredSkills(),
            $job->getMinimumAge(),
            $job->getMaximumAge(),
            $job->getQuantity(),
            $job->getApplicationDeadline(),
            $job->getRejectionReason(),
            $job->getPublishedAt(),
            $legacyType,
            $job->getWorkMode() === "onsite" ? "on-site" : $job->getWorkMode(),
            $job->getApplicationDeadline(),
            $job->getCategoryId()
        ]);
    }

    public function update(Job $job): void
    {
        $stmt = $this->db->prepare(
            "UPDATE `jobs` SET 
                `title` = ?,
                `description` = ?,
                `requirements` = ?,
                `benefits` = ?,
                `category_id` = ?,
                `category` = ?,
                `location_id` = ?,
                `location` = ?,
                `city` = ?,
                `district` = ?,
                `address` = ?,
                `status` = ?,
                `work_type` = ?,
                `type` = ?,
                `work_mode` = ?,
                `work_format` = ?,
                `salary_type` = ?,
                `salary_min` = ?,
                `salary_max` = ?,
                `currency` = ?,
                `shift_type` = ?,
                `shift_information` = ?,
                `working_schedule` = ?,
                `required_skills` = ?,
                `minimum_age` = ?,
                `maximum_age` = ?,
                `quantity` = ?,
                `application_deadline` = ?,
                `deadline` = ?,
                `rejection_reason` = ?,
                `published_at` = ?,
                `updated_at` = NOW()
            WHERE `id` = ? AND `deleted_at` IS NULL"
        );

        $legacyType = str_replace("_", "-", $job->getWorkType());
        if (!in_array($legacyType, ["full-time", "part-time", "contract", "freelance"], true)) {
            $legacyType = "part-time";
        }

        $stmt->execute([
            $job->getTitle(),
            $job->getDescription(),
            $job->getRequirements(),
            $job->getBenefits(),
            $job->getCategoryId(),
            $job->getCategoryId(),
            $job->getLocationId(),
            $job->getLocation(),
            $job->getCity(),
            $job->getDistrict(),
            $job->getAddress(),
            $job->getStatus(),
            $job->getWorkType(),
            $legacyType,
            $job->getWorkMode(),
            $job->getWorkMode() === "onsite" ? "on-site" : $job->getWorkMode(),
            $job->getSalaryType(),
            $job->getSalaryMin(),
            $job->getSalaryMax(),
            $job->getCurrency(),
            $job->getShiftType(),
            $job->getShiftInformation(),
            $job->getWorkingSchedule(),
            $job->getRequiredSkills(),
            $job->getMinimumAge(),
            $job->getMaximumAge(),
            $job->getQuantity(),
            $job->getApplicationDeadline(),
            $job->getApplicationDeadline(),
            $job->getRejectionReason(),
            $job->getPublishedAt(),
            $job->getId()
        ]);
    }

    public function close(string $id): void
    {
        $stmt = $this->db->prepare(
            "UPDATE `jobs` SET `status` = 'closed', `updated_at` = NOW() WHERE `id` = ? AND `deleted_at` IS NULL"
        );
        $stmt->execute([$id]);
    }

    public function delete(string $id): void
    {
        $stmt = $this->db->prepare(
            "UPDATE `jobs` SET `deleted_at` = NOW(), `status` = 'closed', `updated_at` = NOW() WHERE `id` = ?"
        );
        $stmt->execute([$id]);
    }
}
