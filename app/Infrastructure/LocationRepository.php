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
            "mysql:dbname={$config['dbname']};host={$config['host']};charset=utf8mb4",
            $config["user"],
            $config["password"]
        );
    }
    public function getAll(): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM locations ORDER BY name ASC"
        );
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getHierarchy(): array
    {
        $groups = [];
        foreach ($this->getAll() as $location) {
            $name = trim((string)($location["name"] ?? ""));
            $parts = preg_split('/\s+-\s+/u', $name, 2) ?: [];
            $province = trim((string)($parts[0] ?? $name));
            $area = trim((string)($parts[1] ?? "Toàn khu vực"));
            if ($province === "") {
                continue;
            }

            if (!isset($groups[$province])) {
                $groups[$province] = [
                    "province_name" => $province,
                    "location_ids" => [],
                    "areas" => [],
                ];
            }

            $item = [
                "id" => (string)$location["id"],
                "name" => $name,
                "area_name" => $area,
            ];
            $groups[$province]["location_ids"][] = $item["id"];
            $groups[$province]["areas"][] = $item;
        }

        return array_values($groups);
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
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
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
