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

        // Main nationwide catalogue: 34 provinces/cities and all current wards/communes.
        $administrative = $this->getAdministrativeHierarchy("current");
        foreach ($administrative["provinces"] as $province) {
            $provinceName = $this->shortAdministrativeName((string)($province["name"] ?? ""));
            if ($provinceName === "") continue;

            $key = $this->normalizeAdministrativeName($provinceName);
            $groups[$key] = [
                "province_name" => $provinceName,
                "province_code" => (string)($province["code"] ?? ""),
                "location_ids" => [],
                "areas" => [],
            ];
            foreach (is_array($province["communes"] ?? null) ? $province["communes"] : [] as $commune) {
                $areaName = trim((string)($commune["name"] ?? ""));
                $areaCode = (string)($commune["code"] ?? "");
                if ($areaName === "" || $areaCode === "") continue;
                $item = [
                    "id" => "vn-current-" . $areaCode,
                    "name" => $provinceName . " - " . $areaName,
                    "area_name" => $areaName,
                    "area_code" => $areaCode,
                    "administrative_type" => (string)($commune["type"] ?? ""),
                ];
                $groups[$key]["location_ids"][] = $item["id"];
                $groups[$key]["areas"][] = $item;
            }
        }

        // Keep legacy seeded IDs selectable so old jobs and bookmarked URLs continue working.
        foreach ($this->getAll() as $location) {
            $name = trim((string)($location["name"] ?? ""));
            $parts = preg_split('/\s+-\s+/u', $name, 2) ?: [];
            $province = $this->shortAdministrativeName(trim((string)($parts[0] ?? $name)));
            $area = trim((string)($parts[1] ?? "Toàn khu vực"));
            if ($province === "") {
                continue;
            }

            $key = $this->normalizeAdministrativeName($province);
            if (!isset($groups[$key])) {
                $groups[$key] = [
                    "province_name" => $province,
                    "province_code" => null,
                    "location_ids" => [],
                    "areas" => [],
                ];
            }

            $item = [
                "id" => (string)$location["id"],
                "name" => $name,
                "area_name" => $area,
                "area_code" => null,
                "administrative_type" => "legacy",
            ];
            if (!in_array($item["id"], $groups[$key]["location_ids"], true)) {
                $groups[$key]["location_ids"][] = $item["id"];
                $groups[$key]["areas"][] = $item;
            }
        }

        $result = array_values($groups);
        usort($result, fn(array $a, array $b): int => strnatcasecmp($a["province_name"], $b["province_name"]));
        return $result;
    }

    public function getAdministrativeHierarchy(string $schema = "current"): array
    {
        $allowed = ["current", "legacy"];
        if (!in_array($schema, $allowed, true)) {
            $schema = "current";
        }

        $path = BASE_PATH . "/app/Data/vietnam_administrative_{$schema}.json";
        if (!is_file($path)) {
            throw new \RuntimeException("Dữ liệu đơn vị hành chính chưa được cài đặt.");
        }

        $decoded = json_decode((string)file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($decoded) || !is_array($decoded["provinces"] ?? null)) {
            throw new \RuntimeException("Dữ liệu đơn vị hành chính không hợp lệ.");
        }
        return $decoded;
    }

    private function shortAdministrativeName(string $name): string
    {
        return trim((string)preg_replace('/^(Tỉnh|Thành phố|TP\.?)\s+/u', '', trim($name)));
    }

    private function normalizeAdministrativeName(string $name): string
    {
        $name = $this->shortAdministrativeName($name);
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name);
        return strtolower((string)preg_replace('/[^a-zA-Z0-9]+/', '', $ascii !== false ? $ascii : $name));
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
