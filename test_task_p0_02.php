<?php

/**
 * Isolated Regression & Feature Test Suite for TASK-P0-02:
 * Lock Legacy Company CRUD by Role, Ownership, Anti-Mass Assignment,
 * Hard Delete Protection, and Read-IDOR Privacy Policy.
 *
 * Requirements & Acceptance Criteria:
 * 1. Test Environment Isolation:
 *    - Loads dedicated .env.testing.
 *    - Fails-fast if APP_ENV !== 'testing' or DB_NAME is development/shared.
 *    - Base URL resolved from test configuration/environment.
 * 2. Unauthenticated calls blocked:
 *    - PUT /companies/{id} -> HTTP 401.
 *    - DELETE /companies/{id} -> HTTP 401.
 *    - POST /companies -> HTTP 401.
 * 3. Role enforcement:
 *    - Student PUT/DELETE/POST company -> HTTP 403 Forbidden.
 * 4. Ownership enforcement (IDOR Mutation):
 *    - Company B PUT/DELETE company A -> HTTP 403 Forbidden.
 * 5. Anti-Mass Assignment:
 *    - Client cannot change company owner via PUT /companies/{id}.
 *    - Client cannot spoof user_id on POST /companies (owner always taken from JWT subject).
 * 6. Hard-delete disabled via API:
 *    - DELETE /companies/{id} rejected (HTTP 403), preserving jobs and applications.
 * 7. Read-IDOR & Privacy Policy:
 *    - Student / Employer B reading Employer A -> Sanitized Public DTO (no user_id, contact_person, contact_phone, verification_status, rejection_reason).
 *    - Owner reading own company -> Full Record (includes private fields).
 *    - Admin reading any company -> Full Record (includes private fields).
 *    - Admin GET /companies -> Full list.
 *    - Non-owner / Student reading unverified company -> HTTP 404 Not Found.
 * 8. Integrity Verification:
 *    - Victim's company, job, and application records remain 100% unchanged in DB.
 * 9. Company owner can still update via /company/profile -> HTTP 200.
 * 10. Fixture Cleanup in finally block:
 *    - Deletes all created test records safely.
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

$baseUrl = rtrim($_ENV["TEST_BASE_URL"] ?? $_ENV["APP_URL"] ?? "in-process", "/");

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
 * Dispatch API request with optional Bearer token
 */
function apiCall(string $method, string $url, ?array $body = null, ?string $token = null): array
{
    global $baseUrl, $kernel;

    if ($baseUrl !== "in-process" && (str_starts_with($baseUrl, "http://") || str_starts_with($baseUrl, "https://"))) {
        $ch = curl_init($baseUrl . $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        $headers = [
            "Accept: application/json",
            "Content-Type: application/json"
        ];
        if ($token !== null) {
            $headers[] = "Authorization: Bearer " . $token;
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

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

    // In-process Kernel dispatch
    $server = [
        "REQUEST_METHOD" => strtoupper($method),
        "REQUEST_URI"    => $url,
        "CONTENT_TYPE"   => "application/json",
        "HTTP_ACCEPT"    => "application/json"
    ];

    if ($token !== null) {
        $server["HTTP_AUTHORIZATION"] = "Bearer " . $token;
        $_SERVER["HTTP_AUTHORIZATION"] = "Bearer " . $token;
    } else {
        unset($_SERVER["HTTP_AUTHORIZATION"]);
    }

    $request = new \JobMarket\Http\Request([], $body ?? [], [], [], $server);
    $response = $kernel->handler($request);

    unset($_SERVER["HTTP_AUTHORIZATION"]);

    return [
        "status" => $response->getStatusCode(),
        "body"   => $response->getPayload()
    ];
}

echo "================================================================" . PHP_EOL;
echo "   KIỂM THỬ ĐỘC LẬP / HỒI QUY TASK-P0-02 (COMPANY CRUD & READ)  " . PHP_EOL;
echo "================================================================" . PHP_EOL;
echo " Môi trường : " . $appEnv . PHP_EOL;
echo " Database   : " . $config["dbname"] . " (" . $config["host"] . ":" . $config["port"] . ")" . PHP_EOL;
echo " Dispatcher : " . ($baseUrl === "in-process" ? "In-Process Kernel" : $baseUrl) . PHP_EOL;
echo "================================================================" . PHP_EOL . PHP_EOL;

$passCount = 0;
$totalTests = 23;

// Track all IDs for cleanup
$cleanupUserIds = [];
$cleanupCompanyIds = [];
$cleanupJobIds = [];
$cleanupAppIds = [];

try {
    // -------------------------------------------------------------
    // Set up Test Fixtures
    // -------------------------------------------------------------
    $victimUserId = "u_victim_" . uniqid();
    $victimEmail = "victim_company_" . uniqid() . "@test.vn";
    $cleanupUserIds[] = $victimUserId;

    $victimCompanyId = "comp_victim_" . uniqid();
    $cleanupCompanyIds[] = $victimCompanyId;

    $unverifiedCompanyId = "comp_unver_" . uniqid();
    $cleanupCompanyIds[] = $unverifiedCompanyId;

    $unverifiedOwnerUserId = "u_unver_owner_" . uniqid();
    $cleanupUserIds[] = $unverifiedOwnerUserId;

    $rejectedCompanyId = "comp_rej_" . uniqid();
    $cleanupCompanyIds[] = $rejectedCompanyId;

    $rejectedOwnerUserId = "u_rej_owner_" . uniqid();
    $cleanupUserIds[] = $rejectedOwnerUserId;

    $victimJobId = "job_victim_" . uniqid();
    $cleanupJobIds[] = $victimJobId;

    $studentUserId = "u_student_" . uniqid();
    $studentEmail = "applicant_student_" . uniqid() . "@test.vn";
    $cleanupUserIds[] = $studentUserId;

    $victimAppId = "app_victim_" . uniqid();
    $cleanupAppIds[] = $victimAppId;

    $attackerStudentId = "u_att_stu_" . uniqid();
    $attackerStudentEmail = "attacker_stu_" . uniqid() . "@test.vn";
    $cleanupUserIds[] = $attackerStudentId;

    $attackerCompanyUserId = "u_att_comp_" . uniqid();
    $attackerCompanyEmail = "attacker_comp_" . uniqid() . "@test.vn";
    $cleanupUserIds[] = $attackerCompanyUserId;

    $attackerCompanyId = "comp_att_" . uniqid();
    $cleanupCompanyIds[] = $attackerCompanyId;

    $adminUserId = "u_admin_" . uniqid();
    $adminEmail = "admin_test_" . uniqid() . "@test.vn";
    $cleanupUserIds[] = $adminUserId;

    // A fresh company user without existing company (to test POST /companies)
    $freshCompanyUserId = "u_fresh_comp_" . uniqid();
    $freshCompanyEmail = "fresh_comp_" . uniqid() . "@test.vn";
    $cleanupUserIds[] = $freshCompanyUserId;

    // 1. Insert Users
    $passwordHash = password_hash("Password@123", PASSWORD_BCRYPT);
    $userStmt = $db->prepare("INSERT INTO users (id, name, email, password, role) VALUES (?, ?, ?, ?, ?)");
    $userStmt->execute([$victimUserId, "Victim Company Owner", $victimEmail, $passwordHash, "company"]);
    $userStmt->execute([$unverifiedOwnerUserId, "Unverified Owner", "unver_" . uniqid() . "@test.vn", $passwordHash, "company"]);
    $userStmt->execute([$rejectedOwnerUserId, "Rejected Owner", "rej_" . uniqid() . "@test.vn", $passwordHash, "company"]);
    $userStmt->execute([$studentUserId, "Applicant Student", $studentEmail, $passwordHash, "student"]);
    $userStmt->execute([$attackerStudentId, "Attacker Student", $attackerStudentEmail, $passwordHash, "student"]);
    $userStmt->execute([$attackerCompanyUserId, "Attacker Company Owner", $attackerCompanyEmail, $passwordHash, "company"]);
    $userStmt->execute([$adminUserId, "Admin Tester", $adminEmail, $passwordHash, "admin"]);
    $userStmt->execute([$freshCompanyUserId, "Fresh Company Owner", $freshCompanyEmail, $passwordHash, "company"]);

    // 2. Insert Companies (Victim has private contact and verified status)
    $compStmt = $db->prepare(
        "INSERT INTO companies (id, user_id, name, description, contact_person, contact_phone, address, city, district, website, verification_status, rejection_reason) 
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $compStmt->execute([
        $victimCompanyId, $victimUserId, "Victim Corporation", "Original Description",
        "Private Contact A", "0901234567", "123 Le Loi", "HCM", "Quan 1",
        "https://victim.vn", "verified", null
    ]);
    $compStmt->execute([
        $attackerCompanyId, $attackerCompanyUserId, "Attacker Corporation", "Attacker Description",
        "Private Contact B", "0909999999", "456 Nguyen Hue", "HCM", "Quan 1",
        "https://attacker.vn", "verified", null
    ]);
    // Unverified company for testing visibility
    $compStmt->execute([
        $unverifiedCompanyId, $unverifiedOwnerUserId, "Unverified Subsidiary", "Pending moderation",
        "Private Contact C", "0908888888", "789 Tran Phu", "HCM", "Quan 5",
        "https://unverified.vn", "pending", null
    ]);
    // Rejected company for testing visibility
    $compStmt->execute([
        $rejectedCompanyId, $rejectedOwnerUserId, "Rejected Subsidiary", "Spam profile",
        "Private Contact D", "0907777777", "101 Hai Ba Trung", "HCM", "Quan 1",
        "https://rejected.vn", "rejected", "Spam profile"
    ]);

    // 3. Insert Job belonging to Victim Company
    $jobStmt = $db->prepare(
        "INSERT INTO jobs (id, company_id, title, description, requirements, benefits, status) 
         VALUES (?, ?, ?, ?, ?, ?, ?)"
    );
    $jobStmt->execute([$victimJobId, $victimCompanyId, "Part-time Barista", "Making coffee", "Friendly", "Tips", "published"]);

    // 4. Insert Application for Victim Job
    $appStmt = $db->prepare(
        "INSERT INTO applications (id, job_id, developer_id, cover_letter, status) 
         VALUES (?, ?, ?, ?, ?)"
    );
    $appStmt->execute([$victimAppId, $victimJobId, $studentUserId, "I love coffee", "pending"]);

    // 5. Generate Tokens
    $tokenStudent      = \JobMarket\Facades\JWT::encode(["id" => $attackerStudentId, "email" => $attackerStudentEmail, "role" => "student"]);
    $tokenAttackerComp = \JobMarket\Facades\JWT::encode(["id" => $attackerCompanyUserId, "email" => $attackerCompanyEmail, "role" => "company"]);
    $tokenVictimComp   = \JobMarket\Facades\JWT::encode(["id" => $victimUserId, "email" => $victimEmail, "role" => "company"]);
    $tokenAdmin        = \JobMarket\Facades\JWT::encode(["id" => $adminUserId, "email" => $adminEmail, "role" => "admin"]);
    $tokenFreshComp    = \JobMarket\Facades\JWT::encode(["id" => $freshCompanyUserId, "email" => $freshCompanyEmail, "role" => "company"]);

    // -------------------------------------------------------------
    // [TEST 1] Guest attempts PUT /companies/{victimCompanyId} -> 401
    // -------------------------------------------------------------
    echo "[TEST 1] Khách vãng lai (Guest) cố sửa công ty nạn nhân (PUT /companies/{id})" . PHP_EOL;
    $res1 = apiCall("PUT", "/companies/{$victimCompanyId}", ["name" => "Hacked By Guest"]);
    if ($res1["status"] === 401) {
        echo "  -> PASS: Bị từ chối chính xác với HTTP 401 Unauthorized." . PHP_EOL;
        $passCount++;
    } else {
        echo "  -> FAIL: Mong đợi HTTP 401 nhưng nhận HTTP {$res1['status']}" . PHP_EOL;
    }
    echo PHP_EOL;

    // -------------------------------------------------------------
    // [TEST 2] Guest attempts DELETE /companies/{victimCompanyId} -> 401
    // -------------------------------------------------------------
    echo "[TEST 2] Khách vãng lai (Guest) cố xóa công ty nạn nhân (DELETE /companies/{id})" . PHP_EOL;
    $res2 = apiCall("DELETE", "/companies/{$victimCompanyId}");
    if ($res2["status"] === 401) {
        echo "  -> PASS: Bị từ chối chính xác với HTTP 401 Unauthorized." . PHP_EOL;
        $passCount++;
    } else {
        echo "  -> FAIL: Mong đợi HTTP 401 nhưng nhận HTTP {$res2['status']}" . PHP_EOL;
    }
    echo PHP_EOL;

    // -------------------------------------------------------------
    // [TEST 3] Guest attempts POST /companies -> 401
    // -------------------------------------------------------------
    echo "[TEST 3] Khách vãng lai (Guest) không có token gọi POST /companies" . PHP_EOL;
    $res3 = apiCall("POST", "/companies", ["name" => "Unauthorized Company"]);
    if ($res3["status"] === 401) {
        echo "  -> PASS: Bị từ chối chính xác với HTTP 401 Unauthorized." . PHP_EOL;
        $passCount++;
    } else {
        echo "  -> FAIL: Mong đợi HTTP 401 nhưng nhận HTTP {$res3['status']}" . PHP_EOL;
    }
    echo PHP_EOL;

    // -------------------------------------------------------------
    // [TEST 4] Student attempts PUT /companies/{victimCompanyId} -> 403
    // -------------------------------------------------------------
    echo "[TEST 4] Sinh viên (Student) cố sửa công ty nạn nhân (PUT /companies/{id})" . PHP_EOL;
    $res4 = apiCall("PUT", "/companies/{$victimCompanyId}", ["name" => "Hacked By Student"], $tokenStudent);
    if ($res4["status"] === 403) {
        echo "  -> PASS: Bị từ chối chính xác với HTTP 403 Forbidden." . PHP_EOL;
        $passCount++;
    } else {
        echo "  -> FAIL: Mong đợi HTTP 403 nhưng nhận HTTP {$res4['status']}" . PHP_EOL;
    }
    echo PHP_EOL;

    // -------------------------------------------------------------
    // [TEST 5] Student attempts DELETE /companies/{victimCompanyId} -> 403
    // -------------------------------------------------------------
    echo "[TEST 5] Sinh viên (Student) cố xóa công ty nạn nhân (DELETE /companies/{id})" . PHP_EOL;
    $res5 = apiCall("DELETE", "/companies/{$victimCompanyId}", null, $tokenStudent);
    if ($res5["status"] === 403) {
        echo "  -> PASS: Bị từ chối chính xác với HTTP 403 Forbidden." . PHP_EOL;
        $passCount++;
    } else {
        echo "  -> FAIL: Mong đợi HTTP 403 nhưng nhận HTTP {$res5['status']}" . PHP_EOL;
    }
    echo PHP_EOL;

    // -------------------------------------------------------------
    // [TEST 6] Student attempts POST /companies -> 403
    // -------------------------------------------------------------
    echo "[TEST 6] Sinh viên (Student) cố tạo hồ sơ công ty (POST /companies)" . PHP_EOL;
    $res6 = apiCall("POST", "/companies", ["name" => "Student Fake Company"], $tokenStudent);
    if ($res6["status"] === 403) {
        echo "  -> PASS: Bị từ chối chính xác với HTTP 403 Forbidden." . PHP_EOL;
        $passCount++;
    } else {
        echo "  -> FAIL: Mong đợi HTTP 403 nhưng nhận HTTP {$res6['status']}" . PHP_EOL;
    }
    echo PHP_EOL;

    // -------------------------------------------------------------
    // [TEST 7] Attacker Company attempts PUT /companies/{victimCompanyId} -> 403
    // -------------------------------------------------------------
    echo "[TEST 7] Công ty khác (Company B) cố sửa công ty nạn nhân (PUT /companies/{id})" . PHP_EOL;
    $res7 = apiCall("PUT", "/companies/{$victimCompanyId}", ["name" => "Hacked By Company B"], $tokenAttackerComp);
    if ($res7["status"] === 403) {
        echo "  -> PASS: Bị từ chối chính xác với HTTP 403 Forbidden (Cross-company IDOR blocked)." . PHP_EOL;
        $passCount++;
    } else {
        echo "  -> FAIL: Mong đợi HTTP 403 nhưng nhận HTTP {$res7['status']}" . PHP_EOL;
    }
    echo PHP_EOL;

    // -------------------------------------------------------------
    // [TEST 8] Attacker Company attempts DELETE /companies/{victimCompanyId} -> 403
    // -------------------------------------------------------------
    echo "[TEST 8] Công ty khác (Company B) cố xóa công ty nạn nhân (DELETE /companies/{id})" . PHP_EOL;
    $res8 = apiCall("DELETE", "/companies/{$victimCompanyId}", null, $tokenAttackerComp);
    if ($res8["status"] === 403) {
        echo "  -> PASS: Bị từ chối chính xác với HTTP 403 Forbidden (Cross-company delete blocked)." . PHP_EOL;
        $passCount++;
    } else {
        echo "  -> FAIL: Mong đợi HTTP 403 nhưng nhận HTTP {$res8['status']}" . PHP_EOL;
    }
    echo PHP_EOL;

    // -------------------------------------------------------------
    // [TEST 9] Attacker attempts to hijack ownership via PUT /companies/{victimCompanyId}
    // -------------------------------------------------------------
    echo "[TEST 9] Attacker gửi payload chiếm quyền sở hữu qua PUT (user_id = attacker_id)" . PHP_EOL;
    $res9 = apiCall("PUT", "/companies/{$victimCompanyId}", [
        "name"    => "Takeover Corp",
        "user_id" => $attackerCompanyUserId
    ], $tokenAttackerComp);

    $checkOwnerStmt = $db->prepare("SELECT user_id, name FROM companies WHERE id = ?");
    $checkOwnerStmt->execute([$victimCompanyId]);
    $currentComp = $checkOwnerStmt->fetch();

    if ($res9["status"] === 403 && $currentComp["user_id"] === $victimUserId && $currentComp["name"] === "Victim Corporation") {
        echo "  -> PASS: Bị từ chối HTTP 403 và cơ sở dữ liệu xác nhận chủ sở hữu (user_id) không bị đổi." . PHP_EOL;
        $passCount++;
    } else {
        echo "  -> FAIL: Status: {$res9['status']}, Current Owner: {$currentComp['user_id']}" . PHP_EOL;
    }
    echo PHP_EOL;

    // -------------------------------------------------------------
    // [TEST 10] Company Owner attempts to change user_id via PUT /companies/{myId}
    // -------------------------------------------------------------
    echo "[TEST 10] Chủ sở hữu cập nhật công ty và cố ý truyền user_id khác (Anti-mass assignment)" . PHP_EOL;
    $res10 = apiCall("PUT", "/companies/{$victimCompanyId}", [
        "name"        => "Victim Corp Updated",
        "user_id"     => $attackerCompanyUserId, // Tampering attempt
        "description" => "Legit updated description"
    ], $tokenVictimComp);

    $checkOwnerStmt->execute([$victimCompanyId]);
    $ownerAfterPut = $checkOwnerStmt->fetch();

    if ($res10["status"] === 200 && $ownerAfterPut["user_id"] === $victimUserId && $ownerAfterPut["name"] === "Victim Corp Updated") {
        echo "  -> PASS: Cập nhật thành công (HTTP 200). Trường user_id tuyệt đối không bị thay đổi trong DB." . PHP_EOL;
        $passCount++;
    } else {
        echo "  -> FAIL: Status: {$res10['status']}, Owner in DB: {$ownerAfterPut['user_id']}" . PHP_EOL;
    }
    echo PHP_EOL;

    // -------------------------------------------------------------
    // [TEST 11] Company Owner attempts DELETE /companies/{myId} -> Must be rejected (No hard-delete)
    // -------------------------------------------------------------
    echo "[TEST 11] Chủ sở hữu cố gọi DELETE /companies/{myId} (Không cho hard-delete qua API)" . PHP_EOL;
    $res11 = apiCall("DELETE", "/companies/{$victimCompanyId}", null, $tokenVictimComp);
    if ($res11["status"] === 403) {
        echo "  -> PASS: Bị từ chối chính xác với HTTP 403 (Không cho hard-delete làm cascade mất job/app)." . PHP_EOL;
        $passCount++;
    } else {
        echo "  -> FAIL: Mong đợi HTTP 403 nhưng nhận HTTP {$res11['status']}" . PHP_EOL;
    }
    echo PHP_EOL;

    // -------------------------------------------------------------
    // [TEST 12] Company account POST /companies with spoofed user_id
    // -------------------------------------------------------------
    echo "[TEST 12] Tài khoản Company tạo công ty mới qua POST /companies với user_id mạo danh" . PHP_EOL;
    $spoofedPostName = "Spoof Proof Company " . uniqid();
    $res12 = apiCall("POST", "/companies", [
        "name"        => $spoofedPostName,
        "description" => "Testing user_id spoof protection",
        "user_id"     => $victimUserId // Attacker tries to create company assigned to victim
    ], $tokenFreshComp);

    $findSpoofedStmt = $db->prepare("SELECT id, user_id FROM companies WHERE name = ?");
    $findSpoofedStmt->execute([$spoofedPostName]);
    $spoofedRow = $findSpoofedStmt->fetch();
    if ($spoofedRow) {
        $cleanupCompanyIds[] = $spoofedRow["id"];
    }

    if ($res12["status"] === 201 && $spoofedRow && $spoofedRow["user_id"] === $freshCompanyUserId && $spoofedRow["user_id"] !== $victimUserId) {
        echo "  -> PASS: Tạo thành công (HTTP 201). Backend tự động bỏ qua user_id client gửi và gán đúng JWT subject." . PHP_EOL;
        $passCount++;
    } else {
        echo "  -> FAIL: Status: {$res12['status']}, DB user_id: " . ($spoofedRow['user_id'] ?? 'none') . PHP_EOL;
    }
    echo PHP_EOL;

    // -------------------------------------------------------------
    // [TEST 13] Read Policy: Student đọc company của Employer khác (GET /companies/{id})
    // -------------------------------------------------------------
    echo "[TEST 13] Quyền riêng tư: Sinh viên đọc công ty của Employer (GET /companies/{victimCompanyId})" . PHP_EOL;
    $res13 = apiCall("GET", "/companies/{$victimCompanyId}", null, $tokenStudent);
    $data13 = $res13["body"]["data"] ?? [];

    $noUserId13         = !isset($data13["user_id"]);
    $noContactPerson13  = !isset($data13["contact_person"]);
    $noContactPhone13   = !isset($data13["contact_phone"]);
    $noVerification13   = !isset($data13["verification_status"]);
    $noRejectionReason13= !isset($data13["rejection_reason"]);
    $hasPublicName13    = !empty($data13["name"]);

    if ($res13["status"] === 200 && $hasPublicName13 && $noUserId13 && $noContactPerson13 && $noContactPhone13 && $noVerification13 && $noRejectionReason13) {
        echo "  -> PASS: Trả về DTO công khai (HTTP 200). Đã lọc sạch 100% trường riêng tư (user_id, contact, phone, verification)." . PHP_EOL;
        $passCount++;
    } else {
        echo "  -> FAIL: Status: {$res13['status']}, Dữ liệu trả về bị rò rỉ: " . json_encode($data13, JSON_UNESCAPED_UNICODE) . PHP_EOL;
    }
    echo PHP_EOL;

    // -------------------------------------------------------------
    // [TEST 14] Read Policy: Employer A đọc company của Employer B (GET /companies/{id})
    // -------------------------------------------------------------
    echo "[TEST 14] Quyền riêng tư: Employer A đọc công ty của Employer B (GET /companies/{victimCompanyId})" . PHP_EOL;
    $res14 = apiCall("GET", "/companies/{$victimCompanyId}", null, $tokenAttackerComp);
    $data14 = $res14["body"]["data"] ?? [];

    $noUserId14         = !isset($data14["user_id"]);
    $noContactPerson14  = !isset($data14["contact_person"]);
    $noContactPhone14   = !isset($data14["contact_phone"]);
    $noVerification14   = !isset($data14["verification_status"]);

    if ($res14["status"] === 200 && !empty($data14["name"]) && $noUserId14 && $noContactPerson14 && $noContactPhone14 && $noVerification14) {
        echo "  -> PASS: Employer A chỉ nhận DTO công khai của Employer B. Không thấy bất kỳ thông tin nội bộ nào." . PHP_EOL;
        $passCount++;
    } else {
        echo "  -> FAIL: Dữ liệu rò rỉ sang Employer khác: " . json_encode($data14, JSON_UNESCAPED_UNICODE) . PHP_EOL;
    }
    echo PHP_EOL;

    // -------------------------------------------------------------
    // [TEST 15] Read Policy: Owner đọc company của chính mình (GET /companies/{id})
    // -------------------------------------------------------------
    echo "[TEST 15] Quyền riêng tư: Chủ sở hữu đọc công ty của chính mình (GET /companies/{victimCompanyId})" . PHP_EOL;
    $res15 = apiCall("GET", "/companies/{$victimCompanyId}", null, $tokenVictimComp);
    $data15 = $res15["body"]["data"] ?? [];

    $hasUserId15         = isset($data15["user_id"]) && $data15["user_id"] === $victimUserId;
    $hasContactPerson15  = isset($data15["contact_person"]) && $data15["contact_person"] === "Private Contact A";
    $hasContactPhone15   = isset($data15["contact_phone"]) && $data15["contact_phone"] === "0901234567";
    $hasVerification15   = isset($data15["verification_status"]) && $data15["verification_status"] === "verified";

    if ($res15["status"] === 200 && $hasUserId15 && $hasContactPerson15 && $hasContactPhone15 && $hasVerification15) {
        echo "  -> PASS: Chủ sở hữu nhận đầy đủ toàn bộ bản ghi công ty bao gồm thông tin liên hệ và trạng thái xác thực." . PHP_EOL;
        $passCount++;
    } else {
        echo "  -> FAIL: Chủ sở hữu không nhận được đầy đủ dữ liệu riêng: " . json_encode($data15, JSON_UNESCAPED_UNICODE) . PHP_EOL;
    }
    echo PHP_EOL;

    // -------------------------------------------------------------
    // [TEST 16] Read Policy: Admin đọc company bất kỳ (GET /companies/{id})
    // -------------------------------------------------------------
    echo "[TEST 16] Quyền riêng tư: Quản trị viên (Admin) đọc công ty bất kỳ (GET /companies/{victimCompanyId})" . PHP_EOL;
    $res16 = apiCall("GET", "/companies/{$victimCompanyId}", null, $tokenAdmin);
    $data16 = $res16["body"]["data"] ?? [];

    $adminHasUserId16        = isset($data16["user_id"]);
    $adminHasContactPerson16 = isset($data16["contact_person"]);
    $adminHasVerification16  = isset($data16["verification_status"]);

    if ($res16["status"] === 200 && $adminHasUserId16 && $adminHasContactPerson16 && $adminHasVerification16) {
        echo "  -> PASS: Quản trị viên nhận đầy đủ bản ghi chi tiết phục vụ kiểm duyệt và quản trị." . PHP_EOL;
        $passCount++;
    } else {
        echo "  -> FAIL: Admin không nhận được dữ liệu quản trị: " . json_encode($data16, JSON_UNESCAPED_UNICODE) . PHP_EOL;
    }
    echo PHP_EOL;

    // -------------------------------------------------------------
    // [TEST 17] Read Policy: Admin xem toàn bộ danh sách công ty (GET /companies)
    // -------------------------------------------------------------
    echo "[TEST 17] Quản trị viên (Admin) xem toàn bộ danh sách công ty (GET /companies)" . PHP_EOL;
    $res17 = apiCall("GET", "/companies", null, $tokenAdmin);
    $list17 = $res17["body"]["data"] ?? [];

    $unverifiedFoundInAdmin = false;
    $rejectedFoundInAdmin = false;
    foreach ($list17 as $item) {
        if (($item["id"] ?? "") === $unverifiedCompanyId) {
            $unverifiedFoundInAdmin = true;
        }
        if (($item["id"] ?? "") === $rejectedCompanyId) {
            $rejectedFoundInAdmin = true;
        }
    }

    if ($res17["status"] === 200 && $unverifiedFoundInAdmin && $rejectedFoundInAdmin) {
        echo "  -> PASS: Admin xem được toàn bộ công ty (bao gồm cả công ty chưa duyệt 'pending' và bị từ chối 'rejected')." . PHP_EOL;
        $passCount++;
    } else {
        echo "  -> FAIL: Admin không thấy công ty chưa duyệt / từ chối trong danh sách tổng hợp." . PHP_EOL;
    }
    echo PHP_EOL;

    // -------------------------------------------------------------
    // [TEST 18] Read Policy: Sinh viên (Student non-admin) xem danh sách công ty (GET /companies)
    // -------------------------------------------------------------
    echo "[TEST 18] Sinh viên (Student non-admin) xem danh sách công ty (GET /companies)" . PHP_EOL;
    $res18 = apiCall("GET", "/companies", null, $tokenStudent);
    $list18 = $res18["body"]["data"] ?? [];

    $studentFoundVerified = false;
    $studentFoundUnverified = false;
    $studentFoundRejected = false;
    $studentHasPrivateField = false;
    $studentPrivateKeysFound = [];

    $privateFieldsToCheck = [
        "user_id", "contact_person", "contact_phone", "phone", 
        "verification_status", "rejection_reason", "password", "email"
    ];

    foreach ($list18 as $item) {
        if (($item["id"] ?? "") === $victimCompanyId) {
            $studentFoundVerified = true;
        }
        if (($item["id"] ?? "") === $unverifiedCompanyId) {
            $studentFoundUnverified = true;
        }
        if (($item["id"] ?? "") === $rejectedCompanyId) {
            $studentFoundRejected = true;
        }
        foreach ($privateFieldsToCheck as $f) {
            if (array_key_exists($f, $item)) {
                $studentHasPrivateField = true;
                $studentPrivateKeysFound[] = $f;
            }
        }
    }

    if ($res18["status"] === 200 && is_array($list18) && !empty($list18) && $studentFoundVerified && !$studentFoundUnverified && !$studentFoundRejected && !$studentHasPrivateField) {
        echo "  -> PASS: Trả về HTTP 200. Danh sách chỉ chứa công ty verified. Công ty unverified/rejected bị ẩn hoàn toàn. DTO đã sanitize không lộ bất kỳ trường riêng tư nào." . PHP_EOL;
        $passCount++;
    } else {
        echo "  -> FAIL: Status: {$res18['status']}, VerifiedFound: " . ($studentFoundVerified ? 'true' : 'false') 
            . ", UnverifiedFound: " . ($studentFoundUnverified ? 'true' : 'false') 
            . ", RejectedFound: " . ($studentFoundRejected ? 'true' : 'false') 
            . ", LeakedKeys: " . implode(',', array_unique($studentPrivateKeysFound)) . PHP_EOL;
    }
    echo PHP_EOL;

    // -------------------------------------------------------------
    // [TEST 19] Read Policy: Nhà tuyển dụng khác (Employer non-admin) xem danh sách công ty (GET /companies)
    // -------------------------------------------------------------
    echo "[TEST 19] Nhà tuyển dụng khác (Employer non-admin) xem danh sách công ty (GET /companies)" . PHP_EOL;
    $res19 = apiCall("GET", "/companies", null, $tokenAttackerComp);
    $list19 = $res19["body"]["data"] ?? [];

    $employerFoundVerified = false;
    $employerFoundUnverified = false;
    $employerFoundRejected = false;
    $employerHasPrivateField = false;
    $employerPrivateKeysFound = [];

    foreach ($list19 as $item) {
        if (($item["id"] ?? "") === $victimCompanyId) {
            $employerFoundVerified = true;
        }
        if (($item["id"] ?? "") === $unverifiedCompanyId) {
            $employerFoundUnverified = true;
        }
        if (($item["id"] ?? "") === $rejectedCompanyId) {
            $employerFoundRejected = true;
        }
        foreach ($privateFieldsToCheck as $f) {
            if (array_key_exists($f, $item)) {
                $employerHasPrivateField = true;
                $employerPrivateKeysFound[] = $f;
            }
        }
    }

    if ($res19["status"] === 200 && is_array($list19) && !empty($list19) && $employerFoundVerified && !$employerFoundUnverified && !$employerFoundRejected && !$employerHasPrivateField) {
        echo "  -> PASS: Employer non-admin chỉ nhận DTO công khai đã verified. Không lộ company chưa duyệt hoặc thông tin nội bộ của công ty khác." . PHP_EOL;
        $passCount++;
    } else {
        echo "  -> FAIL: Status: {$res19['status']}, VerifiedFound: " . ($employerFoundVerified ? 'true' : 'false') 
            . ", UnverifiedFound: " . ($employerFoundUnverified ? 'true' : 'false') 
            . ", RejectedFound: " . ($employerFoundRejected ? 'true' : 'false') 
            . ", LeakedKeys: " . implode(',', array_unique($employerPrivateKeysFound)) . PHP_EOL;
    }
    echo PHP_EOL;

    // -------------------------------------------------------------
    // [TEST 20] Read Policy: Student/Non-owner đọc công ty chưa duyệt (GET /companies/{unverifiedCompanyId}) -> 404
    // -------------------------------------------------------------
    echo "[TEST 20] Sinh viên / Người khác cố truy cập công ty chưa duyệt (GET /companies/{unverifiedCompanyId})" . PHP_EOL;
    $res20 = apiCall("GET", "/companies/{$unverifiedCompanyId}", null, $tokenStudent);
    if ($res20["status"] === 404) {
        echo "  -> PASS: Bị chặn chính xác với HTTP 404 Not Found (Ẩn hoàn toàn công ty chưa duyệt khỏi public)." . PHP_EOL;
        $passCount++;
    } else {
        echo "  -> FAIL: Mong đợi HTTP 404 nhưng nhận HTTP {$res20['status']}" . PHP_EOL;
    }
    echo PHP_EOL;

    // -------------------------------------------------------------
    // [TEST 21] Read Policy: Student/Non-owner đọc công ty bị từ chối (GET /companies/{rejectedCompanyId}) -> 404
    // -------------------------------------------------------------
    echo "[TEST 21] Sinh viên / Người khác cố truy cập công ty bị từ chối (GET /companies/{rejectedCompanyId})" . PHP_EOL;
    $res21 = apiCall("GET", "/companies/{$rejectedCompanyId}", null, $tokenStudent);
    if ($res21["status"] === 404) {
        echo "  -> PASS: Bị chặn chính xác với HTTP 404 Not Found (Ẩn hoàn toàn công ty bị từ chối khỏi public)." . PHP_EOL;
        $passCount++;
    } else {
        echo "  -> FAIL: Mong đợi HTTP 404 nhưng nhận HTTP {$res21['status']}" . PHP_EOL;
    }
    echo PHP_EOL;

    // -------------------------------------------------------------
    // [TEST 22] Integrity Check: Victim Company, Job, and Application are 100% Intact
    // -------------------------------------------------------------
    echo "[TEST 22] Kiểm tra toàn vẹn dữ liệu: Company, Job và Application của nạn nhân vẫn nguyên vẹn" . PHP_EOL;
    $compExists = $db->query("SELECT COUNT(*) FROM companies WHERE id = '{$victimCompanyId}'")->fetchColumn();
    $jobExists  = $db->query("SELECT COUNT(*) FROM jobs WHERE id = '{$victimJobId}'")->fetchColumn();
    $appExists  = $db->query("SELECT COUNT(*) FROM applications WHERE id = '{$victimAppId}'")->fetchColumn();

    if ((int)$compExists === 1 && (int)$jobExists === 1 && (int)$appExists === 1) {
        echo "  -> PASS: Toàn bộ dữ liệu (Company: 1, Job: 1, Application: 1) vẫn an toàn, không bị cascade xóa." . PHP_EOL;
        $passCount++;
    } else {
        echo "  -> FAIL: Dữ liệu bị thất thoát! Comp: {$compExists}, Job: {$jobExists}, App: {$appExists}" . PHP_EOL;
    }
    echo PHP_EOL;

    // -------------------------------------------------------------
    // [TEST 23] Legitimate Company Owner can still update via PUT /company/profile
    // -------------------------------------------------------------
    echo "[TEST 23] Chủ sở hữu hợp lệ cập nhật thông tin qua PUT /company/profile" . PHP_EOL;
    $res23 = apiCall("PUT", "/company/profile", [
        "name"        => "Victim Corp Official",
        "description" => "Updated via /company/profile",
        "address"     => "789 Tran Hung Dao",
        "city"        => "Ha Noi",
        "district"    => "Hoan Kiem"
    ], $tokenVictimComp);

    $checkProfileStmt = $db->prepare("SELECT name, city FROM companies WHERE id = ?");
    $checkProfileStmt->execute([$victimCompanyId]);
    $profRow = $checkProfileStmt->fetch();

    if ($res23["status"] === 200 && ($res23["body"]["success"] ?? false) === true && $profRow["name"] === "Victim Corp Official") {
        echo "  -> PASS: Cập nhật qua /company/profile thành công (HTTP 200). Dữ liệu DB đồng bộ chính xác." . PHP_EOL;
        $passCount++;
    } else {
        echo "  -> FAIL: HTTP {$res23['status']} - " . json_encode($res23["body"], JSON_UNESCAPED_UNICODE) . PHP_EOL;
    }
    echo PHP_EOL;

} finally {
    // -------------------------------------------------------------
    // Fixture Cleanup: Always executed regardless of test pass/fail
    // -------------------------------------------------------------
    try {
        if (!empty($cleanupAppIds)) {
            $ph = implode(",", array_fill(0, count($cleanupAppIds), "?"));
            $db->prepare("DELETE FROM applications WHERE id IN ($ph)")->execute($cleanupAppIds);
        }
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
            $db->prepare("DELETE FROM users WHERE id IN ($ph)")->execute($cleanupUserIds);
        }
        echo "[CLEANUP] Đã dọn dẹp sạch sẽ toàn bộ fixtures test khỏi database '{$config['dbname']}'." . PHP_EOL;
    } catch (Throwable $e) {
        fwrite(STDERR, "[CLEANUP ERROR] Lỗi dọn dẹp test fixture: " . $e->getMessage() . PHP_EOL);
    }
}

echo "================================================================" . PHP_EOL;
echo "   KẾT QUẢ: {$passCount}/{$totalTests} BÀI KIỂM THỬ TASK-P0-02 ĐẠT THÀNH CÔNG " . ($passCount === $totalTests ? "(100% PASS)" : "(FAILED)") . PHP_EOL;
echo "================================================================" . PHP_EOL;

exit($passCount === $totalTests ? 0 : 1);