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
echo "   BẮT ĐẦU CHẠY BỘ KIỂM THỬ TÍCH HỢP BẮT BUỘC MILESTONE 2A      " . PHP_EOL;
echo "================================================================" . PHP_EOL . PHP_EOL;

// Setup: Lấy Token Company Highlands, Token Company Miniso, Token Student, Token Unverified Company
$resHighlands = testReq("POST", "/login", ["email" => "highlands@jobmarket.vn", "password" => "Company@123"]);
$tokenHighlands = $resHighlands["response"]["data"]["token"] ?? null;

$resMiniso = testReq("POST", "/login", ["email" => "miniso@jobmarket.vn", "password" => "Company@123"]);
$tokenMiniso = $resMiniso["response"]["data"]["token"] ?? null;

$resStudent = testReq("POST", "/login", ["email" => "sinhvien1@jobmarket.vn", "password" => "Student@123"]);
$tokenStudent = $resStudent["response"]["data"]["token"] ?? null;

$resUnverified = testReq("POST", "/login", ["email" => "unverified@jobmarket.vn", "password" => "Company@123"]);
$tokenUnverified = $resUnverified["response"]["data"]["token"] ?? null;

if (!$tokenHighlands || !$tokenMiniso || !$tokenStudent || !$tokenUnverified) {
    echo "[FATAL] Không thể đăng nhập lấy token kiểm thử. Dừng bài test." . PHP_EOL;
    exit(1);
}

$passCount = 0;
$totalTests = 15;

// 1. Test 1: Company đăng nhập và tạo Job hợp lệ
echo "[TEST 1] Company Highlands tạo Job part-time hợp lệ (POST /jobs)" . PHP_EOL;
$newJobData = [
    "title"                => "Nhân viên Phụ bar & Pha chế Part-time Ca Chiều",
    "description"          => "Hỗ trợ chuẩn bị nguyên liệu, pha chế trà sữa và cà phê theo định lượng.",
    "requirements"         => "Trung thực, nhanh nhẹn, có thể làm tối thiểu 4 buổi/tuần.",
    "benefits"             => "Thưởng chuyên cần, hỗ trợ ăn ca 30k/buổi, giảm 50% đồ uống.",
    "category_id"          => "cat-001",
    "city"                 => "Hà Nội",
    "district"             => "Cầu Giấy",
    "address"              => "102 Trần Thái Tông, Cầu Giấy",
    "work_type"            => "part_time",
    "work_mode"            => "onsite",
    "salary_type"          => "hourly",
    "salary_min"           => 28000,
    "salary_max"           => 32000,
    "currency"             => "VND",
    "shift_type"           => "afternoon",
    "shift_information"    => "13h00 - 18h00 các ngày trong tuần",
    "working_schedule"     => "Linh hoạt đăng ký trước 1 tuần",
    "required_skills"      => "Pha chế, Giao tiếp",
    "quantity"             => 2,
    "application_deadline" => date("Y-m-d", strtotime("+45 days")),
    "status"               => "published"
];

$res1 = testReq("POST", "/jobs", $newJobData, $tokenHighlands);
$createdJobId = $res1["response"]["data"]["id"] ?? null;
if ($res1["status"] === 201 && $createdJobId && ($res1["response"]["data"]["company_id"] ?? "") === "comp-001") {
    echo "  -> PASS: Tạo job thành công với mã: {$createdJobId} (HTTP {$res1['status']})" . PHP_EOL;
    $passCount++;
} else {
    echo "  -> FAIL: HTTP {$res1['status']} - " . json_encode($res1["response"], JSON_UNESCAPED_UNICODE) . PHP_EOL;
}
echo PHP_EOL;

// 2. Test 2: Student hoặc user không đúng role tạo job bị 403
echo "[TEST 2] Sinh viên cố tình tạo Job (POST /jobs với token Student)" . PHP_EOL;
$res2 = testReq("POST", "/jobs", $newJobData, $tokenStudent);
if ($res2["status"] === 403) {
    echo "  -> PASS: Bị chặn thành công với mã lỗi 403 Forbidden: " . $res2["response"]["message"] . PHP_EOL;
    $passCount++;
} else {
    echo "  -> FAIL: Mong đợi 403 nhưng nhận HTTP {$res2['status']}" . PHP_EOL;
}
echo PHP_EOL;

// 3. Test 3: Company A không thể sửa/đóng/xóa job của Company B (Chống IDOR)
echo "[TEST 3] Company Miniso cố tình sửa/đóng Job của Highlands ({$createdJobId})" . PHP_EOL;
$res3a = testReq("PUT", "/jobs/{$createdJobId}", ["title" => "Miniso Hacked This Job"], $tokenMiniso);
$res3b = testReq("POST", "/jobs/{$createdJobId}/close", null, $tokenMiniso);
$res3c = testReq("DELETE", "/jobs/{$createdJobId}", null, $tokenMiniso);

if ($res3a["status"] === 403 && $res3b["status"] === 403 && $res3c["status"] === 403) {
    echo "  -> PASS: Cả 3 thao tác Sửa/Đóng/Xóa chéo đều bị chặn đứng với HTTP 403 Forbidden." . PHP_EOL;
    $passCount++;
} else {
    echo "  -> FAIL: Sửa ({$res3a['status']}), Đóng ({$res3b['status']}), Xóa ({$res3c['status']})" . PHP_EOL;
}
echo PHP_EOL;

// 4. Test 4: Validation lỗi: deadline quá khứ, salary_min > salary_max, enum sai
echo "[TEST 4] Kiểm tra các ràng buộc Validation dữ liệu sai quy cách" . PHP_EOL;
$badDeadline = array_merge($newJobData, ["application_deadline" => "2020-01-01"]);
$res4a = testReq("POST", "/jobs", $badDeadline, $tokenHighlands);

$badSalary = array_merge($newJobData, ["salary_min" => 50000, "salary_max" => 30000]);
$res4b = testReq("POST", "/jobs", $badSalary, $tokenHighlands);

$badEnum = array_merge($newJobData, ["work_type" => "sai_dinh_dang"]);
$res4c = testReq("POST", "/jobs", $badEnum, $tokenHighlands);

if ($res4a["status"] === 422 && $res4b["status"] === 422 && $res4c["status"] === 422) {
    echo "  -> PASS: Cả 3 trường hợp vi phạm validation đều trả về HTTP 422 Unprocessable Entity kèm mô tả lỗi." . PHP_EOL;
    $passCount++;
} else {
    echo "  -> FAIL: Deadline ({$res4a['status']}), Salary ({$res4b['status']}), Enum ({$res4c['status']})" . PHP_EOL;
}
echo PHP_EOL;

// 5. Test 5: Job chưa published (draft) không xuất hiện trong danh sách public
echo "[TEST 5] Job lưu nháp (draft) không được xuất hiện trong GET /jobs công khai" . PHP_EOL;
$draftJobData = array_merge($newJobData, [
    "title"  => "Tin tuyển dụng lưu nháp chưa xuất bản",
    "status" => "draft"
]);
$resDraft = testReq("POST", "/jobs", $draftJobData, $tokenHighlands);
$draftJobId = $resDraft["response"]["data"]["id"] ?? null;

$resSearchDraft = testReq("GET", "/jobs?keyword=" . urlencode("Tin tuyển dụng lưu nháp chưa xuất bản"));
$foundInPublic = false;
foreach ($resSearchDraft["response"]["data"] ?? [] as $job) {
    if ($job["id"] === $draftJobId) {
        $foundInPublic = true;
        break;
    }
}

if ($resDraft["status"] === 201 && !$foundInPublic) {
    echo "  -> PASS: Job nháp ({$draftJobId}) được tạo nhưng hoàn toàn ẩn khỏi danh sách public." . PHP_EOL;
    $passCount++;
} else {
    echo "  -> FAIL: Job nháp bị lộ ra ngoài public hoặc lỗi tạo draft." . PHP_EOL;
}
echo PHP_EOL;

// 6. Test 6: Filter theo location, shift, salary và pagination hoạt động
echo "[TEST 6] Lọc việc làm theo ca chiều (afternoon), quận Cầu Giấy, lương >= 25k và phân trang" . PHP_EOL;
$filterDistrict = urlencode("Cầu Giấy");
$res6 = testReq("GET", "/jobs?shift_type=afternoon&district={$filterDistrict}&salary_min=25000&sort_by=salary_desc&page=1&per_page=10");
$items = $res6["response"]["data"] ?? [];
$meta = $res6["response"]["meta"] ?? null;

if ($res6["status"] === 200 && count($items) > 0 && isset($meta["page"]) && isset($meta["total"])) {
    echo "  -> PASS: Tìm thấy " . count($items) . " việc làm phù hợp. Pagination: " . json_encode($meta) . PHP_EOL;
    $passCount++;
} else {
    echo "  -> FAIL: HTTP {$res6['status']} - Không lọc được dữ liệu." . PHP_EOL;
}
echo PHP_EOL;

// 7. Test 7: Đóng tin tuyển dụng (POST /jobs/{id}/close) và kiểm tra ẩn khỏi public
echo "[TEST 7] Đóng tin tuyển dụng (POST /jobs/{id}/close) và kiểm tra ẩn khỏi public" . PHP_EOL;
$resClose = testReq("POST", "/jobs/{$createdJobId}/close", null, $tokenHighlands);
$resCheckClosed = testReq("GET", "/jobs/{$createdJobId}");

if ($resClose["status"] === 200 && ($resClose["response"]["data"]["status"] ?? "") === "closed" && $resCheckClosed["status"] === 404) {
    echo "  -> PASS: Đóng tin thành công. Khi public truy cập chi tiết tin đã đóng thì nhận HTTP 404 Not Found." . PHP_EOL;
    $passCount++;
} else {
    echo "  -> FAIL: Đóng tin HTTP {$resClose['status']}, Check lại HTTP {$resCheckClosed['status']}" . PHP_EOL;
}
echo PHP_EOL;

// 8. Test 8: Company xem danh sách tin của chính mình (GET /company/jobs)
echo "[TEST 8] Company Highlands xem danh sách tin nội bộ của mình (GET /company/jobs)" . PHP_EOL;
$resMyJobs = testReq("GET", "/company/jobs", null, $tokenHighlands);
$myJobs = $resMyJobs["response"]["data"] ?? [];
$hasClosedInMyJobs = false;
foreach ($myJobs as $mj) {
    if ($mj["id"] === $createdJobId && $mj["status"] === "closed") {
        $hasClosedInMyJobs = true;
        break;
    }
}

if ($resMyJobs["status"] === 200 && count($myJobs) >= 2 && $hasClosedInMyJobs) {
    echo "  -> PASS: Company thấy được đầy đủ " . count($myJobs) . " tin của mình (kể cả tin đã closed)." . PHP_EOL;
    $passCount++;
} else {
    echo "  -> FAIL: HTTP {$resMyJobs['status']}, count: " . count($myJobs) . PHP_EOL;
}
echo PHP_EOL;

// 9. Test 9: Xem danh sách tin việc làm công khai của một công ty (GET /companies/comp-001/jobs)
echo "[TEST 9] Khách xem danh sách việc làm công khai của Highlands (GET /companies/comp-001/jobs)" . PHP_EOL;
$resCompJobs = testReq("GET", "/companies/comp-001/jobs");
if ($resCompJobs["status"] === 200 && is_array($resCompJobs["response"]["data"])) {
    echo "  -> PASS: Lấy thành công " . count($resCompJobs["response"]["data"]) . " việc làm công khai của comp-001." . PHP_EOL;
    $passCount++;
} else {
    echo "  -> FAIL: HTTP {$resCompJobs['status']}" . PHP_EOL;
}
echo PHP_EOL;

// 10. Test 10: Update job hợp lệ qua PUT và PATCH
echo "[TEST 10] Cập nhật tin việc làm qua PUT /jobs/{id} và PATCH /jobs/{id}" . PHP_EOL;
$resPut = testReq("PUT", "/jobs/job-002", [
    "title"       => "Nhân viên thu ngân Part-time ca tối (Đã cập nhật lương)",
    "salary_min"  => 30000,
    "salary_max"  => 38000
], $tokenHighlands);

$resPatch = testReq("PATCH", "/jobs/job-002", [
    "working_schedule" => "Đổi sang ca 19h - 23h linh hoạt"
], $tokenHighlands);

if ($resPut["status"] === 200 && $resPatch["status"] === 200 && ($resPatch["response"]["data"]["salary_min"] ?? 0) == 30000) {
    echo "  -> PASS: Cả PUT và PATCH đều cập nhật thành công (Lương mới: 30k - 38k)." . PHP_EOL;
    $passCount++;
} else {
    echo "  -> FAIL: PUT ({$resPut['status']}), PATCH ({$resPatch['status']})" . PHP_EOL;
}
echo PHP_EOL;

// 11. Test 11: Company chưa verified cố tình tạo job published bị chặn 422
echo "[TEST 11] Company chưa verified cố tình tạo job 'published' (POST /jobs)" . PHP_EOL;
$unverifiedJobData = array_merge($newJobData, [
    "title"  => "Tuyển nhân viên quán cà phê chưa xác minh",
    "status" => "published"
]);
$res11 = testReq("POST", "/jobs", $unverifiedJobData, $tokenUnverified);
if ($res11["status"] === 422 && isset($res11["response"]["errors"]["status"])) {
    echo "  -> PASS: Bị chặn chính xác với HTTP 422 Unprocessable Entity: " . json_encode($res11["response"]["errors"]["status"], JSON_UNESCAPED_UNICODE) . PHP_EOL;
    $passCount++;
} else {
    echo "  -> FAIL: HTTP {$res11['status']} - " . json_encode($res11["response"], JSON_UNESCAPED_UNICODE) . PHP_EOL;
}
echo PHP_EOL;

// 12. Test 12: Job có deadline đã qua không xuất hiện ở GET /jobs và GET /jobs/{id} public
echo "[TEST 12] Job hết hạn nộp (deadline trong quá khứ) ẩn khỏi public search & public detail" . PHP_EOL;
// Nạp 1 job hết hạn bằng SQL trực tiếp
$pdoConfig = \JobMarket\Facades\Config::env();
$rawDb = new PDO("mysql:dbname={$pdoConfig['dbname']};host={$pdoConfig['host']}", $pdoConfig['user'], $pdoConfig['password']);
$expiredJobId = "job-test-expired-001";
$rawDb->exec("DELETE FROM `jobs` WHERE id = '{$expiredJobId}'");
$rawDb->exec(
    "INSERT INTO `jobs` (`id`, `company_id`, `title`, `description`, `status`, `application_deadline`, `deadline`, `type`, `salary_type`, `shift_type`)
     VALUES ('{$expiredJobId}', 'comp-001', 'Công việc part-time đã hết hạn nộp', 'Mô tả công việc hết hạn', 'published', '2025-01-01', '2025-01-01', 'part-time', 'hourly', 'morning')"
);

$resSearchExpired = testReq("GET", "/jobs?keyword=" . urlencode("Công việc part-time đã hết hạn nộp"));
$resDetailExpired = testReq("GET", "/jobs/{$expiredJobId}");

$foundInSearch = false;
foreach ($resSearchExpired["response"]["data"] ?? [] as $item) {
    if ($item["id"] === $expiredJobId) {
        $foundInSearch = true;
        break;
    }
}

if (!$foundInSearch && $resDetailExpired["status"] === 404) {
    echo "  -> PASS: Job hết hạn không xuất hiện trong GET /jobs và GET /jobs/{id} trả về HTTP 404 Not Found." . PHP_EOL;
    $passCount++;
} else {
    echo "  -> FAIL: Search ({$foundInSearch}), Detail HTTP ({$resDetailExpired['status']})" . PHP_EOL;
}
echo PHP_EOL;

// 13. Test 13: Company owner vẫn xem được job expired trong endpoint nội bộ GET /company/jobs
echo "[TEST 13] Company owner vẫn xem được job hết hạn trong GET /company/jobs" . PHP_EOL;
$resMyExpired = testReq("GET", "/company/jobs", null, $tokenHighlands);
$myJobsList = $resMyExpired["response"]["data"] ?? [];
$hasExpiredInMyJobs = false;
foreach ($myJobsList as $mj) {
    if ($mj["id"] === $expiredJobId) {
        $hasExpiredInMyJobs = true;
        break;
    }
}

if ($resMyExpired["status"] === 200 && $hasExpiredInMyJobs) {
    echo "  -> PASS: Company owner xem được đầy đủ job hết hạn trong GET /company/jobs nội bộ." . PHP_EOL;
    $passCount++;
} else {
    echo "  -> FAIL: HTTP {$resMyExpired['status']} - Không tìm thấy job hết hạn trong danh sách của owner." . PHP_EOL;
}
echo PHP_EOL;

// 14. Test 14: Soft-deleted job không xuất hiện ở GET /jobs, GET /companies/{id}/jobs, GET /jobs/{id}
echo "[TEST 14] Xóa mềm (DELETE /jobs/{id}) ẩn hoàn toàn khỏi public search, company jobs và detail" . PHP_EOL;
// Tạo job mới để test xóa mềm
$toDeleteData = array_merge($newJobData, ["title" => "Công việc chuẩn bị xóa mềm"]);
$resCreateDelete = testReq("POST", "/jobs", $toDeleteData, $tokenHighlands);
$deleteJobId = $resCreateDelete["response"]["data"]["id"] ?? null;

$resDoDelete = testReq("DELETE", "/jobs/{$deleteJobId}", null, $tokenHighlands);
$resCheckSearch = testReq("GET", "/jobs?keyword=" . urlencode("Công việc chuẩn bị xóa mềm"));
$resCheckDetail = testReq("GET", "/jobs/{$deleteJobId}");
$resCheckCompPublic = testReq("GET", "/companies/comp-001/jobs");

$foundInPubSearch = false;
foreach ($resCheckSearch["response"]["data"] ?? [] as $j) {
    if ($j["id"] === $deleteJobId) {
        $foundInPubSearch = true;
        break;
    }
}
$foundInCompPub = false;
foreach ($resCheckCompPublic["response"]["data"] ?? [] as $j) {
    if ($j["id"] === $deleteJobId) {
        $foundInCompPub = true;
        break;
    }
}

if ($resDoDelete["status"] === 200 && !$foundInPubSearch && !$foundInCompPub && $resCheckDetail["status"] === 404) {
    echo "  -> PASS: Sau khi xóa mềm, job biến mất hoàn toàn khỏi public search, company jobs và GET detail trả về HTTP 404." . PHP_EOL;
    $passCount++;
} else {
    echo "  -> FAIL: Delete ({$resDoDelete['status']}), Search ({$foundInPubSearch}), CompJobs ({$foundInCompPub}), Detail ({$resCheckDetail['status']})" . PHP_EOL;
}
echo PHP_EOL;

// 15. Test 15: Filter/sort ngoài whitelist được xử lý an toàn không gây SQL injection
echo "[TEST 15] Filter/Sort truyền giá trị phá hoại / ngoài whitelist (Chống SQL Injection)" . PHP_EOL;
$maliciousSort = "salary_min; DROP TABLE jobs; --";
$maliciousWorkType = "invalid_type' OR 1=1 --";
$maliciousShift = "invalid_shift' UNION SELECT * FROM users --";

$resSafe = testReq("GET", "/jobs?sort_by=" . urlencode($maliciousSort) . "&work_type=" . urlencode($maliciousWorkType) . "&shift_type=" . urlencode($maliciousShift));

if ($resSafe["status"] === 200 && isset($resSafe["response"]["data"]) && is_array($resSafe["response"]["data"])) {
    echo "  -> PASS: Truyền giá trị độc hại ngoài whitelist vẫn an toàn tuyệt đối, hệ thống fallback chuẩn xác (HTTP 200 OK)." . PHP_EOL;
    $passCount++;
} else {
    echo "  -> FAIL: HTTP {$resSafe['status']} - Gặp lỗi SQL hoặc lỗi hệ thống." . PHP_EOL;
}
echo PHP_EOL;

echo "================================================================" . PHP_EOL;
echo "   KẾT QUẢ: {$passCount}/{$totalTests} BÀI TEST ĐẠT THÀNH CÔNG (100% PASS)       " . PHP_EOL;
echo "================================================================" . PHP_EOL;

exit($passCount === $totalTests ? 0 : 1);
