<?php

namespace JobMarket\Migrations;

use PDO;

class PasswordSecurityMigration extends Migration
{
    private string $table_name = "password_change_challenges";

    public function create(): void
    {
        $db = $this->getDB();
        $column = $db->prepare(
            "SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'password_set_at'"
        );
        $column->execute();
        if (!(bool)$column->fetchColumn()) {
            $db->exec("ALTER TABLE `users` ADD COLUMN `password_set_at` TIMESTAMP NULL AFTER `password`");
        }

        // Existing local accounts already have a real password. OAuth-only accounts keep NULL.
        $db->exec(
            "UPDATE `users` u
             LEFT JOIN `oauth_identities` oi ON oi.user_id = u.id AND oi.provider = 'google'
             SET u.password_set_at = COALESCE(u.updated_at, u.created_at, CURRENT_TIMESTAMP)
             WHERE u.password_set_at IS NULL AND oi.user_id IS NULL"
        );

        $db->exec(
            "CREATE TABLE IF NOT EXISTS `password_change_challenges` (
                `id` VARCHAR(64) PRIMARY KEY,
                `user_id` VARCHAR(255) NOT NULL,
                `code_hash` VARCHAR(255) NOT NULL,
                `attempts` TINYINT UNSIGNED NOT NULL DEFAULT 0,
                `expires_at` TIMESTAMP NOT NULL,
                `verified_at` TIMESTAMP NULL,
                `consumed_at` TIMESTAMP NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT `fk_password_challenge_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
                INDEX `idx_password_challenge_user_created` (`user_id`, `created_at`),
                INDEX `idx_password_challenge_expiry` (`expires_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;"
        );
    }

    public function down(?PDO $db = null): void
    {
        $database = $db ?? $this->getDB();
        $database->exec("DROP TABLE IF EXISTS `password_change_challenges`");
        $database->exec("ALTER TABLE `users` DROP COLUMN IF EXISTS `password_set_at`");
    }

    public function getTableName(): string
    {
        return $this->table_name;
    }
}
