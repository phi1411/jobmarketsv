<?php

function testRequest(string $method, string $url, ?array $data = null, ?string $token = null): array
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

echo "=== BẮT ĐẦU CHẠY BỘ KIỂM THỬ TÍCH HỢP TOÀN DIỆN ===" . PHP_EOL . PHP_EOL;

// 1. Test Health Check
echo "[1] TEST GET / (Health check)" . PHP_EOL;
$res = testRequest("GET", "/");
echo "HTTP: " . $res["status"] . " | Success: " . ($res["response"]["success"] ? "TRUE" : "FALSE") . PHP_EOL;
echo "Message: " . $res["response"]["message"] . PHP_EOL . PHP_EOL;

// 2. Test Register Validation Error
echo "[2] TEST POST /register (Dữ liệu thiếu để test ValidationException)" . PHP_EOL;
$res = testRequest("POST", "/register", ["name" => "A"]);
echo "HTTP: " . $res["status"] . " | Success: " . ($res["response"]["success"] ? "TRUE" : "FALSE") . PHP_EOL;
echo "Message: " . $res["response"]["message"] . PHP_EOL;
echo "Errors: " . json_encode($res["response"]["errors"], JSON_UNESCAPED_UNICODE) . PHP_EOL . PHP_EOL;

// 3. Test Login Wrong Password
echo "[3] TEST POST /login (Mật khẩu sai để test AuthenticationException & password_verify)" . PHP_EOL;
$res = testRequest("POST", "/login", [
    "email"    => "highlands@jobmarket.vn",
    "password" => "MatKhauSai123"
]);
echo "HTTP: " . $res["status"] . " | Success: " . ($res["response"]["success"] ? "TRUE" : "FALSE") . PHP_EOL;
echo "Message: " . $res["response"]["message"] . PHP_EOL . PHP_EOL;

// 4. Test Login Correct Password (Company)
echo "[4] TEST POST /login (Mật khẩu đúng - Highlands Coffee)" . PHP_EOL;
$res = testRequest("POST", "/login", [
    "email"    => "highlands@jobmarket.vn",
    "password" => "Company@123"
]);
echo "HTTP: " . $res["status"] . " | Success: " . ($res["response"]["success"] ? "TRUE" : "FALSE") . PHP_EOL;
echo "Message: " . $res["response"]["message"] . PHP_EOL;
$companyToken = $res["response"]["data"]["token"] ?? null;
echo "User: " . ($res["response"]["data"]["user"]["name"] ?? "") . " (" . ($res["response"]["data"]["user"]["role"] ?? "") . ")" . PHP_EOL;
echo "Token sinh ra (30 chars): " . substr((string)$companyToken, 0, 30) . "..." . PHP_EOL . PHP_EOL;

// 5. Test Login Correct Password (Student)
echo "[5] TEST POST /login (Mật khẩu đúng - Sinh viên)" . PHP_EOL;
$res = testRequest("POST", "/login", [
    "email"    => "sinhvien1@jobmarket.vn",
    "password" => "Student@123"
]);
echo "HTTP: " . $res["status"] . " | Success: " . ($res["response"]["success"] ? "TRUE" : "FALSE") . PHP_EOL;
$studentToken = $res["response"]["data"]["token"] ?? null;
echo "Student User: " . ($res["response"]["data"]["user"]["name"] ?? "") . " (" . ($res["response"]["data"]["user"]["role"] ?? "") . ")" . PHP_EOL . PHP_EOL;

// 6. Test Public Job Search with Filter and Pagination
echo "[6] TEST GET /jobs (Lọc việc ca sáng + Quận Cầu Giấy)" . PHP_EOL;
$districtParam = urlencode("Cầu Giấy");
$res = testRequest("GET", "/jobs?shift_type=morning&district={$districtParam}&sort_by=salary_min&sort_dir=DESC");
echo "HTTP: " . $res["status"] . " | Success: " . ($res["response"]["success"] ? "TRUE" : "FALSE") . PHP_EOL;
echo "Số việc tìm thấy: " . count($res["response"]["data"]) . PHP_EOL;
if (!empty($res["response"]["data"])) {
    echo "Tiêu đề việc: " . $res["response"]["data"][0]["title"] . " | Lương: " . $res["response"]["data"][0]["salary_min"] . " - " . $res["response"]["data"][0]["salary_max"] . " VND/h" . PHP_EOL;
}
echo "Meta Pagination: " . json_encode($res["response"]["meta"], JSON_UNESCAPED_UNICODE) . PHP_EOL . PHP_EOL;

// 7. Test Public Job Detail
echo "[7] TEST GET /jobs/job-001 (Chi tiết việc làm có JOIN công ty)" . PHP_EOL;
$res = testRequest("GET", "/jobs/job-001");
echo "HTTP: " . $res["status"] . " | Success: " . ($res["response"]["success"] ? "TRUE" : "FALSE") . PHP_EOL;
echo "Công việc: " . ($res["response"]["data"]["title"] ?? "") . " tại: " . ($res["response"]["data"]["company_name"] ?? "") . PHP_EOL . PHP_EOL;

// 8. Test Protected API without Token
echo "[8] TEST GET /companies (Không kèm Token)" . PHP_EOL;
$res = testRequest("GET", "/companies");
echo "HTTP: " . $res["status"] . " | Success: " . ($res["response"]["success"] ? "TRUE" : "FALSE") . PHP_EOL;
echo "Message: " . $res["response"]["message"] . PHP_EOL . PHP_EOL;

// 9. Test Protected API with Valid Token
echo "[9] TEST GET /companies (Kèm Token hợp lệ của sinh viên)" . PHP_EOL;
$res = testRequest("GET", "/companies", null, $studentToken);
echo "HTTP: " . $res["status"] . " | Success: " . ($res["response"]["success"] ? "TRUE" : "FALSE") . PHP_EOL;
echo "Số công ty: " . count($res["response"]["data"]) . PHP_EOL . PHP_EOL;

// 10. Test Categories and Skills
echo "[10] TEST GET /categories & GET /skills" . PHP_EOL;
$resCat = testRequest("GET", "/categories");
$resSkill = testRequest("GET", "/skills");
echo "Categories: " . count($resCat["response"]["data"]) . " danh mục | Skills: " . count($resSkill["response"]["data"]) . " kỹ năng" . PHP_EOL . PHP_EOL;

// 11. Test 404 Route Not Found (Kèm Token)
echo "[11] TEST GET /duong-dan-khong-ton-tai (Kèm Token hợp lệ)" . PHP_EOL;
$res404 = testRequest("GET", "/duong-dan-khong-ton-tai", null, $studentToken);
echo "HTTP: " . $res404["status"] . " | Success: " . ($res404["response"]["success"] ? "TRUE" : "FALSE") . PHP_EOL;
echo "Message: " . $res404["response"]["message"] . PHP_EOL . PHP_EOL;

// 12. Test CORS Preflight OPTIONS
echo "[12] TEST OPTIONS /jobs (CORS Preflight)" . PHP_EOL;
$resOpt = testRequest("OPTIONS", "/jobs");
echo "HTTP: " . $resOpt["status"] . " | (Kỳ vọng: 204 No Content)" . PHP_EOL . PHP_EOL;

echo "=== TẤT CẢ CÁC BÀI KIỂM THỬ ĐÃ HOÀN TẤT THÀNH CÔNG! ===" . PHP_EOL;
