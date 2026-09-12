<?php

declare(strict_types=1);

define("BASE_PATH", __DIR__);
require_once __DIR__ . "/vendor/autoload.php";
Dotenv\Dotenv::createImmutable(__DIR__)->load();

use JobMarket\Domain\JobAlertService;
use JobMarket\Domain\JobService;
use JobMarket\Domain\SavedSearch\SavedSearch;
use JobMarket\Facades\Config;
use JobMarket\Infrastructure\SavedSearchRepository;

$config = Config::env();
$db = new PDO(
    "mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8mb4",
    $config["user"],
    $config["password"],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$searchId = null;
$jobId = null;
$notificationId = null;
$originalStates = [];

try {
    $student = $db->query("SELECT id, name, email, role FROM users WHERE role IN ('student','developer') ORDER BY created_at ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    $companyUser = $db->query(
        "SELECT u.id, u.name, u.email, u.role
         FROM users u JOIN companies c ON c.user_id = u.id
         WHERE u.role = 'company' AND c.verification_status = 'verified' LIMIT 1"
    )->fetch(PDO::FETCH_ASSOC);
    $categoryId = $db->query("SELECT id FROM categories ORDER BY id LIMIT 1")->fetchColumn();
    $locationId = $db->query("SELECT id FROM locations ORDER BY id LIMIT 1")->fetchColumn();
    if (!$student || !$companyUser || !$categoryId) {
        throw new RuntimeException("Thiếu dữ liệu mẫu student/company/category để chạy kiểm thử.");
    }

    foreach ($db->query("SELECT id, notification_enabled FROM saved_searches")->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $originalStates[$row["id"]] = (int)$row["notification_enabled"];
    }
    $db->exec("UPDATE saved_searches SET notification_enabled = 0");

    $marker = "alert" . date("His") . random_int(100, 999);
    $search = SavedSearch::create((string)$student["id"], "Kiểm thử cảnh báo việc làm");
    $search->setKeyword($marker)
        ->setNotificationEnabled(true)
        ->setEmailEnabled(true)
        ->setMinimumMatchScore(80)
        ->setFrequency("instant");
    (new SavedSearchRepository())->create($search);
    $searchId = $search->getId();

    $job = (new JobService())->createJob([
        "title" => "Nhân viên {$marker}",
        "description" => "Tin kiểm thử tự động cho luồng tìm kiếm đã lưu và gửi thông báo.",
        "requirements" => "Sinh viên chủ động, có trách nhiệm và có thể làm việc theo ca.",
        "category_id" => (string)$categoryId,
        "location_id" => $locationId ? (string)$locationId : null,
        "location" => "Hồ Chí Minh",
        "status" => "published",
        "work_type" => "part_time",
        "work_mode" => "onsite",
        "salary_type" => "hourly",
        "salary_min" => 30000,
        "salary_max" => 40000,
        "shift_type" => "flexible",
        "application_deadline" => date("Y-m-d", strtotime("+7 days")),
    ], $companyUser);
    $jobId = $job["id"];

    $alert = $db->query("SELECT * FROM saved_search_job_alerts WHERE saved_search_id = " . $db->quote($searchId) . " AND job_id = " . $db->quote($jobId))->fetch(PDO::FETCH_ASSOC);
    if (!$alert) {
        throw new RuntimeException("Không tạo được bản ghi cảnh báo.");
    }
    if ((int)$alert["match_score"] < 80) {
        throw new RuntimeException("Điểm phù hợp không đạt ngưỡng kiểm thử.");
    }
    $notificationId = $alert["notification_id"];
    $notificationCount = (int)$db->query("SELECT COUNT(*) FROM notifications WHERE id = " . $db->quote((string)$notificationId) . " AND type = 'saved_search_job_match'")->fetchColumn();
    if ($notificationCount !== 1) {
        throw new RuntimeException("Thông báo trong ứng dụng không được tạo đúng.");
    }
    if (!in_array($alert["email_status"], ["preview", "sent"], true)) {
        throw new RuntimeException("Email không được gửi hoặc tạo preview: {$alert['email_status']}.");
    }

    (new JobAlertService())->processPublishedJob($job);
    $dedupeCount = (int)$db->query("SELECT COUNT(*) FROM saved_search_job_alerts WHERE saved_search_id = " . $db->quote($searchId) . " AND job_id = " . $db->quote($jobId))->fetchColumn();
    if ($dedupeCount !== 1) {
        throw new RuntimeException("Cơ chế chống gửi trùng không hoạt động.");
    }

    echo "PASS: score={$alert['match_score']}%, in_app=created, email={$alert['email_status']}, dedupe=ok" . PHP_EOL;
} finally {
    if ($notificationId) {
        $stmt = $db->prepare("DELETE FROM notifications WHERE id = ?");
        $stmt->execute([$notificationId]);
    }
    if ($searchId) {
        $stmt = $db->prepare("DELETE FROM saved_searches WHERE id = ?");
        $stmt->execute([$searchId]);
    }
    if ($jobId) {
        $stmt = $db->prepare("DELETE FROM jobs WHERE id = ?");
        $stmt->execute([$jobId]);
    }
    foreach ($originalStates as $id => $enabled) {
        $stmt = $db->prepare("UPDATE saved_searches SET notification_enabled = ? WHERE id = ?");
        $stmt->execute([$enabled, $id]);
    }
}
