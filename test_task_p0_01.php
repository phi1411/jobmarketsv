<?php

/**
 * Isolated Regression & Feature Test Suite for TASK-P0-01:
 * Public Registration Role Validation & Admin Account Creation Guard.
 *
 * Requirements & Acceptance Criteria:
 * 1. Test Environment Isolation:
 *    - Loads dedicated .env.testing.
 *    - Fails-fast if APP_ENV !== 'testing' or DB_NAME is development/shared.
 *    - Base URL resolved from test configuration/environment (no hardcoding).
 * 2. POST /register with role=admin MUST be rejected with HTTP 422.
 * 3. POST /register with role=student MUST succeed with HTTP 201.
 * 4. POST /register with role=developer MUST succeed with HTTP 201 and map to 'student'.
 * 5. POST /register with role=company MUST succeed with HTTP 201.
 * 6. POST /register with role=employer MUST succeed with HTTP 201 and map to 'company'.
 * 7. POST /register with arbitrary roles (e.g. superadmin, moderator, root) MUST be rejected with HTTP 422.
 * 8. Verified via Database: No admin user created. Stored roles match expected mapping in test database.
 * 9. Verified via POST /login: Users can successfully authenticate with their mapped roles.
 * 10. Fixture Cleanup in finally block: Guarantees test records are always purged even upon assertion failure.
 */

if (!defined("BASE_PATH")) {
    define("BASE_PATH", __DIR__);
}

require_once __DIR__ . "/vendor/autoload.php";

// -------------------------------------------------------------
// 1. Environment & Configuration Loading
// -------------------------------------------------------------
$envTestingFile = __DIR__ . "/.env.testing";
if (!file_exists($envTestingFile)) {
    fwrite(STDERR, "[FAIL-FAST] Lỗi nghiêm trọng: Không tìm thấy file '.env.testing'. Vui lòng cấu hình môi trường test biệt lập." . PHP_EOL);
    exit(1);
}

// Load test environment (mutable to ensure .env.testing variables take precedence)
$dotenv = Dotenv\Dotenv::createMutable(__DIR__, ".env.testing");
$dotenv->load();

// -------------------------------------------------------------
// 2. Fail-Fast Guard: Environment & Database Isolation Checks
// -------------------------------------------------------------
$appEnv = $_ENV["APP_ENV"] ?? getenv("APP_ENV") ?: "";
if ($appEnv !== "testing") {
    fwrite(STDERR, "[FAIL-FAST] Lỗi an toàn: Test suite chỉ được phép chạy khi APP_ENV=testing. Hiện tại: '{$appEnv}'." . PHP_EOL);
    exit(1);
}

$config = \JobMarket\Facades\Config::env();
$dbName = strtolower($config["dbname"] ?? "");

if (empty($dbName) || $dbName === "jobmarket" || !str_contains($dbName, "test")) {
    fwrite(STDERR, "[FAIL-FAST] Lỗi an toàn: DB_NAME ('{$dbName}') là cơ sở dữ liệu development/shared. Không được phép chạy test trên database này!" . PHP_EOL);
    exit(1);
}

// Resolve Base URL from test config/env
$baseUrl = rtrim($_ENV["TEST_BASE_URL"] ?? $_ENV["APP_URL"] ?? "in-process", "/");

// Connect to dedicated test database
$db = new PDO(
    "mysql:dbname={$config['dbname']};host={$config['host']};port={$config['port']}",
    $config["user"],
    $config["password"],
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]
);

$kernel = new \JobMarket\Http\Kernel();

/**
 * Dispatch API request either in-process or via HTTP curl depending on config.
 */
function apiCall(string $method, string $url, ?array $body = null): array
{
    global $baseUrl, $kernel;

    if ($baseUrl !== "in-process" && (str_starts_with($baseUrl, "http://") || str_starts_with($baseUrl, "https://"))) {
        $ch = curl_init($baseUrl . $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Accept: application/json",
            "Content-Type: application/json"
        ]);

        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_UNICODE));
        }

        $raw = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [
            "status" => $httpCode,
            "body"   => json_decode((string)$raw, true) ?? $raw
        ];
    }

    // In-process Kernel dispatch (ensures 100% test isolation using DB_NAME from .env.testing)
    $server = [
        "REQUEST_METHOD" => strtoupper($method),
        "REQUEST_URI"    => $url,
        "CONTENT_TYPE"   => "application/json",
        "HTTP_ACCEPT"    => "application/json"
    ];

    $request = new \JobMarket\Http\Request([], $body ?? [], [], [], $server);
    $response = $kernel->handler($request);

    return [
        "status" => $response->getStatusCode(),
        "body"   => $response->getPayload()
    ];
}

echo "================================================================" . PHP_EOL;
echo "   KIỂM THỬ ĐỘC LẬP / HỒI QUY TASK-P0-01 (PUBLIC REGISTRATION)  " . PHP_EOL;
echo "================================================================" . PHP_EOL;
echo " Môi trường : " . $appEnv . PHP_EOL;
echo " Database   : " . $config["dbname"] . " (" . $config["host"] . ":" . $config["port"] . ")" . PHP_EOL;
echo " Dispatcher : " . ($baseUrl === "in-process" ? "In-Process Kernel" : $baseUrl) . PHP_EOL;
echo "================================================================" . PHP_EOL . PHP_EOL;

$passCount = 0;
$totalTests = 8;
$createdEmails = [];

try {
    // -------------------------------------------------------------
    // [TEST 1] POST /register with role=admin -> MUST FAIL (HTTP 422)
    // -------------------------------------------------------------
    echo "[TEST 1] POST /register với role=admin (Cố tình leo thang đặc quyền)" . PHP_EOL;
    $fakeAdminEmail = "attacker_admin_" . uniqid() . "@test.vn";
    $createdEmails[] = $fakeAdminEmail;

    $res1 = apiCall("POST", "/register", [
        "name"     => "Attacker Admin",
        "email"    => $fakeAdminEmail,
        "password" => "Attacker@123",
        "role"     => "admin"
    ]);

    if ($res1["status"] === 422 && isset($res1["body"]["errors"]["role"])) {
        echo "  -> PASS: Bị từ chối chính xác với HTTP 422 Unprocessable Entity." . PHP_EOL;
        echo "     Lỗi: " . json_encode($res1["body"]["errors"]["role"], JSON_UNESCAPED_UNICODE) . PHP_EOL;
        $passCount++;
    } else {
        echo "  -> FAIL: Mong đợi HTTP 422 nhưng nhận HTTP {$res1['status']} - " . json_encode($res1["body"], JSON_UNESCAPED_UNICODE) . PHP_EOL;
    }
    echo PHP_EOL;

    // -------------------------------------------------------------
    // [TEST 2] Database Verification: No admin user exists for fakeAdminEmail
    // -------------------------------------------------------------
    echo "[TEST 2] Xác minh Database ({$config['dbname']}): Không có bản ghi admin nào được tạo trong bảng users" . PHP_EOL;
    $checkAdminStmt = $db->prepare("SELECT id, role FROM users WHERE email = ?");
    $checkAdminStmt->execute([$fakeAdminEmail]);
    $adminRow = $checkAdminStmt->fetch();

    if ($adminRow === false) {
        echo "  -> PASS: Xác nhận tài khoản admin không hề được lưu vào cơ sở dữ liệu." . PHP_EOL;
        $passCount++;
    } else {
        echo "  -> FAIL: Phát hiện tài khoản admin vẫn bị chèn vào DB! ID: " . $adminRow["id"] . PHP_EOL;
    }
    echo PHP_EOL;

    // -------------------------------------------------------------
    // [TEST 3] POST /register with role=student -> MUST SUCCEED (HTTP 201)
    // -------------------------------------------------------------
    echo "[TEST 3] POST /register với role=student (Sinh viên tiêu chuẩn)" . PHP_EOL;
    $studentEmail = "valid_student_" . uniqid() . "@test.vn";
    $createdEmails[] = $studentEmail;

    $res3 = apiCall("POST", "/register", [
        "name"     => "Sinh Viên Chuẩn",
        "email"    => $studentEmail,
        "password" => "Student@123",
        "role"     => "student"
    ]);

    if ($res3["status"] === 201 && ($res3["body"]["success"] ?? false) === true) {
        // Verify role in DB
        $stmt = $db->prepare("SELECT role FROM users WHERE email = ?");
        $stmt->execute([$studentEmail]);
        $dbRole = $stmt->fetchColumn();

        if ($dbRole === "student") {
            echo "  -> PASS: Đăng ký thành công (HTTP 201). Role trong DB: 'student'." . PHP_EOL;
            $passCount++;
        } else {
            echo "  -> FAIL: HTTP 201 nhưng role trong DB là: {$dbRole}" . PHP_EOL;
        }
    } else {
        echo "  -> FAIL: HTTP {$res3['status']} - " . json_encode($res3["body"], JSON_UNESCAPED_UNICODE) . PHP_EOL;
    }
    echo PHP_EOL;

    // -------------------------------------------------------------
    // [TEST 4] POST /register with role=developer -> MUST SUCCEED (HTTP 201) & Map to 'student'
    // -------------------------------------------------------------
    echo "[TEST 4] POST /register với role=developer (Legacy alias sang student)" . PHP_EOL;
    $devEmail = "valid_dev_" . uniqid() . "@test.vn";
    $createdEmails[] = $devEmail;

    $res4 = apiCall("POST", "/register", [
        "name"     => "Developer Alias",
        "email"    => $devEmail,
        "password" => "Student@123",
        "role"     => "developer"
    ]);

    if ($res4["status"] === 201 && ($res4["body"]["success"] ?? false) === true) {
        // Verify role mapping in DB
        $stmt = $db->prepare("SELECT role FROM users WHERE email = ?");
        $stmt->execute([$devEmail]);
        $dbRole = $stmt->fetchColumn();

        if ($dbRole === "student") {
            echo "  -> PASS: Đăng ký thành công (HTTP 201). Role được ánh xạ chính xác thành: 'student'." . PHP_EOL;
            $passCount++;
        } else {
            echo "  -> FAIL: HTTP 201 nhưng role trong DB là: {$dbRole} (Kỳ vọng: student)" . PHP_EOL;
        }
    } else {
        echo "  -> FAIL: HTTP {$res4['status']} - " . json_encode($res4["body"], JSON_UNESCAPED_UNICODE) . PHP_EOL;
    }
    echo PHP_EOL;

    // -------------------------------------------------------------
    // [TEST 5] POST /register with role=company -> MUST SUCCEED (HTTP 201)
    // -------------------------------------------------------------
    echo "[TEST 5] POST /register với role=company (Doanh nghiệp tiêu chuẩn)" . PHP_EOL;
    $compEmail = "valid_comp_" . uniqid() . "@test.vn";
    $createdEmails[] = $compEmail;

    $res5 = apiCall("POST", "/register", [
        "name"     => "Công Ty Chuẩn",
        "email"    => $compEmail,
        "password" => "Company@123",
        "role"     => "company"
    ]);

    if ($res5["status"] === 201 && ($res5["body"]["success"] ?? false) === true) {
        // Verify role in DB
        $stmt = $db->prepare("SELECT role FROM users WHERE email = ?");
        $stmt->execute([$compEmail]);
        $dbRole = $stmt->fetchColumn();

        if ($dbRole === "company") {
            echo "  -> PASS: Đăng ký thành công (HTTP 201). Role trong DB: 'company'." . PHP_EOL;
            $passCount++;
        } else {
            echo "  -> FAIL: HTTP 201 nhưng role trong DB là: {$dbRole}" . PHP_EOL;
        }
    } else {
        echo "  -> FAIL: HTTP {$res5['status']} - " . json_encode($res5["body"], JSON_UNESCAPED_UNICODE) . PHP_EOL;
    }
    echo PHP_EOL;

    // -------------------------------------------------------------
    // [TEST 6] POST /register with role=employer -> MUST SUCCEED (HTTP 201) & Map to 'company'
    // -------------------------------------------------------------
    echo "[TEST 6] POST /register với role=employer (Legacy alias sang company)" . PHP_EOL;
    $empEmail = "valid_emp_" . uniqid() . "@test.vn";
    $createdEmails[] = $empEmail;

    $res6 = apiCall("POST", "/register", [
        "name"     => "Employer Alias",
        "email"    => $empEmail,
        "password" => "Company@123",
        "role"     => "employer"
    ]);

    if ($res6["status"] === 201 && ($res6["body"]["success"] ?? false) === true) {
        // Verify role mapping in DB
        $stmt = $db->prepare("SELECT role FROM users WHERE email = ?");
        $stmt->execute([$empEmail]);
        $dbRole = $stmt->fetchColumn();

        if ($dbRole === "company") {
            echo "  -> PASS: Đăng ký thành công (HTTP 201). Role được ánh xạ chính xác thành: 'company'." . PHP_EOL;
            $passCount++;
        } else {
            echo "  -> FAIL: HTTP 201 nhưng role trong DB là: {$dbRole} (Kỳ vọng: company)" . PHP_EOL;
        }
    } else {
        echo "  -> FAIL: HTTP {$res6['status']} - " . json_encode($res6["body"], JSON_UNESCAPED_UNICODE) . PHP_EOL;
    }
    echo PHP_EOL;

    // -------------------------------------------------------------
    // [TEST 7] POST /register with invalid arbitrary roles -> MUST FAIL (HTTP 422)
    // -------------------------------------------------------------
    echo "[TEST 7] POST /register với các role tùy ý ngoài whitelist (superadmin, root, moderator)" . PHP_EOL;
    $invalidRolesOk = true;
    foreach (["superadmin", "root", "moderator"] as $badRole) {
        $badEmail = "bad_role_{$badRole}_" . uniqid() . "@test.vn";
        $createdEmails[] = $badEmail;

        $badRes = apiCall("POST", "/register", [
            "name"     => "Bad Role User",
            "email"    => $badEmail,
            "password" => "Password@123",
            "role"     => $badRole
        ]);

        if ($badRes["status"] !== 422 || !isset($badRes["body"]["errors"]["role"])) {
            $invalidRolesOk = false;
            echo "  -> FAIL: Role '{$badRole}' không bị chặn 422! HTTP {$badRes['status']}" . PHP_EOL;
            break;
        }
    }

    if ($invalidRolesOk) {
        echo "  -> PASS: Mọi role lạ ('superadmin', 'root', 'moderator') đều bị chặn chính xác với HTTP 422." . PHP_EOL;
        $passCount++;
    }
    echo PHP_EOL;

    // -------------------------------------------------------------
    // [TEST 8] End-to-End Authentication: Verify registered users can log in
    // -------------------------------------------------------------
    echo "[TEST 8] Kiểm tra Đăng nhập thực tế (POST /login) cho các tài khoản vừa đăng ký" . PHP_EOL;
    $loginStudent = apiCall("POST", "/login", ["email" => $studentEmail, "password" => "Student@123"]);
    $loginDev     = apiCall("POST", "/login", ["email" => $devEmail, "password" => "Student@123"]);
    $loginComp    = apiCall("POST", "/login", ["email" => $compEmail, "password" => "Company@123"]);
    $loginEmp     = apiCall("POST", "/login", ["email" => $empEmail, "password" => "Company@123"]);

    $stuRoleOk  = ($loginStudent["body"]["data"]["user"]["role"] ?? "") === "student";
    $devRoleOk  = ($loginDev["body"]["data"]["user"]["role"] ?? "") === "student";
    $compRoleOk = ($loginComp["body"]["data"]["user"]["role"] ?? "") === "company";
    $empRoleOk  = ($loginEmp["body"]["data"]["user"]["role"] ?? "") === "company";

    if ($loginStudent["status"] === 200 && $loginDev["status"] === 200 &&
        $loginComp["status"] === 200 && $loginEmp["status"] === 200 &&
        $stuRoleOk && $devRoleOk && $compRoleOk && $empRoleOk) {
        echo "  -> PASS: Cả 4 tài khoản đăng nhập thành công với đúng vai trò được cấp (student/company)." . PHP_EOL;
        $passCount++;
    } else {
        echo "  -> FAIL: Lỗi đăng nhập hoặc sai vai trò trong payload JWT/user." . PHP_EOL;
    }
    echo PHP_EOL;

} finally {
    // -------------------------------------------------------------
    // Fixture Cleanup: Always executed regardless of test pass/fail
    // -------------------------------------------------------------
    if (!empty($createdEmails) && isset($db)) {
        try {
            $uniqueEmails = array_values(array_unique($createdEmails));
            $placeholders = implode(",", array_fill(0, count($uniqueEmails), "?"));
            $delStmt = $db->prepare("DELETE FROM users WHERE email IN ($placeholders)");
            $delStmt->execute($uniqueEmails);
            $deletedCount = $delStmt->rowCount();
            echo "[CLEANUP] Đã dọn dẹp an toàn: Xóa {$deletedCount} bản ghi test khỏi database '{$config['dbname']}'." . PHP_EOL;
        } catch (Throwable $cleanupEx) {
            fwrite(STDERR, "[CLEANUP ERROR] Lỗi dọn dẹp fixture: " . $cleanupEx->getMessage() . PHP_EOL);
        }
    }
}

echo "================================================================" . PHP_EOL;
echo "   KẾT QUẢ: {$passCount}/{$totalTests} BÀI KIỂM THỬ TASK-P0-01 ĐẠT THÀNH CÔNG " . ($passCount === $totalTests ? "(100% PASS)" : "(FAILED)") . PHP_EOL;
echo "================================================================" . PHP_EOL;

exit($passCount === $totalTests ? 0 : 1);

