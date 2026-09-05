<?php

namespace JobMarket\Migrations;

use PDO;

class OAuthIdentityMigration extends Migration
{
    private string $table_name = "oauth_identities";

    public function __construct()
    {
        parent::__construct();
    }

    public function create(): void
    {
        $stmt = $this->getDB()->prepare(
            "CREATE TABLE IF NOT EXISTS `" . $this->getTableName() . "` (
                `id` VARCHAR(255) PRIMARY KEY,
                `user_id` VARCHAR(255) NOT NULL,
                `provider` VARCHAR(50) NOT NULL,
                `provider_subject` VARCHAR(255) NOT NULL,
                `email_at_link` VARCHAR(255) NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY `unique_provider_subject` (`provider`, `provider_subject`),
                UNIQUE KEY `unique_user_provider` (`user_id`, `provider`),
                CONSTRAINT `fk_oauth_identities_user_id` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;"
        );

        $stmt->execute();
    }

    public function down(?PDO $db = null): void
    {
        $database = $db ?? $this->getDB();
        $database->exec("DROP TABLE IF EXISTS `" . $this->getTableName() . "`;");
    }

    public function getTableName(): string
    {
        return $this->table_name;
    }
}