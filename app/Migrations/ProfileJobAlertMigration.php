<?php

namespace JobMarket\Migrations;

use PDO;

class ProfileJobAlertMigration extends Migration
{
    private string $table_name = "profile_job_alerts";

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

        if (!$hasColumn("student_profiles", "recommendation_alert_enabled")) {
            $db->exec("ALTER TABLE `student_profiles` ADD COLUMN `recommendation_alert_enabled` TINYINT(1) NOT NULL DEFAULT 0 AFTER `profile_completion_percent`");
        }
        if (!$hasColumn("student_profiles", "recommendation_email_enabled")) {
            $db->exec("ALTER TABLE `student_profiles` ADD COLUMN `recommendation_email_enabled` TINYINT(1) NOT NULL DEFAULT 0 AFTER `recommendation_alert_enabled`");
        }
        if (!$hasColumn("student_profiles", "recommendation_minimum_score")) {
            $db->exec("ALTER TABLE `student_profiles` ADD COLUMN `recommendation_minimum_score` TINYINT UNSIGNED NOT NULL DEFAULT 65 AFTER `recommendation_email_enabled`");
        }

        $db->exec(
            "CREATE TABLE IF NOT EXISTS `profile_job_alerts` (
                `id` VARCHAR(255) PRIMARY KEY,
                `user_id` VARCHAR(255) NOT NULL,
                `job_id` VARCHAR(255) NOT NULL,
                `notification_id` VARCHAR(255) NULL,
                `match_score` TINYINT UNSIGNED NOT NULL,
                `coverage_percent` TINYINT UNSIGNED NOT NULL,
                `match_details` JSON NULL,
                `email_status` ENUM('disabled','pending','preview','sent','failed') NOT NULL DEFAULT 'disabled',
                `email_sent_at` TIMESTAMP NULL,
                `email_error` VARCHAR(500) NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY `uq_profile_alert_user_job` (`user_id`, `job_id`),
                KEY `idx_profile_alert_user` (`user_id`, `created_at`),
                CONSTRAINT `fk_profile_alert_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
                CONSTRAINT `fk_profile_alert_job` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }

    public function down(PDO $db): void
    {
        $db->exec("DROP TABLE IF EXISTS `profile_job_alerts`");
        foreach (["recommendation_minimum_score", "recommendation_email_enabled", "recommendation_alert_enabled"] as $column) {
            $stmt = $db->prepare(
                "SELECT COUNT(*) FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'student_profiles' AND COLUMN_NAME = ?"
            );
            $stmt->execute([$column]);
            if ($stmt->fetchColumn()) {
                $db->exec("ALTER TABLE `student_profiles` DROP COLUMN `{$column}`");
            }
        }
    }

    public function getTableName(): string
    {
        return $this->table_name;
    }
}
