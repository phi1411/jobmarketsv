<?php

namespace JobMarket\Migrations;

class JobMigration extends Migration
{
    private string $table_name = "jobs";
    public function __construct()
    {
        parent::__construct();
    }

    public function create(): void
    {
        $stmt = $this->getDB()->prepare(
            "CREATE TABLE IF NOT EXISTS `" .  $this->getTableName() . "` (
                id INT PRIMARY KEY AUTO_INCREMENT,
                company_id INT NOT NULL,
                title VARCHAR(255) NOT NULL,
                description TEXT,
                requirements TEXT,
                location VARCHAR(255),
                category VARCHAR(255),
                type ENUM('full-time', 'part-time', 'contract', 'freelance') NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
              );"
        );

        $stmt->execute();
    }

    public function getTableName(): string
    {
        return $this->table_name;
    }
}