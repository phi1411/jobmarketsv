<?php

namespace JobMarket\Infrastructure;

use JobMarket\Domain\Favorite\FavoriteRepositoryInterface;
use JobMarket\Facades\Config;
use JobMarket\Support\Pagination;
use PDO;

class FavoriteRepository implements FavoriteRepositoryInterface
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

    public function add(string $userId, string $jobId): string
    {
        $id = "fav-" . uniqid();
        $stmt = $this->db->prepare(
            "INSERT INTO `favorites` (`id`, `user_id`, `job_id`, `created_at`) 
             VALUES (?, ?, ?, NOW()) 
             ON DUPLICATE KEY UPDATE `updated_at` = NOW()"
        );
        $stmt->execute([$id, $userId, $jobId]);

        return $id;
    }

    public function remove(string $userId, string $jobId): void
    {
        $stmt = $this->db->prepare(
            "DELETE FROM `favorites` WHERE `user_id` = ? AND (`job_id` = ? OR `id` = ?)"
        );
        $stmt->execute([$userId, $jobId, $jobId]);
    }

    public function isFavorited(string $userId, string $jobId): bool
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM `favorites` WHERE `user_id` = ? AND `job_id` = ?"
        );
        $stmt->execute([$userId, $jobId]);

        return ((int)$stmt->fetchColumn()) > 0;
    }

    public function getByUser(string $userId, ?Pagination $pagination = null): array
    {
        $sql = "SELECT f.*, 
                       j.title AS job_title, j.company_id, 
                       c.name AS company_name, c.logo_url AS company_logo,
                       j.location, j.city, j.district, 
                       j.salary_min, j.salary_max, j.salary_type, 
                       j.shift_type, j.work_type, j.application_deadline
                FROM `favorites` f
                JOIN `jobs` j ON f.job_id = j.id
                LEFT JOIN `companies` c ON j.company_id = c.id
                WHERE f.user_id = ? 
                  AND j.deleted_at IS NULL 
                  AND j.status = 'published'
                ORDER BY f.created_at DESC";

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
            "SELECT COUNT(*) 
             FROM `favorites` f
             JOIN `jobs` j ON f.job_id = j.id
             WHERE f.user_id = ? 
               AND j.deleted_at IS NULL 
               AND j.status = 'published'"
        );
        $stmt->execute([$userId]);

        return (int)$stmt->fetchColumn();
    }
}
