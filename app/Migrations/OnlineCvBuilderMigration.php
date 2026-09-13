<?php

namespace JobMarket\Migrations;

use PDO;

class OnlineCvBuilderMigration extends Migration
{
    private string $table_name = "online_cvs";

    public function create(): void
    {
        $this->getDB()->exec(
            "CREATE TABLE IF NOT EXISTS `online_cvs` (
                `id` VARCHAR(64) PRIMARY KEY,
                `user_id` VARCHAR(255) NOT NULL,
                `title` VARCHAR(120) NOT NULL,
                `template_key` VARCHAR(50) NOT NULL DEFAULT 'student-simple',
                `language` VARCHAR(5) NOT NULL DEFAULT 'vi',
                `content_json` JSON NOT NULL,
                `style_json` JSON NOT NULL,
                `section_order_json` JSON NOT NULL,
                `hidden_sections_json` JSON NOT NULL,
                `completion_percent` TINYINT UNSIGNED NOT NULL DEFAULT 0,
                `is_primary` TINYINT(1) NOT NULL DEFAULT 0,
                `is_public` TINYINT(1) NOT NULL DEFAULT 0,
                `public_slug` VARCHAR(64) NOT NULL,
                `version` INT UNSIGNED NOT NULL DEFAULT 1,
                `last_exported_at` TIMESTAMP NULL,
                `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                `deleted_at` TIMESTAMP NULL,
                UNIQUE KEY `uq_online_cvs_public_slug` (`public_slug`),
                KEY `idx_online_cvs_owner` (`user_id`, `deleted_at`, `updated_at`),
                KEY `idx_online_cvs_public` (`is_public`, `public_slug`, `deleted_at`),
                CONSTRAINT `fk_online_cvs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }

    public function down(PDO $db): void
    {
        $db->exec("DROP TABLE IF EXISTS `online_cvs`");
    }

    public function getTableName(): string
    {
        return $this->table_name;
    }
}
