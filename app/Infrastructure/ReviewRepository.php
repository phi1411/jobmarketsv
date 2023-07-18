<?php

namespace JobMarket\Infrastructure;

use JobMarket\Domain\Review\Review;
use JobMarket\Domain\Review\ReviewRepositoryInterface;
use JobMarket\Facades\Config;
use PDO;

class ReviewRepository implements ReviewRepositoryInterface
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
            "SELECT * FROM reviews"
        );
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function create(Review $review): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO reviews (id, company_id, user_id, rating, comment) VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $review->getId(),
            $review->getCompanyId(),
            $review->getUserId(),
            $review->getRating(),
            $review->getComment()
        ]);
    }
    public function getById(string $id): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM reviews WHERE id = ?"
        );
        $stmt->execute([$id]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    public function update(Review $review): void
    {
        $stmt = $this->db->prepare(
            "UPDATE reviews SET company_id = ?, user_id = ?, rating = ?, comment = ? WHERE id = ?"
        );
        $stmt->execute([
            $review->getCompanyId(),
            $review->getUserId(),
            $review->getRating(),
            $review->getComment(),
            $review->getId()
        ]);
    }
    public function delete(string $id): void
    {
        $stmt = $this->db->prepare(
            "DELETE FROM reviews WHERE id = ?"
        );
        $stmt->execute([$id]);
    }
}
