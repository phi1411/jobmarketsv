<?php

namespace JobMarket\Infrastructure;

use JobMarket\Domain\Subscription\Subscription;
use JobMarket\Domain\Subscription\SubscriptionRepositoryInterface;
use JobMarket\Facades\Config;
use PDO;

class SubscriptionRepository implements SubscriptionRepositoryInterface
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
    public function create(Subscription $sub): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO subscriptions (id, user_id, criteria) VALUES (?, ?, ?)"
        );
        $stmt->execute([
            $sub->getId(),
            $sub->getUserId(),
            $sub->getCriteria()
        ]);
    }
    public function delete(string $id): void
    {
        $stmt = $this->db->prepare(
            "DELETE FROM subscriptions WHERE id = ?"
        );
        $stmt->execute([$id]);
    }
}
