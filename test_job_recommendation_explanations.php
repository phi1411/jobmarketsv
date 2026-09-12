<?php

declare(strict_types=1);

define("BASE_PATH", __DIR__);
require __DIR__ . "/vendor/autoload.php";
Dotenv\Dotenv::createImmutable(__DIR__)->load();

use JobMarket\Domain\JobRecommendationService;

function assertExplanation(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException("[FAIL] {$message}");
    }
    echo "[PASS] {$message}" . PHP_EOL;
}

$result = (new JobRecommendationService())->recommend([
    "id" => "user-student-01",
    "role" => "student",
], 50, 0);

$job = null;
foreach ($result["items"] as $item) {
    if (($item["id"] ?? null) === "job-kfc-01") {
        $job = $item;
        break;
    }
}

assertExplanation(is_array($job), "Có việc mẫu để kiểm tra giải thích");
assertExplanation(count($job["criteria"] ?? []) === 6, "Trả về đủ 6 tiêu chí có trọng số");
assertExplanation(!empty($job["score_disclaimer"]), "Có lưu ý điểm không phải xác suất tuyển dụng");

$criteriaByKey = [];
foreach ($job["criteria"] as $criterion) {
    $criteriaByKey[$criterion["key"]] = $criterion;
    assertExplanation(
        array_key_exists("score", $criterion)
            && isset($criterion["weight"], $criterion["state"], $criterion["status"])
            && is_array($criterion["matched_items"])
            && is_array($criterion["missing_items"]),
        "Tiêu chí {$criterion['key']} có đủ dữ liệu giải thích"
    );
}

$skills = $criteriaByKey["skills"] ?? [];
assertExplanation(($skills["status"] ?? null) === "needs_improvement", "Nhận diện kỹ năng cần cải thiện");
assertExplanation(!empty($skills["matched_items"]), "Hiển thị kỹ năng đã đáp ứng");
assertExplanation(count($skills["missing_items"] ?? []) >= 1, "Hiển thị kỹ năng còn thiếu");

$suggestions = $job["improvement_suggestions"] ?? [];
assertExplanation($suggestions !== [], "Có gợi ý cải thiện hồ sơ");
assertExplanation(($suggestions[0]["key"] ?? null) === "skills", "Ưu tiên gợi ý theo mức ảnh hưởng đến điểm");

echo "Hoàn tất kiểm tra giải thích điểm phù hợp." . PHP_EOL;
