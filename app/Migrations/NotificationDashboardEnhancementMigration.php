<?php

namespace JobMarket\Migrations;

use PDO;

class NotificationDashboardEnhancementMigration extends Migration
{
    private string $table_name = "notification_dashboard_enhancement";

    public function create(): void
    {
        $db = $this->getDB();

        $hasColumn = function(string $table, string $column) use ($db): bool {
            $stmt = $db->prepare(
                "SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?"
            );
            $stmt->execute([$table, $column]);
            return (bool)$stmt->fetchColumn();
        };

        $hasIndex = function(string $table, string $indexName) use ($db): bool {
            $stmt = $db->prepare(
                "SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?"
            );
            $stmt->execute([$table, $indexName]);
            return (bool)$stmt->fetchColumn();
        };

        // 1. Add columns to notifications table
        if (!$hasColumn("notifications", "type")) {
            $db->exec("ALTER TABLE `notifications` ADD COLUMN `type` VARCHAR(50) NOT NULL DEFAULT 'general' AFTER `user_id`");
        }
        if (!$hasColumn("notifications", "data")) {
            $db->exec("ALTER TABLE `notifications` ADD COLUMN `data` JSON NULL AFTER `message`");
        }
        if (!$hasColumn("notifications", "read_at")) {
            $db->exec("ALTER TABLE `notifications` ADD COLUMN `read_at` TIMESTAMP NULL AFTER `data`");
        }

        // 2. Add performance indexes
        if (!$hasIndex("notifications", "idx_notifications_user_read")) {
            $db->exec("CREATE INDEX `idx_notifications_user_read` ON `notifications` (`user_id`, `read_at`)");
        }
        if (!$hasIndex("notifications", "idx_notifications_user_created")) {
            $db->exec("CREATE INDEX `idx_notifications_user_created` ON `notifications` (`user_id`, `created_at`)");
        }
    }

    public function down(PDO $db): void
    {
        $config = \JobMarket\Facades\Config::env();
        if (($config["env"] ?? "") === "production") {
            echo "  [WARNING] Destructive rollback is disabled in production environment to prevent data loss." . PHP_EOL;
            return;
        }

        $hasIndex = function(string $table, string $indexName) use ($db): bool {
            $stmt = $db->prepare(
                "SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?"
            );
            $stmt->execute([$table, $indexName]);
            return (bool)$stmt->fetchColumn();
        };

        if ($hasIndex("notifications", "idx_notifications_user_read")) {
            try {
                $db->exec("ALTER TABLE `notifications` DROP INDEX `idx_notifications_user_read`");
            } catch (\PDOException $e) {
            }
        }
        if ($hasIndex("notifications", "idx_notifications_user_created")) {
            try {
                $db->exec("ALTER TABLE `notifications` DROP INDEX `idx_notifications_user_created`");
            } catch (\PDOException $e) {
            }
        }

        $hasColumn = function(string $table, string $column) use ($db): bool {
            $stmt = $db->prepare(
                "SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?"
            );
            $stmt->execute([$table, $column]);
            return (bool)$stmt->fetchColumn();
        };

        $cols = ["type", "data", "read_at"];
        foreach ($cols as $col) {
            if ($hasColumn("notifications", $col)) {
                try {
                    $db->exec("ALTER TABLE `notifications` DROP COLUMN `{$col}`");
                } catch (\PDOException $e) {
                }
            }
        }
    }

    public function getTableName(): string
    {
        return $this->table_name;
    }
}
