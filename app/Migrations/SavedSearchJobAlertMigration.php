<?php

namespace JobMarket\Migrations;

use PDO;

class SavedSearchJobAlertMigration extends Migration
{
    private string $table_name = "saved_search_job_alerts";

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

        if (!$hasColumn("saved_searches", "email_enabled")) {
            $db->exec("ALTER TABLE `saved_searches` ADD COLUMN `email_enabled` TINYINT(1) NOT NULL DEFAULT 1 AFTER `notification_enabled`");
        }
        if (!$hasColumn("saved_searches", "minimum_match_score")) {
            $db->exec("ALTER TABLE `saved_searches` ADD COLUMN `minimum_match_score` TINYINT UNSIGNED NOT NULL DEFAULT 65 AFTER `email_enabled`");
        }
        if (!$hasColumn("saved_searches", "last_matched_at")) {
            $db->exec("ALTER TABLE `saved_searches` ADD COLUMN `last_matched_at` TIMESTAMP NULL AFTER `frequency`");
        }

        $db->exec("ALTER TABLE `saved_searches` MODIFY COLUMN `frequency` ENUM('instant','daily','weekly') NOT NULL DEFAULT 'instant'");
        // Older UI never exposed frequency; its implicit "daily" value should become the requested instant alert default.
        $db->exec("UPDATE `saved_searches` SET `frequency` = 'instant' WHERE `frequency` = 'daily' AND `last_matched_at` IS NULL");

        $db->exec(
            "CREATE TABLE IF NOT EXISTS `saved_search_job_alerts` (
                `id` VARCHAR(255) PRIMARY KEY,
                `saved_search_id` VARCHAR(255) NOT NULL,
                `job_id` VARCHAR(255) NOT NULL,
                `user_id` VARCHAR(255) NOT NULL,
                `notification_id` VARCHAR(255) NULL,
                `match_score` TINYINT UNSIGNED NOT NULL,
                `match_details` JSON NULL,
                `email_status` ENUM('disabled','pending','preview','sent','failed') NOT NULL DEFAULT 'pending',
                `email_sent_at` TIMESTAMP NULL,
                `email_error` VARCHAR(500) NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY `uq_saved_search_job` (`saved_search_id`, `job_id`),
                KEY `idx_alert_email_queue` (`email_status`, `created_at`),
                KEY `idx_alert_user` (`user_id`, `created_at`),
                CONSTRAINT `fk_alert_saved_search` FOREIGN KEY (`saved_search_id`) REFERENCES `saved_searches` (`id`) ON DELETE CASCADE,
                CONSTRAINT `fk_alert_job` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`) ON DELETE CASCADE,
                CONSTRAINT `fk_alert_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }

    public function down(PDO $db): void
    {
        $db->exec("DROP TABLE IF EXISTS `saved_search_job_alerts`");
    }

    public function getTableName(): string
    {
        return $this->table_name;
    }
}
