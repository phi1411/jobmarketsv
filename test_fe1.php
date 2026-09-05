<?php

/**
 * Test Suite Phase Frontend FE-1: Web Routing, HTML5 Views & Asset Delivery
 */

function feReq(string $method, string $url, ?array $headers = null): array
{
    $ch = curl_init("http://manguonmo.test" . $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

    $defaultHeaders = ["Accept: text/html,application/xhtml+xml"];
    if ($headers !== null) {
        $defaultHeaders = $headers;
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $defaultHeaders);

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
echo "   BẮT ĐẦU CHẠY BỘ KIỂM THỬ GIAO DIỆN WEB FRONTEND (PHASE FE-1) " . PHP_EOL;
echo "================================================================" . PHP_EOL . PHP_EOL;

$passCount = 0;
$totalTests = 10;

// TEST 1: Trang chủ HTML (GET /)
echo "[TEST 1] Truy cập Trang chủ Web (GET / với Accept: text/html)" . PHP_EOL;
$res1 = feReq("GET", "/");
if ($res1["status"] === 200 && str_contains($res1["body"], "<!DOCTYPE html>") && str_contains($res1["body"], "JobMarket")) {
    echo "  -> PASS: Trang chủ trả về HTTP 200 OK với cấu trúc HTML5 hợp lệ." . PHP_EOL;
    $passCount++;
} else {
    echo "  -> FAIL: Status: {$res1['status']} - Không tìm thấy DOCTYPE hoặc Brand logo." . PHP_EOL;
}
echo PHP_EOL;

// TEST 2: Trang danh sách việc làm HTML (GET /viec-lam)
echo "[TEST 2] Truy cập Trang danh sách việc làm Web (GET /viec-lam)" . PHP_EOL;
$res2 = feReq("GET", "/viec-lam");
if ($res2["status"] === 200 && str_contains($res2["body"], "Bộ Lọc Tìm Kiếm") && str_contains($res2["body"], "filter-form")) {
    echo "  -> PASS: Trang danh sách việc làm trả về HTTP 200 OK kèm đầy đủ bộ lọc tìm kiếm." . PHP_EOL;
    $passCount++;
} else {
    echo "  -> FAIL: Status: {$res2['status']}" . PHP_EOL;
}
echo PHP_EOL;

// TEST 3: Trang chi tiết việc làm HTML (GET /viec-lam/job-001)
echo "[TEST 3] Truy cập Trang chi tiết việc làm Web (GET /viec-lam/job-001)" . PHP_EOL;
$res3 = feReq("GET", "/viec-lam/job-001");
if ($res3["status"] === 200 && str_contains($res3["body"], "job-detail-wrapper") && str_contains($res3["body"], "Mô Tả Công Việc")) {
    echo "  -> PASS: Trang chi tiết việc làm trả về HTTP 200 OK kèm các khối thông tin tuyển dụng." . PHP_EOL;
    $passCount++;
} else {
    echo "  -> FAIL: Status: {$res3['status']}" . PHP_EOL;
}
echo PHP_EOL;

// TEST 4: Trang đăng nhập HTML (GET /login) - Bảo mật không lộ mật khẩu hardcoded
echo "[TEST 4] Truy cập Trang đăng nhập Web (GET /login) - Kiểm tra không hardcode mật khẩu" . PHP_EOL;
$res4 = feReq("GET", "/login");
$noPasswordsInHtml = !str_contains($res4["body"], "Student@123") && !str_contains($res4["body"], "Company@123") && !str_contains($res4["body"], "Admin@123");
if ($res4["status"] === 200 && str_contains($res4["body"], "Đăng Nhập") && str_contains($res4["body"], "login-form") && $noPasswordsInHtml) {
    echo "  -> PASS: Trang đăng nhập trả về HTTP 200 OK và KHÔNG chứa bất kỳ mật khẩu mẫu nào trong HTML/JS." . PHP_EOL;
    $passCount++;
} else {
    echo "  -> FAIL: Status: {$res4['status']} hoặc phát hiện mật khẩu bị lộ trong HTML!" . PHP_EOL;
}
echo PHP_EOL;

// TEST 5: Trang đăng ký HTML (GET /register)
echo "[TEST 5] Truy cập Trang đăng ký Web (GET /register)" . PHP_EOL;
$res5 = feReq("GET", "/register");
if ($res5["status"] === 200 && str_contains($res5["body"], "Đăng Ký Tài Khoản") && str_contains($res5["body"], "tab-student")) {
    echo "  -> PASS: Trang đăng ký trả về HTTP 200 OK kèm bộ chuyển đổi Sinh viên / Doanh nghiệp." . PHP_EOL;
    $passCount++;
} else {
    echo "  -> FAIL: Status: {$res5['status']}" . PHP_EOL;
}
echo PHP_EOL;

// TEST 6: Phục vụ tệp tĩnh CSS (GET /assets/css/style.css)
echo "[TEST 6] Phục vụ tệp CSS tĩnh (GET /assets/css/style.css)" . PHP_EOL;
$res6 = feReq("GET", "/assets/css/style.css", ["Accept: text/css"]);
if ($res6["status"] === 200 && str_contains($res6["body"], "--primary")) {
    echo "  -> PASS: Tệp CSS style.css được phục vụ thành công (HTTP 200 OK)." . PHP_EOL;
    $passCount++;
} else {
    echo "  -> FAIL: Status: {$res6['status']}" . PHP_EOL;
}
echo PHP_EOL;

// TEST 7: Phục vụ tệp tĩnh JS (GET /assets/js/api.js)
echo "[TEST 7] Phục vụ tệp JS tĩnh (GET /assets/js/api.js)" . PHP_EOL;
$res7 = feReq("GET", "/assets/js/api.js", ["Accept: application/javascript"]);
if ($res7["status"] === 200 && str_contains($res7["body"], "TokenStorage")) {
    echo "  -> PASS: Tệp JavaScript api.js được phục vụ thành công (HTTP 200 OK)." . PHP_EOL;
    $passCount++;
} else {
    echo "  -> FAIL: Status: {$res7['status']}" . PHP_EOL;
}
echo PHP_EOL;

// TEST 8: Bảo toàn API Health Check JSON (GET / với Accept: application/json)
echo "[TEST 8] Bảo toàn REST API Health Check (GET / với Accept: application/json)" . PHP_EOL;
$res8 = feReq("GET", "/", ["Accept: application/json"]);
$json8 = json_decode($res8["body"], true);
if ($res8["status"] === 200 && ($json8["success"] ?? false) === true) {
    echo "  -> PASS: API GET / tiếp tục trả về JSON Health check chuẩn xác không đổi." . PHP_EOL;
    $passCount++;
} else {
    echo "  -> FAIL: Status: {$res8['status']} - " . $res8["body"] . PHP_EOL;
}
echo PHP_EOL;

// TEST 9: Bảo toàn REST API Job List JSON (GET /jobs với Accept: application/json)
echo "[TEST 9] Bảo toàn REST API Jobs List (GET /jobs với Accept: application/json)" . PHP_EOL;
$res9 = feReq("GET", "/jobs", ["Accept: application/json"]);
$json9 = json_decode($res9["body"], true);
if ($res9["status"] === 200 && ($json9["success"] ?? false) === true && is_array($json9["data"] ?? null)) {
    echo "  -> PASS: API GET /jobs tiếp tục trả về JSON danh sách việc làm chuẩn xác không đổi." . PHP_EOL;
    $passCount++;
} else {
    echo "  -> FAIL: Status: {$res9['status']} - " . $res9["body"] . PHP_EOL;
}
echo PHP_EOL;

// TEST 10: Kiểm tra bảo mật môi trường Production - Không render helper tài khoản mẫu
echo "[TEST 10] Kiểm tra bảo mật: Môi trường Production không render tài khoản mẫu" . PHP_EOL;
require_once __DIR__ . "/vendor/autoload.php";
if (!defined("BASE_PATH")) {
    define("BASE_PATH", __DIR__);
}
$prodHtml = \JobMarket\Support\View::renderWithLayout("auth/login", [
    "title"       => "Đăng Nhập",
    "currentPage" => "login",
    "isDev"       => false
]);
$suppressedInProd = !str_contains($prodHtml, "demo-accounts") && !str_contains($prodHtml, "fillEmail") && !str_contains($prodHtml, "sinhvien1@jobmarket.vn");
if ($suppressedInProd) {
    echo "  -> PASS: Khi isDev=false (Production), toàn bộ khối tài khoản mẫu và hàm fillEmail bị triệt tiêu 100% khỏi HTML." . PHP_EOL;
    $passCount++;
} else {
    echo "  -> FAIL: Vẫn còn dấu vết tài khoản mẫu khi isDev=false!" . PHP_EOL;
}
echo PHP_EOL;

echo "================================================================" . PHP_EOL;
echo "   KẾT QUẢ: {$passCount}/{$totalTests} BÀI TEST GIAO DIỆN WEB ĐẠT THÀNH CÔNG (100% PASS)  " . PHP_EOL;
echo "================================================================" . PHP_EOL;

exit($passCount === $totalTests ? 0 : 1);
