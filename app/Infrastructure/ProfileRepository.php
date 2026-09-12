<?php

namespace JobMarket\Infrastructure;

use JobMarket\Domain\Profile\Profile;
use JobMarket\Domain\Profile\ProfileRepositoryInterface;
use JobMarket\Facades\Config;
use JobMarket\Support\Pagination;
use JobMarket\Support\QueryHelper;
use PDO;

class ProfileRepository implements ProfileRepositoryInterface
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

    public function findByUserId(string $userId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT sp.*, u.email, u.name AS user_account_name
             FROM `student_profiles` sp
             JOIN `users` u ON sp.user_id = u.id
             WHERE sp.user_id = ? LIMIT 1"
        );
        $stmt->execute([$userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function findById(string $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT sp.*, u.name AS user_account_name
             FROM `student_profiles` sp
             JOIN `users` u ON sp.user_id = u.id
             WHERE (sp.id = ? OR sp.user_id = ?) LIMIT 1"
        );
        $stmt->execute([$id, $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function upsert(Profile $profile): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO `student_profiles` (
                `id`, `user_id`, `full_name`, `phone`, `date_of_birth`, `gender`, 
                `university`, `major`, `academic_year`, `bio`, `location_id`, 
                `preferred_location`, `preferred_locations`, `available_schedule`, 
                `skills`, `skill_ids`, `work_experience`, `education`, `certificates`, 
                `cv_url`, `profile_completion_percent`,
                `cv_storage_path`, `cv_original_name`, `cv_mime_type`, `cv_file_size`, `cv_uploaded_at`
            ) VALUES (
                ?, ?, ?, ?, ?, ?, 
                ?, ?, ?, ?, ?, 
                ?, ?, ?, 
                ?, ?, ?, ?, ?, 
                ?, ?,
                ?, ?, ?, ?, ?
            ) ON DUPLICATE KEY UPDATE
                `full_name` = VALUES(`full_name`),
                `phone` = VALUES(`phone`),
                `date_of_birth` = VALUES(`date_of_birth`),
                `gender` = VALUES(`gender`),
                `university` = VALUES(`university`),
                `major` = VALUES(`major`),
                `academic_year` = VALUES(`academic_year`),
                `bio` = VALUES(`bio`),
                `location_id` = VALUES(`location_id`),
                `preferred_location` = VALUES(`preferred_location`),
                `preferred_locations` = VALUES(`preferred_locations`),
                `available_schedule` = VALUES(`available_schedule`),
                `skills` = VALUES(`skills`),
                `skill_ids` = VALUES(`skill_ids`),
                `work_experience` = VALUES(`work_experience`),
                `education` = VALUES(`education`),
                `certificates` = VALUES(`certificates`),
                `cv_url` = VALUES(`cv_url`),
                `profile_completion_percent` = VALUES(`profile_completion_percent`),
                `cv_storage_path` = VALUES(`cv_storage_path`),
                `cv_original_name` = VALUES(`cv_original_name`),
                `cv_mime_type` = VALUES(`cv_mime_type`),
                `cv_file_size` = VALUES(`cv_file_size`),
                `cv_uploaded_at` = VALUES(`cv_uploaded_at`),
                `updated_at` = NOW()"
        );

        $scheduleJson = $profile->getAvailableSchedule() !== null ? json_encode($profile->getAvailableSchedule(), JSON_UNESCAPED_UNICODE) : null;
        $skillIdsJson = $profile->getSkillIds() !== null ? json_encode($profile->getSkillIds(), JSON_UNESCAPED_UNICODE) : null;

        $stmt->execute([
            $profile->getId(),
            $profile->getUserId(),
            $profile->getFullName(),
            $profile->getPhone(),
            $profile->getDateOfBirth(),
            $profile->getGender(),
            $profile->getUniversity(),
            $profile->getMajor(),
            $profile->getAcademicYear(),
            $profile->getBio(),
            $profile->getLocationId(),
            $profile->getPreferredLocation(),
            $profile->getPreferredLocations(),
            $scheduleJson,
            $profile->getSkills(),
            $skillIdsJson,
            $profile->getWorkExperience(),
            $profile->getEducation(),
            $profile->getCertificates(),
            $profile->getCvUrl(),
            $profile->getProfileCompletionPercent(),
            $profile->getCvStoragePath(),
            $profile->getCvOriginalName(),
            $profile->getCvMimeType(),
            $profile->getCvFileSize(),
            $profile->getCvUploadedAt(),
        ]);

        // Also sync name in users table if full_name is present
        if (!empty($profile->getFullName())) {
            $userStmt = $this->db->prepare("UPDATE `users` SET `name` = ? WHERE `id` = ?");
            $userStmt->execute([$profile->getFullName(), $profile->getUserId()]);
        }
    }

    public function updateCvMetadata(string $userId, ?array $cvData): void
    {
        if ($cvData === null) {
            $stmt = $this->db->prepare(
                "UPDATE `student_profiles` SET
                    `cv_storage_path` = NULL,
                    `cv_original_name` = NULL,
                    `cv_mime_type` = NULL,
                    `cv_file_size` = NULL,
                    `cv_uploaded_at` = NULL,
                    `updated_at` = NOW()
                 WHERE `user_id` = ?"
            );
            $stmt->execute([$userId]);
        } else {
            $stmt = $this->db->prepare(
                "UPDATE `student_profiles` SET
                    `cv_storage_path` = ?,
                    `cv_original_name` = ?,
                    `cv_mime_type` = ?,
                    `cv_file_size` = ?,
                    `cv_uploaded_at` = ?,
                    `updated_at` = NOW()
                 WHERE `user_id` = ?"
            );
            $stmt->execute([
                $cvData['storage_path'] ?? null,
                $cvData['original_name'] ?? null,
                $cvData['mime_type'] ?? null,
                $cvData['file_size'] ?? null,
                $cvData['uploaded_at'] ?? date('Y-m-d H:i:s'),
                $userId
            ]);
        }
    }

    public function searchPublic(array $filters = [], ?Pagination $pagination = null): array
    {
        $sql = "SELECT sp.*, u.name AS user_account_name 
                FROM `student_profiles` sp
                JOIN `users` u ON sp.user_id = u.id
                WHERE u.status = 'active'";
        $params = [];

        if (!empty($filters["university"])) {
            $sql .= " AND sp.university LIKE ?";
            $params[] = "%" . QueryHelper::escapeLike($filters["university"]) . "%";
        }

        if (!empty($filters["academic_year"]) && is_numeric($filters["academic_year"])) {
            $sql .= " AND sp.academic_year = ?";
            $params[] = (int)$filters["academic_year"];
        }

        if (!empty($filters["location_id"])) {
            $sql .= " AND sp.location_id = ?";
            $params[] = $filters["location_id"];
        }

        if (!empty($filters["keyword"])) {
            $keyword = "%" . QueryHelper::escapeLike($filters["keyword"]) . "%";
            $sql .= " AND (sp.full_name LIKE ? OR sp.major LIKE ? OR sp.bio LIKE ? OR sp.skills LIKE ?)";
            $params[] = $keyword;
            $params[] = $keyword;
            $params[] = $keyword;
            $params[] = $keyword;
        }

        $sort = QueryHelper::sanitizeSort(
            $filters["sort_by"] ?? "created_at",
            $filters["sort_dir"] ?? "DESC",
            ["created_at", "profile_completion_percent", "academic_year"]
        );
        $sql .= " " . $sort["sql"];

        if ($pagination !== null) {
            $sql .= " LIMIT " . $pagination->getLimit() . " OFFSET " . $pagination->getOffset();
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countPublic(array $filters = []): int
    {
        $sql = "SELECT COUNT(*) 
                FROM `student_profiles` sp
                JOIN `users` u ON sp.user_id = u.id
                WHERE u.status = 'active'";
        $params = [];

        if (!empty($filters["university"])) {
            $sql .= " AND sp.university LIKE ?";
            $params[] = "%" . QueryHelper::escapeLike($filters["university"]) . "%";
        }

        if (!empty($filters["academic_year"]) && is_numeric($filters["academic_year"])) {
            $sql .= " AND sp.academic_year = ?";
            $params[] = (int)$filters["academic_year"];
        }

        if (!empty($filters["location_id"])) {
            $sql .= " AND sp.location_id = ?";
            $params[] = $filters["location_id"];
        }

        if (!empty($filters["keyword"])) {
            $keyword = "%" . QueryHelper::escapeLike($filters["keyword"]) . "%";
            $sql .= " AND (sp.full_name LIKE ? OR sp.major LIKE ? OR sp.bio LIKE ? OR sp.skills LIKE ?)";
            $params[] = $keyword;
            $params[] = $keyword;
            $params[] = $keyword;
            $params[] = $keyword;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (int)$stmt->fetchColumn();
    }
}