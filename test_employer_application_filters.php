<?php

declare(strict_types=1);

define("BASE_PATH", __DIR__);
require __DIR__ . "/vendor/autoload.php";
Dotenv\Dotenv::createImmutable(__DIR__)->load();

use JobMarket\Domain\ApplicationService;
use JobMarket\Facades\Config;
use JobMarket\Infrastructure\ApplicationRepository;

function assertEmployerFilter(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException("[FAIL] {$message}");
    }
    echo "[PASS] {$message}" . PHP_EOL;
}

$repository = new ApplicationRepository();
$companyId = "comp-lotte-nowzone";

assertEmployerFilter($repository->countByCompany($companyId) === 2, "Đọc đúng tổng hồ sơ của công ty mẫu");
assertEmployerFilter($repository->countByCompany($companyId, ["university" => "Thương Mại"]) === 2, "Lọc theo trường");
assertEmployerFilter($repository->countByCompany($companyId, ["major" => "Quản trị kinh doanh"]) === 2, "Lọc theo ngành");
assertEmployerFilter($repository->countByCompany($companyId, ["preferred_shift" => "afternoon"]) === 1, "Lọc theo ca mong muốn");
assertEmployerFilter($repository->countByCompany($companyId, ["skill_ids" => "skill-002,skill-003"]) === 2, "Lọc nhiều kỹ năng theo điều kiện có đủ");
assertEmployerFilter($repository->countByCompany($companyId, ["match_filter" => "50"]) === 1, "Lọc hồ sơ đạt từ 50%");
assertEmployerFilter($repository->countByCompany($companyId, ["match_filter" => "65"]) === 0, "Không đưa hồ sơ dưới ngưỡng vào kết quả");
assertEmployerFilter($repository->countByCompany($companyId, ["match_filter" => "unscored"]) === 1, "Tách riêng hồ sơ chưa có điểm");
assertEmployerFilter($repository->countByCompany("comp-001", ["job_id" => "job-lt-01"]) === 0, "Không lọt hồ sơ giữa hai công ty");

$sorted = $repository->getByCompany($companyId, ["sort_by" => "match_desc"]);
assertEmployerFilter(($sorted[0]["match_score"] ?? null) !== null, "Sắp xếp điểm cao đưa hồ sơ đã đánh giá lên trước");

$config = Config::env();
$db = new PDO(
    "mysql:dbname={$config['dbname']};host={$config['host']};charset=utf8mb4",
    $config["user"],
    $config["password"],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
$ownerId = (string)$db->query("SELECT user_id FROM companies WHERE id = 'comp-lotte-nowzone'")->fetchColumn();
$result = (new ApplicationService())->getCompanyApplications(
    ["id" => $ownerId, "role" => "company"],
    ["sort_by" => "match_desc"]
);

assertEmployerFilter($result["total"] === 2, "Service trả đúng tổng kết quả");
assertEmployerFilter(($result["items"][0]["match_analysis"]["status"] ?? null) === "available", "Hiển thị điểm khi có đồng ý và đủ dữ liệu");
assertEmployerFilter(($result["items"][1]["match_analysis"]["status"] ?? null) === "not_consented", "Không biến hồ sơ chưa đồng ý thành 0 điểm");
assertEmployerFilter(is_array($result["items"][0]["student_skills"] ?? null), "Trả kỹ năng dạng danh sách an toàn cho giao diện");

$view = file_get_contents(__DIR__ . "/app/Views/company/applications.php");
foreach ([
    "filter-app-university",
    "filter-app-major",
    "filter-app-shift",
    "filter-skill-trigger",
    "filter-app-match",
    "filter-app-sort",
] as $controlId) {
    assertEmployerFilter(str_contains($view, $controlId), "Giao diện có bộ lọc {$controlId}");
}

echo "Hoàn tất kiểm tra bộ lọc ứng viên cho nhà tuyển dụng." . PHP_EOL;
