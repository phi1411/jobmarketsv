<?php

namespace JobMarket\Migrations;

use PDO;

class ApplicationDecisionNotificationMigration extends Migration
{
    private string $table_name = "application_decision_deliveries";

    public function create(): void
    {
        $db = $this->getDB();
        $hasColumn = function (string $table, string $column) use ($db): bool {
            $stmt = $db->prepare(
                "SELECT COUNT(*) FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?"
            );
            $stmt->execute([$table, $column]);
            return (bool)$stmt->fetchColumn();
        };

        if (!$hasColumn("applications", "student_message")) {
            $db->exec("ALTER TABLE `applications` ADD COLUMN `student_message` TEXT NULL AFTER `employer_note`");
        }

        $db->exec(
            "CREATE TABLE IF NOT EXISTS `application_decision_deliveries` (
                `id` VARCHAR(255) PRIMARY KEY,
                `application_id` VARCHAR(255) NOT NULL,
                `status` ENUM('interview','accepted','rejected') NOT NULL,
                `student_message` TEXT NOT NULL,
                `notification_id` VARCHAR(255) NULL,
                `email_status` ENUM('sent','preview','failed') NOT NULL,
                `email_error` VARCHAR(500) NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                KEY `idx_decision_delivery_application` (`application_id`, `created_at`),
                CONSTRAINT `fk_decision_delivery_application` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        // Chuẩn hóa dữ liệu cũ sang bộ trạng thái quyết định mới.
        $db->exec("UPDATE `applications` SET `status` = 'pending' WHERE `status` IN ('viewed','reviewed')");
        $db->exec("UPDATE `applications` SET `status` = 'interview' WHERE `status` = 'shortlisted'");

        $db->exec(
            "INSERT INTO `application_status_history` (`id`,`application_id`,`status`,`actor_role`,`note`,`created_at`)
             SELECT CONCAT('hist-decision-interview-', a.id), a.id, 'interview', 'system', 'Mời phỏng vấn', COALESCE(a.updated_at, NOW())
             FROM `applications` a
             WHERE a.status = 'interview' AND NOT EXISTS (
                 SELECT 1 FROM `application_status_history` h WHERE h.application_id = a.id AND h.status = 'interview'
             )"
        );
    }

    public function down(PDO $db): void
    {
        $db->exec("DROP TABLE IF EXISTS `application_decision_deliveries`");
        $stmt = $db->query(
            "SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'applications' AND COLUMN_NAME = 'student_message'"
        );
        if ($stmt->fetchColumn()) {
            $db->exec("ALTER TABLE `applications` DROP COLUMN `student_message`");
        }
    }

    public function getTableName(): string
    {
        return $this->table_name;
    }
}
