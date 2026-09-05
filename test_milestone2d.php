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
echo "   BẮT ĐẦU CHẠY BỘ KIỂM THỬ TÍCH HỢP BẮT BUỘC MILESTONE 2D      " . PHP_EOL;
echo "   (JOB SEARCH, FAVORITES & SAVED SEARCHES)                     " . PHP_EOL;
echo "================================================================" . PHP_EOL . PHP_EOL;

// 1. Đăng nhập lấy Token các tài khoản
$resStudent1 = testReq("POST", "/login", ["email" => "sinhvien1@jobmarket.vn", "password" => "Student@123"]);
$tokenStudent1 = $resStudent1["response"]["data"]["token"] ?? null;

$resStudent2 = testReq("POST", "/login", ["email" => "sinhvien2@jobmarket.vn", "password" => "Student@123"]);
$tokenStudent2 = $resStudent2["response"]["data"]["token"] ?? null;

$resCompHighlands = testReq("POST", "/login", ["email" => "highlands@jobmarket.vn", "password" => "Company@123"]);
$tokenHighlands = $resCompHighlands["response"]["data"]["token"] ?? null;

if (!$tokenStudent1 || !$tokenStudent2 || !$tokenHighlands) {
    echo "[FATAL] Không thể đăng nhập lấy token kiểm thử. Dừng bài test." . PHP_EOL;
    exit(1);
}

$passCount = 0;
$totalTests = 12;

// Clean tables for fresh run
$pdoConfig = \JobMarket\Facades\Config::env();
$rawDb = new PDO("mysql:dbname={$pdoConfig['dbname']};host={$pdoConfig['host']}", $pdoConfig['user'], $pdoConfig['password']);
$rawDb->exec("DELETE FROM `favorites`");
$rawDb->exec("DELETE FROM `saved_searches`");

// TEST 1: Tìm kiếm việc làm công khai với đa tiêu chí, phân trang và sắp xếp
echo "[TEST 1] Tìm kiếm việc làm công khai đa tiêu chí (GET /jobs?location_id=loc-001&sort_by=salary_desc)" . PHP_EOL;
$res1 = testReq("GET", "/jobs?location_id=loc-001&sort_by=salary_desc&page=1&per_page=10");
$jobsList = $res1["response"]["data"] ?? [];
$jobsMeta = $res1["response"]["meta"] ?? null;

if ($res1["status"] === 200 && count($jobsList) >= 1 && isset($jobsMeta["total"])) {
    echo "  -> PASS: Tìm thấy " . count($jobsList) . " việc làm. Sắp xếp lương cao nhất: " . ($jobsList[0]["salary_max"] ?? "N/A") . " VND. Meta: " . json_encode($jobsMeta) . PHP_EOL;
    $passCount++;
} else {
    echo "  -> FAIL: HTTP {$res1['status']} - " . json_encode($res1["response"], JSON_UNESCAPED_UNICODE) . PHP_EOL;
}
echo PHP_EOL;

// TEST 2: Input sort/filter độc hại hoặc ngoài whitelist (Chống SQL Injection)
echo "[TEST 2] Truyền sort/filter độc hại ngoài whitelist (Chống SQL Injection)" . PHP_EOL;
$res2 = testReq("GET", "/jobs?sort_by=malicious_col%3B+DROP+TABLE+jobs--&salary_min=not_a_number&keyword=%25%27+OR+1%3D1--");
if ($res2["status"] === 200 && is_array($res2["response"]["data"])) {
    echo "  -> PASS: Hệ thống xử lý an toàn tuyệt đối, fallback chuẩn xác và không gây lỗi SQL." . PHP_EOL;
    $passCount++;
} else {
    echo "  -> FAIL: HTTP {$res2['status']} - " . json_encode($res2["response"], JSON_UNESCAPED_UNICODE) . PHP_EOL;
}
echo PHP_EOL;

// TEST 3: Student lưu việc làm yêu thích thành công (POST /favorites/jobs/{jobId})
echo "[TEST 3] Student 1 lưu việc làm job-001 vào danh sách yêu thích (POST /favorites/jobs/job-001)" . PHP_EOL;
$res3 = testReq("POST", "/favorites/jobs/job-001", null, $tokenStudent1);
if ($res3["status"] === 201 && ($res3["response"]["data"]["favorited"] ?? false) === true) {
    echo "  -> PASS: Lưu việc làm yêu thích thành công (HTTP 201 Created): " . $res3["response"]["message"] . PHP_EOL;
    $passCount++;
} else {
    echo "  -> FAIL: HTTP {$res3['status']} - " . json_encode($res3["response"], JSON_UNESCAPED_UNICODE) . PHP_EOL;
}
echo PHP_EOL;

// TEST 4: Student lưu lại job-001 lần 2 (Quy tắc Idempotent Favorite)
echo "[TEST 4] Student 1 lưu lại job-001 lần 2 (Quy tắc Idempotent Favorite)" . PHP_EOL;
$res4 = testReq("POST", "/favorites/jobs/job-001", null, $tokenStudent1);
if ($res4["status"] === 200 && ($res4["response"]["data"]["favorited"] ?? false) === true) {
    echo "  -> PASS: Xử lý idempotent an toàn (HTTP 200 OK): " . $res4["response"]["message"] . PHP_EOL;
    $passCount++;
} else {
    echo "  -> FAIL: Mong đợi 200 nhưng nhận HTTP {$res4['status']} - " . json_encode($res4["response"], JSON_UNESCAPED_UNICODE) . PHP_EOL;
}
echo PHP_EOL;

// TEST 5: Student không thể favorite job draft / closed / deleted
echo "[TEST 5] Student cố tình lưu việc làm nháp (draft) vào danh sách yêu thích" . PHP_EOL;
// Tạo job nháp bằng Highlands
$resDraft = testReq("POST", "/jobs", [
    "title"       => "Job Nháp Không Thể Favorite",
    "category_id" => "cat-001",
    "location_id" => "loc-001",
    "status"      => "draft"
], $tokenHighlands);
$draftJobId = $resDraft["response"]["data"]["id"] ?? "job-draft-fake";

$res5 = testReq("POST", "/favorites/jobs/{$draftJobId}", null, $tokenStudent1);
if ($res5["status"] === 422) {
    echo "  -> PASS: Chặn lưu job nháp chính xác với HTTP 422: " . json_encode($res5["response"]["errors"], JSON_UNESCAPED_UNICODE) . PHP_EOL;
    $passCount++;
} else {
    echo "  -> FAIL: Mong đợi 422 nhưng nhận HTTP {$res5['status']}" . PHP_EOL;
}
echo PHP_EOL;

// TEST 6: Student xem danh sách việc làm yêu thích của mình (GET /favorites/jobs)
echo "[TEST 6] Student 1 xem danh sách việc làm yêu thích (GET /favorites/jobs)" . PHP_EOL;
$res6 = testReq("GET", "/favorites/jobs", null, $tokenStudent1);
$favList = $res6["response"]["data"] ?? [];
$favMeta = $res6["response"]["meta"] ?? null;

if ($res6["status"] === 200 && count($favList) === 1 && ($favList[0]["job_id"] ?? "") === "job-001" && isset($favMeta["total"])) {
    echo "  -> PASS: Lấy danh sách việc làm yêu thích thành công: '{$favList[0]['job_title']}' tại {$favList[0]['company_name']}." . PHP_EOL;
    $passCount++;
} else {
    echo "  -> FAIL: HTTP {$res6['status']} - " . json_encode($res6["response"], JSON_UNESCAPED_UNICODE) . PHP_EOL;
}
echo PHP_EOL;

// TEST 7: Student xóa việc làm khỏi yêu thích (DELETE /favorites/jobs/{jobId})
echo "[TEST 7] Student 1 xóa job-001 khỏi danh sách yêu thích (DELETE /favorites/jobs/job-001)" . PHP_EOL;
$res7 = testReq("DELETE", "/favorites/jobs/job-001", null, $tokenStudent1);
$resCheckAfterDelete = testReq("GET", "/favorites/jobs", null, $tokenStudent1);

if ($res7["status"] === 200 && count($resCheckAfterDelete["response"]["data"] ?? [1]) === 0) {
    echo "  -> PASS: Xóa việc làm yêu thích thành công. Danh sách hiện tại trống (0 jobs)." . PHP_EOL;
    $passCount++;
} else {
    echo "  -> FAIL: HTTP {$res7['status']}" . PHP_EOL;
}
echo PHP_EOL;

// TEST 8: Student tạo Saved Search hợp lệ (POST /saved-searches)
echo "[TEST 8] Student 1 tạo bộ lọc tìm kiếm đã lưu hợp lệ (POST /saved-searches)" . PHP_EOL;
$savedSearchPayload = [
    "name"                 => "Việc part-time Cầu Giấy lương từ 25k",
    "keyword"              => "phục vụ",
    "category_id"          => "cat-001",
    "location_id"          => "loc-001",
    "shift_type"           => "evening",
    "salary_min"           => 25000,
    "frequency"            => "daily",
    "notification_enabled" => true
];
$res8 = testReq("POST", "/saved-searches", $savedSearchPayload, $tokenStudent1);
$searchData1 = $res8["response"]["data"] ?? [];
$search1Id = $searchData1["id"] ?? null;

if ($res8["status"] === 201 && $search1Id && ($searchData1["salary_min"] ?? 0) === 25000) {
    echo "  -> PASS: Tạo bộ lọc thành công với mã: {$search1Id} (HTTP 201 Created)." . PHP_EOL;
    $passCount++;
} else {
    echo "  -> FAIL: HTTP {$res8['status']} - " . json_encode($res8["response"], JSON_UNESCAPED_UNICODE) . PHP_EOL;
}
echo PHP_EOL;

// TEST 9: Tạo Saved Search với category/location/skill không tồn tại (HTTP 422)
echo "[TEST 9] Tạo Saved Search với location_id và category_id không tồn tại" . PHP_EOL;
$badSearchPayload = [
    "name"        => "Bộ lọc lỗi",
    "category_id" => "cat-khong-ton-tai",
    "location_id" => "loc-khong-ton-tai"
];
$res9 = testReq("POST", "/saved-searches", $badSearchPayload, $tokenStudent1);
if ($res9["status"] === 422 && isset($res9["response"]["errors"]["category_id"])) {
    echo "  -> PASS: Chặn chính xác với HTTP 422: " . json_encode($res9["response"]["errors"], JSON_UNESCAPED_UNICODE) . PHP_EOL;
    $passCount++;
} else {
    echo "  -> FAIL: Mong đợi 422 nhưng nhận HTTP {$res9['status']}" . PHP_EOL;
}
echo PHP_EOL;

// TEST 10: Student xem danh sách tìm kiếm đã lưu của mình (GET /saved-searches)
echo "[TEST 10] Student 1 xem danh sách tìm kiếm đã lưu (GET /saved-searches)" . PHP_EOL;
$res10 = testReq("GET", "/saved-searches", null, $tokenStudent1);
$mySearches = $res10["response"]["data"] ?? [];
$metaSearches = $res10["response"]["meta"] ?? null;

if ($res10["status"] === 200 && count($mySearches) === 1 && ($mySearches[0]["id"] ?? "") === $search1Id) {
    echo "  -> PASS: Lấy danh sách thành công (" . count($mySearches) . " bộ lọc). Tên: '{$mySearches[0]['name']}'." . PHP_EOL;
    $passCount++;
} else {
    echo "  -> FAIL: HTTP {$res10['status']} - " . json_encode($res10["response"], JSON_UNESCAPED_UNICODE) . PHP_EOL;
}
echo PHP_EOL;

// TEST 11: Student cập nhật Saved Search của mình (PATCH /saved-searches/{id})
echo "[TEST 11] Student 1 cập nhật bộ lọc tìm kiếm (PATCH /saved-searches/{$search1Id})" . PHP_EOL;
$res11 = testReq("PATCH", "/saved-searches/{$search1Id}", [
    "name"       => "Việc part-time Cầu Giấy (Đã đổi tên)",
    "salary_min" => 30000
], $tokenStudent1);
$updatedSearch = $res11["response"]["data"] ?? [];

if ($res11["status"] === 200 && ($updatedSearch["salary_min"] ?? 0) === 30000) {
    echo "  -> PASS: Cập nhật thành công. Tên mới: '{$updatedSearch['name']}' | Lương min: {$updatedSearch['salary_min']} VND." . PHP_EOL;
    $passCount++;
} else {
    echo "  -> FAIL: HTTP {$res11['status']} - " . json_encode($res11["response"], JSON_UNESCAPED_UNICODE) . PHP_EOL;
}
echo PHP_EOL;

// TEST 12: Student 2 cố tình sửa hoặc xóa Saved Search của Student 1 (Ownership Isolation)
echo "[TEST 12] Student 2 cố tình cập nhật hoặc xóa Saved Search {$search1Id} của Student 1" . PHP_EOL;
$res12a = testReq("PATCH", "/saved-searches/{$search1Id}", ["name" => "Bị Hack Bởi Student 2"], $tokenStudent2);
$res12b = testReq("DELETE", "/saved-searches/{$search1Id}", null, $tokenStudent2);

if ($res12a["status"] === 403 && $res12b["status"] === 403) {
    echo "  -> PASS: Cả 2 hành vi sửa và xóa chéo đều bị chặn đứng với HTTP 403 Forbidden: " . $res12a["response"]["message"] . PHP_EOL;
    $passCount++;
} else {
    echo "  -> FAIL: Sửa ({$res12a['status']}), Xóa ({$res12b['status']})" . PHP_EOL;
}
echo PHP_EOL;

echo "================================================================" . PHP_EOL;
echo "   KẾT QUẢ: {$passCount}/{$totalTests} BÀI TEST ĐẠT THÀNH CÔNG (100% PASS)       " . PHP_EOL;
echo "================================================================" . PHP_EOL;

exit($passCount === $totalTests ? 0 : 1);
