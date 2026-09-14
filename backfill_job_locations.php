<?php

declare(strict_types=1);

define("BASE_PATH", __DIR__);
require __DIR__ . "/vendor/autoload.php";

use JobMarket\Facades\Config;
use JobMarket\Infrastructure\Maps\GoongMapsClient;

Dotenv\Dotenv::createImmutable(__DIR__)->safeLoad();

$execute = in_array("--execute", $argv, true);
$limit = 25;
foreach ($argv as $argument) {
    if (preg_match('/^--limit=(\d+)$/', $argument, $matches)) {
        $limit = max(1, min(500, (int)$matches[1]));
    }
}

$config = Config::env();
$db = new PDO(
    "mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8mb4",
    $config["user"],
    $config["password"],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$rows = $db->query(
    "SELECT id, address_text, district_text_legacy, province
     FROM job_locations
     WHERE latitude IS NULL OR longitude IS NULL
     ORDER BY address_text, id"
)->fetchAll(PDO::FETCH_ASSOC);

$groups = [];
foreach ($rows as $row) {
    $key = implode("|", [
        trim((string)$row["address_text"]),
        trim((string)($row["district_text_legacy"] ?? "")),
        trim((string)($row["province"] ?? "")),
    ]);
    $groups[$key]["sample"] = $row;
    $groups[$key]["ids"][] = (string)$row["id"];
}
$groups = array_slice($groups, 0, $limit, true);

if ($groups === []) {
    echo "Không có địa điểm nào cần chuẩn hóa." . PHP_EOL;
    exit(0);
}

echo ($execute ? "EXECUTE" : "DRY-RUN") . ": " . count($groups) . " địa chỉ duy nhất, "
    . array_sum(array_map(fn(array $group): int => count($group["ids"]), $groups)) . " bản ghi." . PHP_EOL;

if (!$execute) {
    echo "Chạy lại với --execute để gọi Goong và cập nhật dữ liệu." . PHP_EOL;
    exit(0);
}

$client = new GoongMapsClient();
$resolvedGroups = [];
$failures = [];

foreach ($groups as $key => $group) {
    $sample = $group["sample"];
    $parts = [];
    foreach (["address_text", "district_text_legacy", "province"] as $field) {
        $part = trim((string)($sample[$field] ?? ""));
        if ($part !== "" && !in_array(mb_strtolower($part), array_map('mb_strtolower', $parts), true)) {
            $parts[] = $part;
        }
    }
    $query = implode(", ", $parts);

    try {
        $results = $client->geocode($query);
        $match = null;
        foreach ($results as $candidate) {
            if (is_numeric($candidate["latitude"] ?? null) && is_numeric($candidate["longitude"] ?? null)) {
                $match = $candidate;
                break;
            }
        }
        if ($match === null) {
            $failures[] = $query . " (không có tọa độ)";
            continue;
        }
        $resolvedGroups[] = ["group" => $group, "match" => $match];
    } catch (Throwable $exception) {
        $failures[] = $query . " (" . $exception->getMessage() . ")";
    }
}

$updated = 0;
$db->beginTransaction();
try {
    $update = $db->prepare(
        "UPDATE job_locations SET
            province = COALESCE(?, province),
            commune = COALESCE(?, commune),
            district_text_legacy = COALESCE(?, district_text_legacy),
            latitude = ?, longitude = ?, provider = 'goong',
            provider_place_id = ?, geocode_status = 'verified'
         WHERE id = ? AND (latitude IS NULL OR longitude IS NULL)"
    );
    foreach ($resolvedGroups as $resolved) {
        $match = $resolved["match"];
        foreach ($resolved["group"]["ids"] as $id) {
            $update->execute([
                $match["province"] ?? null,
                $match["commune"] ?? null,
                $match["district_text_legacy"] ?? null,
                $match["latitude"],
                $match["longitude"],
                !empty($match["place_id"]) ? $match["place_id"] : null,
                $id,
            ]);
            $updated += $update->rowCount();
        }
    }
    $db->commit();
} catch (Throwable $exception) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    throw $exception;
}

echo "Đã chuẩn hóa {$updated} bản ghi từ " . count($resolvedGroups) . " địa chỉ duy nhất." . PHP_EOL;
if ($failures !== []) {
    echo "Chưa xử lý được " . count($failures) . " địa chỉ:" . PHP_EOL;
    foreach ($failures as $failure) {
        echo "- {$failure}" . PHP_EOL;
    }
}

