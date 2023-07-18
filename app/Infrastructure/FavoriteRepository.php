<?php

namespace JobMarket\Infrastructure;

use JobMarket\Domain\Favorite\Favorite;
use JobMarket\Domain\Favorite\FavoriteRepositoryInterface;
use JobMarket\Facades\Config;
use PDO;

class FavoriteRepository implements FavoriteRepositoryInterface
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
    public function getAll(string $user_id): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM favorites WHERE user_id = ?"
        );
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function create(Favorite $favorite): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO favorites (id, user_id, job_id) VALUES (?, ?, ?)"
        );
        $stmt->execute([
            $favorite->getId(),
            $favorite->getUserId(),
            $favorite->getJobId()
        ]);
    }
    public function delete(string $id): void
    {
        $stmt = $this->db->prepare(
            "DELETE FROM favorites WHERE id = ?"
        );
        $stmt->execute([$id]);
    }
}
