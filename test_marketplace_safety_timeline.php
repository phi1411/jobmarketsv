<?php

define("BASE_PATH", __DIR__);
require_once __DIR__ . "/vendor/autoload.php";
Dotenv\Dotenv::createImmutable(__DIR__)->safeLoad();

use JobMarket\Domain\FavoriteDeadlineReminderService;
use JobMarket\Domain\JobReportService;
use JobMarket\Domain\MailService;
use JobMarket\Domain\NotificationService;
use JobMarket\Facades\Config;
use JobMarket\Infrastructure\ApplicationDecisionDeliveryRepository;
use JobMarket\Infrastructure\ApplicationRepository;

$config = Config::env();
$db = new PDO("mysql:dbname={$config['dbname']};host={$config['host']};charset=utf8mb4", $config["user"], $config["password"], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$checks = 0;
$assert = function (bool $condition, string $message) use (&$checks): void {
    if (!$condition) throw new RuntimeException($message);
    $checks++;
};

$student = $db->query("SELECT id, role FROM users WHERE role IN ('student','developer') AND status = 'active' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$admin = $db->query("SELECT id, role FROM users WHERE role = 'admin' AND status = 'active' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$job = $db->query("SELECT j.id FROM jobs j WHERE j.status = 'published' AND j.deleted_at IS NULL AND NOT EXISTS (SELECT 1 FROM job_reports r WHERE r.job_id=j.id AND r.user_id=" . $db->quote($student["id"]) . ") LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$assert((bool)$student && (bool)$admin && (bool)$job, "Thiếu dữ liệu mẫu để kiểm thử báo cáo.");

$report = (new JobReportService())->report($student, $job["id"], ["reason" => "fee_required", "description" => "Báo cáo tích hợp tạm thời"]);
try {
    $assert($report["status"] === "pending", "Báo cáo mới phải ở trạng thái pending.");
    try {
        (new JobReportService())->report($student, $job["id"], ["reason" => "scam"]);
        $assert(false, "Không được cho phép báo cáo trùng cùng một tin.");
    } catch (JobMarket\Exceptions\AppException $e) {
        $assert($e->getStatusCode() === 409, "Báo cáo trùng phải trả về HTTP 409.");
    }
    $updated = (new JobReportService())->resolve($admin, $report["id"], ["status" => "reviewing", "resolution_action" => "none", "admin_note" => "Đang xác minh"]);
    $assert($updated["status"] === "reviewing", "Admin phải chuyển được báo cáo sang reviewing.");
} finally {
    $stmt = $db->prepare("DELETE FROM job_reports WHERE id = ?");
    $stmt->execute([$report["id"]]);
}

$application = $db->query("SELECT id, status FROM applications LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$assert((bool)$application, "Thiếu đơn ứng tuyển mẫu.");
$repository = new ApplicationRepository();
$repoDb = $repository->getDb();
$repoDb->beginTransaction();
try {
    $target = $application["status"] === "interview" ? "accepted" : "interview";
    $before = count($repository->getStatusHistory($application["id"]));
    $repository->updateStatus($application["id"], $target, null, $admin["id"], "company", "Kiểm thử timeline");
    $history = $repository->getStatusHistory($application["id"]);
    $assert(count($history) === $before + 1, "Đổi trạng thái phải tạo thêm một mốc timeline.");
    $assert(end($history)["status"] === $target, "Mốc cuối timeline phải là trạng thái mới.");
} finally {
    $repoDb->rollBack();
}

$decisionApp = $db->query(
    "SELECT a.id, a.status, c.user_id AS company_user_id
     FROM applications a JOIN jobs j ON j.id=a.job_id JOIN companies c ON c.id=j.company_id LIMIT 1"
)->fetch(PDO::FETCH_ASSOC);
$fakeNotifications = new class extends NotificationService {
    public array $calls = [];
    public function __construct() {}
    public function notify(string $userId, string $title, string $message, string $type = "general", ?array $data = null): JobMarket\Domain\Notification\Notification
    {
        $this->calls[] = compact("userId", "title", "message", "type", "data");
        return JobMarket\Domain\Notification\Notification::create($userId, $title, $message, $type, $data);
    }
};
$fakeMail = new class extends MailService {
    public array $calls = [];
    public function sendApplicationDecision(array $recipient, array $application, string $status, string $message): array
    {
        $this->calls[] = compact("recipient", "application", "status", "message");
        return ["status" => "sent", "error" => null, "preview_path" => null];
    }
};
$fakeDeliveries = new class extends ApplicationDecisionDeliveryRepository {
    public array $calls = [];
    public function __construct() {}
    public function create(string $applicationId, string $status, string $message, ?string $notificationId, array $emailResult): void
    {
        $this->calls[] = compact("applicationId", "status", "message", "notificationId", "emailResult");
    }
};
$decisionRepo = new ApplicationRepository();
$decisionDb = $decisionRepo->getDb();
$decisionDb->beginTransaction();
try {
    $decisionStatus = $decisionApp["status"] === "interview" ? "accepted" : "interview";
    $decisionMessage = "Phỏng vấn lúc 09:00 ngày 15/09 tại văn phòng công ty.";
    $service = new JobMarket\Domain\ApplicationService(
        $decisionRepo, null, null, null, $fakeNotifications, null, $fakeMail, $fakeDeliveries
    );
    $result = $service->updateStatus(
        ["id" => $decisionApp["company_user_id"], "role" => "company"],
        $decisionApp["id"],
        ["status" => $decisionStatus, "student_message" => $decisionMessage]
    );
    $assert($result["student_message"] === $decisionMessage, "Nội dung gửi sinh viên phải được lưu riêng.");
    $assert(count($fakeNotifications->calls) === 1, "Phải tạo một thông báo trong app.");
    $assert(count($fakeMail->calls) === 1 && $fakeMail->calls[0]["message"] === $decisionMessage, "Email phải nhận đúng nội dung nhà tuyển dụng.");
    $assert(($result["delivery"]["email_status"] ?? null) === "sent", "API phải trả kết quả gửi email.");
} finally {
    $decisionDb->rollBack();
}

$dryRun = (new FavoriteDeadlineReminderService())->dispatch(true);
$assert($dryRun["dry_run"] === true && isset($dryRun["candidates"]), "Deadline reminder dry-run không hợp lệ.");

echo "PASS: {$checks} checks" . PHP_EOL;
