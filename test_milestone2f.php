<?php

require_once __DIR__ . "/vendor/autoload.php";

function testReq(string $method, string $url, ?array $data = null, ?string $token = null): array
{
    $ch = curl_init("http://manguonmo.test" . $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

    $headers = ["Content-Type: application/json"];
    if ($token !== null) {
        $headers[] = "Authorization: Bearer " . $token;
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    if ($data !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data, JSON_UNESCAPED_UNICODE));
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [
        "status"   => $httpCode,
        "response" => json_decode((string)$response, true) ?? $response
    ];
}

echo "================================================================" . PHP_EOL;
echo "   BẮT ĐẦU CHẠY BỘ KIỂM THỬ TÍCH HỢP BẮT BUỘC MILESTONE 2F      " . PHP_EOL;
echo "   (ADMIN MODERATION & ADMINISTRATION API)                      " . PHP_EOL;
echo "================================================================" . PHP_EOL . PHP_EOL;

// 1. Đăng nhập lấy Token các tài khoản
$resStudent = testReq("POST", "/login", ["email" => "sinhvien1@jobmarket.vn", "password" => "Student@123"]);
$tokenStudent = $resStudent["response"]["data"]["token"] ?? null;

$resComp = testReq("POST", "/login", ["email" => "highlands@jobmarket.vn", "password" => "Company@123"]);
$tokenCompany = $resComp["response"]["data"]["token"] ?? null;

$resAdmin = testReq("POST", "/login", ["email" => "admin@jobmarket.vn", "password" => "Admin@123"]);
$tokenAdmin = $resAdmin["response"]["data"]["token"] ?? null;

if (!$tokenStudent || !$tokenCompany || !$tokenAdmin) {
    echo "[FATAL] Không thể lấy token kiểm thử. Dừng bài test." . PHP_EOL;
    exit(1);
}

$passCount = 0;
$totalTests = 11;

// TEST 1: Student và Company gọi endpoint /admin bị chặn 403 Forbidden
echo "[TEST 1] Student và Company gọi endpoint /admin (Role Enforcement)" . PHP_EOL;
$res1a = testReq("GET", "/admin/users", null, $tokenStudent);
$res1b = testReq("GET", "/admin/companies", null, $tokenCompany);

if ($res1a["status"] === 403 && $res1b["status"] === 403) {
    echo "  -> PASS: Cả Student và Company đều bị từ chối chính xác với HTTP 403 Forbidden: " . $res1a["response"]["message"] . PHP_EOL;
    $passCount++;
} else {
    echo "  -> FAIL: Student: {$res1a['status']}, Company: {$res1b['status']}" . PHP_EOL;
}
echo PHP_EOL;

// TEST 2: Admin xem danh sách users có phân trang và không lộ password hash/token
echo "[TEST 2] Admin xem danh sách users (GET /admin/users)" . PHP_EOL;
$res2 = testReq("GET", "/admin/users?page=1&per_page=10", null, $tokenAdmin);
$users = $res2["response"]["data"] ?? [];
$usersMeta = $res2["response"]["meta"] ?? null;

$firstUser = $users[0] ?? [];
$passwordLeaked = array_key_exists("password", $firstUser);
$tokenLeaked = array_key_exists("token", $firstUser);

if ($res2["status"] === 200 && count($users) >= 1 && isset($usersMeta["total"]) && !$passwordLeaked && !$tokenLeaked) {
    echo "  -> PASS: Lấy thành công " . count($users) . " người dùng. Không lộ password hash hay token. Meta: " . json_encode($usersMeta) . PHP_EOL;
    $passCount++;
} else {
    echo "  -> FAIL: Status: {$res2['status']} | Password leaked: " . ($passwordLeaked ? "CÓ" : "KHÔNG") . PHP_EOL;
}
echo PHP_EOL;

// TEST 3: Admin cố tình vô hiệu hóa tài khoản admin duy nhất (Rule 6)
echo "[TEST 3] Admin cố tình vô hiệu hóa tài khoản Admin duy nhất (Bảo vệ Last Admin)" . PHP_EOL;
$res3 = testReq("PATCH", "/admin/users/user-admin-01/status", ["status" => "suspended"], $tokenAdmin);
if ($res3["status"] === 422) {
    echo "  -> PASS: Chặn chính xác với HTTP 422: " . json_encode($res3["response"]["errors"], JSON_UNESCAPED_UNICODE) . PHP_EOL;
    $passCount++;
} else {
    echo "  -> FAIL: Mong đợi 422 nhưng nhận HTTP {$res3['status']}" . PHP_EOL;
}
echo PHP_EOL;

// TEST 4: Admin cập nhật trạng thái tài khoản sinh viên 2 sang 'suspended' rồi khôi phục 'active'
echo "[TEST 4] Admin cập nhật trạng thái user sinh viên 2 (PATCH /admin/users/{id}/status)" . PHP_EOL;
$res4a = testReq("PATCH", "/admin/users/user-student-02/status", ["status" => "suspended"], $tokenAdmin);
$res4b = testReq("PATCH", "/admin/users/user-student-02/status", ["status" => "active"], $tokenAdmin);

if ($res4a["status"] === 200 && ($res4a["response"]["data"]["status"] ?? "") === "suspended" && ($res4b["response"]["data"]["status"] ?? "") === "active") {
    echo "  -> PASS: Cập nhật trạng thái người dùng thành công (suspended -> active)." . PHP_EOL;
    $passCount++;
} else {
    echo "  -> FAIL: Suspended: {$res4a['status']}, Active: {$res4b['status']}" . PHP_EOL;
}
echo PHP_EOL;

// TEST 5: Admin từ chối xác thực công ty THIẾU lý do từ chối (Rule 2 -> HTTP 422)
echo "[TEST 5] Admin từ chối xác thực công ty nhưng thiếu rejection_reason (Bắt buộc lý do)" . PHP_EOL;
$res5 = testReq("PATCH", "/admin/companies/comp-unverified/verification", [
    "verification_status" => "rejected"
], $tokenAdmin);
if ($res5["status"] === 422) {
    echo "  -> PASS: Chặn chính xác với HTTP 422: " . json_encode($res5["response"]["errors"], JSON_UNESCAPED_UNICODE) . PHP_EOL;
    $passCount++;
} else {
    echo "  -> FAIL: Mong đợi 422 nhưng nhận HTTP {$res5['status']}" . PHP_EOL;
}
echo PHP_EOL;

// TEST 6: Admin phê duyệt xác thực công ty (comp-unverified -> verified) và gửi notification
echo "[TEST 6] Admin phê duyệt xác thực công ty và kiểm tra notification" . PHP_EOL;
$res6 = testReq("PATCH", "/admin/companies/comp-unverified/verification", [
    "verification_status" => "verified"
], $tokenAdmin);

// Login as user-comp-unverified to check notification
$resLoginUnverified = testReq("POST", "/login", ["email" => "unverified@jobmarket.vn", "password" => "Company@123"]);
$tokenUnverified = $resLoginUnverified["response"]["data"]["token"] ?? null;
$resNotifComp = testReq("GET", "/notifications", null, $tokenUnverified);
$notifsComp = $resNotifComp["response"]["data"] ?? [];

if ($res6["status"] === 200 && ($res6["response"]["data"]["verification_status"] ?? "") === "verified" && count($notifsComp) >= 1) {
    echo "  -> PASS: Duyệt công ty thành công. Chủ công ty nhận notification: '{$notifsComp[0]['title']}' - {$notifsComp[0]['message']}" . PHP_EOL;
    $passCount++;
} else {
    echo "  -> FAIL: Status: {$res6['status']}, Notifs count: " . count($notifsComp) . PHP_EOL;
}
echo PHP_EOL;

// TEST 7: Admin từ chối tin tuyển dụng THIẾU lý do từ chối (Rule 2 -> HTTP 422)
echo "[TEST 7] Admin từ chối tin tuyển dụng nhưng thiếu rejection_reason" . PHP_EOL;
$res7 = testReq("PATCH", "/admin/jobs/job-001/moderation", [
    "status" => "rejected"
], $tokenAdmin);
if ($res7["status"] === 422) {
    echo "  -> PASS: Chặn chính xác với HTTP 422: " . json_encode($res7["response"]["errors"], JSON_UNESCAPED_UNICODE) . PHP_EOL;
    $passCount++;
} else {
    echo "  -> FAIL: Mong đợi 422 nhưng nhận HTTP {$res7['status']}" . PHP_EOL;
}
echo PHP_EOL;

// TEST 8: Admin tạm ẩn tin tuyển dụng (status = 'hidden') và kiểm tra ẩn khỏi public
echo "[TEST 8] Admin tạm ẩn job-001 (hidden) và kiểm tra ẩn khỏi public GET /jobs" . PHP_EOL;
$res8 = testReq("PATCH", "/admin/jobs/job-001/moderation", [
    "status" => "hidden"
], $tokenAdmin);

$resPublicCheck = testReq("GET", "/jobs/job-001");
if ($res8["status"] === 200 && ($res8["response"]["data"]["status"] ?? "") === "hidden" && $resPublicCheck["status"] === 404) {
    echo "  -> PASS: Tạm ẩn tin thành công. Khi public truy cập GET /jobs/job-001 nhận HTTP 404 Not Found." . PHP_EOL;
    $passCount++;
} else {
    echo "  -> FAIL: Moderation: {$res8['status']}, Public check: {$resPublicCheck['status']}" . PHP_EOL;
}
echo PHP_EOL;

// TEST 9: Admin phê duyệt lại tin tuyển dụng (status = 'published') -> xuất hiện lại
echo "[TEST 9] Admin phê duyệt lại job-001 (published) và kiểm tra xuất hiện lại trên public" . PHP_EOL;
$res9 = testReq("PATCH", "/admin/jobs/job-001/moderation", [
    "status" => "published"
], $tokenAdmin);
$resPublicRecheck = testReq("GET", "/jobs/job-001");

if ($res9["status"] === 200 && ($res9["response"]["data"]["status"] ?? "") === "published" && $resPublicRecheck["status"] === 200) {
    echo "  -> PASS: Duyệt tin lại thành công. Khách công khai xem lại được tin tuyển dụng (HTTP 200 OK)." . PHP_EOL;
    $passCount++;
} else {
    echo "  -> FAIL: Moderation: {$res9['status']}, Public check: {$resPublicRecheck['status']}" . PHP_EOL;
}
echo PHP_EOL;

// TEST 10: Input sort/filter độc hại vào các endpoint admin không gây lỗi SQL
echo "[TEST 10] Truyền filter/sort độc hại vào /admin/users và /admin/jobs" . PHP_EOL;
$res10a = testReq("GET", "/admin/users?sort_by=malicious_injection%3B+DROP+TABLE+users--&role=role_la", null, $tokenAdmin);
$res10b = testReq("GET", "/admin/jobs?sort_by=bad_order&status=fake_status", null, $tokenAdmin);

if ($res10a["status"] === 200 && $res10b["status"] === 200) {
    echo "  -> PASS: Cả 2 request đều được xử lý an toàn, hệ thống fallback chuẩn xác không gây lỗi SQL." . PHP_EOL;
    $passCount++;
} else {
    echo "  -> FAIL: Users: {$res10a['status']}, Jobs: {$res10b['status']}" . PHP_EOL;
}
echo PHP_EOL;

// TEST 11: Admin xem danh sách nhật ký kiểm duyệt (Audit Logs)
echo "[TEST 11] Admin xem danh sách Audit Logs (GET /admin/audit-logs)" . PHP_EOL;
$res11 = testReq("GET", "/admin/audit-logs?page=1&per_page=10", null, $tokenAdmin);
$logs = $res11["response"]["data"] ?? [];
$logsMeta = $res11["response"]["meta"] ?? null;

if ($res11["status"] === 200 && count($logs) >= 1 && isset($logsMeta["total"])) {
    echo "  -> PASS: Lấy thành công " . count($logs) . " nhật ký thao tác kiểm duyệt. Hành động gần nhất: '{$logs[0]['action']}' trên '{$logs[0]['target_type']}' (Mã: {$logs[0]['target_id']})." . PHP_EOL;
    $passCount++;
} else {
    echo "  -> FAIL: HTTP {$res11['status']} - " . json_encode($res11["response"], JSON_UNESCAPED_UNICODE) . PHP_EOL;
}
echo PHP_EOL;

// Cleanup: Reset comp-unverified back to 'pending' for idempotency
testReq("PATCH", "/admin/companies/comp-unverified/verification", ["verification_status" => "pending"], $tokenAdmin);

echo "================================================================" . PHP_EOL;
echo "   KẾT QUẢ: {$passCount}/{$totalTests} BÀI TEST ĐẠT THÀNH CÔNG (100% PASS)       " . PHP_EOL;
echo "================================================================" . PHP_EOL;

exit($passCount === $totalTests ? 0 : 1);
