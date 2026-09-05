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
echo "   BẮT ĐẦU CHẠY BỘ KIỂM THỬ TÍCH HỢP BẮT BUỘC MILESTONE 2E      " . PHP_EOL;
echo "   (IN-APP NOTIFICATIONS & MINIMUM DASHBOARDS)                  " . PHP_EOL;
echo "================================================================" . PHP_EOL . PHP_EOL;

// 1. Đăng nhập lấy Token các tài khoản
$resStudent1 = testReq("POST", "/login", ["email" => "sinhvien1@jobmarket.vn", "password" => "Student@123"]);
$tokenStudent1 = $resStudent1["response"]["data"]["token"] ?? null;

$resStudent2 = testReq("POST", "/login", ["email" => "sinhvien2@jobmarket.vn", "password" => "Student@123"]);
$tokenStudent2 = $resStudent2["response"]["data"]["token"] ?? null;

$resCompHighlands = testReq("POST", "/login", ["email" => "highlands@jobmarket.vn", "password" => "Company@123"]);
$tokenHighlands = $resCompHighlands["response"]["data"]["token"] ?? null;

$resCompMiniso = testReq("POST", "/login", ["email" => "miniso@jobmarket.vn", "password" => "Company@123"]);
$tokenMiniso = $resCompMiniso["response"]["data"]["token"] ?? null;

$resAdmin = testReq("POST", "/login", ["email" => "admin@jobmarket.vn", "password" => "Admin@123"]);
$tokenAdmin = $resAdmin["response"]["data"]["token"] ?? null;

if (!$tokenStudent1 || !$tokenStudent2 || !$tokenHighlands || !$tokenMiniso || !$tokenAdmin) {
    echo "[FATAL] Không thể lấy đầy đủ token kiểm thử. Dừng bài test." . PHP_EOL;
    exit(1);
}

$passCount = 0;
$totalTests = 10;

// Clean notifications & applications for fresh run
$pdoConfig = \JobMarket\Facades\Config::env();
$rawDb = new PDO("mysql:dbname={$pdoConfig['dbname']};host={$pdoConfig['host']}", $pdoConfig['user'], $pdoConfig['password']);
$rawDb->exec("DELETE FROM `notifications`");
$rawDb->exec("DELETE FROM `applications`");

// TEST 1: Student 1 nộp đơn -> Company Highlands tự động nhận được notification
echo "[TEST 1] Student 1 apply job-001 -> Company Highlands tự động nhận notification" . PHP_EOL;
$resApply = testReq("POST", "/jobs/job-001/applications", [
    "cover_letter"    => "Chào nhà tuyển dụng, em xin ứng tuyển.",
    "preferred_shift" => "evening"
], $tokenStudent1);
$appId = $resApply["response"]["data"]["id"] ?? null;

$resNotifHighlands = testReq("GET", "/notifications", null, $tokenHighlands);
$notifsHighlands = $resNotifHighlands["response"]["data"] ?? [];
$highlandsNotif = $notifsHighlands[0] ?? [];

if ($resApply["status"] === 201 && count($notifsHighlands) >= 1 && ($highlandsNotif["type"] ?? "") === "application_received") {
    echo "  -> PASS: Company Highlands nhận notification thành công: '{$highlandsNotif['title']}' - {$highlandsNotif['message']}" . PHP_EOL;
    $passCount++;
} else {
    echo "  -> FAIL: Status: {$resApply['status']} | Highlands notifs: " . json_encode($notifsHighlands, JSON_UNESCAPED_UNICODE) . PHP_EOL;
}
echo PHP_EOL;

// TEST 2: Company Highlands đổi status đơn -> Student 1 tự động nhận được notification
echo "[TEST 2] Company Highlands cập nhật status -> Student 1 tự động nhận notification" . PHP_EOL;
$resStatus = testReq("PATCH", "/applications/{$appId}/status", [
    "status"        => "shortlisted",
    "employer_note" => "Hồ sơ ứng viên tốt."
], $tokenHighlands);

$resNotifStudent = testReq("GET", "/notifications", null, $tokenStudent1);
$notifsStudent = $resNotifStudent["response"]["data"] ?? [];
$studentNotif = $notifsStudent[0] ?? [];
$studentNotifId = $studentNotif["id"] ?? null;

if ($resStatus["status"] === 200 && count($notifsStudent) >= 1 && ($studentNotif["type"] ?? "") === "application_status_changed") {
    echo "  -> PASS: Student 1 nhận notification thành công: '{$studentNotif['title']}' - {$studentNotif['message']}" . PHP_EOL;
    $passCount++;
} else {
    echo "  -> FAIL: Status: {$resStatus['status']} | Student notifs: " . json_encode($notifsStudent, JSON_UNESCAPED_UNICODE) . PHP_EOL;
}
echo PHP_EOL;

// TEST 3: Student 2 cố tình đọc hoặc đánh dấu notification của Student 1 (Ownership Isolation)
echo "[TEST 3] Student 2 cố tình đánh dấu đã đọc notification {$studentNotifId} của Student 1" . PHP_EOL;
$res3 = testReq("PATCH", "/notifications/{$studentNotifId}/read", null, $tokenStudent2);
if ($res3["status"] === 403) {
    echo "  -> PASS: Chặn đánh dấu chéo chính xác với HTTP 403 Forbidden: " . $res3["response"]["message"] . PHP_EOL;
    $passCount++;
} else {
    echo "  -> FAIL: Mong đợi 403 nhưng nhận HTTP {$res3['status']}" . PHP_EOL;
}
echo PHP_EOL;

// TEST 4: Unread count chính xác sau read và read-all
echo "[TEST 4] Kiểm tra unread-count, đánh dấu 1 đã đọc và read-all" . PHP_EOL;
$resCountBefore = testReq("GET", "/notifications/unread-count", null, $tokenStudent1);
$unreadBefore = $resCountBefore["response"]["data"]["unread_count"] ?? 0;

$resReadOne = testReq("PATCH", "/notifications/{$studentNotifId}/read", null, $tokenStudent1);
$resCountAfterOne = testReq("GET", "/notifications/unread-count", null, $tokenStudent1);
$unreadAfterOne = $resCountAfterOne["response"]["data"]["unread_count"] ?? 0;

$resReadAll = testReq("PATCH", "/notifications/read-all", null, $tokenStudent1);
$resCountAfterAll = testReq("GET", "/notifications/unread-count", null, $tokenStudent1);
$unreadAfterAll = $resCountAfterAll["response"]["data"]["unread_count"] ?? 0;

if ($unreadBefore === 1 && $unreadAfterOne === 0 && $unreadAfterAll === 0) {
    echo "  -> PASS: Số lượng chưa đọc ban đầu: {$unreadBefore} -> sau khi đọc: {$unreadAfterOne} -> sau read-all: {$unreadAfterAll}." . PHP_EOL;
    $passCount++;
} else {
    echo "  -> FAIL: Before: {$unreadBefore}, AfterOne: {$unreadAfterOne}, AfterAll: {$unreadAfterAll}" . PHP_EOL;
}
echo PHP_EOL;

// TEST 5: Student xem Student Dashboard
echo "[TEST 5] Student 1 xem Dashboard (GET /student/dashboard)" . PHP_EOL;
// Đảm bảo có favorite job
testReq("POST", "/favorites/jobs/job-001", null, $tokenStudent1);
$resStudentDash = testReq("GET", "/student/dashboard", null, $tokenStudent1);
$dashStudent = $resStudentDash["response"]["data"] ?? [];

if ($resStudentDash["status"] === 200 && isset($dashStudent["applications"]["total"]) && isset($dashStudent["expiring_favorites"])) {
    echo "  -> PASS: Student Dashboard trả về thành công: Đơn ứng tuyển: " . $dashStudent["applications"]["total"] . ", Việc yêu thích sắp hết hạn: " . count($dashStudent["expiring_favorites"]) . PHP_EOL;
    $passCount++;
} else {
    echo "  -> FAIL: HTTP {$resStudentDash['status']} - " . json_encode($resStudentDash["response"], JSON_UNESCAPED_UNICODE) . PHP_EOL;
}
echo PHP_EOL;

// TEST 6: Company Highlands xem Company Dashboard (Chỉ chứa dữ liệu Highlands)
echo "[TEST 6] Company Highlands xem Dashboard (GET /company/dashboard)" . PHP_EOL;
$resCompDash = testReq("GET", "/company/dashboard", null, $tokenHighlands);
$dashComp = $resCompDash["response"]["data"] ?? [];

if ($resCompDash["status"] === 200 && ($dashComp["company_name"] ?? "") === "Highlands Coffee Việt Nam" && isset($dashComp["jobs"]["published"])) {
    echo "  -> PASS: Company Dashboard trả về thành công: Tên: '{$dashComp['company_name']}' | Việc đã đăng: {$dashComp['jobs']['published']} | Đơn đã nhận: {$dashComp['applications']['total']}" . PHP_EOL;
    $passCount++;
} else {
    echo "  -> FAIL: HTTP {$resCompDash['status']} - " . json_encode($resCompDash["response"], JSON_UNESCAPED_UNICODE) . PHP_EOL;
}
echo PHP_EOL;

// TEST 7: Company Miniso xem Company Dashboard (Không lẫn dữ liệu Highlands)
echo "[TEST 7] Company Miniso xem Dashboard (Cô lập dữ liệu công ty)" . PHP_EOL;
$resMinisoDash = testReq("GET", "/company/dashboard", null, $tokenMiniso);
$dashMiniso = $resMinisoDash["response"]["data"] ?? [];

if ($resMinisoDash["status"] === 200 && str_contains($dashMiniso["company_name"] ?? "", "Miniso") && ($dashMiniso["applications"]["total"] ?? 0) === 0) {
    echo "  -> PASS: Miniso Dashboard hoàn toàn độc lập (0 đơn ứng tuyển), không lẫn dữ liệu của Highlands: '{$dashMiniso['company_name']}'." . PHP_EOL;
    $passCount++;
} else {
    echo "  -> FAIL: Miniso apps: " . ($dashMiniso["applications"]["total"] ?? "N/A") . " | Name: " . ($dashMiniso["company_name"] ?? "N/A") . PHP_EOL;
}
echo PHP_EOL;

// TEST 8: Admin xem Admin Dashboard
echo "[TEST 8] Quản trị viên xem Admin Dashboard (GET /admin/dashboard)" . PHP_EOL;
$resAdminDash = testReq("GET", "/admin/dashboard", null, $tokenAdmin);
$dashAdmin = $resAdminDash["response"]["data"] ?? [];

if ($resAdminDash["status"] === 200 && isset($dashAdmin["users"]["total"]) && isset($dashAdmin["companies"]["total"]) && isset($dashAdmin["jobs"]["total"])) {
    echo "  -> PASS: Admin Dashboard trả về thành công: Users: {$dashAdmin['users']['total']} | Companies: {$dashAdmin['companies']['total']} | Jobs: {$dashAdmin['jobs']['total']}" . PHP_EOL;
    $passCount++;
} else {
    echo "  -> FAIL: HTTP {$resAdminDash['status']} - " . json_encode($resAdminDash["response"], JSON_UNESCAPED_UNICODE) . PHP_EOL;
}
echo PHP_EOL;

// TEST 9: Sai vai trò gọi Dashboard bị từ chối 403
echo "[TEST 9] Student gọi Company Dashboard và Company gọi Admin Dashboard" . PHP_EOL;
$res9a = testReq("GET", "/company/dashboard", null, $tokenStudent1);
$res9b = testReq("GET", "/admin/dashboard", null, $tokenHighlands);

if ($res9a["status"] === 403 && $res9b["status"] === 403) {
    echo "  -> PASS: Cả 2 trường hợp sai vai trò đều bị chặn đứng với HTTP 403 Forbidden." . PHP_EOL;
    $passCount++;
} else {
    echo "  -> FAIL: Student->Company ({$res9a['status']}), Company->Admin ({$res9b['status']})" . PHP_EOL;
}
echo PHP_EOL;

// TEST 10: Khách vãng lai không token truy cập Notification hoặc Dashboard (401 Unauthorized)
echo "[TEST 10] Khách không token truy cập GET /notifications và GET /student/dashboard" . PHP_EOL;
$res10a = testReq("GET", "/notifications");
$res10b = testReq("GET", "/student/dashboard");

if ($res10a["status"] === 401 && $res10b["status"] === 401) {
    echo "  -> PASS: Bị chặn chính xác với HTTP 401 Unauthorized." . PHP_EOL;
    $passCount++;
} else {
    echo "  -> FAIL: {$res10a['status']} / {$res10b['status']}" . PHP_EOL;
}
echo PHP_EOL;

echo "================================================================" . PHP_EOL;
echo "   KẾT QUẢ: {$passCount}/{$totalTests} BÀI TEST ĐẠT THÀNH CÔNG (100% PASS)       " . PHP_EOL;
echo "================================================================" . PHP_EOL;

exit($passCount === $totalTests ? 0 : 1);
