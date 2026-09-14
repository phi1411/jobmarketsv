<?php

define("BASE_PATH", __DIR__);
require __DIR__ . "/vendor/autoload.php";

use JobMarket\Exceptions\ValidationException;
use JobMarket\Infrastructure\LocationRepository;
use JobMarket\Infrastructure\Maps\GoongMapsClient;

$assert = static function (bool $condition, string $message): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
};

$read = static function (string $schema): array {
    $path = BASE_PATH . "/app/Data/vietnam_administrative_{$schema}.json";
    if (!is_file($path)) {
        throw new RuntimeException("Missing {$schema} administrative dataset.");
    }
    return json_decode((string)file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
};

$current = $read("current");
$legacy = $read("legacy");
$currentCommuneCount = array_sum(array_map(static fn(array $p): int => count($p["communes"] ?? []), $current["provinces"]));
$legacyDistrictCount = array_sum(array_map(static fn(array $p): int => count($p["districts"] ?? []), $legacy["provinces"]));
$legacyWardCount = 0;
foreach ($legacy["provinces"] as $province) {
    foreach ($province["districts"] ?? [] as $district) {
        $legacyWardCount += count($district["wards"] ?? []);
    }
}

$assert(count($current["provinces"]) === 34, "Current catalogue must contain 34 provinces/cities.");
$assert($currentCommuneCount === 3321, "Current catalogue must contain 3,321 wards/communes.");
$assert(count($legacy["provinces"]) === 63, "Legacy catalogue must contain 63 provinces/cities.");
$assert($legacyDistrictCount === 696, "Legacy catalogue must contain 696 districts.");
$assert($legacyWardCount === 10051, "Legacy catalogue must contain 10,051 wards/communes.");

$repo = new LocationRepository();
$hierarchy = $repo->getHierarchy();
$assert(count($hierarchy) === 34, "Job filter must expose all 34 current provinces/cities.");
$hierarchyAreaCount = array_sum(array_map(static fn(array $p): int => count($p["areas"] ?? []), $hierarchy));
$assert($hierarchyAreaCount >= 3321, "Job filter must expose every current ward/commune.");

$fakeHttp = static function (string $url): array {
    return [
        "status" => 200,
        "body" => json_encode([
            "status" => "OK",
            "results" => [[
                "place_id" => "catalogue-test",
                "formatted_address" => "1 Đồng Khởi, Bến Nghé, Hồ Chí Minh",
                "compound" => [],
                "geometry" => ["location" => ["lat" => 10.7756, "lng" => 106.7039]],
            ]],
        ], JSON_UNESCAPED_UNICODE),
    ];
};
$service = new JobMarket\Domain\LocationFeatureService(new GoongMapsClient("test-key", "https://example.test", 3, $fakeHttp));

$valid = $service->resolveInputLocation([
    "source" => "manual",
    "administrative_mode" => "current",
    "province" => "Hồ Chí Minh",
    "province_code" => "79",
    "ward" => "Phường Sài Gòn",
    "commune_code" => "26740",
    "address_detail" => "1 Đồng Khởi",
]);
$assert($valid["province"] === "Thành phố Hồ Chí Minh", "Server must canonicalize the province name.");
$assert($valid["commune"] === "Phường Sài Gòn", "Server must canonicalize the commune name.");

$rejected = false;
try {
    $service->resolveInputLocation([
        "source" => "manual",
        "administrative_mode" => "current",
        "province" => "Thành phố Hà Nội",
        "province_code" => "1",
        "ward" => "Phường Bến Nghé",
        "commune_code" => "26740",
        "address_detail" => "1 Địa chỉ giả",
    ]);
} catch (ValidationException) {
    $rejected = true;
}
$assert($rejected, "Server must reject a commune paired with the wrong province.");

$companyView = (string)file_get_contents(BASE_PATH . "/app/Views/company/job_form.php");
$studentView = (string)file_get_contents(BASE_PATH . "/app/Views/jobs/index.php");
$layout = (string)file_get_contents(BASE_PATH . "/app/Views/layouts/main.php");
$routes = (string)file_get_contents(BASE_PATH . "/app/Routes/api.php");
$auth = (string)file_get_contents(BASE_PATH . "/app/Http/Middlewares/AuthMiddleware.php");

foreach (["company-current", "company-legacy"] as $group) {
    $assert(str_contains($companyView, "data-vn-address-group=\"{$group}\""), "Company form is missing {$group} selects.");
}
foreach (["student-current", "student-legacy"] as $group) {
    $assert(str_contains($studentView, "data-vn-address-group=\"{$group}\""), "Student form is missing {$group} selects.");
}
$assert(str_contains($layout, "administrative_address_picker.js"), "Address picker asset is not included in the main layout.");
$assert(str_contains($routes, '"/locations/administrative"'), "Administrative endpoint is not registered.");
$assert(str_contains($auth, '"/locations/administrative"'), "Administrative endpoint must be public.");
$assert(!str_contains($companyView, '<input type="text" id="manual-curr-province"'), "Company province must not be a free-text input.");
$assert(!str_contains($studentView, '<input type="text" id="stu-manual-curr-province"'), "Student province must not be a free-text input.");
$assert(str_contains($studentView, 'id="filter-city"'), "Quick city filters must use a dedicated city parameter.");
$assert(!str_contains($studentView, 'selectProvince("Hà Nội"'), "Quick city filters must not expand a province into hundreds of commune IDs.");

echo "PASS: nationwide structured address catalogue and server validation.\n";
echo "Current: 34 provinces/cities, {$currentCommuneCount} wards/communes.\n";
echo "Legacy: 63 provinces/cities, {$legacyDistrictCount} districts, {$legacyWardCount} wards/communes.\n";
