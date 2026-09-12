<?php

namespace JobMarket\Infrastructure;

use JobMarket\Domain\Chat\ChatRepositoryInterface;
use JobMarket\Facades\Config;
use PDO;
use Throwable;

class ChatRepository implements ChatRepositoryInterface
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        if ($db !== null) {
            $this->db = $db;
        } else {
            $config = Config::env();
            $this->db = new PDO(
                "mysql:dbname={$config['dbname']};host={$config['host']};charset=utf8mb4",
                $config["user"],
                $config["password"],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
                ]
            );
        }
    }

    public function getDB(): PDO
    {
        return $this->db;
    }

    public function findAdminUser(): ?array
    {
        $stmt = $this->db->prepare("SELECT `id`, `name`, `email`, `role` FROM `users` WHERE `role` = 'admin' LIMIT 1");
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            return $user;
        }

        // Fallback default admin
        return [
            "id"    => "user-admin-01",
            "name"  => "Quản Trị Viên",
            "email" => "admin@jobmarket.vn",
            "role"  => "admin"
        ];
    }

    public function getOrCreateSupportConversation(string $userId, string $adminId): array
    {
        // Check if conversation already exists between user and admin
        $stmt = $this->db->prepare(
            "SELECT * FROM `conversations` 
             WHERE (`sender_id` = :u1 AND `recipient_id` = :a1) 
                OR (`sender_id` = :a2 AND `recipient_id` = :u2) 
             LIMIT 1"
        );
        $stmt->execute([
            ":u1" => $userId,
            ":a1" => $adminId,
            ":a2" => $adminId,
            ":u2" => $userId
        ]);

        $conv = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($conv) {
            return $conv;
        }

        // Create new conversation
        $id = "conv-" . bin2hex(random_bytes(10));
        $insert = $this->db->prepare(
            "INSERT INTO `conversations` (`id`, `sender_id`, `recipient_id`, `subject`, `created_at`, `updated_at`) 
             VALUES (?, ?, ?, 'Hỗ trợ trực tuyến', NOW(), NOW())"
        );
        $insert->execute([$id, $userId, $adminId]);

        return [
            "id"           => $id,
            "sender_id"    => $userId,
            "recipient_id" => $adminId,
            "subject"      => "Hỗ trợ trực tuyến",
            "created_at"   => date("Y-m-d H:i:s"),
            "updated_at"   => date("Y-m-d H:i:s")
        ];
    }

    public function getConversationById(string $conversationId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM `conversations` WHERE `id` = ? LIMIT 1");
        $stmt->execute([$conversationId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function listAdminConversations(): array
    {
        $admin = $this->findAdminUser();
        $adminId = $admin["id"];

        // Query all conversations with the latest message and other user details
        $sql = "
            SELECT 
                c.id AS conversation_id,
                c.sender_id,
                c.recipient_id,
                c.subject,
                c.updated_at AS last_activity,
                u.id AS user_id,
                u.name AS user_name,
                u.email AS user_email,
                u.role AS user_role,
                (
                    SELECT m.content 
                    FROM messages m 
                    WHERE m.conversation_id = c.id 
                    ORDER BY m.created_at DESC, m.id DESC 
                    LIMIT 1
                ) AS last_message,
                (
                    SELECT m.created_at 
                    FROM messages m 
                    WHERE m.conversation_id = c.id 
                    ORDER BY m.created_at DESC, m.id DESC 
                    LIMIT 1
                ) AS last_message_time,
                (
                    SELECT COUNT(*) 
                    FROM messages m 
                    WHERE m.conversation_id = c.id 
                      AND m.sender_id != :adminId 
                      AND m.is_read = 0
                ) AS unread_count
            FROM conversations c
            JOIN users u ON u.id = CASE 
                WHEN c.sender_id = :adminId2 THEN c.recipient_id 
                ELSE c.sender_id 
            END
            WHERE c.sender_id = :adminId3 OR c.recipient_id = :adminId4
            ORDER BY c.updated_at DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ":adminId"  => $adminId,
            ":adminId2" => $adminId,
            ":adminId3" => $adminId,
            ":adminId4" => $adminId
        ]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $result = [];
        foreach ($rows as $row) {
            $result[] = [
                "id"           => $row["conversation_id"],
                "subject"      => $row["subject"] ?? "Hỗ trợ trực tuyến",
                "updated_at"   => $row["last_activity"] ?? date("Y-m-d H:i:s"),
                "unread_count" => (int)($row["unread_count"] ?? 0),
                "other_user"   => [
                    "id"    => $row["user_id"],
                    "name"  => $row["user_name"],
                    "email" => $row["user_email"],
                    "role"  => $row["user_role"]
                ],
                "last_message" => !empty($row["last_message"]) ? [
                    "content"    => $row["last_message"],
                    "created_at" => $row["last_message_time"]
                ] : null
            ];
        }

        return $result;
    }

    public function getMessages(string $conversationId, ?string $afterId = null, int $limit = 100): array
    {
        if (!empty($afterId)) {
            // Incremental polling: fetch messages created after the reference message
            $refStmt = $this->db->prepare("SELECT `created_at` FROM `messages` WHERE `id` = ? LIMIT 1");
            $refStmt->execute([$afterId]);
            $refTime = $refStmt->fetchColumn();

            if ($refTime) {
                $stmt = $this->db->prepare("
                    SELECT m.id, m.conversation_id, m.sender_id, m.content, m.is_read, m.created_at,
                           u.name AS sender_name, u.role AS sender_role
                    FROM messages m
                    JOIN users u ON u.id = m.sender_id
                    WHERE m.conversation_id = :convId 
                      AND (m.created_at > :refTime OR (m.created_at = :refTime2 AND m.id != :afterId2))
                    ORDER BY m.created_at ASC, m.id ASC
                    LIMIT :limit
                ");
                $stmt->bindValue(":convId", $conversationId);
                $stmt->bindValue(":refTime", $refTime);
                $stmt->bindValue(":refTime2", $refTime);
                $stmt->bindValue(":afterId2", $afterId);
                $stmt->bindValue(":limit", $limit, PDO::PARAM_INT);
                $stmt->execute();
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        }

        // Full fetch (initial load)
        $stmt = $this->db->prepare("
            SELECT m.id, m.conversation_id, m.sender_id, m.content, m.is_read, m.created_at,
                   u.name AS sender_name, u.role AS sender_role
            FROM messages m
            JOIN users u ON u.id = m.sender_id
            WHERE m.conversation_id = :convId
            ORDER BY m.created_at ASC, m.id ASC
            LIMIT :limit
        ");
        $stmt->bindValue(":convId", $conversationId);
        $stmt->bindValue(":limit", $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createMessage(string $conversationId, string $senderId, string $content): array
    {
        $id = "msg-" . bin2hex(random_bytes(10));
        $now = date("Y-m-d H:i:s");

        $stmt = $this->db->prepare(
            "INSERT INTO `messages` (`id`, `conversation_id`, `sender_id`, `content`, `is_read`, `created_at`, `updated_at`) 
             VALUES (?, ?, ?, ?, 0, ?, ?)"
        );
        $stmt->execute([$id, $conversationId, $senderId, $content, $now, $now]);

        // Update conversation updated_at
        $upd = $this->db->prepare("UPDATE `conversations` SET `updated_at` = ? WHERE `id` = ?");
        $upd->execute([$now, $conversationId]);

        // Fetch sender info
        $userStmt = $this->db->prepare("SELECT `name`, `role` FROM `users` WHERE `id` = ? LIMIT 1");
        $userStmt->execute([$senderId]);
        $user = $userStmt->fetch(PDO::FETCH_ASSOC);

        return [
            "id"              => $id,
            "conversation_id" => $conversationId,
            "sender_id"       => $senderId,
            "sender_name"     => $user["name"] ?? "Người dùng",
            "sender_role"     => $user["role"] ?? "user",
            "content"         => $content,
            "is_read"         => 0,
            "created_at"      => $now
        ];
    }

    public function markAsRead(string $conversationId, string $readerId): void
    {
        $stmt = $this->db->prepare(
            "UPDATE `messages` 
             SET `is_read` = 1 
             WHERE `conversation_id` = ? 
               AND `sender_id` != ? 
               AND `is_read` = 0"
        );
        $stmt->execute([$conversationId, $readerId]);
    }

    public function getUnreadCountForUser(string $userId): int
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) 
            FROM messages m
            JOIN conversations c ON c.id = m.conversation_id
            WHERE (c.sender_id = :u1 OR c.recipient_id = :u2)
              AND m.sender_id != :u3
              AND m.is_read = 0
        ");
        $stmt->execute([
            ":u1" => $userId,
            ":u2" => $userId,
            ":u3" => $userId
        ]);

        return (int)$stmt->fetchColumn();
    }
}
