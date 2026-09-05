<?php

namespace JobMarket\Migrations;

use PDO;

class JobSearchFavoritesSavedSearchesMigration extends Migration
{
    private string $table_name = "job_search_favorites_saved_searches";

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

        // 1. Add Unique Key to favorites table
        if (!$hasIndex("favorites", "uq_favorites_user_job")) {
            $db->exec("ALTER TABLE `favorites` ADD UNIQUE KEY `uq_favorites_user_job` (`user_id`, `job_id`)");
        }

        // 2. Add structured columns to saved_searches table
        if (!$hasColumn("saved_searches", "name")) {
            $db->exec("ALTER TABLE `saved_searches` ADD COLUMN `name` VARCHAR(150) NOT NULL DEFAULT 'Tìm kiếm đã lưu' AFTER `user_id`");
        }
        if (!$hasColumn("saved_searches", "keyword")) {
            $db->exec("ALTER TABLE `saved_searches` ADD COLUMN `keyword` VARCHAR(255) NULL AFTER `name`");
        }
        if (!$hasColumn("saved_searches", "category_id")) {
            $db->exec("ALTER TABLE `saved_searches` ADD COLUMN `category_id` VARCHAR(255) NULL AFTER `keyword`");
        }
        if (!$hasColumn("saved_searches", "location_id")) {
            $db->exec("ALTER TABLE `saved_searches` ADD COLUMN `location_id` VARCHAR(255) NULL AFTER `category_id`");
        }
        if (!$hasColumn("saved_searches", "work_type")) {
            $db->exec("ALTER TABLE `saved_searches` ADD COLUMN `work_type` VARCHAR(50) NULL AFTER `location_id`");
        }
        if (!$hasColumn("saved_searches", "work_mode")) {
            $db->exec("ALTER TABLE `saved_searches` ADD COLUMN `work_mode` VARCHAR(50) NULL AFTER `work_type`");
        }
        if (!$hasColumn("saved_searches", "salary_min")) {
            $db->exec("ALTER TABLE `saved_searches` ADD COLUMN `salary_min` INT UNSIGNED NULL AFTER `work_mode`");
        }
        if (!$hasColumn("saved_searches", "salary_max")) {
            $db->exec("ALTER TABLE `saved_searches` ADD COLUMN `salary_max` INT UNSIGNED NULL AFTER `salary_min`");
        }
        if (!$hasColumn("saved_searches", "skill_ids")) {
            $db->exec("ALTER TABLE `saved_searches` ADD COLUMN `skill_ids` JSON NULL AFTER `salary_max`");
        }
        if (!$hasColumn("saved_searches", "shift_type")) {
            $db->exec("ALTER TABLE `saved_searches` ADD COLUMN `shift_type` VARCHAR(50) NULL AFTER `skill_ids`");
        }
        if (!$hasColumn("saved_searches", "notification_enabled")) {
            $db->exec("ALTER TABLE `saved_searches` ADD COLUMN `notification_enabled` TINYINT(1) NOT NULL DEFAULT 1 AFTER `shift_type`");
        }
        if (!$hasColumn("saved_searches", "frequency")) {
            $db->exec("ALTER TABLE `saved_searches` ADD COLUMN `frequency` ENUM('daily', 'weekly') NOT NULL DEFAULT 'daily' AFTER `notification_enabled`");
        }

        // 3. Add Index to saved_searches
        if (!$hasIndex("saved_searches", "idx_saved_searches_user")) {
            $db->exec("CREATE INDEX `idx_saved_searches_user` ON `saved_searches` (`user_id`)");
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

        if ($hasIndex("favorites", "uq_favorites_user_job")) {
            try {
                $db->exec("ALTER TABLE `favorites` DROP INDEX `uq_favorites_user_job`");
            } catch (\PDOException $e) {
            }
        }

        if ($hasIndex("saved_searches", "idx_saved_searches_user")) {
            try {
                $db->exec("ALTER TABLE `saved_searches` DROP INDEX `idx_saved_searches_user`");
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

        $columns = [
            "name", "keyword", "category_id", "location_id", "work_type", "work_mode",
            "salary_min", "salary_max", "skill_ids", "shift_type", "notification_enabled", "frequency"
        ];
        foreach ($columns as $col) {
            if ($hasColumn("saved_searches", $col)) {
                try {
                    $db->exec("ALTER TABLE `saved_searches` DROP COLUMN `{$col}`");
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
