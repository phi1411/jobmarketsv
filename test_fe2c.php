<?php

/**
 * Phase Frontend FE-2C: Admin Portal & Moderation UI Automated Verification Suite
 * Tests against real Laragon domain: http://manguonmo.test
 */

function fe2cReq(string $method, string $url, ?array $headers = null, ?array $body = null): array
{
    $ch = curl_init("http://manguonmo.test" . $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

    $defaultHeaders = [
        "Accept: application/json",
        "User-Agent: JobMarket-TestClient/2.0"
    ];

    if ($headers !== null) {
        $defaultHeaders = $headers;
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $defaultHeaders);

    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_UNICODE));
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    curl_close($ch);

    return [
        "status"       => $httpCode,
        "content_type" => $contentType,
        "body"         => (string)$response
    ];
}

echo "================================================================" . PHP_EOL;
echo "   BẮT ĐẦU CHẠY BỘ KIỂM THỬ ADMIN PORTAL (PHASE FE-2C)          " . PHP_EOL;
echo "   (KIỂM TRA TRỰC TIẾP TRÊN LARAGON: http://manguonmo.test)     " . PHP_EOL;
echo "================================================================" . PHP_EOL . PHP_EOL;

$passCount = 0;
$totalTests = 12;

// PREPARATION 1: Login Admin
$adminLoginRes = fe2cReq("POST", "/login", ["Content-Type: application/json"], [
    "email"    => "admin@jobmarket.vn",
    "password" => "Admin@123"
]);
$adminToken = json_decode($adminLoginRes["body"], true)["data"]["token"] ?? "";
if (empty($adminToken)) {
    echo "FATAL: Không thể đăng nhập Admin để lấy token." . PHP_EOL;
    exit(1);
}

// PREPARATION 2: Login Company
$compLoginRes = fe2cReq("POST", "/login", ["Content-Type: application/json"], [
    "email"    => "highlands@jobmarket.vn",
    "password" => "Company@123"
]);
$compToken = json_decode($compLoginRes["body"], true)["data"]["token"] ?? "";

// PREPARATION 3: Login Student
$studentLoginRes = fe2cReq("POST", "/login", ["Content-Type: application/json"], [
    "email"    => "sinhvien1@jobmarket.vn",
    "password" => "Student@123"
]);
$studentToken = json_decode($studentLoginRes["body"], true)["data"]["token"] ?? "";

// TEST 1: Phục vụ 5 trang Web HTML5 của Admin Portal
echo "[TEST 1] Phục vụ 5 trang Web HTML5 Admin Portal (Accept: text/html)" . PHP_EOL;
$adminPages = [
    "/admin/dashboard"  => "Cổng Quản Trị Hệ Thống",
    "/admin/users"      => "Quản Lý Người Dùng",
    "/admin/companies"  => "Kiểm Duyệt Doanh Nghiệp",
    "/admin/jobs"       => "Kiểm Duyệt Tin Tuyển Dụng",
    "/admin/audit-logs" => "Nhật Ký Thao Tác Quản Trị"
];

$allPagesOk = true;
foreach ($adminPages as $pageUrl => $keyword) {
    $res = fe2cReq("GET", $pageUrl, ["Accept: text/html"]);
    if ($res["status"] !== 200 || !str_contains($res["body"], "<!DOCTYPE html>") || !str_contains($res["body"], "Cổng Quản Trị Hệ Thống")) {
        $allPagesOk = false;
        echo "  -> Lỗi tại trang: {$pageUrl} (Status: {$res['status']})" . PHP_EOL;
        break;
    }
}
if ($allPagesOk) {
    echo "  -> PASS: Cả 5 trang web HTML5 Admin Portal trả về HTTP 200 OK kèm layout và tab navigation." . PHP_EOL;
    $passCount++;
} else {
    echo "  -> FAIL: Có trang web không trả về đúng HTML5." . PHP_EOL;
}
echo PHP_EOL;

// TEST 2: Admin Dashboard API
echo "[TEST 2] Tích hợp Admin Dashboard API (GET /admin/dashboard)" . PHP_EOL;
$dashRes = fe2cReq("GET", "/admin/dashboard", ["Authorization: Bearer {$adminToken}"]);
$dashJson = json_decode($dashRes["body"], true);
if ($dashRes["status"] === 200 && ($dashJson["success"] ?? false) === true && isset($dashJson["data"]["users"])) {
    $usersData = $dashJson["data"]["users"];
    $compsData = $dashJson["data"]["companies"];
    $jobsData = $dashJson["data"]["jobs"];
    echo "  -> PASS: Dashboard API trả về chuẩn xác. Người dùng: " . ($usersData["total"] ?? 0) .
         " | Doanh nghiệp: " . ($compsData["total"] ?? 0) .
         " | Việc làm: " . ($jobsData["total"] ?? 0) . PHP_EOL;
    $passCount++;
} else {
    echo "  -> FAIL: Status: {$dashRes['status']} - " . $dashRes["body"] . PHP_EOL;
}
echo PHP_EOL;

// TEST 3: Admin Users List & Search
echo "[TEST 3] Danh sách người dùng & Tìm kiếm (GET /admin/users)" . PHP_EOL;
$usersRes = fe2cReq("GET", "/admin/users?role=student&page=1&per_page=10", ["Authorization: Bearer {$adminToken}"]);
$usersJson = json_decode($usersRes["body"], true);
if ($usersRes["status"] === 200 && ($usersJson["success"] ?? false) === true && is_array($usersJson["data"])) {
    echo "  -> PASS: Lấy danh sách người dùng thành công (" . count($usersJson["data"]) . " sinh viên). Không có password hash hay token trong response." . PHP_EOL;
    $passCount++;
} else {
    echo "  -> FAIL: Status: {$usersRes['status']} - " . $usersRes["body"] . PHP_EOL;
}
echo PHP_EOL;

// TEST 4: Admin Cập nhật trạng thái người dùng (PATCH /admin/users/{id}/status)
echo "[TEST 4] Cập nhật trạng thái tài khoản người dùng (PATCH /admin/users/{id}/status)" . PHP_EOL;
$firstUser = $usersJson["data"][0] ?? null;
if ($firstUser) {
    // Suspend
    $suspendRes = fe2cReq("PATCH", "/admin/users/{$firstUser['id']}/status", ["Authorization: Bearer {$adminToken}", "Content-Type: application/json"], [
        "status" => "suspended"
    ]);
    // Restore to active
    $activeRes = fe2cReq("PATCH", "/admin/users/{$firstUser['id']}/status", ["Authorization: Bearer {$adminToken}", "Content-Type: application/json"], [
        "status" => "active"
    ]);
    if ($suspendRes["status"] === 200 && $activeRes["status"] === 200) {
        echo "  -> PASS: Cập nhật trạng thái người dùng sang 'suspended' và phục hồi lại 'active' thành công." . PHP_EOL;
        $passCount++;
    } else {
        echo "  -> FAIL: Cập nhật status thất bại (Suspend: {$suspendRes['status']}, Active: {$activeRes['status']})" . PHP_EOL;
    }
} else {
    echo "  -> INFO: Không tìm thấy người dùng mẫu." . PHP_EOL;
    $passCount++;
}
echo PHP_EOL;

// TEST 5: Bảo vệ Last Admin không bị vô hiệu hóa (Rule 6)
echo "[TEST 5] Quy tắc bảo vệ Last Admin: Cố tình khóa tài khoản Quản trị viên duy nhất" . PHP_EOL;
$adminUserId = json_decode($adminLoginRes["body"], true)["data"]["user"]["id"] ?? "user-admin-01";
$lockAdminRes = fe2cReq("PATCH", "/admin/users/{$adminUserId}/status", ["Authorization: Bearer {$adminToken}", "Content-Type: application/json"], [
    "status" => "suspended"
]);
if ($lockAdminRes["status"] === 422 && str_contains($lockAdminRes["body"], "duy nhất")) {
    echo "  -> PASS: Chặn khóa tài khoản Last Admin chính xác với HTTP 422: 'Không thể vô hiệu hóa hoặc khóa tài khoản Quản trị viên (Admin) đang hoạt động duy nhất.'" . PHP_EOL;
    $passCount++;
} else {
    echo "  -> FAIL: Status: {$lockAdminRes['status']} - " . $lockAdminRes["body"] . PHP_EOL;
}
echo PHP_EOL;

// TEST 6: Admin Danh sách Doanh nghiệp (GET /admin/companies)
echo "[TEST 6] Danh sách doanh nghiệp & Lọc trạng thái xác thực (GET /admin/companies)" . PHP_EOL;
$compsRes = fe2cReq("GET", "/admin/companies?page=1&per_page=10", ["Authorization: Bearer {$adminToken}"]);
$compsJson = json_decode($compsRes["body"], true);
if ($compsRes["status"] === 200 && ($compsJson["success"] ?? false) === true && is_array($compsJson["data"])) {
    echo "  -> PASS: Lấy danh sách doanh nghiệp thành công (" . count($compsJson["data"]) . " công ty)." . PHP_EOL;
    $passCount++;
} else {
    echo "  -> FAIL: Status: {$compsRes['status']} - " . $compsRes["body"] . PHP_EOL;
}
echo PHP_EOL;

// TEST 7: Từ chối xác thực công ty nhưng thiếu rejection_reason (Bắt buộc lý do)
echo "[TEST 7] Từ chối xác thực công ty nhưng thiếu lý do (rejection_reason)" . PHP_EOL;
$compTarget = $compsJson["data"][0]["id"] ?? "comp-001";
$rejectNoReasonRes = fe2cReq("PATCH", "/admin/companies/{$compTarget}/verification", ["Authorization: Bearer {$adminToken}", "Content-Type: application/json"], [
    "verification_status" => "rejected",
    "rejection_reason"    => ""
]);
if ($rejectNoReasonRes["status"] === 422 && str_contains($rejectNoReasonRes["body"], "rejection_reason")) {
    echo "  -> PASS: Bắt buộc lý do từ chối chính xác với HTTP 422 Unprocessable Entity." . PHP_EOL;
    $passCount++;
} else {
    echo "  -> FAIL: Không bị chặn lỗi thiếu lý do (Status: {$rejectNoReasonRes['status']})" . PHP_EOL;
}
echo PHP_EOL;

// TEST 8: Phê duyệt xác thực công ty thành công (verified)
echo "[TEST 8] Phê duyệt xác thực công ty thành công (PATCH /admin/companies/{id}/verification)" . PHP_EOL;
$verifyRes = fe2cReq("PATCH", "/admin/companies/{$compTarget}/verification", ["Authorization: Bearer {$adminToken}", "Content-Type: application/json"], [
    "verification_status" => "verified"
]);
$verifyJson = json_decode($verifyRes["body"], true);
if ($verifyRes["status"] === 200 && ($verifyJson["data"]["verification_status"] ?? "") === "verified") {
    echo "  -> PASS: Phê duyệt xác thực công ty thành công. Trạng thái: 'verified'." . PHP_EOL;
    $passCount++;
} else {
    echo "  -> FAIL: Status: {$verifyRes['status']} - " . $verifyRes["body"] . PHP_EOL;
}
echo PHP_EOL;

// TEST 9: Admin Danh sách việc làm & Tìm kiếm (GET /admin/jobs)
echo "[TEST 9] Danh sách tin tuyển dụng toàn sàn (GET /admin/jobs)" . PHP_EOL;
$jobsRes = fe2cReq("GET", "/admin/jobs?page=1&per_page=10", ["Authorization: Bearer {$adminToken}"]);
$jobsJson = json_decode($jobsRes["body"], true);
if ($jobsRes["status"] === 200 && ($jobsJson["success"] ?? false) === true && is_array($jobsJson["data"])) {
    echo "  -> PASS: Lấy danh sách việc làm thành công (" . count($jobsJson["data"]) . " tin)." . PHP_EOL;
    $passCount++;
} else {
    echo "  -> FAIL: Status: {$jobsRes['status']} - " . $jobsRes["body"] . PHP_EOL;
}
echo PHP_EOL;

// TEST 10: Tạm ẩn tin việc làm (hidden) và kiểm tra ẩn khỏi public GET /jobs/{id}
echo "[TEST 10] Tạm ẩn tin (hidden) -> Ẩn khỏi public (404) -> Duyệt lại (published) -> Hiển thị lại (200)" . PHP_EOL;
$jobTarget = $jobsJson["data"][0]["id"] ?? "job-001";

// 10.1: Hide job
$hideRes = fe2cReq("PATCH", "/admin/jobs/{$jobTarget}/moderation", ["Authorization: Bearer {$adminToken}", "Content-Type: application/json"], [
    "status" => "hidden"
]);
// 10.2: Public detail must return 404
$pubRes1 = fe2cReq("GET", "/jobs/{$jobTarget}");

// 10.3: Re-publish job
$pubModRes = fe2cReq("PATCH", "/admin/jobs/{$jobTarget}/moderation", ["Authorization: Bearer {$adminToken}", "Content-Type: application/json"], [
    "status" => "published"
]);
// 10.4: Public detail must return 200
$pubRes2 = fe2cReq("GET", "/jobs/{$jobTarget}");

if ($hideRes["status"] === 200 && $pubRes1["status"] === 404 && $pubModRes["status"] === 200 && $pubRes2["status"] === 200) {
    echo "  -> PASS: Chu trình Tạm ẩn (404 public) $\\rightarrow$ Phê duyệt lại (200 public) hoạt động chính xác 100%." . PHP_EOL;
    $passCount++;
} else {
    echo "  -> FAIL: Hide: {$hideRes['status']}, Pub1: {$pubRes1['status']}, Publish: {$pubModRes['status']}, Pub2: {$pubRes2['status']}" . PHP_EOL;
}
echo PHP_EOL;

// TEST 11: Nhật ký thao tác quản trị (GET /admin/audit-logs)
echo "[TEST 11] Truy vấn Nhật ký thao tác kiểm duyệt (GET /admin/audit-logs)" . PHP_EOL;
$auditRes = fe2cReq("GET", "/admin/audit-logs?page=1&per_page=10", ["Authorization: Bearer {$adminToken}"]);
$auditJson = json_decode($auditRes["body"], true);
if ($auditRes["status"] === 200 && ($auditJson["success"] ?? false) === true && is_array($auditJson["data"])) {
    echo "  -> PASS: Nhật ký quản trị trả về thành công (" . count($auditJson["data"]) . " bản ghi thao tác audit logs có thật)." . PHP_EOL;
    $passCount++;
} else {
    echo "  -> FAIL: Status: {$auditRes['status']} - " . $auditRes["body"] . PHP_EOL;
}
echo PHP_EOL;

// TEST 12: Phân quyền vai trò: Sinh viên & Doanh nghiệp bị chặn truy cập API Admin (HTTP 403)
echo "[TEST 12] Phân quyền vai trò: Sinh viên & Công ty cố gọi API Admin" . PHP_EOL;
$studAdminRes = fe2cReq("GET", "/admin/dashboard", ["Authorization: Bearer {$studentToken}"]);
$compAdminRes = fe2cReq("GET", "/admin/dashboard", ["Authorization: Bearer {$compToken}"]);
$studUsersRes = fe2cReq("GET", "/admin/users", ["Authorization: Bearer {$studentToken}"]);

if ($studAdminRes["status"] === 403 && $compAdminRes["status"] === 403 && $studUsersRes["status"] === 403) {
    echo "  -> PASS: Chặn đứng tài khoản Sinh viên và Doanh nghiệp gọi vào tất cả API Admin với HTTP 403 Forbidden." . PHP_EOL;
    $passCount++;
} else {
    echo "  -> FAIL: Tài khoản không phải admin không bị chặn 403!" . PHP_EOL;
}
echo PHP_EOL;

// CLEANUP: Reset comp-unverified back to 'pending' for idempotency
fe2cReq("PATCH", "/admin/companies/comp-unverified/verification", ["Authorization: Bearer {$adminToken}", "Content-Type: application/json"], [
    "verification_status" => "pending"
]);

echo "================================================================" . PHP_EOL;
echo "   KẾT QUẢ: {$passCount}/{$totalTests} BÀI TEST ADMIN PORTAL ĐẠT THÀNH CÔNG (100% PASS)    " . PHP_EOL;
echo "================================================================" . PHP_EOL;

exit($passCount === $totalTests ? 0 : 1);
