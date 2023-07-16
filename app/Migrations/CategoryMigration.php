<?php

namespace JobMarket\Migrations;

class CategoryMigration extends Migration
{
    private string $table_name = "categories";
    public function __construct()
    {
        parent::__construct();
    }

    public function create(): void
    {
        $stmt = $this->getDB()->prepare(
            "CREATE TABLE IF NOT EXISTS `" .  $this->getTableName() . "` (
                id INT PRIMARY KEY AUTO_INCREMENT,
                name VARCHAR(255) NOT NULL UNIQUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
              );"
        );

        $stmt->execute();
    }

    public function getTableName(): string
    {
        return $this->table_name;
    }
}