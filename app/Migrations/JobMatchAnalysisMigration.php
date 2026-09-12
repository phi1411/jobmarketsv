<?php

declare(strict_types=1);

namespace JobMarket\Migrations;

use PDO;

class JobMatchAnalysisMigration extends Migration
{
    private string $table_name = "job_match_analyses";
    private ?PDO $customDb = null;

    public function __construct(?PDO $db = null)
    {
        $this->customDb = $db;
        // Eager DB connection is deferred to getDB() so this class can be
        // instantiated, syntax-checked, and inspected safely in offline/test environments.
    }

    public function getDB(): PDO
    {
        if ($this->customDb !== null) {
            return $this->customDb;
        }

        parent::__construct();
        return parent::getDB();
    }

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

        $hasTable = function(string $table) use ($db): bool {
            $stmt = $db->prepare(
                "SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?"
            );
            $stmt->execute([$table]);
            return (bool)$stmt->fetchColumn();
        };

        // 1. Add per-application consent columns to applications table (CV-AI-P0-04)
        if (!$hasColumn("applications", "ai_match_consent")) {
            $db->exec("ALTER TABLE `applications` ADD COLUMN `ai_match_consent` TINYINT(1) NOT NULL DEFAULT 0 AFTER `status`");
        }
        if (!$hasColumn("applications", "ai_match_consented_at")) {
            $db->exec("ALTER TABLE `applications` ADD COLUMN `ai_match_consented_at` TIMESTAMP NULL AFTER `ai_match_consent`");
        }
        if (!$hasColumn("applications", "ai_match_consent_revoked_at")) {
            $db->exec("ALTER TABLE `applications` ADD COLUMN `ai_match_consent_revoked_at` TIMESTAMP NULL AFTER `ai_match_consented_at`");
        }
        if (!$hasColumn("applications", "ai_match_notice_version")) {
            $db->exec("ALTER TABLE `applications` ADD COLUMN `ai_match_notice_version` VARCHAR(32) NULL AFTER `ai_match_consent_revoked_at`");
        }

        // 2. Create job_match_analyses table
        if (!$hasTable("job_match_analyses")) {
            $db->exec(
                "CREATE TABLE `job_match_analyses` (
                    `id` VARCHAR(64) NOT NULL PRIMARY KEY,
                    `application_id` VARCHAR(255) NOT NULL,
                    `candidate_source` ENUM('profile') NOT NULL,
                    `candidate_hash` CHAR(64) NOT NULL,
                    `job_hash` CHAR(64) NOT NULL,
                    `cache_key` CHAR(64) NOT NULL,
                    `matcher_version` VARCHAR(32) NOT NULL,
                    `prompt_version` VARCHAR(32) NOT NULL,
                    `schema_version` VARCHAR(32) NOT NULL,
                    `gemini_model` VARCHAR(100) NULL,
                    `status` ENUM('processing', 'completed', 'partial', 'failed', 'revoked') NOT NULL,
                    `consent_snapshot` TINYINT(1) NOT NULL,
                    `candidate_snapshot_json` JSON NULL,
                    `job_snapshot_json` JSON NULL,
                    `candidate_extraction_json` JSON NULL,
                    `job_extraction_json` JSON NULL,
                    `criteria_json` JSON NULL,
                    `summary_json` JSON NULL,
                    `overall_score` DECIMAL(5,2) NULL,
                    `coverage_percent` DECIMAL(5,2) NULL,
                    `classification` ENUM('HIGH_MATCH', 'GOOD_MATCH', 'REVIEW_NEEDED', 'INSUFFICIENT_DATA') NULL,
                    `failure_code` VARCHAR(50) NULL,
                    `duration_ms` INT UNSIGNED NULL,
                    `usage_metadata_json` JSON NULL,
                    `started_at` TIMESTAMP NULL,
                    `completed_at` TIMESTAMP NULL,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY `uniq_job_match_cache_key` (`cache_key`),
                    INDEX `idx_match_app_created` (`application_id`, `created_at`),
                    INDEX `idx_match_cand_hash` (`candidate_hash`, `prompt_version`, `schema_version`, `gemini_model`, `status`),
                    INDEX `idx_match_job_hash` (`job_hash`, `prompt_version`, `schema_version`, `gemini_model`, `status`),
                    INDEX `idx_match_status_updated` (`status`, `updated_at`),
                    CONSTRAINT `fk_job_match_application_id` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;"
            );
        }
    }

    public function down(PDO $db): void
    {
        if (\JobMarket\Facades\Config::isProduction()) {
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

        // Drop analysis table
        $db->exec("DROP TABLE IF EXISTS `job_match_analyses`;");

        // Drop consent columns from applications
        $columns = [
            "ai_match_notice_version",
            "ai_match_consent_revoked_at",
            "ai_match_consented_at",
            "ai_match_consent",
        ];

        foreach ($columns as $col) {
            if ($hasColumn("applications", $col)) {
                $db->exec("ALTER TABLE `applications` DROP COLUMN `{$col}`");
            }
        }
    }

    public function getTableName(): string
    {
        return $this->table_name;
    }
}
