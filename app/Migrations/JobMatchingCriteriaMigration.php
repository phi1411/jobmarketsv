<?php

namespace JobMarket\Migrations;

use PDO;

class JobMatchingCriteriaMigration extends Migration
{
    private string $table_name = "job_matching_criteria";

    public function create(): void
    {
        $db = $this->getDB();
        $hasColumn = function (string $column) use ($db): bool {
            $stmt = $db->prepare(
                "SELECT COUNT(*) FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'jobs' AND COLUMN_NAME = ?"
            );
            $stmt->execute([$column]);
            return (bool)$stmt->fetchColumn();
        };

        if (!$hasColumn("minimum_age")) {
            $db->exec("ALTER TABLE `jobs` ADD COLUMN `minimum_age` TINYINT UNSIGNED NULL AFTER `required_skills`");
        }
        if (!$hasColumn("maximum_age")) {
            $db->exec("ALTER TABLE `jobs` ADD COLUMN `maximum_age` TINYINT UNSIGNED NULL AFTER `minimum_age`");
        }

        // Tin mới có thể không ràng buộc ca; dữ liệu tin cũ được giữ nguyên.
        $db->exec(
            "ALTER TABLE `jobs` MODIFY COLUMN `shift_type`
             ENUM('morning','afternoon','evening','night','rotating','weekend','flexible') NULL DEFAULT NULL"
        );
    }

    public function down(PDO $db): void
    {
        $db->exec("UPDATE `jobs` SET `shift_type` = 'morning' WHERE `shift_type` IS NULL");
        $db->exec(
            "ALTER TABLE `jobs` MODIFY COLUMN `shift_type`
             ENUM('morning','afternoon','evening','night','rotating','weekend','flexible') NOT NULL DEFAULT 'morning'"
        );
        if ($this->columnExists($db, "maximum_age")) {
            $db->exec("ALTER TABLE `jobs` DROP COLUMN `maximum_age`");
        }
        if ($this->columnExists($db, "minimum_age")) {
            $db->exec("ALTER TABLE `jobs` DROP COLUMN `minimum_age`");
        }
    }

    private function columnExists(PDO $db, string $column): bool
    {
        $stmt = $db->prepare(
            "SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'jobs' AND COLUMN_NAME = ?"
        );
        $stmt->execute([$column]);
        return (bool)$stmt->fetchColumn();
    }

    public function getTableName(): string
    {
        return $this->table_name;
    }
}
