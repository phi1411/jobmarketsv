<?php

namespace JobMarket\Migrations;

use PDO;

class StudentCvUploadMigration extends Migration
{
    private string $table_name = "student_cv_upload";

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

        // Add private active CV metadata columns to student_profiles
        if (!$hasColumn("student_profiles", "cv_storage_path")) {
            $db->exec("ALTER TABLE `student_profiles` ADD COLUMN `cv_storage_path` VARCHAR(255) NULL AFTER `cv_url`");
        }
        if (!$hasColumn("student_profiles", "cv_original_name")) {
            $db->exec("ALTER TABLE `student_profiles` ADD COLUMN `cv_original_name` VARCHAR(255) NULL AFTER `cv_storage_path`");
        }
        if (!$hasColumn("student_profiles", "cv_mime_type")) {
            $db->exec("ALTER TABLE `student_profiles` ADD COLUMN `cv_mime_type` VARCHAR(50) NULL AFTER `cv_original_name`");
        }
        if (!$hasColumn("student_profiles", "cv_file_size")) {
            $db->exec("ALTER TABLE `student_profiles` ADD COLUMN `cv_file_size` INT UNSIGNED NULL AFTER `cv_mime_type`");
        }
        if (!$hasColumn("student_profiles", "cv_uploaded_at")) {
            $db->exec("ALTER TABLE `student_profiles` ADD COLUMN `cv_uploaded_at` TIMESTAMP NULL AFTER `cv_file_size`");
        }
    }

    public function down(PDO $db): void
    {
        $config = \JobMarket\Facades\Config::env();
        if (($config["env"] ?? "") === "production") {
            echo "  [WARNING] Destructive rollback is disabled in production environment to prevent data loss." . PHP_EOL;
            return;
        }

        $hasColumn = function(string $table, string $column) use ($db): bool {
            $stmt = $db->prepare(
                "SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?"
            );
            $stmt->execute([$table, $column]);
            return (bool)$stmt->fetchColumn();
        };

        $columns = [
            "cv_uploaded_at",
            "cv_file_size",
            "cv_mime_type",
            "cv_original_name",
            "cv_storage_path"
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
