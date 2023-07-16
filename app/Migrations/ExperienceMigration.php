<?php

namespace JobMarket\Migrations;

class ExperienceMigration extends Migration
{
    private string $table_name = "experiences";
    public function __construct()
    {
        parent::__construct();
    }

    public function create(): void
    {
        $stmt = $this->getDB()->prepare(
            "CREATE TABLE IF NOT EXISTS `" .  $this->getTableName() . "` (
                id INT PRIMARY KEY AUTO_INCREMENT,
                developer_id INT NOT NULL,
                company VARCHAR(255),
                position VARCHAR(255),
                start_date DATE,
                end_date DATE,
                description TEXT,
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
