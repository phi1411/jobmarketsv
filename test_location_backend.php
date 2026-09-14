<?php

define("BASE_PATH", __DIR__);
require __DIR__ . "/vendor/autoload.php";

use FastRoute\RouteCollector;
use JobMarket\Facades\Config;
use JobMarket\Infrastructure\JobLocationRepository;
use JobMarket\Infrastructure\Maps\GoongMapsClient;
use JobMarket\Support\Pagination;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

$assert = function (bool $condition, string $message): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
};

$fakeHttp = function (string $url): array {
    if (str_contains($url, "/Place/AutoComplete")) {
        return [
            "status" => 200,
            "body" => json_encode([
                "status" => "OK",
                "predictions" => [[
                    "place_id" => "test-place",
                    "description" => "Bến Nghé, Hồ Chí Minh",
                    "compound" => ["province" => "Hồ Chí Minh", "commune" => "Bến Nghé", "district" => "Quận 1"],
                ]],
            ], JSON_UNESCAPED_UNICODE),
        ];
    }
    return [
        "status" => 200,
        "body" => json_encode([
            "status" => "OK",
            "result" => [
                "place_id" => "test-place",
                "formatted_address" => "Nguyễn Huệ, Bến Nghé, Hồ Chí Minh",
                "compound" => ["province" => "Hồ Chí Minh", "commune" => "Bến Nghé"],
                "geometry" => ["location" => ["lat" => 10.7738, "lng" => 106.7036]],
            ],
        ], JSON_UNESCAPED_UNICODE),
    ];
};

$client = new GoongMapsClient("test-key", "https://example.test", 3, $fakeHttp);
$suggestions = $client->autocomplete("Nguyen Hue", 10.77, 106.70, 5, 20, "session-123");
$assert(count($suggestions) === 1, "Autocomplete normalization failed.");
$assert($suggestions[0]["commune"] === "Bến Nghé", "Commune normalization failed.");
$detail = $client->placeDetail("test-place", "session-123");
$assert($detail["latitude"] === 10.7738 && $detail["longitude"] === 106.7036, "Place coordinates failed.");
$assert(JobLocationRepository::haversine(10.77, 106.70, 10.77, 106.70) === 0.0, "Haversine zero-distance failed.");

$config = Config::env();
$db = new PDO(
    "mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8mb4",
    $config["user"],
    $config["password"],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
foreach (["job_locations", "student_preferred_locations"] as $table) {
    $stmt = $db->prepare("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?");
    $stmt->execute([$table]);
    $assert((int)$stmt->fetchColumn() === 1, "Missing table {$table}.");
}

// Exercise the real nearby SQL without leaving test data behind.
$jobRow = $db->query(
    "SELECT id, title, shift_type, location_id, salary_min, salary_max FROM jobs WHERE status = 'published' AND deleted_at IS NULL
     AND (application_deadline IS NULL OR application_deadline >= CURDATE())
     AND (deadline IS NULL OR deadline >= CURDATE())
     AND shift_type IS NOT NULL AND title IS NOT NULL LIMIT 1"
)->fetch(PDO::FETCH_ASSOC);
$jobId = is_array($jobRow) ? (string)$jobRow["id"] : "";
if ($jobId !== "") {
    $testLocationId = "jl-test-" . bin2hex(random_bytes(8));
    $db->beginTransaction();
    try {
        $insert = $db->prepare(
            "INSERT INTO job_locations
             (id, job_id, address_text, latitude, longitude, provider, is_primary, geocode_status)
             VALUES (?, ?, 'Test location', 10.7769000, 106.7009000, 'manual', 0, 'manual')"
        );
        $insert->execute([$testLocationId, $jobId]);
        $repository = new JobLocationRepository($db);
        $nearby = $repository->nearby(10.7769, 106.7009, 2, [], new Pagination(1, 12));
        $found = array_filter($nearby["items"], fn(array $item): bool => $item["id"] === $jobId);
        $assert($found !== [], "Nearby query did not return the job at the same coordinates.");

        $matchingFilters = [
            "shift_type" => $jobRow["shift_type"],
            "keyword" => $jobRow["title"],
        ];
        if (!empty($jobRow["location_id"])) {
            $matchingFilters["location_id"] = $jobRow["location_id"];
        }
        if (is_numeric($jobRow["salary_min"]) && (float)$jobRow["salary_min"] > 0) {
            $matchingFilters["salary_min"] = (float)$jobRow["salary_min"];
        }
        $filtered = $repository->nearby(10.7769, 106.7009, 2, $matchingFilters, new Pagination(1, 12));
        $filteredFound = array_filter($filtered["items"], fn(array $item): bool => $item["id"] === $jobId);
        $assert($filteredFound !== [], "Nearby query dropped a job matching all UI filters.");

        $mismatch = $repository->nearby(10.7769, 106.7009, 2, ["shift_type" => "not-a-real-shift"], new Pagination(1, 12));
        $mismatchFound = array_filter($mismatch["items"], fn(array $item): bool => $item["id"] === $jobId);
        $assert($mismatchFound === [], "Nearby shift filter did not exclude a mismatched job.");
    } finally {
        $db->rollBack();
    }
}

// Ensures all API routes can be registered without conflicts.
FastRoute\simpleDispatcher(function (RouteCollector $collector): void {
    foreach (include BASE_PATH . "/app/Routes/api.php" as $route) {
        $collector->addRoute(...$route);
    }
});

echo "Location backend tests passed." . PHP_EOL;
