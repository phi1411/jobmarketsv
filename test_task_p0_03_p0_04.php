<?php

/**
 * ============================================================================
 * KIỂM THỬ ĐỘC LẬP / HỒI QUY TASK-P0-03 & TASK-P0-04
 * ============================================================================
 *
 * TASK-P0-03: Hoàn thiện employer onboarding để tạo hồ sơ company an toàn
 * - Đăng ký company/employer tạo user và company skeleton trong cùng transaction.
 * - Đăng ký student không tạo company record.
 * - Lỗi giữa chừng rollback sạch sẽ không để user/company orphan.
 * - Company mới đăng ký -> login -> xem và cập nhật /company/profile thành công.
 * - Tạo draft/pending job thành công; job published bị chặn khi chưa verified.
 *
 * TASK-P0-04: Đồng nhất kiểu dữ liệu required_skills giữa form và domain
 * - Job::fromArray() với [], ['skill-1'] không throw TypeError.
 * - Tạo và sửa job với zero, one, nhiều skills không lỗi 500.
 * - Đọc lại chi tiết việc làm khôi phục danh sách kỹ năng đã chọn.
 * - Payload sai kiểu (int, bool, object, invalid element) trả HTTP 422 rõ ràng.
 */

define("BASE_PATH", __DIR__);

require_once BASE_PATH . "/vendor/autoload.php";

$envTestingFile = __DIR__ . "/.env.testing";
if (!file_exists($envTestingFile)) {
    fwrite(STDERR, "[FAIL-FAST] Lỗi nghiêm trọng: Không tìm thấy file '.env.testing'. Vui lòng cấu hình môi trường test biệt lập." . PHP_EOL);
    exit(1);
}

$dotenv = Dotenv\Dotenv::createMutable(__DIR__, ".env.testing");
$dotenv->load();

use JobMarket\Domain\Job\Job;
use JobMarket\Facades\Config;
use JobMarket\Facades\JWT;
use JobMarket\Http\Kernel;
use JobMarket\Http\Request;

$appEnv = $_ENV["APP_ENV"] ?? getenv("APP_ENV") ?: "";
$config = Config::env();

if ($appEnv !== "testing") {
    fwrite(STDERR, "[CHẶN TOÀN BỘ] Environment hiện tại là '{$appEnv}'. Test chỉ được phép chạy trên environment 'testing'!" . PHP_EOL);
    exit(1);
}

if (!isset($config["dbname"]) || strpos($config["dbname"], "test") === false) {
    fwrite(STDERR, "[CHẶN TOÀN BỘ] Database '{$config['dbname']}' không phải database testing! Nguy cơ ảnh hưởng dữ liệu." . PHP_EOL);
    exit(1);
}

try {
    $db = new PDO(
        "mysql:dbname={$config['dbname']};host={$config['host']};port=" . ($config["port"] ?? 3306),
        $config["user"],
        $config["password"],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    fwrite(STDERR, "[ERROR] Không thể kết nối cơ sở dữ liệu test: " . $e->getMessage() . PHP_EOL);
    exit(1);
}

$kernel = new Kernel();

function apiCall(string $method, string $uri, ?array $body = null, ?string $token = null): array
{
    global $kernel;

    $server = [
        "REQUEST_METHOD" => strtoupper($method),
        "REQUEST_URI"    => $uri,
        "CONTENT_TYPE"   => "application/json",
        "HTTP_ACCEPT"    => "application/json"
    ];

    if ($token !== null) {
        $server["HTTP_AUTHORIZATION"] = "Bearer " . $token;
        $_SERVER["HTTP_AUTHORIZATION"] = "Bearer " . $token;
    } else {
        unset($_SERVER["HTTP_AUTHORIZATION"]);
    }

    $request = new Request([], $body ?? [], [], [], $server);
    $response = $kernel->handler($request);

    unset($_SERVER["HTTP_AUTHORIZATION"]);

    return [
        "status" => $response->getStatusCode(),
        "body"   => $response->getPayload()
    ];
}

echo "================================================================" . PHP_EOL;
echo "   KIỂM THỬ ĐỘC LẬP / HỒI QUY TASK-P0-03 & TASK-P0-04           " . PHP_EOL;
echo "================================================================" . PHP_EOL;
echo " Môi trường : " . $appEnv . PHP_EOL;
echo " Database   : " . $config["dbname"] . " (" . $config["host"] . ":" . ($config["port"] ?? 3306) . ")" . PHP_EOL;
echo " Dispatcher : In-Process Kernel" . PHP_EOL;
echo "================================================================" . PHP_EOL . PHP_EOL;

$passCount = 0;
$totalTests = 23;

$cleanupUserIds = [];
$cleanupCompanyIds = [];
$cleanupJobIds = [];

try {
    // =========================================================================
    // PHẦN 1: KIỂM THỬ TASK-P0-03 (EMPLOYER ONBOARDING & COMPANY SKELETON)
    // =========================================================================

    // [TEST 1] Đăng ký tài khoản role=company tạo cả user và company skeleton
    echo "[TEST 1] Đăng ký role=company -> tạo đồng thời user và company skeleton" . PHP_EOL;
    $compEmail1 = "comp_p03_" . uniqid() . "@test.vn";
    $compName1 = "Công Ty TNHH Test P03 A";
    $regRes1 = apiCall("POST", "/register", [
        "name"     => $compName1,
        "email"    => $compEmail1,
        "password" => "Password@123",
        "role"     => "company"
    ]);

    $user1 = $db->query("SELECT id, name, role FROM users WHERE email = '$compEmail1'")->fetch();
    $company1 = null;
    if ($user1) {
        $cleanupUserIds[] = $user1["id"];
        $company1 = $db->query("SELECT id, user_id, name, verification_status FROM companies WHERE user_id = '{$user1['id']}'")->fetch();
        if ($company1) {
            $cleanupCompanyIds[] = $company1["id"];
        }
    }

    if ($regRes1["status"] === 201 && $user1 && $company1 && $company1["name"] === $compName1 && $company1["verification_status"] === "pending") {
        echo "  -> PASS: Đăng ký thành công (HTTP 201). Skeleton company được tạo tự động với verification_status='pending'." . PHP_EOL;
        $passCount++;
    } else {
        echo "  -> FAIL: Đăng ký company không tạo đúng user/company skeleton. Status: {$regRes1['status']}" . PHP_EOL;
    }
    echo PHP_EOL;

    // [TEST 2] Đăng ký tài khoản legacy role=employer tạo đúng company skeleton
    echo "[TEST 2] Đăng ký legacy role=employer -> ánh xạ thành company và tạo skeleton" . PHP_EOL;
    $compEmail2 = "employer_p03_" . uniqid() . "@test.vn";
    $compName2 = "Công Ty CP Test Employer Legacy";
    $regRes2 = apiCall("POST", "/register", [
        "name"     => $compName2,
        "email"    => $compEmail2,
        "password" => "Password@123",
        "role"     => "employer"
    ]);

    $user2 = $db->query("SELECT id, name, role FROM users WHERE email = '$compEmail2'")->fetch();
    $company2 = null;
    if ($user2) {
        $cleanupUserIds[] = $user2["id"];
        $company2 = $db->query("SELECT id, user_id, name, verification_status FROM companies WHERE user_id = '{$user2['id']}'")->fetch();
        if ($company2) {
            $cleanupCompanyIds[] = $company2["id"];
        }
    }

    if ($regRes2["status"] === 201 && $user2 && $user2["role"] === "company" && $company2 && $company2["name"] === $compName2) {
        echo "  -> PASS: Legacy role=employer ánh xạ chính xác thành role=company và tạo company skeleton thành công." . PHP_EOL;
        $passCount++;
    } else {
        echo "  -> FAIL: Legacy employer đăng ký không đúng." . PHP_EOL;
    }
    echo PHP_EOL;

    // [TEST 3] Đăng ký tài khoản role=student KHÔNG tạo company record
    echo "[TEST 3] Đăng ký role=student -> KHÔNG tạo company record" . PHP_EOL;
    $studentEmail1 = "student_p03_" . uniqid() . "@test.vn";
    $studentName1 = "Sinh Viên Test P03";
    $regRes3 = apiCall("POST", "/register", [
        "name"     => $studentName1,
        "email"    => $studentEmail1,
        "password" => "Password@123",
        "role"     => "student"
    ]);

    $user3 = $db->query("SELECT id, name, role FROM users WHERE email = '$studentEmail1'")->fetch();
    $company3 = null;
    if ($user3) {
        $cleanupUserIds[] = $user3["id"];
        $company3 = $db->query("SELECT id FROM companies WHERE user_id = '{$user3['id']}'")->fetch();
    }

    if ($regRes3["status"] === 201 && $user3 && $company3 === false) {
        echo "  -> PASS: Đăng ký student thành công (HTTP 201) và không tạo bất kỳ record nào trong bảng companies." . PHP_EOL;
        $passCount++;
    } else {
        echo "  -> FAIL: Student đăng ký nhưng lại xuất hiện record trong bảng companies!" . PHP_EOL;
    }
    echo PHP_EOL;

    // [TEST 4] Đăng nhập Company mới đăng ký -> lấy JWT thành công
    echo "[TEST 4] Đăng nhập Company vừa đăng ký (POST /login)" . PHP_EOL;
    $loginRes = apiCall("POST", "/login", [
        "email"    => $compEmail1,
        "password" => "Password@123"
    ]);

    $compToken = $loginRes["body"]["data"]["token"] ?? "";
    if ($loginRes["status"] === 200 && !empty($compToken)) {
        echo "  -> PASS: Đăng nhập thành công (HTTP 200). Đã nhận Bearer token." . PHP_EOL;
        $passCount++;
    } else {
        echo "  -> FAIL: Đăng nhập thất bại. Status: {$loginRes['status']}" . PHP_EOL;
    }
    echo PHP_EOL;

    // [TEST 5] Mở hồ sơ công ty (GET /company/profile) thành công (HTTP 200)
    echo "[TEST 5] Mở hồ sơ công ty mới đăng ký (GET /company/profile)" . PHP_EOL;
    $getProfileRes = apiCall("GET", "/company/profile", null, $compToken);
    $profileData = $getProfileRes["body"]["data"] ?? [];

    if ($getProfileRes["status"] === 200 && ($profileData["name"] ?? "") === $compName1 && ($profileData["verification_status"] ?? "") === "pending") {
        echo "  -> PASS: GET /company/profile thành công (HTTP 200). Tìm thấy skeleton công ty '{$profileData['name']}'." . PHP_EOL;
        $passCount++;
    } else {
        echo "  -> FAIL: GET /company/profile thất bại. Status: {$getProfileRes['status']}" . PHP_EOL;
    }
    echo PHP_EOL;

    // [TEST 6] Lưu/cập nhật hồ sơ công ty (PUT /company/profile) thành công (HTTP 200)
    echo "[TEST 6] Cập nhật thông tin công ty (PUT /company/profile)" . PHP_EOL;
    $updatePayload = [
        "name"           => $compName1 . " Updated",
        "description"    => "Mô tả công ty test P03",
        "contact_person" => "Nguyễn Tuyển Dụng",
        "contact_phone"  => "0987654321",
        "address"        => "123 Đường Cầu Giấy",
        "city"           => "Hà Nội",
        "district"       => "Cầu Giấy",
        "website"        => "https://testcompany.vn"
    ];

    $putProfileRes = apiCall("PUT", "/company/profile", $updatePayload, $compToken);
    $updatedData = $putProfileRes["body"]["data"] ?? [];

    if ($putProfileRes["status"] === 200 && ($updatedData["name"] ?? "") === $updatePayload["name"] && ($updatedData["contact_person"] ?? "") === $updatePayload["contact_person"]) {
        echo "  -> PASS: Cập nhật hồ sơ công ty thành công (HTTP 200). Dữ liệu được cập nhật đầy đủ." . PHP_EOL;
        $passCount++;
    } else {
        echo "  -> FAIL: PUT /company/profile thất bại. Status: {$putProfileRes['status']}" . PHP_EOL;
    }
    echo PHP_EOL;

    // [TEST 7] Tạo tin tuyển dụng dạng draft (POST /jobs với status=draft) thành công
    echo "[TEST 7] Tạo tin tuyển dụng dạng nháp (status=draft) từ công ty unverified" . PHP_EOL;
    $draftJobPayload = [
        "title"            => "Nhân viên phục vụ part-time ca sáng draft",
        "description"      => "Mô tả công việc phục vụ tại quán cafe test",
        "work_type"        => "part_time",
        "work_mode"        => "onsite",
        "salary_type"      => "hourly",
        "salary_min"       => 25000,
        "salary_max"       => 30000,
        "shift_type"       => "morning",
        "status"           => "draft",
        "required_skills"  => ["skill-001", "skill-002"]
    ];

    $draftJobRes = apiCall("POST", "/jobs", $draftJobPayload, $compToken);
    $draftJobData = $draftJobRes["body"]["data"] ?? [];
    if (!empty($draftJobData["id"])) {
        $cleanupJobIds[] = $draftJobData["id"];
    }

    if ($draftJobRes["status"] === 201 && ($draftJobData["status"] ?? "") === "draft") {
        echo "  -> PASS: Tạo tin tuyển dụng draft thành công (HTTP 201). Job ID: {$draftJobData['id']}." . PHP_EOL;
        $passCount++;
    } else {
        echo "  -> FAIL: Tạo draft job thất bại. Status: {$draftJobRes['status']}" . PHP_EOL;
    }
    echo PHP_EOL;

    // [TEST 8] Tạo tin tuyển dụng dạng pending_approval (POST /jobs với status=pending_approval)
    echo "[TEST 8] Tạo tin tuyển dụng chờ duyệt (status=pending_approval) từ công ty unverified" . PHP_EOL;
    $pendingJobPayload = [
        "title"            => "Nhân viên thu ngân part-time ca chiều pending",
        "description"      => "Mô tả công việc thu ngân test tại quầy",
        "work_type"        => "part_time",
        "work_mode"        => "onsite",
        "salary_type"      => "hourly",
        "salary_min"       => 28000,
        "salary_max"       => 35000,
        "shift_type"       => "afternoon",
        "status"           => "pending_approval",
        "required_skills"  => ["skill-002"]
    ];

    $pendingJobRes = apiCall("POST", "/jobs", $pendingJobPayload, $compToken);
    $pendingJobData = $pendingJobRes["body"]["data"] ?? [];
    if (!empty($pendingJobData["id"])) {
        $cleanupJobIds[] = $pendingJobData["id"];
    }

    if ($pendingJobRes["status"] === 201 && ($pendingJobData["status"] ?? "") === "pending_approval") {
        echo "  -> PASS: Tạo tin tuyển dụng pending_approval thành công (HTTP 201). Job ID: {$pendingJobData['id']}." . PHP_EOL;
        $passCount++;
    } else {
        echo "  -> FAIL: Tạo pending job thất bại. Status: {$pendingJobRes['status']}" . PHP_EOL;
    }
    echo PHP_EOL;

    // [TEST 9] Cố công khai tin (status=published) khi công ty chưa verified -> chặn HTTP 422
    echo "[TEST 9] Cố công khai tin (status=published) khi công ty chưa verified" . PHP_EOL;
    $publishedJobPayload = [
        "title"            => "Nhân viên pha chế part-time công khai trái phép",
        "description"      => "Mô tả công việc pha chế đồ uống",
        "work_type"        => "part_time",
        "work_mode"        => "onsite",
        "salary_type"      => "hourly",
        "salary_min"       => 30000,
        "salary_max"       => 40000,
        "shift_type"       => "morning",
        "status"           => "published",
        "required_skills"  => ["skill-001"]
    ];

    $pubJobRes = apiCall("POST", "/jobs", $publishedJobPayload, $compToken);
    if ($pubJobRes["status"] === 422) {
        echo "  -> PASS: Bị từ chối chính xác với HTTP 422 Unprocessable Entity (Chưa verified không được publish trực tiếp)." . PHP_EOL;
        $passCount++;
    } else {
        echo "  -> FAIL: Mong đợi HTTP 422 nhưng nhận HTTP {$pubJobRes['status']}" . PHP_EOL;
    }
    echo PHP_EOL;

    // [TEST 10] Atomicity & Rollback: Ép lỗi khi insert company sau khi insert user -> chứng minh rollback hoàn toàn
    echo "[TEST 10] Atomicity & Rollback: Ép lỗi khi insert company -> chứng minh rollback cả user và company" . PHP_EOL;
    $rollbackEmail = "rollback_fail_" . uniqid() . "@test.vn";
    $rollbackName = "Simulate_Rollback_P03_" . uniqid();

    // Cài đặt trigger tạm thời trên test database: chặn INSERT vào companies nếu tên trùng với $rollbackName
    $db->exec("DROP TRIGGER IF EXISTS test_fail_company_insert");
    $db->exec("CREATE TRIGGER test_fail_company_insert BEFORE INSERT ON companies FOR EACH ROW BEGIN IF NEW.name = '{$rollbackName}' THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Simulated mid-transaction failure on company insert'; END IF; END;");

    try {
        $regFailRes = apiCall("POST", "/register", [
            "name"     => $rollbackName,
            "email"    => $rollbackEmail,
            "password" => "Password@123",
            "role"     => "company"
        ]);

        // 1. Assert response là lỗi mong đợi phát sinh từ trigger mid-transaction (HTTP 500)
        $isRequestFailed = ($regFailRes["status"] === 500) 
            && (($regFailRes["body"]["success"] ?? true) === false)
            && (str_contains($regFailRes["body"]["message"] ?? "", "Simulated mid-transaction failure on company insert") 
                || str_contains($regFailRes["body"]["message"] ?? "", "SQLSTATE[45000]"));

        // 2. Assert database rollback: Cả users và companies đều KHÔNG có bản ghi nào tồn tại
        $orphanUser = $db->query("SELECT id FROM users WHERE email = '{$rollbackEmail}'")->fetch();
        $orphanCompany = $db->query("SELECT id FROM companies WHERE name = '{$rollbackName}'")->fetch();
        $isRollbackClean = ($orphanUser === false && $orphanCompany === false);

        if ($isRequestFailed && $isRollbackClean) {
            echo "  -> PASS: Request đăng ký thất bại với HTTP 500 do lỗi insert company và transaction rollback hoàn toàn (0 user/company orphan)." . PHP_EOL;
            $passCount++;
        } else {
            echo "  -> FAIL: Rollback test không đạt yêu cầu:" . PHP_EOL;
            if (!$isRequestFailed) {
                echo "     Request không trả về lỗi mong đợi (Status: {$regFailRes['status']}, Body: " . json_encode($regFailRes['body']) . ")" . PHP_EOL;
            }
            if (!$isRollbackClean) {
                echo "     Vẫn còn dữ liệu mồ côi trong database sau khi rollback thất bại!" . PHP_EOL;
                if ($orphanUser) echo "     User orphan ID: " . $orphanUser["id"] . PHP_EOL;
                if ($orphanCompany) echo "     Company orphan ID: " . $orphanCompany["id"] . PHP_EOL;
            }
        }
    } finally {
        $db->exec("DROP TRIGGER IF EXISTS test_fail_company_insert");
    }
    echo PHP_EOL;

    // =========================================================================
    // PHẦN 2: KIỂM THỬ TASK-P0-04 (ĐỒNG NHẤT KIỂU DỮ LIỆU required_skills)
    // =========================================================================

    // [TEST 11] Unit check: Job::fromArray(['required_skills' => []]) không 500 / TypeError
    echo "[TEST 11] Job::fromArray() với required_skills là mảng rỗng []" . PHP_EOL;
    try {
        $jobZero = Job::fromArray([
            "company_id"      => $company1["id"],
            "title"           => "Job with zero skills",
            "required_skills" => []
        ]);
        if ($jobZero->getRequiredSkillsArray() === []) {
            echo "  -> PASS: Job::fromArray() xử lý mảng rỗng [] mượt mà, không gặp TypeError." . PHP_EOL;
            $passCount++;
        } else {
            echo "  -> FAIL: Mảng kỹ năng trả về không rỗng." . PHP_EOL;
        }
    } catch (TypeError $e) {
        echo "  -> FAIL: Bị TypeError: " . $e->getMessage() . PHP_EOL;
    }
    echo PHP_EOL;

    // [TEST 12] Unit check: Job::fromArray(['required_skills' => ['skill-1']]) không 500 / TypeError
    echo "[TEST 12] Job::fromArray() với required_skills là mảng có phần tử ['skill-1']" . PHP_EOL;
    try {
        $jobOne = Job::fromArray([
            "company_id"      => $company1["id"],
            "title"           => "Job with one skill",
            "required_skills" => ["skill-1"]
        ]);
        if ($jobOne->getRequiredSkillsArray() === ["skill-1"]) {
            echo "  -> PASS: Job::fromArray() xử lý mảng ['skill-1'] thành công, không gặp TypeError." . PHP_EOL;
            $passCount++;
        } else {
            echo "  -> FAIL: Mảng kỹ năng trả về không khớp." . PHP_EOL;
        }
    } catch (TypeError $e) {
        echo "  -> FAIL: Bị TypeError: " . $e->getMessage() . PHP_EOL;
    }
    echo PHP_EOL;

    // [TEST 13] API POST /jobs với zero skills (required_skills: []) thành công không 500
    echo "[TEST 13] POST /jobs với required_skills = [] (Zero skills)" . PHP_EOL;
    $resZero = apiCall("POST", "/jobs", [
        "title"            => "Việc làm không yêu cầu kỹ năng part-time",
        "description"      => "Mô tả công việc không yêu cầu kỹ năng cụ thể",
        "work_type"        => "part_time",
        "work_mode"        => "onsite",
        "salary_type"      => "hourly",
        "salary_min"       => 20000,
        "salary_max"       => 25000,
        "shift_type"       => "morning",
        "status"           => "draft",
        "required_skills"  => []
    ], $compToken);

    $jobZeroData = $resZero["body"]["data"] ?? [];
    if (!empty($jobZeroData["id"])) {
        $cleanupJobIds[] = $jobZeroData["id"];
    }

    if ($resZero["status"] === 201 && isset($jobZeroData["skills"]) && $jobZeroData["skills"] === []) {
        echo "  -> PASS: Tạo việc làm với zero skills thành công (HTTP 201). skills = []." . PHP_EOL;
        $passCount++;
    } else {
        echo "  -> FAIL: Status: {$resZero['status']}. Body: " . json_encode($resZero['body']) . PHP_EOL;
    }
    echo PHP_EOL;

    // [TEST 14] API POST /jobs với one skill (required_skills: ["skill-001"]) thành công không 500
    echo "[TEST 14] POST /jobs với required_skills = ['skill-001'] (One skill)" . PHP_EOL;
    $resOne = apiCall("POST", "/jobs", [
        "title"            => "Việc làm yêu cầu 1 kỹ năng pha chế part-time",
        "description"      => "Mô tả công việc yêu cầu duy nhất một kỹ năng pha chế",
        "work_type"        => "part_time",
        "work_mode"        => "onsite",
        "salary_type"      => "hourly",
        "salary_min"       => 22000,
        "salary_max"       => 28000,
        "shift_type"       => "afternoon",
        "status"           => "draft",
        "required_skills"  => ["skill-001"]
    ], $compToken);

    $jobOneData = $resOne["body"]["data"] ?? [];
    if (!empty($jobOneData["id"])) {
        $cleanupJobIds[] = $jobOneData["id"];
    }

    if ($resOne["status"] === 201 && isset($jobOneData["skills"]) && $jobOneData["skills"] === ["skill-001"]) {
        echo "  -> PASS: Tạo việc làm với 1 skill thành công (HTTP 201). skills = ['skill-001']." . PHP_EOL;
        $passCount++;
    } else {
        echo "  -> FAIL: Status: {$resOne['status']}. Body: " . json_encode($resOne['body']) . PHP_EOL;
    }
    echo PHP_EOL;

    // [TEST 15] API POST /jobs với multiple skills (required_skills: ["skill-001", "skill-002", "skill-003"])
    echo "[TEST 15] POST /jobs với required_skills có nhiều phần tử" . PHP_EOL;
    $skillsMultiple = ["skill-001", "skill-002", "skill-003"];
    $resMulti = apiCall("POST", "/jobs", [
        "title"            => "Việc làm yêu cầu nhiều kỹ năng tổng hợp",
        "description"      => "Mô tả công việc yêu cầu giao tiếp, pha chế và ngoại ngữ",
        "work_type"        => "part_time",
        "work_mode"        => "onsite",
        "salary_type"      => "hourly",
        "salary_min"       => 30000,
        "salary_max"       => 35000,
        "shift_type"       => "flexible",
        "status"           => "draft",
        "required_skills"  => $skillsMultiple
    ], $compToken);

    $jobMultiData = $resMulti["body"]["data"] ?? [];
    $multiJobId = $jobMultiData["id"] ?? "";
    if (!empty($multiJobId)) {
        $cleanupJobIds[] = $multiJobId;
    }

    if ($resMulti["status"] === 201 && isset($jobMultiData["skills"]) && $jobMultiData["skills"] === $skillsMultiple) {
        echo "  -> PASS: Tạo việc làm với nhiều skills thành công (HTTP 201). skills = ['skill-001', 'skill-002', 'skill-003']." . PHP_EOL;
        $passCount++;
    } else {
        echo "  -> FAIL: Status: {$resMulti['status']}. Body: " . json_encode($resMulti['body']) . PHP_EOL;
    }
    echo PHP_EOL;

    // [TEST 16] GET /jobs/{id} đọc lại chi tiết việc làm khôi phục selection
    echo "[TEST 16] GET /jobs/{id} đọc lại dữ liệu và khôi phục mảng skills đã chọn" . PHP_EOL;
    $getDetailRes = apiCall("GET", "/jobs/{$multiJobId}", null, $compToken);
    $detailData = $getDetailRes["body"]["data"] ?? [];

    if ($getDetailRes["status"] === 200 && isset($detailData["skills"]) && $detailData["skills"] === $skillsMultiple) {
        echo "  -> PASS: GET /jobs/{id} khôi phục đầy đủ mảng kỹ năng đã chọn để hiển thị và check form edit." . PHP_EOL;
        $passCount++;
    } else {
        echo "  -> FAIL: GET /jobs/{id} không khôi phục đúng skills. Data: " . json_encode($detailData) . PHP_EOL;
    }
    echo PHP_EOL;

    // [TEST 17] PUT /jobs/{id} chỉnh sửa cập nhật lại danh sách skills với nhiều phần tử mới
    echo "[TEST 17] PUT /jobs/{id} chỉnh sửa cập nhật danh sách skills với nhiều phần tử mới" . PHP_EOL;
    $skillsUpdatedMulti = ["skill-002", "skill-003"];
    $updateJobResMulti = apiCall("PUT", "/jobs/{$multiJobId}", [
        "title"            => "Việc làm yêu cầu nhiều kỹ năng (cập nhật nhiều skills)",
        "description"      => "Mô tả công việc đã sửa lại danh sách nhiều skills",
        "work_type"        => "part_time",
        "work_mode"        => "onsite",
        "salary_type"      => "hourly",
        "salary_min"       => 35000,
        "salary_max"       => 40000,
        "shift_type"       => "flexible",
        "status"           => "draft",
        "required_skills"  => $skillsUpdatedMulti
    ], $compToken);

    $updatedJobDataMulti = $updateJobResMulti["body"]["data"] ?? [];
    $getDetailMulti = apiCall("GET", "/jobs/{$multiJobId}", null, $compToken);
    $detailDataMulti = $getDetailMulti["body"]["data"] ?? [];

    if ($updateJobResMulti["status"] === 200 
        && isset($updatedJobDataMulti["skills"]) && $updatedJobDataMulti["skills"] === $skillsUpdatedMulti
        && isset($detailDataMulti["skills"]) && $detailDataMulti["skills"] === $skillsUpdatedMulti) {
        echo "  -> PASS: Chỉnh sửa job với nhiều skills thành công (HTTP 200). GET /jobs/{id} khôi phục đúng mảng nhiều skills." . PHP_EOL;
        $passCount++;
    } else {
        echo "  -> FAIL: PUT /jobs/{id} với nhiều skills thất bại. Status: {$updateJobResMulti['status']}" . PHP_EOL;
    }
    echo PHP_EOL;

    // [TEST 18] PUT /jobs/{id} chỉnh sửa giảm còn 1 skill thành công
    echo "[TEST 18] PUT /jobs/{id} chỉnh sửa cập nhật lại danh sách skills còn 1 phần tử" . PHP_EOL;
    $updateJobRes1 = apiCall("PUT", "/jobs/{$multiJobId}", [
        "title"            => "Việc làm yêu cầu nhiều kỹ năng (đã cập nhật)",
        "description"      => "Mô tả công việc đã sửa lại thông tin",
        "work_type"        => "part_time",
        "work_mode"        => "onsite",
        "salary_type"      => "hourly",
        "salary_min"       => 32000,
        "salary_max"       => 38000,
        "shift_type"       => "flexible",
        "status"           => "draft",
        "required_skills"  => ["skill-002"]
    ], $compToken);

    $updatedJobData1 = $updateJobRes1["body"]["data"] ?? [];
    if ($updateJobRes1["status"] === 200 && isset($updatedJobData1["skills"]) && $updatedJobData1["skills"] === ["skill-002"]) {
        echo "  -> PASS: Chỉnh sửa job với 1 skill thành công (HTTP 200). skills = ['skill-002']." . PHP_EOL;
        $passCount++;
    } else {
        echo "  -> FAIL: PUT /jobs/{id} thất bại. Status: {$updateJobRes1['status']}" . PHP_EOL;
    }
    echo PHP_EOL;

    // [TEST 19] PUT /jobs/{id} chỉnh sửa bỏ toàn bộ skill (required_skills: []) thành công
    echo "[TEST 19] PUT /jobs/{id} chỉnh sửa xóa sạch kỹ năng (required_skills = [])" . PHP_EOL;
    $updateJobRes2 = apiCall("PUT", "/jobs/{$multiJobId}", [
        "title"            => "Việc làm yêu cầu nhiều kỹ năng (xóa sạch skills)",
        "description"      => "Mô tả công việc đã sửa lại thông tin không cần skill",
        "work_type"        => "part_time",
        "work_mode"        => "onsite",
        "salary_type"      => "hourly",
        "salary_min"       => 32000,
        "salary_max"       => 38000,
        "shift_type"       => "flexible",
        "status"           => "draft",
        "required_skills"  => []
    ], $compToken);

    $updatedJobData2 = $updateJobRes2["body"]["data"] ?? [];
    if ($updateJobRes2["status"] === 200 && isset($updatedJobData2["skills"]) && $updatedJobData2["skills"] === []) {
        echo "  -> PASS: Chỉnh sửa job thành zero skills thành công (HTTP 200). skills = []." . PHP_EOL;
        $passCount++;
    } else {
        echo "  -> FAIL: PUT /jobs/{id} với [] thất bại. Status: {$updateJobRes2['status']}" . PHP_EOL;
    }
    echo PHP_EOL;

    // [TEST 20] POST /jobs với required_skills sai kiểu (integer 12345) -> trả HTTP 422
    echo "[TEST 20] POST /jobs với required_skills sai kiểu (số nguyên 12345) -> trả HTTP 422" . PHP_EOL;
    $badTypeRes1 = apiCall("POST", "/jobs", [
        "title"            => "Việc làm kiểm tra kiểu dữ liệu sai int",
        "description"      => "Mô tả công việc test validation type error",
        "work_type"        => "part_time",
        "work_mode"        => "onsite",
        "salary_type"      => "hourly",
        "salary_min"       => 20000,
        "salary_max"       => 25000,
        "shift_type"       => "morning",
        "status"           => "draft",
        "required_skills"  => 12345
    ], $compToken);

    if ($badTypeRes1["status"] === 422) {
        echo "  -> PASS: Bị từ chối chính xác với HTTP 422 Unprocessable Entity khi truyền required_skills dạng số nguyên." . PHP_EOL;
        $passCount++;
    } else {
        echo "  -> FAIL: Mong đợi HTTP 422 nhưng nhận HTTP {$badTypeRes1['status']}" . PHP_EOL;
    }
    echo PHP_EOL;

    // [TEST 21] POST /jobs với required_skills sai kiểu (boolean true) -> trả HTTP 422
    echo "[TEST 21] POST /jobs với required_skills sai kiểu (boolean true) -> trả HTTP 422" . PHP_EOL;
    $badTypeRes2 = apiCall("POST", "/jobs", [
        "title"            => "Việc làm kiểm tra kiểu dữ liệu sai bool",
        "description"      => "Mô tả công việc test validation type error bool",
        "work_type"        => "part_time",
        "work_mode"        => "onsite",
        "salary_type"      => "hourly",
        "salary_min"       => 20000,
        "salary_max"       => 25000,
        "shift_type"       => "morning",
        "status"           => "draft",
        "required_skills"  => true
    ], $compToken);

    if ($badTypeRes2["status"] === 422) {
        echo "  -> PASS: Bị từ chối chính xác với HTTP 422 Unprocessable Entity khi truyền required_skills dạng boolean." . PHP_EOL;
        $passCount++;
    } else {
        echo "  -> FAIL: Mong đợi HTTP 422 nhưng nhận HTTP {$badTypeRes2['status']}" . PHP_EOL;
    }
    echo PHP_EOL;

    // [TEST 22] POST /jobs với required_skills chứa phần tử rỗng hoặc không hợp lệ -> trả HTTP 422
    echo "[TEST 22] POST /jobs với required_skills chứa chuỗi rỗng [''] -> trả HTTP 422" . PHP_EOL;
    $badElemRes = apiCall("POST", "/jobs", [
        "title"            => "Việc làm kiểm tra phần tử rỗng trong mảng",
        "description"      => "Mô tả công việc test validation empty element in array",
        "work_type"        => "part_time",
        "work_mode"        => "onsite",
        "salary_type"      => "hourly",
        "salary_min"       => 20000,
        "salary_max"       => 25000,
        "shift_type"       => "morning",
        "status"           => "draft",
        "required_skills"  => [""]
    ], $compToken);

    if ($badElemRes["status"] === 422) {
        echo "  -> PASS: Bị từ chối chính xác với HTTP 422 Unprocessable Entity khi mảng chứa phần tử rỗng." . PHP_EOL;
        $passCount++;
    } else {
        echo "  -> FAIL: Mong đợi HTTP 422 nhưng nhận HTTP {$badElemRes['status']}" . PHP_EOL;
    }
    echo PHP_EOL;

    // [TEST 23] Sinh viên cố tạo job (POST /jobs) bị từ chối quyền (HTTP 403)
    echo "[TEST 23] Sinh viên cố tạo tin tuyển dụng (POST /jobs) -> bị chặn HTTP 403" . PHP_EOL;
    $studentLoginRes = apiCall("POST", "/login", [
        "email"    => $studentEmail1,
        "password" => "Password@123"
    ]);
    $studentToken = $studentLoginRes["body"]["data"]["token"] ?? "";

    $studentJobRes = apiCall("POST", "/jobs", [
        "title"            => "Sinh viên tự đăng tin tuyển dụng trái phép",
        "description"      => "Mô tả tin tuyển dụng do sinh viên đăng",
        "work_type"        => "part_time",
        "work_mode"        => "onsite",
        "salary_type"      => "hourly",
        "salary_min"       => 20000,
        "salary_max"       => 25000,
        "shift_type"       => "morning",
        "status"           => "draft"
    ], $studentToken);

    if ($studentJobRes["status"] === 403) {
        echo "  -> PASS: Bị từ chối chính xác với HTTP 403 Forbidden (Chỉ tài khoản Company mới có quyền đăng tin)." . PHP_EOL;
        $passCount++;
    } else {
        echo "  -> FAIL: Mong đợi HTTP 403 nhưng nhận HTTP {$studentJobRes['status']}" . PHP_EOL;
    }
    echo PHP_EOL;

} finally {
    // -------------------------------------------------------------
    // Fixture Cleanup: Always executed regardless of test pass/fail
    // -------------------------------------------------------------
    try {
        if (!empty($cleanupJobIds)) {
            $ph = implode(",", array_fill(0, count($cleanupJobIds), "?"));
            $db->prepare("DELETE FROM jobs WHERE id IN ($ph)")->execute($cleanupJobIds);
        }
        if (!empty($cleanupCompanyIds)) {
            $ph = implode(",", array_fill(0, count($cleanupCompanyIds), "?"));
            $db->prepare("DELETE FROM companies WHERE id IN ($ph)")->execute($cleanupCompanyIds);
        }
        if (!empty($cleanupUserIds)) {
            $ph = implode(",", array_fill(0, count($cleanupUserIds), "?"));
            $db->prepare("DELETE FROM student_profiles WHERE user_id IN ($ph)")->execute($cleanupUserIds);
            $db->prepare("DELETE FROM audit_logs WHERE target_id IN ($ph) OR actor_id IN ($ph)")->execute(array_merge($cleanupUserIds, $cleanupUserIds));
            $db->prepare("DELETE FROM users WHERE id IN ($ph)")->execute($cleanupUserIds);
        }
        echo "[CLEANUP] Đã dọn dẹp sạch sẽ toàn bộ fixtures test khỏi database '{$config['dbname']}'." . PHP_EOL;
    } catch (Throwable $e) {
        fwrite(STDERR, "[CLEANUP ERROR] Lỗi dọn dẹp test fixture: " . $e->getMessage() . PHP_EOL);
    }
}

echo "================================================================" . PHP_EOL;
echo "   KẾT QUẢ: {$passCount}/{$totalTests} BÀI KIỂM THỬ TASK-P0-03 & TASK-P0-04 ĐẠT THÀNH CÔNG " . ($passCount === $totalTests ? "(100% PASS)" : "(FAILED)") . PHP_EOL;
echo "================================================================" . PHP_EOL;

exit($passCount === $totalTests ? 0 : 1);
