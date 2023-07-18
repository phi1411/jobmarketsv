<?php

namespace JobMarket\Infrastructure;

use JobMarket\Domain\Notification\Notification;
use JobMarket\Domain\Notification\NotificationRepositoryInterface;
use JobMarket\Facades\Config;
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
            $config["password"]
        );
    }
    public function getAll(): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM notifications"
        );
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function create(Notification $notif): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO notifications (id, user_id, message, is_read) VALUES (?, ?, ?, ?)"
        );
        $stmt->execute([
            $notif->getId(),
            $notif->getUserId(),
            $notif->getMessage(),
            $notif->getIsRead()
        ]);
    }
    public function getById(string $id): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM notifications WHERE id = ?"
        );
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    public function update(Notification $notif): void
    {
        $stmt = $this->db->prepare(
            "UPDATE notifications SET user_id = ?, message = ?, is_read = ? WHERE id = ?"
        );
        $stmt->execute([
            $notif->getUserId(),
            $notif->getMessage(),
            $notif->getIsRead(),
            $notif->getId()
        ]);
    }
    public function delete(string $id): void
    {
        $stmt = $this->db->prepare(
            "DELETE FROM notifications WHERE id = ?"
        );
        $stmt->execute([$id]);
    }
}
