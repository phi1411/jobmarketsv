<?php

namespace JobMarket\Migrations;

use PDO;

class JobPostingEnhancementMigration extends Migration
{
    private string $table_name = "jobs_part_time_enhancement";

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

        // 1. Modify existing ENUMs for Part-time student requirements
        $db->exec(
            "ALTER TABLE `jobs` MODIFY COLUMN `status` ENUM('draft', 'pending_approval', 'published', 'rejected', 'closed', 'expired') NOT NULL DEFAULT 'published'"
        );
        $db->exec(
            "ALTER TABLE `jobs` MODIFY COLUMN `salary_type` ENUM('hourly', 'daily', 'monthly', 'negotiable') NOT NULL DEFAULT 'hourly'"
        );
        $db->exec(
            "ALTER TABLE `jobs` MODIFY COLUMN `shift_type` ENUM('morning', 'afternoon', 'evening', 'night', 'rotating', 'weekend', 'flexible') NOT NULL DEFAULT 'morning'"
        );
        $db->exec(
            "ALTER TABLE `jobs` MODIFY COLUMN `type` ENUM('full-time', 'part-time', 'contract', 'freelance', 'part_time', 'internship') NOT NULL DEFAULT 'part-time'"
        );

        // 2. Add new columns
        if (!$hasColumn("jobs", "benefits")) {
            $db->exec("ALTER TABLE `jobs` ADD COLUMN `benefits` TEXT NULL AFTER `requirements`");
        }
        if (!$hasColumn("jobs", "location_id")) {
            $db->exec("ALTER TABLE `jobs` ADD COLUMN `location_id` VARCHAR(255) NULL AFTER `category_id`");
        }
        if (!$hasColumn("jobs", "work_type")) {
            $db->exec("ALTER TABLE `jobs` ADD COLUMN `work_type` ENUM('part_time', 'internship', 'freelance') NOT NULL DEFAULT 'part_time' AFTER `type`");
        }
        if (!$hasColumn("jobs", "work_mode")) {
            $db->exec("ALTER TABLE `jobs` ADD COLUMN `work_mode` ENUM('onsite', 'remote', 'hybrid') NOT NULL DEFAULT 'onsite' AFTER `work_format`");
        }
        if (!$hasColumn("jobs", "currency")) {
            $db->exec("ALTER TABLE `jobs` ADD COLUMN `currency` VARCHAR(10) NOT NULL DEFAULT 'VND' AFTER `salary_max`");
        }
        if (!$hasColumn("jobs", "shift_information")) {
            $db->exec("ALTER TABLE `jobs` ADD COLUMN `shift_information` TEXT NULL AFTER `shift_type`");
        }
        if (!$hasColumn("jobs", "working_schedule")) {
            $db->exec("ALTER TABLE `jobs` ADD COLUMN `working_schedule` TEXT NULL AFTER `shift_information`");
        }
        if (!$hasColumn("jobs", "required_skills")) {
            $db->exec("ALTER TABLE `jobs` ADD COLUMN `required_skills` TEXT NULL AFTER `working_schedule`");
        }
        if (!$hasColumn("jobs", "quantity")) {
            $db->exec("ALTER TABLE `jobs` ADD COLUMN `quantity` INT UNSIGNED NOT NULL DEFAULT 1 AFTER `required_skills`");
        }
        if (!$hasColumn("jobs", "application_deadline")) {
            $db->exec("ALTER TABLE `jobs` ADD COLUMN `application_deadline` DATE NULL AFTER `quantity`");
        }
        if (!$hasColumn("jobs", "rejection_reason")) {
            $db->exec("ALTER TABLE `jobs` ADD COLUMN `rejection_reason` TEXT NULL AFTER `application_deadline`");
        }
        if (!$hasColumn("jobs", "published_at")) {
            $db->exec("ALTER TABLE `jobs` ADD COLUMN `published_at` TIMESTAMP NULL AFTER `rejection_reason`");
        }
        if (!$hasColumn("jobs", "deleted_at")) {
            $db->exec("ALTER TABLE `jobs` ADD COLUMN `deleted_at` TIMESTAMP NULL AFTER `updated_at`");
        }

        // 3. Sync existing data for application_deadline if empty
        $db->exec("UPDATE `jobs` SET `application_deadline` = `deadline` WHERE `application_deadline` IS NULL AND `deadline` IS NOT NULL");
        $db->exec("UPDATE `jobs` SET `published_at` = `created_at` WHERE `published_at` IS NULL AND `status` = 'published'");

        // 4. Add Indexes for high-performance search queries
        if (!$hasIndex("jobs", "idx_jobs_status_deadline")) {
            $db->exec("CREATE INDEX `idx_jobs_status_deadline` ON `jobs` (`status`, `application_deadline`)");
        }
        if (!$hasIndex("jobs", "idx_jobs_company_status")) {
            $db->exec("CREATE INDEX `idx_jobs_company_status` ON `jobs` (`company_id`, `status`)");
        }
        if (!$hasIndex("jobs", "idx_jobs_category")) {
            $db->exec("CREATE INDEX `idx_jobs_category` ON `jobs` (`category_id`)");
        }
        if (!$hasIndex("jobs", "idx_jobs_shift")) {
            $db->exec("CREATE INDEX `idx_jobs_shift` ON `jobs` (`shift_type`)");
        }
        if (!$hasIndex("jobs", "idx_jobs_work_type")) {
            $db->exec("CREATE INDEX `idx_jobs_work_type` ON `jobs` (`work_type`)");
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

        // Drop indexes
        $indexes = [
            "idx_jobs_status_deadline",
            "idx_jobs_company_status",
            "idx_jobs_category",
            "idx_jobs_shift",
            "idx_jobs_work_type"
        ];
        foreach ($indexes as $idx) {
            if ($hasIndex("jobs", $idx)) {
                try {
                    $db->exec("DROP INDEX `{$idx}` ON `jobs`");
                } catch (\PDOException $e) {
                    // Skip if index is needed by an existing foreign key
                }
            }
        }

        $hasColumn = function(string $table, string $column) use ($db): bool {
            $stmt = $db->prepare(
                "SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?"
            );
            $stmt->execute([$table, $column]);
            return (bool)$stmt->fetchColumn();
        };

        // Drop columns
        $columns = [
            "benefits", "location_id", "work_type", "work_mode", "currency",
            "shift_information", "working_schedule", "required_skills",
            "quantity", "application_deadline", "rejection_reason", "published_at", "deleted_at"
        ];
        foreach ($columns as $col) {
            if ($hasColumn("jobs", $col)) {
                try {
                    $db->exec("ALTER TABLE `jobs` DROP COLUMN `{$col}`");
                } catch (\PDOException $e) {
                    // Skip if column has dependencies
                }
            }
        }
    }

    public function getTableName(): string
    {
        return $this->table_name;
    }
}
