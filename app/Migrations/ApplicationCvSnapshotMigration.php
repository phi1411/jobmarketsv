<?php

namespace JobMarket\Migrations;

use PDO;

class ApplicationCvSnapshotMigration extends Migration
{
    private string $table_name = "application_cv_snapshot";

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

        // Add immutable snapshot CV reference columns to applications
        if (!$hasColumn("applications", "cv_storage_path")) {
            $db->exec("ALTER TABLE `applications` ADD COLUMN `cv_storage_path` VARCHAR(255) NULL AFTER `resume`");
        }
        if (!$hasColumn("applications", "cv_original_name")) {
            $db->exec("ALTER TABLE `applications` ADD COLUMN `cv_original_name` VARCHAR(255) NULL AFTER `cv_storage_path`");
        }
        if (!$hasColumn("applications", "cv_file_size")) {
            $db->exec("ALTER TABLE `applications` ADD COLUMN `cv_file_size` INT UNSIGNED NULL AFTER `cv_original_name`");
        }
        if (!$hasColumn("applications", "cv_mime_type")) {
            $db->exec("ALTER TABLE `applications` ADD COLUMN `cv_mime_type` VARCHAR(50) NULL AFTER `cv_file_size`");
        }

        // Add performance index for active CV deletion snapshot lookups
        if (!$hasIndex("applications", "idx_applications_cv_storage_path")) {
            $db->exec("CREATE INDEX `idx_applications_cv_storage_path` ON `applications` (`cv_storage_path`)");
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

        $hasIndex = function(string $table, string $indexName) use ($db): bool {
            $stmt = $db->prepare(
                "SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?"
            );
            $stmt->execute([$table, $indexName]);
            return (bool)$stmt->fetchColumn();
        };

        if ($hasIndex("applications", "idx_applications_cv_storage_path")) {
            $db->exec("DROP INDEX `idx_applications_cv_storage_path` ON `applications`");
        }

        $columns = [
            "cv_mime_type",
            "cv_file_size",
            "cv_original_name",
            "cv_storage_path"
        ];

        foreach ($columns as $column) {
            if ($hasColumn("applications", $column)) {
                $db->exec("ALTER TABLE `applications` DROP COLUMN `{$column}`");
            }
        }
    }

    public function getTableName(): string
    {
        return $this->table_name;
    }
}