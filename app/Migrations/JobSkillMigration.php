<?php

namespace JobMarket\Migrations;

class JobSkillMigration extends Migration
{
    private string $table_name = "job_skills";
    public function __construct()
    {
        parent::__construct();
    }

    public function create(): void
    {
        $stmt = $this->getDB()->prepare(
            "CREATE TABLE IF NOT EXISTS `" .  $this->getTableName() . "` (
                id INT PRIMARY KEY AUTO_INCREMENT,
                job_id INT NOT NULL,
                skill_id INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
                FOREIGN KEY (skill_id) REFERENCES skills(id) ON DELETE CASCADE
              );"
        );

        $stmt->execute();
    }

    public function getTableName(): string
    {
        return $this->table_name;
    }
}