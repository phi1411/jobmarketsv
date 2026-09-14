<?php

namespace JobMarket\Infrastructure;

use JobMarket\Facades\Config;
use JobMarket\Support\Pagination;
use JobMarket\Support\QueryHelper;
use PDO;

class JobLocationRepository
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        if ($db !== null) {
            $this->db = $db;
            return;
        }
        $config = Config::env();
        $this->db = new PDO(
            "mysql:dbname={$config['dbname']};host={$config['host']};charset=utf8mb4",
            $config["user"],
            $config["password"],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    }

    public function listForJob(string $jobId): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM `job_locations` WHERE `job_id` = ?
             ORDER BY `is_primary` DESC, `created_at` ASC"
        );
        $stmt->execute([$jobId]);
        return $this->castRows($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function find(string $jobId, string $locationId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM `job_locations` WHERE `id` = ? AND `job_id` = ? LIMIT 1");
        $stmt->execute([$locationId, $jobId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $this->castRow($row) : null;
    }

    public function create(string $jobId, array $data): array
    {
        $id = "jl-" . bin2hex(random_bytes(16));
        $this->db->beginTransaction();
        try {
            $countStmt = $this->db->prepare("SELECT COUNT(*) FROM `job_locations` WHERE `job_id` = ?");
            $countStmt->execute([$jobId]);
            $isPrimary = (bool)($data["is_primary"] ?? false) || (int)$countStmt->fetchColumn() === 0;
            if ($isPrimary) {
                $clear = $this->db->prepare("UPDATE `job_locations` SET `is_primary` = 0 WHERE `job_id` = ?");
                $clear->execute([$jobId]);
            }

            $stmt = $this->db->prepare(
                "INSERT INTO `job_locations`
                 (`id`, `job_id`, `branch_name`, `address_text`, `province`, `province_code`, `commune`, `commune_code`,
                  `district_text_legacy`, `latitude`, `longitude`, `provider`, `provider_place_id`, `is_primary`, `geocode_status`)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                $id, $jobId, $data["branch_name"] ?? null, $data["address_text"],
                $data["province"] ?? null, $data["province_code"] ?? null,
                $data["commune"] ?? null, $data["commune_code"] ?? null,
                $data["district_text_legacy"] ?? null, $data["latitude"] ?? null,
                $data["longitude"] ?? null, $data["provider"] ?? "manual",
                $data["provider_place_id"] ?? null, $isPrimary ? 1 : 0,
                $data["geocode_status"] ?? "manual",
            ]);
            $this->syncLegacyJobLocation($jobId);
            $this->db->commit();
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
        return $this->find($jobId, $id) ?? [];
    }

    public function update(string $jobId, string $locationId, array $data): array
    {
        $this->db->beginTransaction();
        try {
            if (!empty($data["is_primary"])) {
                $clear = $this->db->prepare("UPDATE `job_locations` SET `is_primary` = 0 WHERE `job_id` = ?");
                $clear->execute([$jobId]);
            }
            $stmt = $this->db->prepare(
                "UPDATE `job_locations` SET
                    `branch_name` = ?, `address_text` = ?, `province` = ?, `province_code` = ?,
                    `commune` = ?, `commune_code` = ?, `district_text_legacy` = ?,
                    `latitude` = ?, `longitude` = ?, `provider` = ?, `provider_place_id` = ?,
                    `is_primary` = ?, `geocode_status` = ?
                 WHERE `id` = ? AND `job_id` = ?"
            );
            $stmt->execute([
                $data["branch_name"] ?? null, $data["address_text"], $data["province"] ?? null,
                $data["province_code"] ?? null, $data["commune"] ?? null, $data["commune_code"] ?? null,
                $data["district_text_legacy"] ?? null, $data["latitude"] ?? null, $data["longitude"] ?? null,
                $data["provider"] ?? "manual", $data["provider_place_id"] ?? null,
                !empty($data["is_primary"]) ? 1 : 0, $data["geocode_status"] ?? "manual",
                $locationId, $jobId,
            ]);
            $this->ensurePrimary($jobId);
            $this->syncLegacyJobLocation($jobId);
            $this->db->commit();
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
        return $this->find($jobId, $locationId) ?? [];
    }

    public function delete(string $jobId, string $locationId): void
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("DELETE FROM `job_locations` WHERE `id` = ? AND `job_id` = ?");
            $stmt->execute([$locationId, $jobId]);
            $this->ensurePrimary($jobId);
            $this->syncLegacyJobLocation($jobId);
            $this->db->commit();
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function nearby(float $latitude, float $longitude, float $radiusKm, array $filters, Pagination $pagination): array
    {
        $latDelta = $radiusKm / 111.045;
        $cos = max(0.01, cos(deg2rad($latitude)));
        $lngDelta = $radiusKm / (111.045 * $cos);
        $distanceSql = "(6371 * 2 * ASIN(SQRT(POWER(SIN(RADIANS(jl.latitude - ?) / 2), 2) + COS(RADIANS(?)) * COS(RADIANS(jl.latitude)) * POWER(SIN(RADIANS(jl.longitude - ?) / 2), 2))))";

        $where = [
            "jl.latitude BETWEEN ? AND ?",
            "jl.longitude BETWEEN ? AND ?",
            "j.deleted_at IS NULL",
            "j.status = 'published'",
            "(j.application_deadline IS NULL OR j.application_deadline >= CURDATE())",
            "(j.deadline IS NULL OR j.deadline >= CURDATE())",
        ];
        $whereParams = [$latitude - $latDelta, $latitude + $latDelta, $longitude - $lngDelta, $longitude + $lngDelta];
        if (!empty($filters["work_mode"])) {
            $where[] = "j.work_mode = ?";
            $whereParams[] = $filters["work_mode"];
        }
        if (!empty($filters["work_type"])) {
            $where[] = "j.work_type = ?";
            $whereParams[] = str_replace("-", "_", (string)$filters["work_type"]);
        }
        if (!empty($filters["category_id"])) {
            $where[] = "(j.category_id = ? OR j.category = ?)";
            $whereParams[] = $filters["category_id"];
            $whereParams[] = $filters["category_id"];
        }
        if (!empty($filters["shift_type"])) {
            $where[] = "j.shift_type = ?";
            $whereParams[] = $filters["shift_type"];
        }
        if (!empty($filters["location_id"])) {
            $where[] = "j.location_id = ?";
            $whereParams[] = $filters["location_id"];
        }
        if (!empty($filters["salary_min"]) && is_numeric($filters["salary_min"])) {
            $where[] = "(j.salary_max >= ? OR j.salary_min >= ?)";
            $whereParams[] = (int)$filters["salary_min"];
            $whereParams[] = (int)$filters["salary_min"];
        }
        if (!empty($filters["salary_max"]) && is_numeric($filters["salary_max"])) {
            $where[] = "j.salary_min <= ?";
            $whereParams[] = (int)$filters["salary_max"];
        }
        if (!empty($filters["keyword"])) {
            $keyword = "%" . QueryHelper::escapeLike((string)$filters["keyword"]) . "%";
            $where[] = "(j.title LIKE ? OR j.description LIKE ? OR j.requirements LIKE ? OR j.benefits LIKE ?)";
            array_push($whereParams, $keyword, $keyword, $keyword, $keyword);
        }
        $whereSql = implode(" AND ", $where);
        $subquery = "SELECT jl.job_id, MIN({$distanceSql}) AS distance_km
                     FROM `job_locations` jl JOIN `jobs` j ON j.id = jl.job_id
                     WHERE {$whereSql}
                     GROUP BY jl.job_id HAVING distance_km <= ?";
        $params = [$latitude, $latitude, $longitude, ...$whereParams, $radiusKm];

        $count = $this->db->prepare("SELECT COUNT(*) FROM ({$subquery}) nearby_count");
        $count->execute($params);
        $total = (int)$count->fetchColumn();

        $sql = "SELECT j.*, c.name AS company_name, c.logo_url AS company_logo,
                       c.verification_status, cat.name AS category_name, nearby.distance_km
                FROM ({$subquery}) nearby
                JOIN `jobs` j ON j.id = nearby.job_id
                LEFT JOIN `companies` c ON c.id = j.company_id
                LEFT JOIN `categories` cat ON (cat.id = j.category_id OR cat.id = j.category)
                ORDER BY nearby.distance_km ASC, j.created_at DESC
                LIMIT {$pagination->getLimit()} OFFSET {$pagination->getOffset()}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($items as &$item) {
            $item["distance_km"] = round((float)$item["distance_km"], 2);
            $locations = $this->listForJob((string)$item["id"]);
            $item["work_locations"] = $locations;
            $item["nearest_location"] = $this->nearestLocation($locations, $latitude, $longitude);
        }

        return ["items" => $items, "total" => $total];
    }

    public function replaceStudentPreferences(string $userId, array $locations): array
    {
        $this->db->beginTransaction();
        try {
            $delete = $this->db->prepare("DELETE FROM `student_preferred_locations` WHERE `user_id` = ?");
            $delete->execute([$userId]);
            $insert = $this->db->prepare(
                "INSERT INTO `student_preferred_locations`
                 (`id`, `user_id`, `address_text`, `province`, `province_code`, `commune`, `commune_code`,
                  `district_text_legacy`, `latitude`, `longitude`, `provider`, `provider_place_id`, `preferred_radius_km`)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            foreach ($locations as $location) {
                $insert->execute([
                    "spl-" . bin2hex(random_bytes(16)), $userId, $location["address_text"],
                    $location["province"] ?? null, $location["province_code"] ?? null,
                    $location["commune"] ?? null, $location["commune_code"] ?? null,
                    $location["district_text_legacy"] ?? null, $location["latitude"] ?? null,
                    $location["longitude"] ?? null, $location["provider"] ?? "goong",
                    $location["provider_place_id"] ?? null, $location["preferred_radius_km"] ?? 10,
                ]);
            }
            $this->db->commit();
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
        return $this->studentPreferences($userId);
    }

    public function studentPreferences(string $userId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM `student_preferred_locations` WHERE `user_id` = ? ORDER BY `created_at`");
        $stmt->execute([$userId]);
        return $this->castRows($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    private function ensurePrimary(string $jobId): void
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM `job_locations` WHERE `job_id` = ? AND `is_primary` = 1");
        $stmt->execute([$jobId]);
        if ((int)$stmt->fetchColumn() > 0) {
            return;
        }
        $update = $this->db->prepare(
            "UPDATE `job_locations` SET `is_primary` = 1
             WHERE `id` = (SELECT id FROM (SELECT id FROM `job_locations` WHERE `job_id` = ? ORDER BY created_at LIMIT 1) first_location)"
        );
        $update->execute([$jobId]);
    }

    private function syncLegacyJobLocation(string $jobId): void
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM `job_locations` WHERE `job_id` = ? ORDER BY `is_primary` DESC, `created_at` ASC LIMIT 1"
        );
        $stmt->execute([$jobId]);
        $primary = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$primary) {
            return;
        }
        $display = $primary["commune"] ?: ($primary["district_text_legacy"] ?: ($primary["province"] ?: $primary["address_text"]));
        $update = $this->db->prepare(
            "UPDATE `jobs` SET `address` = ?, `location` = ?, `city` = ?, `district` = ?, `updated_at` = NOW() WHERE `id` = ?"
        );
        $update->execute([
            $primary["address_text"],
            $display,
            $primary["province"],
            $primary["commune"] ?: $primary["district_text_legacy"],
            $jobId,
        ]);
    }

    private function nearestLocation(array $locations, float $latitude, float $longitude): ?array
    {
        $nearest = null;
        $minimum = INF;
        foreach ($locations as $location) {
            if ($location["latitude"] === null || $location["longitude"] === null) {
                continue;
            }
            $distance = self::haversine($latitude, $longitude, (float)$location["latitude"], (float)$location["longitude"]);
            if ($distance < $minimum) {
                $minimum = $distance;
                $nearest = $location;
                $nearest["distance_km"] = round($distance, 2);
            }
        }
        return $nearest;
    }

    public static function haversine(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $latDelta = deg2rad($lat2 - $lat1);
        $lngDelta = deg2rad($lng2 - $lng1);
        $a = sin($latDelta / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($lngDelta / 2) ** 2;
        return 6371 * 2 * asin(min(1, sqrt($a)));
    }

    private function castRows(array $rows): array
    {
        return array_map(fn(array $row): array => $this->castRow($row), $rows);
    }

    private function castRow(array $row): array
    {
        $row["latitude"] = isset($row["latitude"]) ? (float)$row["latitude"] : null;
        $row["longitude"] = isset($row["longitude"]) ? (float)$row["longitude"] : null;
        if (array_key_exists("is_primary", $row)) {
            $row["is_primary"] = (bool)$row["is_primary"];
        }
        if (array_key_exists("preferred_radius_km", $row)) {
            $row["preferred_radius_km"] = (int)$row["preferred_radius_km"];
        }
        return $row;
    }
}
