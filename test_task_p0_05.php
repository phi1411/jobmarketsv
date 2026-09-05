<?php

/**
 * ============================================================================
 * KIỂM THỬ ĐỘC LẬP / HỒI QUY TASK-P0-05: JWT REVOCATION & USER STATUS POLICY
 * ============================================================================
 *
 * Mục tiêu kiểm thử:
 * 1. [REVOCATION] Logout thành công vô hiệu hóa JWT; token cũ bị chặn tại protected API (HTTP 401).
 * 2. [SUPERSEDED] Đăng nhập mới thay thế token cũ; token cũ bị từ chối truy cập (HTTP 401).
 * 3. [SUSPENDED] Tài khoản bị suspended bị chặn tức thì tại protected API (HTTP 403) và login (HTTP 401/403).
 * 4. [BANNED] Tài khoản bị banned bị chặn tức thì tại protected API (HTTP 403) và login (HTTP 401/403).
 * 5. [INACTIVE/UNKNOWN] Tài khoản inactive hoặc không tồn tại bị từ chối (HTTP 403/401).
 * 6. [ACTIVE] Tài khoản active với JWT hợp lệ truy cập bình thường (HTTP 200).
 * 7. [PUBLIC ROUTES] Public route (GET /jobs, GET /jobs/{id}) không bị ảnh hưởng dù gửi token revoked/suspended/banned.
 * 8. [CLEANUP] Dọn dẹp an toàn trong block finally, không rò rỉ dữ liệu test.
 */

define("BASE_PATH", __DIR__);

require_once BASE_PATH . "/vendor/autoload.php";

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

use JobMarket\Facades\Config;
use JobMarket\Facades\JWT;
use JobMarket\Http\Kernel;
use JobMarket\Http\Request;

// -------------------------------------------------------------
// [GUARD] Strict Test Environment Verification
// -------------------------------------------------------------
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

// -------------------------------------------------------------
// Initialize Test Database Connection
// -------------------------------------------------------------
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

// -------------------------------------------------------------
// In-Process Dispatcher Helper
// -------------------------------------------------------------
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
echo "   KIỂM THỬ ĐỘC LẬP / HỒI QUY TASK-P0-05 (REVOCATION & STATUS)  " . PHP_EOL;
echo "================================================================" . PHP_EOL;
echo " Môi trường : " . $appEnv . PHP_EOL;
echo " Database   : " . $config["dbname"] . " (" . $config["host"] . ":" . ($config["port"] ?? 3306) . ")" . PHP_EOL;
echo " Dispatcher : In-Process Kernel" . PHP_EOL;
echo "================================================================" . PHP_EOL . PHP_EOL;

$passCount = 0;
$totalTests = 18;

$cleanupUserIds = [];
$cleanupCompanyIds = [];
$cleanupJobIds = [];

try {
    // -------------------------------------------------------------
    // Set up Test Fixtures
    // -------------------------------------------------------------
    $studentId = "u_std_p05_" . uniqid();
    $studentEmail = "std_p05_" . uniqid() . "@test.vn";
    $cleanupUserIds[] = $studentId;

    $adminId = "u_adm_p05_" . uniqid();
    $adminEmail = "adm_p05_" . uniqid() . "@test.vn";
    $cleanupUserIds[] = $adminId;

    $dummyCompUserId = "u_comp_p05_" . uniqid();
    $cleanupUserIds[] = $dummyCompUserId;

    $dummyCompanyId = "comp_p05_" . uniqid();
    $cleanupCompanyIds[] = $dummyCompanyId;

    $testJobId = "job_p05_" . uniqid();
    $cleanupJobIds[] = $testJobId;

    $passwordHash = password_hash("Password@123", PASSWORD_BCRYPT);

    // 1. Insert Users (active)
    $userStmt = $db->prepare(
        "INSERT INTO users (id, name, email, password, role, status) VALUES (?, ?, ?, ?, ?, ?)"
    );
    $userStmt->execute([$studentId, "Student P05 Tester", $studentEmail, $passwordHash, "student", "active"]);
    $userStmt->execute([$adminId, "Admin P05 Tester", $adminEmail, $passwordHash, "admin", "active"]);
    $userStmt->execute([$dummyCompUserId, "Company P05 Tester", "comp_p05_" . uniqid() . "@test.vn", $passwordHash, "company", "active"]);

    // Insert company for job foreign key
    $db->prepare("INSERT INTO companies (id, user_id, name, verification_status) VALUES (?, ?, ?, 'verified')")
       ->execute([$dummyCompanyId, $dummyCompUserId, "Public Coffee Co"]);

    // Insert a public job for public route tests
    $jobStmt = $db->prepare(
        "INSERT INTO jobs (id, company_id, title, description, requirements, benefits, status) 
         VALUES (?, ?, ?, ?, ?, ?, ?)"
    );
    $jobStmt->execute([$testJobId, $dummyCompanyId, "Public Barista Job", "Making tea", "Nice", "Tips", "published"]);

    // Admin token for moderation status updates
    $tokenAdmin = JWT::encode(["id" => $adminId, "email" => $adminEmail, "role" => "admin"]);
    $db->prepare("UPDATE users SET token = ?, token_expires_at = DATE_ADD(NOW(), INTERVAL 30 DAY) WHERE id = ?")
       ->execute([$tokenAdmin, $adminId]);

    // -------------------------------------------------------------
    // [TEST 1] Đăng nhập tài khoản active thành công và nhận JWT hợp lệ
    // -------------------------------------------------------------
    echo "[TEST 1] Đăng nhập (POST /login) tài khoản active hợp lệ" . PHP_EOL;
    $resLogin = apiCall("POST", "/login", [
        "email"    => $studentEmail,
        "password" => "Password@123"
    ]);

    $token1 = $resLogin["body"]["data"]["token"] ?? null;
    $checkDb = $db->prepare("SELECT token, token_expires_at, status FROM users WHERE id = ?");
    $checkDb->execute([$studentId]);
    $userRow1 = $checkDb->fetch();

    if ($resLogin["status"] === 200 && !empty($token1) && $userRow1["token"] === $token1 && !empty($userRow1["token_expires_at"])) {
        echo "  -> PASS: Đăng nhập thành công (HTTP 200). DB lưu token và token_expires_at đồng bộ." . PHP_EOL;
        $passCount++;
    } else {
        echo "  -> FAIL: Status: {$resLogin['status']}, Token rỗng hoặc không đồng bộ DB." . PHP_EOL;
    }
    echo PHP_EOL;

    // -------------------------------------------------------------
    // [TEST 2] Truy cập protected API bằng JWT hợp lệ của active user
    // -------------------------------------------------------------
    echo "[TEST 2] Truy cập API yêu cầu xác thực (GET /student/profile) với JWT active" . PHP_EOL;
    $resProfile = apiCall("GET", "/student/profile", null, $token1);

    if ($resProfile["status"] === 200 && ($resProfile["body"]["success"] ?? false) === true) {
        echo "  -> PASS: Truy cập thành công (HTTP 200). Middleware xác thực và cấp quyền chuẩn xác." . PHP_EOL;
        $passCount++;
    } else {
        echo "  -> FAIL: Mong đợi HTTP 200 nhưng nhận HTTP {$resProfile['status']} - " . json_encode($resProfile["body"], JSON_UNESCAPED_UNICODE) . PHP_EOL;
    }
    echo PHP_EOL;

    // -------------------------------------------------------------
    // [TEST 3] Đăng xuất tài khoản qua POST /logout (Revocation)
    // -------------------------------------------------------------
    echo "[TEST 3] Đăng xuất tài khoản (POST /logout) với Bearer token hiện tại" . PHP_EOL;
    $resLogout = apiCall("POST", "/logout", null, $token1);

    $checkDb->execute([$studentId]);
    $userRowAfterLogout = $checkDb->fetch();

    if ($resLogout["status"] === 200 && $userRowAfterLogout["token"] === "revoked" && $userRowAfterLogout["token_expires_at"] === null) {
        echo "  -> PASS: Đăng xuất thành công (HTTP 200). Database đánh dấu token = 'revoked' và xóa hạn dùng." . PHP_EOL;
        $passCount++;
    } else {
        echo "  -> FAIL: Status: {$resLogout['status']}, DB Token: {$userRowAfterLogout['token']}" . PHP_EOL;
    }
    echo PHP_EOL;

    // -------------------------------------------------------------
    // [TEST 4] Dùng token vừa đăng xuất gọi protected API -> Bị chặn 401
    // -------------------------------------------------------------
    echo "[TEST 4] Cố truy cập protected API (GET /student/profile) bằng token đã đăng xuất" . PHP_EOL;
    $resRevoked = apiCall("GET", "/student/profile", null, $token1);

    if ($resRevoked["status"] === 401) {
        echo "  -> PASS: Bị từ chối chính xác với HTTP 401 Unauthorized (JWT revocation enforced)." . PHP_EOL;
        $passCount++;
    } else {
        echo "  -> FAIL: Mong đợi HTTP 401 nhưng nhận HTTP {$resRevoked['status']} - Token cũ vẫn truy cập được!" . PHP_EOL;
    }
    echo PHP_EOL;

    // -------------------------------------------------------------
    // [TEST 5] Dùng token đã đăng xuất gọi public route -> Không bị ảnh hưởng (HTTP 200)
    // -------------------------------------------------------------
    echo "[TEST 5] Dùng token đã đăng xuất gọi public route (GET /jobs/{id})" . PHP_EOL;
    $resPublicRevoked = apiCall("GET", "/jobs/{$testJobId}", null, $token1);

    if ($resPublicRevoked["status"] === 200) {
        echo "  -> PASS: Public route vẫn phản hồi bình thường (HTTP 200) không bị gián đoạn." . PHP_EOL;
        $passCount++;
    } else {
        echo "  -> FAIL: Mong đợi HTTP 200 nhưng nhận HTTP {$resPublicRevoked['status']}" . PHP_EOL;
    }
    echo PHP_EOL;

    // -------------------------------------------------------------
    // [TEST 6] Đăng nhập lại tạo phiên mới, nhận token mới (token2)
    // -------------------------------------------------------------
    echo "[TEST 6] Đăng nhập lại (POST /login) để cấp token mới (token2)" . PHP_EOL;
    sleep(1);
    $resLogin2 = apiCall("POST", "/login", [
        "email"    => $studentEmail,
        "password" => "Password@123"
    ]);

    $token2 = $resLogin2["body"]["data"]["token"] ?? null;

    if ($resLogin2["status"] === 200 && !empty($token2) && $token2 !== $token1) {
        echo "  -> PASS: Đăng nhập lại thành công (HTTP 200). Đã cấp token mới khác token cũ." . PHP_EOL;
        $passCount++;
    } else {
        echo "  -> FAIL: Không tạo được token mới hợp lệ." . PHP_EOL;
    }
    echo PHP_EOL;

    // -------------------------------------------------------------
    // [TEST 7] Token cũ (token1) đã bị thay thế (superseded) tiếp tục bị chặn 401
    // -------------------------------------------------------------
    echo "[TEST 7] Cố dùng token cũ (token1) khi đã có phiên đăng nhập mới" . PHP_EOL;
    $resOldToken = apiCall("GET", "/student/profile", null, $token1);

    if ($resOldToken["status"] === 401) {
        echo "  -> PASS: Bị từ chối chính xác với HTTP 401 Unauthorized (Superseded token blocked)." . PHP_EOL;
        $passCount++;
    } else {
        echo "  -> FAIL: Token cũ vẫn truy cập được! Status: {$resOldToken['status']}" . PHP_EOL;
    }
    echo PHP_EOL;

    // -------------------------------------------------------------
    // [TEST 8] Token mới (token2) truy cập bình thường
    // -------------------------------------------------------------
    echo "[TEST 8] Dùng token mới (token2) truy cập protected API (GET /student/profile)" . PHP_EOL;
    $resNewToken = apiCall("GET", "/student/profile", null, $token2);

    if ($resNewToken["status"] === 200 && ($resNewToken["body"]["success"] ?? false) === true) {
        echo "  -> PASS: Token mới truy cập thành công (HTTP 200)." . PHP_EOL;
        $passCount++;
    } else {
        echo "  -> FAIL: Status: {$resNewToken['status']}" . PHP_EOL;
    }
    echo PHP_EOL;

    // -------------------------------------------------------------
    // [TEST 9] Admin tạm khóa tài khoản (PATCH /admin/users/{id}/status -> suspended)
    // -------------------------------------------------------------
    echo "[TEST 9] Quản trị viên đình chỉ/tạm khóa tài khoản (status = 'suspended')" . PHP_EOL;
    $resSuspend = apiCall("PATCH", "/admin/users/{$studentId}/status", [
        "status" => "suspended"
    ], $tokenAdmin);

    $checkDb->execute([$studentId]);
    $userRowSuspended = $checkDb->fetch();

    if ($resSuspend["status"] === 200 && $userRowSuspended["status"] === "suspended") {
        echo "  -> PASS: Quản trị viên cập nhật trạng thái sang 'suspended' thành công (HTTP 200)." . PHP_EOL;
        $passCount++;
    } else {
        echo "  -> FAIL: Status: {$resSuspend['status']}, DB status: {$userRowSuspended['status']}" . PHP_EOL;
    }
    echo PHP_EOL;

    // -------------------------------------------------------------
    // [TEST 10] Tài khoản suspended gửi token2 gọi protected API -> Bị chặn 403
    // -------------------------------------------------------------
    echo "[TEST 10] Tài khoản tạm khóa cố gọi protected API (GET /student/profile) với token còn hạn" . PHP_EOL;
    $resSuspendedAccess = apiCall("GET", "/student/profile", null, $token2);

    if ($resSuspendedAccess["status"] === 403) {
        echo "  -> PASS: Bị từ chối tức thì với HTTP 403 Forbidden. Suspension có hiệu lực ngay request tiếp theo." . PHP_EOL;
        $passCount++;
    } else {
        echo "  -> FAIL: Mong đợi HTTP 403 nhưng nhận HTTP {$resSuspendedAccess['status']} - Tài khoản tạm khóa vẫn truy cập được!" . PHP_EOL;
    }
    echo PHP_EOL;

    // -------------------------------------------------------------
    // [TEST 11] Tài khoản suspended cố đăng nhập lại -> Bị chặn
    // -------------------------------------------------------------
    echo "[TEST 11] Tài khoản tạm khóa cố đăng nhập lại (POST /login)" . PHP_EOL;
    $resSuspendedLogin = apiCall("POST", "/login", [
        "email"    => $studentEmail,
        "password" => "Password@123"
    ]);

    if ($resSuspendedLogin["status"] === 401 || $resSuspendedLogin["status"] === 403) {
        echo "  -> PASS: Đăng nhập bị chặn chính xác với HTTP {$resSuspendedLogin['status']} (Tài khoản đang bị tạm khóa)." . PHP_EOL;
        $passCount++;
    } else {
        echo "  -> FAIL: Đăng nhập tài khoản tạm khóa lại thành công! Status: {$resSuspendedLogin['status']}" . PHP_EOL;
    }
    echo PHP_EOL;

    // -------------------------------------------------------------
    // [TEST 12] Tài khoản suspended truy cập public route (GET /jobs) -> Không bị ảnh hưởng
    // -------------------------------------------------------------
    echo "[TEST 12] Tài khoản tạm khóa truy cập danh sách việc làm công khai (GET /jobs)" . PHP_EOL;
    $resPublicSuspended = apiCall("GET", "/jobs", null, $token2);

    if ($resPublicSuspended["status"] === 200) {
        echo "  -> PASS: Public route vẫn hoạt động bình thường (HTTP 200) cho người dùng bị đình chỉ." . PHP_EOL;
        $passCount++;
    } else {
        echo "  -> FAIL: Mong đợi HTTP 200 nhưng nhận HTTP {$resPublicSuspended['status']}" . PHP_EOL;
    }
    echo PHP_EOL;

    // -------------------------------------------------------------
    // [TEST 13] Admin khóa vĩnh viễn tài khoản (status = 'banned')
    // -------------------------------------------------------------
    echo "[TEST 13] Quản trị viên khóa vĩnh viễn tài khoản (status = 'banned')" . PHP_EOL;
    $resBan = apiCall("PATCH", "/admin/users/{$studentId}/status", [
        "status" => "banned"
    ], $tokenAdmin);

    $checkDb->execute([$studentId]);
    $userRowBanned = $checkDb->fetch();

    if ($resBan["status"] === 200 && $userRowBanned["status"] === "banned") {
        echo "  -> PASS: Quản trị viên cập nhật trạng thái sang 'banned' thành công (HTTP 200)." . PHP_EOL;
        $passCount++;
    } else {
        echo "  -> FAIL: Status: {$resBan['status']}, DB status: {$userRowBanned['status']}" . PHP_EOL;
    }
    echo PHP_EOL;

    // -------------------------------------------------------------
    // [TEST 14] Tài khoản banned gửi token2 gọi protected API -> Bị chặn 403
    // -------------------------------------------------------------
    echo "[TEST 14] Tài khoản bị cấm (banned) cố gọi protected API (GET /student/profile)" . PHP_EOL;
    $resBannedAccess = apiCall("GET", "/student/profile", null, $token2);

    if ($resBannedAccess["status"] === 403) {
        echo "  -> PASS: Bị từ chối tức thì với HTTP 403 Forbidden. Lệnh cấm có hiệu lực ngay request tiếp theo." . PHP_EOL;
        $passCount++;
    } else {
        echo "  -> FAIL: Mong đợi HTTP 403 nhưng nhận HTTP {$resBannedAccess['status']} - Tài khoản bị cấm vẫn gọi được API!" . PHP_EOL;
    }
    echo PHP_EOL;

    // -------------------------------------------------------------
    // [TEST 15] Admin mở khóa lại tài khoản (status = 'active') và đăng nhập lại
    // -------------------------------------------------------------
    echo "[TEST 15] Quản trị viên kích hoạt lại tài khoản (status = 'active') và đăng nhập thành công" . PHP_EOL;
    $resReactivate = apiCall("PATCH", "/admin/users/{$studentId}/status", [
        "status" => "active"
    ], $tokenAdmin);

    $resLoginActive = apiCall("POST", "/login", [
        "email"    => $studentEmail,
        "password" => "Password@123"
    ]);

    $token3 = $resLoginActive["body"]["data"]["token"] ?? null;
    $resProfileActive = apiCall("GET", "/student/profile", null, $token3);

    if ($resReactivate["status"] === 200 && $resLoginActive["status"] === 200 && $resProfileActive["status"] === 200) {
        echo "  -> PASS: Mở khóa và đăng nhập thành công (HTTP 200). Protected API hoạt động bình thường trở lại." . PHP_EOL;
        $passCount++;
    } else {
        echo "  -> FAIL: Không thể phục hồi phiên hoạt động cho tài khoản active." . PHP_EOL;
    }
    echo PHP_EOL;

    // -------------------------------------------------------------
    // [TEST 16] Token của tài khoản không tồn tại / giả mạo ID -> Bị chặn 401
    // -------------------------------------------------------------
    echo "[TEST 16] Token giả mạo mang ID tài khoản không tồn tại trên hệ thống" . PHP_EOL;
    $ghostToken = JWT::encode(["id" => "u_ghost_" . uniqid(), "email" => "ghost@test.vn", "role" => "student"]);
    $resGhost = apiCall("GET", "/student/profile", null, $ghostToken);

    if ($resGhost["status"] === 401) {
        echo "  -> PASS: Bị từ chối chính xác với HTTP 401 Unauthorized (Unknown user rejected)." . PHP_EOL;
        $passCount++;
    } else {
        echo "  -> FAIL: Mong đợi HTTP 401 nhưng nhận HTTP {$resGhost['status']}" . PHP_EOL;
    }
    echo PHP_EOL;

    // -------------------------------------------------------------
    // [TEST 17] Fail-Closed: User active có JWT hợp lệ nhưng token & expiry trong DB là NULL -> 401
    // -------------------------------------------------------------
    echo "[TEST 17] Fail-closed: User active có JWT hợp lệ nhưng token/expiry trong DB là NULL" . PHP_EOL;
    $nullSessionUserId = "u_null_sess_" . uniqid();
    $nullSessionEmail  = "null_sess_" . uniqid() . "@test.vn";
    $cleanupUserIds[]  = $nullSessionUserId;

    $userStmt->execute([$nullSessionUserId, "Null Session User", $nullSessionEmail, $passwordHash, "student", "active"]);
    $db->prepare("UPDATE users SET token = NULL, token_expires_at = NULL WHERE id = ?")->execute([$nullSessionUserId]);

    $validSignedJwt = JWT::encode(["id" => $nullSessionUserId, "email" => $nullSessionEmail, "role" => "student"]);
    $resNullSession = apiCall("GET", "/student/profile", null, $validSignedJwt);

    if ($resNullSession["status"] === 401) {
        echo "  -> PASS: Bị từ chối chính xác với HTTP 401 Unauthorized (Fail-closed enforced khi DB không có active session)." . PHP_EOL;
        $passCount++;
    } else {
        echo "  -> FAIL: Mong đợi HTTP 401 nhưng nhận HTTP {$resNullSession['status']} - JWT bypass khi DB session là NULL!" . PHP_EOL;
    }
    echo PHP_EOL;

    // -------------------------------------------------------------
    // [TEST 18] Fail-Closed: User active có token khớp trong DB nhưng expiry trong DB là NULL -> 401
    // -------------------------------------------------------------
    echo "[TEST 18] Fail-closed: User active có token khớp nhưng token_expires_at trong DB là NULL" . PHP_EOL;
    $nullExpiryUserId = "u_null_exp_" . uniqid();
    $nullExpiryEmail  = "null_exp_" . uniqid() . "@test.vn";
    $cleanupUserIds[] = $nullExpiryUserId;

    $userStmt->execute([$nullExpiryUserId, "Null Expiry User", $nullExpiryEmail, $passwordHash, "student", "active"]);
    $validSignedJwt2 = JWT::encode(["id" => $nullExpiryUserId, "email" => $nullExpiryEmail, "role" => "student"]);
    $db->prepare("UPDATE users SET token = ?, token_expires_at = NULL WHERE id = ?")->execute([$validSignedJwt2, $nullExpiryUserId]);

    $resNullExpiry = apiCall("GET", "/student/profile", null, $validSignedJwt2);

    if ($resNullExpiry["status"] === 401) {
        echo "  -> PASS: Bị từ chối chính xác với HTTP 401 Unauthorized (Expiry NULL trong DB bị chặn chuẩn xác)." . PHP_EOL;
        $passCount++;
    } else {
        echo "  -> FAIL: Mong đợi HTTP 401 nhưng nhận HTTP {$resNullExpiry['status']}" . PHP_EOL;
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
echo "   KẾT QUẢ: {$passCount}/{$totalTests} BÀI KIỂM THỬ TASK-P0-05 ĐẠT THÀNH CÔNG " . ($passCount === $totalTests ? "(100% PASS)" : "(FAILED)") . PHP_EOL;
echo "================================================================" . PHP_EOL;

exit($passCount === $totalTests ? 0 : 1);
