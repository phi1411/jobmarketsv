<?php

namespace JobMarket\Infrastructure;

use JobMarket\Domain\SavedSearch\SavedSearch;
use JobMarket\Domain\SavedSearch\SavedSearchRepositoryInterface;
use JobMarket\Facades\Config;
use PDO;

class SavedSearchRepository implements SavedSearchRepositoryInterface
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
            "SELECT * FROM saved_searchs"
        );
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function create(SavedSearch $search): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO saved_searchs (id, user_id, criteria) VALUES (?, ?, ?)"
        );
        $stmt->execute([
            $search->getId(),
            $search->getUserId(),
            $search->getCriteria()
        ]);
    }
    public function delete(string $id): void
    {
        $stmt = $this->db->prepare(
            "DELETE FROM saved_searchs WHERE id = ?"
        );
        $stmt->execute([$id]);
    }
}
