<?php

declare(strict_types=1);

$sources = [
    "current" => "https://provinces.open-api.vn/api/v2/?depth=2",
    "legacy" => "https://provinces.open-api.vn/api/?depth=3",
];

function downloadJson(string $url): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 90,
        CURLOPT_USERAGENT => "JobMarketSV-AdministrativeDataSync/1.0",
    ]);
    $body = curl_exec($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    if (!is_string($body) || $status !== 200) {
        throw new RuntimeException("Không tải được dữ liệu hành chính ({$status}): {$error}");
    }
    return json_decode($body, true, 512, JSON_THROW_ON_ERROR);
}

function compactUnit(array $unit): array
{
    return [
        "code" => (string)($unit["code"] ?? ""),
        "name" => trim((string)($unit["name"] ?? "")),
        "type" => trim((string)($unit["division_type"] ?? "")),
    ];
}

$outputDir = dirname(__DIR__) . "/app/Data";
if (!is_dir($outputDir) && !mkdir($outputDir, 0775, true) && !is_dir($outputDir)) {
    throw new RuntimeException("Không thể tạo thư mục app/Data.");
}

$retrievedAt = gmdate("c");

$currentRaw = downloadJson($sources["current"]);
$currentProvinces = [];
$currentCommuneCount = 0;
foreach ($currentRaw as $province) {
    $communes = array_map("compactUnit", is_array($province["wards"] ?? null) ? $province["wards"] : []);
    $currentCommuneCount += count($communes);
    $currentProvinces[] = compactUnit($province) + ["communes" => $communes];
}
if (count($currentProvinces) < 34 || $currentCommuneCount < 3000) {
    throw new RuntimeException("Dữ liệu hiện hành không đầy đủ; từ chối ghi file.");
}

$legacyRaw = downloadJson($sources["legacy"]);
$legacyProvinces = [];
$legacyDistrictCount = 0;
$legacyWardCount = 0;
foreach ($legacyRaw as $province) {
    $districts = [];
    foreach (is_array($province["districts"] ?? null) ? $province["districts"] : [] as $district) {
        $wards = array_map("compactUnit", is_array($district["wards"] ?? null) ? $district["wards"] : []);
        $legacyWardCount += count($wards);
        $districts[] = compactUnit($district) + ["wards" => $wards];
    }
    $legacyDistrictCount += count($districts);
    $legacyProvinces[] = compactUnit($province) + ["districts" => $districts];
}
if (count($legacyProvinces) < 63 || $legacyDistrictCount < 690 || $legacyWardCount < 10000) {
    throw new RuntimeException("Dữ liệu địa chỉ cũ không đầy đủ; từ chối ghi file.");
}

$datasets = [
    "vietnam_administrative_current.json" => [
        "meta" => [
            "schema" => "current",
            "source" => $sources["current"],
            "retrieved_at" => $retrievedAt,
            "province_count" => count($currentProvinces),
            "commune_count" => $currentCommuneCount,
        ],
        "provinces" => $currentProvinces,
    ],
    "vietnam_administrative_legacy.json" => [
        "meta" => [
            "schema" => "legacy",
            "source" => $sources["legacy"],
            "retrieved_at" => $retrievedAt,
            "province_count" => count($legacyProvinces),
            "district_count" => $legacyDistrictCount,
            "ward_count" => $legacyWardCount,
        ],
        "provinces" => $legacyProvinces,
    ],
];

foreach ($datasets as $filename => $dataset) {
    $json = json_encode($dataset, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    if (file_put_contents($outputDir . "/" . $filename, $json . PHP_EOL, LOCK_EX) === false) {
        throw new RuntimeException("Không thể ghi {$filename}.");
    }
}

echo "Current: " . count($currentProvinces) . " tỉnh/thành, {$currentCommuneCount} phường/xã" . PHP_EOL;
echo "Legacy: " . count($legacyProvinces) . " tỉnh/thành, {$legacyDistrictCount} quận/huyện, {$legacyWardCount} phường/xã" . PHP_EOL;
