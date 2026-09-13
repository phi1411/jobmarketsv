<?php

namespace JobMarket\Migrations;

use PDO;

class JobLocationMigration extends Migration
{
    private string $table_name = "job_locations";

    public function create(): void
    {
        $db = $this->getDB();

        $db->exec(
            "CREATE TABLE IF NOT EXISTS `job_locations` (
                `id` VARCHAR(64) PRIMARY KEY,
                `job_id` VARCHAR(255) NOT NULL,
                `branch_name` VARCHAR(150) NULL,
                `address_text` VARCHAR(500) NOT NULL,
                `province` VARCHAR(150) NULL,
                `province_code` VARCHAR(30) NULL,
                `commune` VARCHAR(150) NULL,
                `commune_code` VARCHAR(30) NULL,
                `district_text_legacy` VARCHAR(150) NULL,
                `latitude` DECIMAL(10,7) NULL,
                `longitude` DECIMAL(10,7) NULL,
                `provider` VARCHAR(30) NOT NULL DEFAULT 'manual',
                `provider_place_id` VARCHAR(768) NULL,
                `is_primary` TINYINT(1) NOT NULL DEFAULT 0,
                `geocode_status` VARCHAR(30) NOT NULL DEFAULT 'manual',
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                CONSTRAINT `fk_job_locations_job` FOREIGN KEY (`job_id`) REFERENCES `jobs`(`id`) ON DELETE CASCADE,
                INDEX `idx_job_locations_job` (`job_id`),
                INDEX `idx_job_locations_region` (`province`, `commune`),
                INDEX `idx_job_locations_coordinates` (`latitude`, `longitude`),
                INDEX `idx_job_locations_provider_place` (`provider`, `provider_place_id`(191))
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;"
        );

        $db->exec(
            "CREATE TABLE IF NOT EXISTS `student_preferred_locations` (
                `id` VARCHAR(64) PRIMARY KEY,
                `user_id` VARCHAR(255) NOT NULL,
                `address_text` VARCHAR(500) NOT NULL,
                `province` VARCHAR(150) NULL,
                `province_code` VARCHAR(30) NULL,
                `commune` VARCHAR(150) NULL,
                `commune_code` VARCHAR(30) NULL,
                `district_text_legacy` VARCHAR(150) NULL,
                `latitude` DECIMAL(10,7) NULL,
                `longitude` DECIMAL(10,7) NULL,
                `provider` VARCHAR(30) NOT NULL DEFAULT 'goong',
                `provider_place_id` VARCHAR(768) NULL,
                `preferred_radius_km` TINYINT UNSIGNED NOT NULL DEFAULT 10,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                CONSTRAINT `fk_student_preferred_locations_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
                INDEX `idx_student_preferred_locations_user` (`user_id`),
                INDEX `idx_student_preferred_locations_region` (`province`, `commune`),
                INDEX `idx_student_preferred_coordinates` (`latitude`, `longitude`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;"
        );

        $hasColumn = function (string $table, string $column) use ($db): bool {
            $stmt = $db->prepare(
                "SELECT COUNT(*) FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?"
            );
            $stmt->execute([$table, $column]);
            return (bool)$stmt->fetchColumn();
        };

        $locationColumns = [
            "official_code" => "VARCHAR(30) NULL AFTER `name`",
            "administrative_type" => "VARCHAR(30) NULL AFTER `official_code`",
            "parent_id" => "VARCHAR(255) NULL AFTER `administrative_type`",
            "aliases_json" => "JSON NULL AFTER `parent_id`",
            "latitude" => "DECIMAL(10,7) NULL AFTER `aliases_json`",
            "longitude" => "DECIMAL(10,7) NULL AFTER `latitude`",
            "is_active" => "TINYINT(1) NOT NULL DEFAULT 1 AFTER `longitude`",
        ];
        foreach ($locationColumns as $name => $definition) {
            if (!$hasColumn("locations", $name)) {
                $db->exec("ALTER TABLE `locations` ADD COLUMN `{$name}` {$definition}");
            }
        }

        if (!$hasColumn("student_profiles", "preferred_work_modes")) {
            $db->exec("ALTER TABLE `student_profiles` ADD COLUMN `preferred_work_modes` JSON NULL AFTER `preferred_locations`");
        }
        if (!$hasColumn("student_profiles", "max_commute_km")) {
            $db->exec("ALTER TABLE `student_profiles` ADD COLUMN `max_commute_km` TINYINT UNSIGNED NULL AFTER `preferred_work_modes`");
        }
        if (!$hasColumn("student_profiles", "willing_to_relocate")) {
            $db->exec("ALTER TABLE `student_profiles` ADD COLUMN `willing_to_relocate` TINYINT(1) NOT NULL DEFAULT 0 AFTER `max_commute_km`");
        }

        // Preserve old job data as one non-geocoded work location. It can be verified later via Goong.
        $db->exec(
            "INSERT INTO `job_locations`
                (`id`, `job_id`, `address_text`, `province`, `district_text_legacy`, `provider`, `is_primary`, `geocode_status`)
             SELECT CONCAT('jl-legacy-', LEFT(SHA2(j.id, 256), 40)), j.id,
                    COALESCE(NULLIF(j.address, ''), NULLIF(j.location, ''), CONCAT_WS(', ', j.district, j.city)),
                    NULLIF(j.city, ''), NULLIF(j.district, ''), 'legacy', 1, 'legacy_pending'
             FROM `jobs` j
             WHERE NOT EXISTS (SELECT 1 FROM `job_locations` jl WHERE jl.job_id = j.id)
               AND COALESCE(NULLIF(j.address, ''), NULLIF(j.location, ''), NULLIF(j.district, ''), NULLIF(j.city, '')) IS NOT NULL"
        );
    }

    public function down(PDO $db): void
    {
        $db->exec("DROP TABLE IF EXISTS `student_preferred_locations`");
        $db->exec("DROP TABLE IF EXISTS `job_locations`");
    }

    public function getTableName(): string
    {
        return $this->table_name;
    }
}
