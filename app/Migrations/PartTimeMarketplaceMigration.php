<?php

namespace JobMarket\Migrations;

use PDO;

class PartTimeMarketplaceMigration extends Migration
{
    private string $table_name = "part_time_marketplace_features";

    public function __construct()
    {
        parent::__construct();
    }

    public function create(): void
    {
        $db = $this->getDB();

        // 1. Create student_profiles table
        $db->exec(
            "CREATE TABLE IF NOT EXISTS `student_profiles` (
                `id` VARCHAR(255) PRIMARY KEY,
                `user_id` VARCHAR(255) NOT NULL UNIQUE,
                `phone` VARCHAR(20) NULL,
                `university` VARCHAR(150) NULL,
                `major` VARCHAR(100) NULL,
                `academic_year` TINYINT NULL,
                `bio` TEXT NULL,
                `cv_url` VARCHAR(255) NULL,
                `preferred_location` VARCHAR(100) NULL,
                `available_schedule` JSON NULL,
                `skills` TEXT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;"
        );

        // Helper to check if column exists
        $hasColumn = function(string $table, string $column) use ($db): bool {
            $stmt = $db->prepare(
                "SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?"
            );
            $stmt->execute([$table, $column]);
            return (bool)$stmt->fetchColumn();
        };

        // 2. Add columns and modify role in users table
        $db->exec("ALTER TABLE `users` MODIFY COLUMN `role` ENUM('student', 'developer', 'company', 'employer', 'admin') NOT NULL DEFAULT 'student'");
        $db->exec("ALTER TABLE `users` MODIFY COLUMN `token` TEXT NULL");

        if (!$hasColumn("users", "status")) {
            $db->exec("ALTER TABLE `users` ADD COLUMN `status` ENUM('active', 'banned') NOT NULL DEFAULT 'active' AFTER `role`");
        }

        // 3. Add columns to companies table
        if (!$hasColumn("companies", "contact_person")) {
            $db->exec("ALTER TABLE `companies` ADD COLUMN `contact_person` VARCHAR(100) NULL AFTER `description`");
        }
        if (!$hasColumn("companies", "contact_phone")) {
            $db->exec("ALTER TABLE `companies` ADD COLUMN `contact_phone` VARCHAR(20) NULL AFTER `contact_person`");
        }
        if (!$hasColumn("companies", "address")) {
            $db->exec("ALTER TABLE `companies` ADD COLUMN `address` VARCHAR(255) NULL AFTER `location`");
        }
        if (!$hasColumn("companies", "city")) {
            $db->exec("ALTER TABLE `companies` ADD COLUMN `city` VARCHAR(50) NULL AFTER `address`");
        }
        if (!$hasColumn("companies", "district")) {
            $db->exec("ALTER TABLE `companies` ADD COLUMN `district` VARCHAR(50) NULL AFTER `city`");
        }
        if (!$hasColumn("companies", "logo_url")) {
            $db->exec("ALTER TABLE `companies` ADD COLUMN `logo_url` VARCHAR(255) NULL AFTER `district`");
        }
        if (!$hasColumn("companies", "verification_status")) {
            $db->exec("ALTER TABLE `companies` ADD COLUMN `verification_status` ENUM('pending', 'verified', 'rejected') NOT NULL DEFAULT 'pending' AFTER `website`");
        }

        // 4. Add columns to jobs table
        if (!$hasColumn("jobs", "category_id")) {
            $db->exec("ALTER TABLE `jobs` ADD COLUMN `category_id` VARCHAR(255) NULL AFTER `company_id`");
        }
        if (!$hasColumn("jobs", "salary_type")) {
            $db->exec("ALTER TABLE `jobs` ADD COLUMN `salary_type` ENUM('hourly', 'monthly', 'negotiable') NOT NULL DEFAULT 'hourly' AFTER `type`");
        }
        if (!$hasColumn("jobs", "salary_min")) {
            $db->exec("ALTER TABLE `jobs` ADD COLUMN `salary_min` INT UNSIGNED NULL AFTER `salary_type`");
        }
        if (!$hasColumn("jobs", "salary_max")) {
            $db->exec("ALTER TABLE `jobs` ADD COLUMN `salary_max` INT UNSIGNED NULL AFTER `salary_min`");
        }
        if (!$hasColumn("jobs", "shift_type")) {
            $db->exec("ALTER TABLE `jobs` ADD COLUMN `shift_type` ENUM('morning', 'afternoon', 'evening', 'night', 'rotating', 'weekend') NOT NULL DEFAULT 'morning' AFTER `salary_max`");
        }
        if (!$hasColumn("jobs", "work_format")) {
            $db->exec("ALTER TABLE `jobs` ADD COLUMN `work_format` ENUM('on-site', 'hybrid', 'remote') NOT NULL DEFAULT 'on-site' AFTER `shift_type`");
        }
        if (!$hasColumn("jobs", "city")) {
            $db->exec("ALTER TABLE `jobs` ADD COLUMN `city` VARCHAR(50) NULL AFTER `location`");
        }
        if (!$hasColumn("jobs", "district")) {
            $db->exec("ALTER TABLE `jobs` ADD COLUMN `district` VARCHAR(50) NULL AFTER `city`");
        }
        if (!$hasColumn("jobs", "address")) {
            $db->exec("ALTER TABLE `jobs` ADD COLUMN `address` VARCHAR(255) NULL AFTER `district`");
        }
        if (!$hasColumn("jobs", "status")) {
            $db->exec("ALTER TABLE `jobs` ADD COLUMN `status` ENUM('draft', 'published', 'hidden', 'closed') NOT NULL DEFAULT 'published' AFTER `address`");
        }
        if (!$hasColumn("jobs", "deadline")) {
            $db->exec("ALTER TABLE `jobs` ADD COLUMN `deadline` DATE NULL AFTER `status`");
        }

        // 5. Update applications table status ENUM and employer_note
        if (!$hasColumn("applications", "employer_note")) {
            $db->exec("ALTER TABLE `applications` ADD COLUMN `employer_note` VARCHAR(255) NULL AFTER `status`");
        }

        // 6. Update notifications table columns
        if (!$hasColumn("notifications", "title")) {
            $db->exec("ALTER TABLE `notifications` ADD COLUMN `title` VARCHAR(150) NULL AFTER `user_id`");
        }
        if (!$hasColumn("notifications", "link")) {
            $db->exec("ALTER TABLE `notifications` ADD COLUMN `link` VARCHAR(255) NULL AFTER `message`");
        }
        if (!$hasColumn("notifications", "is_read")) {
            $db->exec("ALTER TABLE `notifications` ADD COLUMN `is_read` TINYINT(1) NOT NULL DEFAULT 0 AFTER `read`");
        }
    }

    public function getTableName(): string
    {
        return $this->table_name;
    }
}
