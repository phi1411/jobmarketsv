<?php

namespace JobMarket\Migrations;

class ApplicationMigration extends Migration
{
    private string $table_name = "applications";
    public function __construct()
    {
        parent::__construct();
    }

    public function create(): void
    {
        $stmt = $this->getDB()->prepare(
            "CREATE TABLE IF NOT EXISTS `" .  $this->getTableName() . "` (
                id VARCHAR(255) PRIMARY KEY,
                job_id VARCHAR(255) NOT NULL,
                developer_id VARCHAR(255) NOT NULL,
                cover_letter TEXT,
                resume VARCHAR(255),
                status ENUM('pending', 'reviewed', 'accepted', 'rejected') NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
                FOREIGN KEY (developer_id) REFERENCES users(id) ON DELETE CASCADE
              );"
        );

        $stmt->execute();
    }

    public function getTableName(): string
    {
        return $this->table_name;
    }
}