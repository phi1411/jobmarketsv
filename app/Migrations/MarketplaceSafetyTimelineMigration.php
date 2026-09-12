<?php

namespace JobMarket\Migrations;

use PDO;

class MarketplaceSafetyTimelineMigration extends Migration
{
    private string $table_name = "job_reports, favorite_deadline_reminders, application_status_history";

    public function create(): void
    {
        $db = $this->getDB();

        $db->exec(
            "CREATE TABLE IF NOT EXISTS `job_reports` (
                `id` VARCHAR(255) PRIMARY KEY,
                `user_id` VARCHAR(255) NOT NULL,
                `job_id` VARCHAR(255) NOT NULL,
                `reason` ENUM('scam','salary_mismatch','fee_required','inappropriate','other') NOT NULL,
                `description` VARCHAR(500) NULL,
                `status` ENUM('pending','reviewing','resolved','dismissed') NOT NULL DEFAULT 'pending',
                `admin_note` VARCHAR(1000) NULL,
                `resolution_action` ENUM('none','hide_job','close_job') NOT NULL DEFAULT 'none',
                `handled_by` VARCHAR(255) NULL,
                `handled_at` TIMESTAMP NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY `uq_job_report_user_job` (`user_id`, `job_id`),
                KEY `idx_job_reports_queue` (`status`, `created_at`),
                KEY `idx_job_reports_job` (`job_id`),
                CONSTRAINT `fk_job_reports_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
                CONSTRAINT `fk_job_reports_job` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`) ON DELETE CASCADE,
                CONSTRAINT `fk_job_reports_handler` FOREIGN KEY (`handled_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $db->exec(
            "CREATE TABLE IF NOT EXISTS `favorite_deadline_reminders` (
                `id` VARCHAR(255) PRIMARY KEY,
                `user_id` VARCHAR(255) NOT NULL,
                `job_id` VARCHAR(255) NOT NULL,
                `deadline` DATE NOT NULL,
                `days_remaining` TINYINT UNSIGNED NOT NULL,
                `notification_id` VARCHAR(255) NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY `uq_deadline_reminder` (`user_id`, `job_id`, `deadline`),
                KEY `idx_deadline_reminder_created` (`created_at`),
                CONSTRAINT `fk_deadline_reminder_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
                CONSTRAINT `fk_deadline_reminder_job` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $db->exec(
            "CREATE TABLE IF NOT EXISTS `application_status_history` (
                `id` VARCHAR(255) PRIMARY KEY,
                `application_id` VARCHAR(255) NOT NULL,
                `status` VARCHAR(32) NOT NULL,
                `actor_id` VARCHAR(255) NULL,
                `actor_role` VARCHAR(32) NOT NULL DEFAULT 'system',
                `note` VARCHAR(500) NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                KEY `idx_application_history_timeline` (`application_id`, `created_at`),
                CONSTRAINT `fk_application_history_application` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE,
                CONSTRAINT `fk_application_history_actor` FOREIGN KEY (`actor_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $db->exec(
            "ALTER TABLE `applications` MODIFY COLUMN `status`
             ENUM('pending','viewed','reviewed','shortlisted','interview','rejected','accepted','withdrawn')
             NOT NULL DEFAULT 'pending'"
        );

        $db->exec(
            "INSERT INTO `application_status_history` (`id`, `application_id`, `status`, `actor_role`, `note`, `created_at`)
             SELECT CONCAT('hist-backfill-pending-', a.id), a.id, 'pending', 'system', 'Đã nộp đơn ứng tuyển', COALESCE(a.applied_at, a.created_at, NOW())
             FROM `applications` a
             WHERE NOT EXISTS (
                 SELECT 1 FROM `application_status_history` h WHERE h.application_id = a.id AND h.status = 'pending'
             )"
        );
        $db->exec(
            "INSERT INTO `application_status_history` (`id`, `application_id`, `status`, `actor_role`, `created_at`)
             SELECT CONCAT('hist-backfill-current-', a.id), a.id, a.status, 'system', COALESCE(a.updated_at, a.applied_at, a.created_at, NOW())
             FROM `applications` a
             WHERE a.status <> 'pending' AND NOT EXISTS (
                 SELECT 1 FROM `application_status_history` h WHERE h.application_id = a.id AND h.status = a.status
             )"
        );
    }

    public function down(PDO $db): void
    {
        $db->exec("DROP TABLE IF EXISTS `application_status_history`");
        $db->exec("DROP TABLE IF EXISTS `favorite_deadline_reminders`");
        $db->exec("DROP TABLE IF EXISTS `job_reports`");
        // Không thu hẹp ENUM để tránh làm mất dữ liệu nếu đã có trạng thái interview.
    }

    public function getTableName(): string
    {
        return $this->table_name;
    }
}
