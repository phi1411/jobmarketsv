<?php

namespace JobMarket\Migrations;

class MessageMigration extends Migration
{
    private string $table_name = "messages";
    public function __construct()
    {
        parent::__construct();
    }

    public function create(): void
    {
        $stmt = $this->getDB()->prepare(
            "CREATE TABLE IF NOT EXISTS `" .  $this->getTableName() . "` (
                id INT PRIMARY KEY AUTO_INCREMENT,
                conversation_id INT NOT NULL,
                sender_id INT NOT NULL,
                content TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
                FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE
              );"
        );

        $stmt->execute();
    }

    public function getTableName(): string
    {
        return $this->table_name;
    }
}