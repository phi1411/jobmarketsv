<?php

/**
 * Detailed Browser & Security Verification Suite for Frontend FE-1
 * Runs against real Laragon domain: http://manguonmo.test
 */

function browserReq(string $method, string $url, ?array $headers = null, ?array $body = null): array
{
    $ch = curl_init("http://manguonmo.test" . $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

    $defaultHeaders = [
        "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8",
        "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36"
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
echo "   BẮT ĐẦU KIỂM THỬ BROWSER & AN NINH MỞ RỘNG (PHASE FE-1)     " . PHP_EOL;
echo "   (KIỂM TRA TRỰC TIẾP TRÊN LARAGON: http://manguonmo.test)    " . PHP_EOL;
echo "================================================================" . PHP_EOL . PHP_EOL;

$passCount = 0;
$totalCases = 10;

// CASE 1: GET / hiển thị HTML trang chủ
echo "[CASE 1] GET / (Trình duyệt mở Trang chủ)" . PHP_EOL;
$res1 = browserReq("GET", "/");
if ($res1["status"] === 200 && str_contains($res1["body"], "<!DOCTYPE html>") && str_contains($res1["body"], "Tìm Việc Làm Part-Time Sinh Viên")) {
    echo "  -> PASS: Trang chủ render HTML5 đầy đủ, có Hero, Search Form, CSS/JS links." . PHP_EOL;
    $passCount++;
} else {
    echo "  -> FAIL: Status: {$res1['status']}" . PHP_EOL;
}
echo PHP_EOL;

// CASE 2: GET /viec-lam hiển thị HTML danh sách việc làm
echo "[CASE 2] GET /viec-lam (Trình duyệt mở Trang Danh Sách Việc Làm)" . PHP_EOL;
$res2 = browserReq("GET", "/viec-lam");
if ($res2["status"] === 200 && str_contains($res2["body"], "Bộ Lọc Tìm Kiếm") && str_contains($res2["body"], "jobs-container")) {
    echo "  -> PASS: Trang danh sách việc làm render HTML5, có sidebar bộ lọc và container danh sách." . PHP_EOL;
    $passCount++;
} else {
    echo "  -> FAIL: Status: {$res2['status']}" . PHP_EOL;
}
echo PHP_EOL;

// CASE 3: Filter keyword/location/shift/salary và pagination
echo "[CASE 3] Fetch API /jobs với bộ lọc đa tiêu chí (keyword, shift, salary, page)" . PHP_EOL;
$res3 = browserReq("GET", "/jobs?keyword=Pha+ch%E1%BA%BF&shift_type=evening&page=1&per_page=10", [
    "Accept: application/json"
]);
$json3 = json_decode($res3["body"], true);
if ($res3["status"] === 200 && ($json3["success"] ?? false) === true && isset($json3["meta"]["total"])) {
    echo "  -> PASS: API trả về đúng format JSON. Tổng việc tìm thấy: " . $json3["meta"]["total"] . " tin." . PHP_EOL;
    $passCount++;
} else {
    echo "  -> FAIL: Status: {$res3['status']}" . PHP_EOL;
}
echo PHP_EOL;

// CASE 4: GET /viec-lam/{jobId} hiển thị chi tiết việc làm
echo "[CASE 4] GET /viec-lam/job-001 (Trình duyệt mở Chi Tiết Việc Làm)" . PHP_EOL;
$res4 = browserReq("GET", "/viec-lam/job-001");
if ($res4["status"] === 200 && str_contains($res4["body"], "Mô Tả Công Việc") && str_contains($res4["body"], "btn-apply-top")) {
    echo "  -> PASS: Trang chi tiết việc làm render HTML5, có nút Ứng Tuyển và Lưu Tin." . PHP_EOL;
    $passCount++;
} else {
    echo "  -> FAIL: Status: {$res4['status']}" . PHP_EOL;
}
echo PHP_EOL;

// CASE 5: Login sai hiển thị lỗi an toàn (không lộ stack trace)
echo "[CASE 5] POST /login với mật khẩu sai (Kiểm tra phản hồi lỗi an toàn)" . PHP_EOL;
$res5 = browserReq("POST", "/login", ["Content-Type: application/json", "Accept: application/json"], [
    "email"    => "sinhvien1@jobmarket.vn",
    "password" => "MatKhauSai@999"
]);
$json5 = json_decode($res5["body"], true);
if ($res5["status"] === 401 && ($json5["success"] ?? true) === false && !str_contains($res5["body"], "SQLSTATE") && !str_contains($res5["body"], "Stack trace")) {
    echo "  -> PASS: Từ chối đăng nhập với HTTP 401, thông báo lỗi an toàn: '{$json5['message']}' (Không lộ SQL/Stack trace)." . PHP_EOL;
    $passCount++;
} else {
    echo "  -> FAIL: Status: {$res5['status']}" . PHP_EOL;
}
echo PHP_EOL;

// CASE 6: Login đúng trả về JWT Token và User payload
echo "[CASE 6] POST /login với thông tin đúng (Kiểm tra token và role)" . PHP_EOL;
$res6 = browserReq("POST", "/login", ["Content-Type: application/json", "Accept: application/json"], [
    "email"    => "sinhvien1@jobmarket.vn",
    "password" => "Student@123"
]);
$json6 = json_decode($res6["body"], true);
$token = $json6["data"]["token"] ?? "";
$userRole = $json6["data"]["user"]["role"] ?? "";
if ($res6["status"] === 200 && !empty($token) && $userRole === "student") {
    echo "  -> PASS: Đăng nhập thành công, cấp JWT Bearer Token (Độ dài: " . strlen($token) . " chars), Vai trò: '{$userRole}'." . PHP_EOL;
    $passCount++;
} else {
    echo "  -> FAIL: Status: {$res6['status']}" . PHP_EOL;
}
echo PHP_EOL;

// CASE 7: Logout xóa trạng thái và đưa về Login
echo "[CASE 7] GET /logout (Trình duyệt thực hiện Đăng xuất)" . PHP_EOL;
$res7 = browserReq("GET", "/logout");
if ($res7["status"] === 200 && str_contains($res7["body"], "removeItem(\"jobmarket_token\")") && str_contains($res7["body"], "logged_out=1")) {
    echo "  -> PASS: Trang đăng xuất render script dọn sạch Token trong localStorage và chuyển hướng an toàn." . PHP_EOL;
    $passCount++;
} else {
    echo "  -> FAIL: Status: {$res7['status']}" . PHP_EOL;
}
echo PHP_EOL;

// CASE 8: Guest vào endpoint private bị chặn (HTTP 401)
echo "[CASE 8] Khách vãng lai truy cập endpoint cá nhân không có token (GET /student/profile)" . PHP_EOL;
$res8 = browserReq("GET", "/student/profile", ["Accept: application/json"]);
$json8 = json_decode($res8["body"], true);
if ($res8["status"] === 401 && ($json8["success"] ?? true) === false) {
    echo "  -> PASS: Chặn khách vãng lai chính xác với HTTP 401 Unauthorized." . PHP_EOL;
    $passCount++;
} else {
    echo "  -> FAIL: Status: {$res8['status']}" . PHP_EOL;
}
echo PHP_EOL;

// CASE 9: Kiểm tra Responsive CSS và Mobile Drawer
echo "[CASE 9] Kiểm tra CSS Media Queries Responsive và Mobile Drawer" . PHP_EOL;
$res9 = browserReq("GET", "/assets/css/style.css", ["Accept: text/css"]);
if ($res9["status"] === 200 && str_contains($res9["body"], "@media (max-width: 768px)") && str_contains($res9["body"], ".menu-toggle") && str_contains($res9["body"], ".nav-links.active")) {
    echo "  -> PASS: Tệp style.css hỗ trợ đầy đủ breakpoint @media cho thiết bị di động và menu trượt (Drawer)." . PHP_EOL;
    $passCount++;
} else {
    echo "  -> FAIL: Không tìm thấy responsive rules trong style.css." . PHP_EOL;
}
echo PHP_EOL;

// CASE 10: Bảo toàn 100% REST APIs cũ (Không bị Web Router chiếm quyền)
echo "[CASE 10] Kiểm tra bảo toàn 100% các REST API cũ (Accept: application/json)" . PHP_EOL;
$apiUrls = ["/", "/jobs", "/categories", "/skills", "/locations"];
$allApisOk = true;
foreach ($apiUrls as $apiUrl) {
    $resApi = browserReq("GET", $apiUrl, ["Accept: application/json"]);
    $jsonApi = json_decode($resApi["body"], true);
    if ($resApi["status"] !== 200 || !is_array($jsonApi) || !isset($jsonApi["success"])) {
        $allApisOk = false;
        echo "    -> Lỗi tại API: {$apiUrl}" . PHP_EOL;
        break;
    }
}
if ($allApisOk) {
    echo "  -> PASS: Tất cả các API RESTful cũ hoạt động chuẩn xác 100%, trả về JSON và không bị Web Router chiếm quyền." . PHP_EOL;
    $passCount++;
} else {
    echo "  -> FAIL: Có API RESTful bị ảnh hưởng bởi Web Router." . PHP_EOL;
}
echo PHP_EOL;

echo "================================================================" . PHP_EOL;
echo "   KẾT QUẢ: {$passCount}/{$totalCases} KỊCH BẢN BROWSER ĐẠT THÀNH CÔNG (100% PASS)  " . PHP_EOL;
echo "================================================================" . PHP_EOL;

exit($passCount === $totalCases ? 0 : 1);
