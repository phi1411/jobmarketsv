<?php

namespace JobMarket\Infrastructure;

use JobMarket\Domain\SavedSearch\SavedSearch;
use JobMarket\Domain\SavedSearch\SavedSearchRepositoryInterface;
use JobMarket\Facades\Config;
use JobMarket\Support\Pagination;
use PDO;

class SavedSearchRepository implements SavedSearchRepositoryInterface
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

    public function create(SavedSearch $search): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO `saved_searches` (
                `id`, `user_id`, `name`, `keyword`, `category_id`, `location_id`, 
                `work_type`, `work_mode`, `salary_min`, `salary_max`, `skill_ids`, 
                `shift_type`, `notification_enabled`, `email_enabled`, `minimum_match_score`, `frequency`, `created_at`
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())"
        );

        $skillIdsJson = $search->getSkillIds() !== null ? json_encode($search->getSkillIds(), JSON_UNESCAPED_UNICODE) : null;

        $stmt->execute([
            $search->getId(),
            $search->getUserId(),
            $search->getName(),
            $search->getKeyword(),
            $search->getCategoryId(),
            $search->getLocationId(),
            $search->getWorkType(),
            $search->getWorkMode(),
            $search->getSalaryMin(),
            $search->getSalaryMax(),
            $skillIdsJson,
            $search->getShiftType(),
            $search->isNotificationEnabled() ? 1 : 0,
            $search->isEmailEnabled() ? 1 : 0,
            $search->getMinimumMatchScore(),
            $search->getFrequency()
        ]);
    }

    public function findById(string $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM `saved_searches` WHERE `id` = ? LIMIT 1"
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function getByUser(string $userId, ?Pagination $pagination = null): array
    {
        $sql = "SELECT * FROM `saved_searches` WHERE `user_id` = ? ORDER BY `created_at` DESC";

        if ($pagination !== null) {
            $sql .= " LIMIT " . $pagination->getLimit() . " OFFSET " . $pagination->getOffset();
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countByUser(string $userId): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM `saved_searches` WHERE `user_id` = ?"
        );
        $stmt->execute([$userId]);

        return (int)$stmt->fetchColumn();
    }

    public function update(SavedSearch $search): void
    {
        $stmt = $this->db->prepare(
            "UPDATE `saved_searches` SET 
                `name` = ?,
                `keyword` = ?,
                `category_id` = ?,
                `location_id` = ?,
                `work_type` = ?,
                `work_mode` = ?,
                `salary_min` = ?,
                `salary_max` = ?,
                `skill_ids` = ?,
                `shift_type` = ?,
                `notification_enabled` = ?,
                `email_enabled` = ?,
                `minimum_match_score` = ?,
                `frequency` = ?,
                `updated_at` = NOW()
            WHERE `id` = ?"
        );

        $skillIdsJson = $search->getSkillIds() !== null ? json_encode($search->getSkillIds(), JSON_UNESCAPED_UNICODE) : null;

        $stmt->execute([
            $search->getName(),
            $search->getKeyword(),
            $search->getCategoryId(),
            $search->getLocationId(),
            $search->getWorkType(),
            $search->getWorkMode(),
            $search->getSalaryMin(),
            $search->getSalaryMax(),
            $skillIdsJson,
            $search->getShiftType(),
            $search->isNotificationEnabled() ? 1 : 0,
            $search->isEmailEnabled() ? 1 : 0,
            $search->getMinimumMatchScore(),
            $search->getFrequency(),
            $search->getId()
        ]);
    }

    public function delete(string $id): void
    {
        $stmt = $this->db->prepare(
            "DELETE FROM `saved_searches` WHERE `id` = ?"
        );
        $stmt->execute([$id]);
    }
}
