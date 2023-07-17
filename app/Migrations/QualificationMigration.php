<?php

namespace JobMarket\Migrations;

class QualificationMigration extends Migration
{
    private string $table_name = "qualification";
    public function __construct()
    {
        parent::__construct();
    }

    public function create(): void
    {
        $stmt = $this->getDB()->prepare(
            "CREATE TABLE IF NOT EXISTS `" .  $this->getTableName() . "` (
                id VARCHAR(255) PRIMARY KEY,
                developer_id VARCHAR(255) NOT NULL,
                degree VARCHAR(255),
                institution VARCHAR(255),
                major VARCHAR(255),
                start_date DATE,
                end_date DATE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
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