<?php

namespace JobMarket\Infrastructure;

use JobMarket\Domain\Location\Location;
use JobMarket\Domain\Location\LocationRepositoryInterface;
use JobMarket\Facades\Config;
use PDO;

class LocationRepository implements LocationRepositoryInterface
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
            "SELECT * FROM locations"
        );
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function create(Location $location): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO locations (id, name) VALUES (?, ?)"
        );
        $stmt->execute([
            $location->getId(),
            $location->getName()
        ]);
    }
    public function getById(string $id): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM locations WHERE id = ?"
        );
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    public function update(Location $location): void
    {
        $stmt = $this->db->prepare(
            "UPDATE locations SET name = ? WHERE id = ?"
        );
        $stmt->execute([
            $location->getName(),
            $location->getId()
        ]);
    }
    public function delete(string $id): void
    {
        $stmt = $this->db->prepare(
            "DELETE FROM locations WHERE id = ?"
        );
        $stmt->execute([$id]);
    }
}
