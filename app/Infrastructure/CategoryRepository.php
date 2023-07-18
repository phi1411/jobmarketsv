<?php

namespace JobMarket\Infrastructure;

use JobMarket\Domain\Category\Category;
use JobMarket\Domain\Category\CategoryRepositoryInterface;
use JobMarket\Facades\Config;
use PDO;

class CategoryRepository implements CategoryRepositoryInterface
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
            "SELECT * FROM categories"
        );
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function create(Category $category): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO categories (id, name) VALUES (?, ?)"
        );
        $stmt->execute([
            $category->getId(),
            $category->getName()
        ]);
    }
    public function getById(string $id): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM categories WHERE id = ?"
        );
        $stmt->execute([$id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function update(Category $category): void
    {
        $stmt = $this->db->prepare(
            "UPDATE categories SET name = ? WHERE id = ?"
        );
        $stmt->execute([
            $category->getName(),
            $category->getId()
        ]);
    }
    public function delete(string $id): void
    {
        $stmt = $this->db->prepare(
            "DELETE FROM categories WHERE id = ?"
        );
        $stmt->execute([$id]);
    }
}
