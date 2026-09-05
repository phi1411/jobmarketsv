<?php

namespace JobMarket\Infrastructure;

use JobMarket\Domain\Notification\Notification;
use JobMarket\Domain\Notification\NotificationRepositoryInterface;
use JobMarket\Facades\Config;
use JobMarket\Support\Pagination;
use PDO;

class NotificationRepository implements NotificationRepositoryInterface
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

    public function create(Notification $notification): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO `notifications` (
                `id`, `user_id`, `type`, `title`, `message`, `data`, `read_at`, `is_read`, `created_at`
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())"
        );

        $dataJson = $notification->getData() !== null ? json_encode($notification->getData(), JSON_UNESCAPED_UNICODE) : null;
        $isRead = $notification->getReadAt() !== null ? 1 : 0;

        $stmt->execute([
            $notification->getId(),
            $notification->getUserId(),
            $notification->getType(),
            $notification->getTitle(),
            $notification->getMessage(),
            $dataJson,
            $notification->getReadAt(),
            $isRead
        ]);
    }

    public function findById(string $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM `notifications` WHERE `id` = ? LIMIT 1"
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function getByUser(string $userId, ?Pagination $pagination = null): array
    {
        $sql = "SELECT * FROM `notifications` WHERE `user_id` = ? ORDER BY `created_at` DESC";

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
            "SELECT COUNT(*) FROM `notifications` WHERE `user_id` = ?"
        );
        $stmt->execute([$userId]);

        return (int)$stmt->fetchColumn();
    }

    public function countUnreadByUser(string $userId): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM `notifications` WHERE `user_id` = ? AND `read_at` IS NULL"
        );
        $stmt->execute([$userId]);

        return (int)$stmt->fetchColumn();
    }

    public function markAsRead(string $id, string $userId): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE `notifications` 
             SET `read_at` = NOW(), `is_read` = 1, `updated_at` = NOW() 
             WHERE `id` = ? AND `user_id` = ?"
        );
        $stmt->execute([$id, $userId]);

        return $stmt->rowCount() > 0;
    }

    public function markAllAsRead(string $userId): int
    {
        $stmt = $this->db->prepare(
            "UPDATE `notifications` 
             SET `read_at` = NOW(), `is_read` = 1, `updated_at` = NOW() 
             WHERE `user_id` = ? AND `read_at` IS NULL"
        );
        $stmt->execute([$userId]);

        return $stmt->rowCount();
    }
}
