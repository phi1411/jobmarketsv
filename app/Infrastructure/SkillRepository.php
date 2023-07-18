<?php

namespace JobMarket\Infrastructure;

use JobMarket\Domain\Skill\Skill;
use JobMarket\Domain\Skill\SkillRepositoryInterface;
use JobMarket\Facades\Config;
use PDO;

class SkillRepository implements SkillRepositoryInterface
{
    private PDO $db;

    public function __construct()
    {
        $config = Config::env();
        $this->db = new PDO(
            "mysql:dbname={$config['dbname']};host={$config['host']}",
            $config["user"],
            $config["password"]
        );
    }
    public function getAll(): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM skills"
        );
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function create(Skill $skill): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO skills (id, name) VALUES (?, ?)"
        );
        $stmt->execute([
            $skill->getId(),
            $skill->getName()
        ]);
    }
    public function getById(string $id): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM skills WHERE id = ?"
        );
        $stmt->execute([$id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function update(Skill $skill): void
    {
        $stmt = $this->db->prepare(
            "UPDATE skills SET name = ? WHERE id = ?"
        );
        $stmt->execute([
            $skill->getName(),
            $skill->getName()
        ]);
    }
    public function delete(string $id): void
    {
        $stmt = $this->db->prepare(
            "DELETE FROM skills WHERE id = ?"
        );
        $stmt->execute([$id]);
    }
}