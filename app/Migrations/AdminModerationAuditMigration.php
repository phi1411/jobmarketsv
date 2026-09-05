<?php

namespace JobMarket\Migrations;

use PDO;

class AdminModerationAuditMigration extends Migration
{
    private string $table_name = "admin_moderation_audit";

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

        // 1. Add rejection_reason to companies table
        if (!$hasColumn("companies", "rejection_reason")) {
            $db->exec("ALTER TABLE `companies` ADD COLUMN `rejection_reason` TEXT NULL AFTER `verification_status`");
        }

        // 2. Expand jobs status enum with 'hidden'
        $db->exec("ALTER TABLE `jobs` MODIFY COLUMN `status` ENUM('draft','pending_approval','published','rejected','hidden','closed','expired') NOT NULL DEFAULT 'published'");

        // 3. Expand users status enum with 'suspended'
        $db->exec("ALTER TABLE `users` MODIFY COLUMN `status` ENUM('active','inactive','suspended','banned') NOT NULL DEFAULT 'active'");

        // 4. Create audit_logs table
        $db->exec("CREATE TABLE IF NOT EXISTS `audit_logs` (
            `id` VARCHAR(255) PRIMARY KEY,
            `actor_id` VARCHAR(255) NOT NULL,
            `action` VARCHAR(100) NOT NULL,
            `target_type` VARCHAR(50) NOT NULL,
            `target_id` VARCHAR(255) NOT NULL,
            `metadata` JSON NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_audit_logs_actor` (`actor_id`),
            INDEX `idx_audit_logs_target` (`target_type`, `target_id`),
            INDEX `idx_audit_logs_created` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    public function down(PDO $db): void
    {
        $config = \JobMarket\Facades\Config::env();
        if (($config["env"] ?? "") === "production") {
            echo "  [WARNING] Destructive rollback is disabled in production environment to prevent data loss." . PHP_EOL;
            return;
        }

        $hasColumn = function(string $table, string $column) use ($db): bool {
            $stmt = $db->prepare(
                "SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?"
            );
            $stmt->execute([$table, $column]);
            return (bool)$stmt->fetchColumn();
        };

        if ($hasColumn("companies", "rejection_reason")) {
            try {
                $db->exec("ALTER TABLE `companies` DROP COLUMN `rejection_reason`");
            } catch (\PDOException) {
            }
        }

        try {
            $db->exec("DROP TABLE IF EXISTS `audit_logs`");
        } catch (\PDOException) {
        }
    }

    public function getTableName(): string
    {
        return $this->table_name;
    }
}
