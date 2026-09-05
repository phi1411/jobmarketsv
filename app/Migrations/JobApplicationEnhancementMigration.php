<?php

namespace JobMarket\Migrations;

use PDO;

class JobApplicationEnhancementMigration extends Migration
{
    private string $table_name = "job_applications_enhancement";

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

        // 1. Modify status column ENUM to support all part-time recruitment stages
        $db->exec("ALTER TABLE `applications` MODIFY COLUMN `status` ENUM('pending', 'viewed', 'reviewed', 'shortlisted', 'rejected', 'accepted', 'withdrawn') NOT NULL DEFAULT 'pending'");

        // 2. Expand employer_note to TEXT
        $db->exec("ALTER TABLE `applications` MODIFY COLUMN `employer_note` TEXT NULL");

        // 3. Add preferred_shift column
        if (!$hasColumn("applications", "preferred_shift")) {
            $db->exec("ALTER TABLE `applications` ADD COLUMN `preferred_shift` ENUM('morning', 'afternoon', 'evening', 'weekend', 'flexible') NULL AFTER `resume`");
        }

        // 4. Add applied_at column
        if (!$hasColumn("applications", "applied_at")) {
            $db->exec("ALTER TABLE `applications` ADD COLUMN `applied_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP AFTER `employer_note`");
        }

        // 5. Add unique constraint (job_id, developer_id) to enforce one-time application
        if (!$hasIndex("applications", "uq_applications_job_developer")) {
            $db->exec("ALTER TABLE `applications` ADD UNIQUE KEY `uq_applications_job_developer` (`job_id`, `developer_id`)");
        }

        // 6. Add performance indexes
        if (!$hasIndex("applications", "idx_applications_job_status")) {
            $db->exec("CREATE INDEX `idx_applications_job_status` ON `applications` (`job_id`, `status`)");
        }
        if (!$hasIndex("applications", "idx_applications_developer_status")) {
            $db->exec("CREATE INDEX `idx_applications_developer_status` ON `applications` (`developer_id`, `status`)");
        }
        if (!$hasIndex("applications", "idx_applications_status")) {
            $db->exec("CREATE INDEX `idx_applications_status` ON `applications` (`status`)");
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
            "uq_applications_job_developer",
            "idx_applications_job_status",
            "idx_applications_developer_status",
            "idx_applications_status"
        ];
        foreach ($indexes as $idx) {
            if ($hasIndex("applications", $idx)) {
                try {
                    $db->exec("ALTER TABLE `applications` DROP INDEX `{$idx}`");
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

        if ($hasColumn("applications", "preferred_shift")) {
            try {
                $db->exec("ALTER TABLE `applications` DROP COLUMN `preferred_shift`");
            } catch (\PDOException $e) {
            }
        }
        if ($hasColumn("applications", "applied_at")) {
            try {
                $db->exec("ALTER TABLE `applications` DROP COLUMN `applied_at`");
            } catch (\PDOException $e) {
            }
        }
    }

    public function getTableName(): string
    {
        return $this->table_name;
    }
}
