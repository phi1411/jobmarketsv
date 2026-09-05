<?php

namespace JobMarket\Migrations;

use PDO;

class StudentProfileEnhancementMigration extends Migration
{
    private string $table_name = "student_profiles_enhancement";

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

        // 1. Add new columns to student_profiles
        if (!$hasColumn("student_profiles", "full_name")) {
            $db->exec("ALTER TABLE `student_profiles` ADD COLUMN `full_name` VARCHAR(150) NULL AFTER `user_id`");
        }
        if (!$hasColumn("student_profiles", "date_of_birth")) {
            $db->exec("ALTER TABLE `student_profiles` ADD COLUMN `date_of_birth` DATE NULL AFTER `phone`");
        }
        if (!$hasColumn("student_profiles", "gender")) {
            $db->exec("ALTER TABLE `student_profiles` ADD COLUMN `gender` ENUM('male', 'female', 'other') NULL AFTER `date_of_birth`");
        }
        if (!$hasColumn("student_profiles", "location_id")) {
            $db->exec("ALTER TABLE `student_profiles` ADD COLUMN `location_id` VARCHAR(255) NULL AFTER `bio`");
        }
        if (!$hasColumn("student_profiles", "preferred_locations")) {
            $db->exec("ALTER TABLE `student_profiles` ADD COLUMN `preferred_locations` TEXT NULL AFTER `preferred_location`");
        }
        if (!$hasColumn("student_profiles", "skill_ids")) {
            $db->exec("ALTER TABLE `student_profiles` ADD COLUMN `skill_ids` JSON NULL AFTER `skills`");
        }
        if (!$hasColumn("student_profiles", "work_experience")) {
            $db->exec("ALTER TABLE `student_profiles` ADD COLUMN `work_experience` TEXT NULL AFTER `skill_ids`");
        }
        if (!$hasColumn("student_profiles", "education")) {
            $db->exec("ALTER TABLE `student_profiles` ADD COLUMN `education` TEXT NULL AFTER `work_experience`");
        }
        if (!$hasColumn("student_profiles", "certificates")) {
            $db->exec("ALTER TABLE `student_profiles` ADD COLUMN `certificates` TEXT NULL AFTER `education`");
        }
        if (!$hasColumn("student_profiles", "profile_completion_percent")) {
            $db->exec("ALTER TABLE `student_profiles` ADD COLUMN `profile_completion_percent` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `certificates`");
        }

        // 2. Sync full_name from users table if empty
        $db->exec("UPDATE `student_profiles` sp JOIN `users` u ON sp.user_id = u.id SET sp.full_name = u.name WHERE sp.full_name IS NULL");

        // 3. Add Indexes
        if (!$hasIndex("student_profiles", "idx_student_profiles_location")) {
            $db->exec("CREATE INDEX `idx_student_profiles_location` ON `student_profiles` (`location_id`)");
        }
        if (!$hasIndex("student_profiles", "idx_student_profiles_academic")) {
            $db->exec("CREATE INDEX `idx_student_profiles_academic` ON `student_profiles` (`academic_year`)");
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

        $indexes = [
            "idx_student_profiles_location",
            "idx_student_profiles_academic"
        ];
        foreach ($indexes as $idx) {
            if ($hasIndex("student_profiles", $idx)) {
                try {
                    $db->exec("DROP INDEX `{$idx}` ON `student_profiles`");
                } catch (\PDOException $e) {
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

        $columns = [
            "full_name", "date_of_birth", "gender", "location_id", "preferred_locations",
            "skill_ids", "work_experience", "education", "certificates", "profile_completion_percent"
        ];
        foreach ($columns as $col) {
            if ($hasColumn("student_profiles", $col)) {
                try {
                    $db->exec("ALTER TABLE `student_profiles` DROP COLUMN `{$col}`");
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
