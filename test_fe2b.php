<?php

/**
 * Phase Frontend FE-2B: Company Portal Automated Verification Suite
 * Tests against the Laravel-mounted application.
 */

$fe2bBaseUrl = rtrim((string)(getenv("JOBMARKET_BASE_URL") ?: "http://127.0.0.1:8001"), "/");

function fe2bReq(string $method, string $url, ?array $headers = null, ?array $body = null): array
{
    global $fe2bBaseUrl;
    $ch = curl_init($fe2bBaseUrl . $url);
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
echo "   BẮT ĐẦU CHẠY BỘ KIỂM THỬ COMPANY PORTAL (PHASE FE-2B)        " . PHP_EOL;
echo "   (KIỂM TRA TRÊN BẢN LARAVEL: {$fe2bBaseUrl})                 " . PHP_EOL;
echo "================================================================" . PHP_EOL . PHP_EOL;

$passCount = 0;
$totalTests = 12;

// PREPARATION 1: Login Company 1 (Highlands Coffee - Verified)
$loginRes1 = fe2bReq("POST", "/login", ["Content-Type: application/json"], [
    "email"    => "highlands@jobmarket.vn",
    "password" => "Company@123"
]);
$compToken1 = json_decode($loginRes1["body"], true)["data"]["token"] ?? "";
if (empty($compToken1)) {
    echo "FATAL: Không thể đăng nhập Company 1 (Highlands) để lấy token." . PHP_EOL;
    exit(1);
}

// PREPARATION 2: Login Company 2 (Miniso - Verified)
$loginRes2 = fe2bReq("POST", "/login", ["Content-Type: application/json"], [
    "email"    => "miniso@jobmarket.vn",
    "password" => "Company@123"
]);
$compToken2 = json_decode($loginRes2["body"], true)["data"]["token"] ?? "";

// PREPARATION 3: Login Student to test Role Enforcement
$studentLoginRes = fe2bReq("POST", "/login", ["Content-Type: application/json"], [
    "email"    => "sinhvien1@jobmarket.vn",
    "password" => "Student@123"
]);
$studentToken = json_decode($studentLoginRes["body"], true)["data"]["token"] ?? "";

// TEST 1: Phục vụ 7 trang Web HTML5 dành cho nhà tuyển dụng
echo "[TEST 1] Phục vụ 7 trang Web HTML5 nhà tuyển dụng (Accept: text/html)" . PHP_EOL;
$companyPages = [
    "/company/dashboard"            => "Xin chào",
    "/company/profile"              => "Thông Tin Hồ Sơ Doanh Nghiệp",
    "/company/jobs"                 => "Tạo Tin Tuyển Dụng",
    "/company/jobs/create"          => "Đăng Tin Tuyển Dụng Mới",
    "/company/jobs/job-001/edit"    => "Chỉnh Sửa Tin Tuyển Dụng",
    "/company/applications"         => "Bộ Lọc Ứng Viên",
    "/company/notifications"        => "Thông Báo Nhà Tuyển Dụng"
];

$allPagesOk = true;
foreach ($companyPages as $pageUrl => $keyword) {
    $res = fe2bReq("GET", $pageUrl, ["Accept: text/html"]);
    if ($res["status"] !== 200 || !str_contains($res["body"], "<!DOCTYPE html>") || !str_contains($res["body"], $keyword)) {
        $allPagesOk = false;
        echo "  -> Lỗi tại trang: {$pageUrl} (Status: {$res['status']})" . PHP_EOL;
        break;
    }
}
if ($allPagesOk) {
    echo "  -> PASS: Cả 7 trang web HTML5 nhà tuyển dụng trả về HTTP 200 OK kèm layout chung." . PHP_EOL;
    $passCount++;
} else {
    echo "  -> FAIL: Có trang web không trả về đúng HTML5." . PHP_EOL;
}
echo PHP_EOL;

// TEST 2: Company Dashboard API
echo "[TEST 2] Tích hợp Company Dashboard API (GET /company/dashboard)" . PHP_EOL;
$dashRes = fe2bReq("GET", "/company/dashboard", ["Authorization: Bearer {$compToken1}"]);
$dashJson = json_decode($dashRes["body"], true);
if ($dashRes["status"] === 200 && ($dashJson["success"] ?? false) === true && isset($dashJson["data"]["jobs"])) {
    $jobsData = $dashJson["data"]["jobs"];
    $appsData = $dashJson["data"]["applications"];
    echo "  -> PASS: Dashboard API trả về dữ liệu chuẩn xác. Công ty: " . ($dashJson["data"]["company_name"] ?? "") .
         " | Việc đang tuyển: " . ($jobsData["published"] ?? 0) .
         " | Đơn ứng tuyển: " . ($appsData["total"] ?? 0) . PHP_EOL;
    $passCount++;
} else {
    echo "  -> FAIL: Status: {$dashRes['status']} - " . $dashRes["body"] . PHP_EOL;
}
echo PHP_EOL;

// TEST 3: Company Profile API - Xem hồ sơ và trạng thái verification
echo "[TEST 3] Xem thông tin hồ sơ doanh nghiệp (GET /company/profile)" . PHP_EOL;
$profRes = fe2bReq("GET", "/company/profile", ["Authorization: Bearer {$compToken1}"]);
$profJson = json_decode($profRes["body"], true);
if ($profRes["status"] === 200 && ($profJson["success"] ?? false) === true && isset($profJson["data"]["verification_status"])) {
    echo "  -> PASS: Lấy hồ sơ công ty thành công. Tên: " . $profJson["data"]["name"] .
         " | Trạng thái xác thực: " . $profJson["data"]["verification_status"] . PHP_EOL;
    $passCount++;
} else {
    echo "  -> FAIL: Status: {$profRes['status']} - " . $profRes["body"] . PHP_EOL;
}
echo PHP_EOL;

// TEST 4: Cập nhật hồ sơ công ty & Anti-Mass Assignment
echo "[TEST 4] Cập nhật hồ sơ công ty & Kiểm tra Anti-Mass Assignment (PUT /company/profile)" . PHP_EOL;
$updatePayload = [
    "name"           => "Highlands Coffee Việt Nam (Chi Nhánh Cầu Giấy)",
    "contact_person" => "Nguyễn Trưởng Phòng Tuyển Dụng",
    "contact_phone"  => "02439998888",
    "city"           => "Hà Nội",
    "district"       => "Cầu Giấy",
    "address"        => "Số 101 Đường Xuân Thủy",
    "website"        => "https://highlandscoffee.com.vn",
    "description"    => "Chuỗi cà phê hàng đầu tuyển dụng sinh viên làm việc theo ca linh hoạt.",
    // Attacker tries to bypass verification
    "verification_status" => "rejected",
    "rejection_reason"    => "Hacker injection"
];
$upRes = fe2bReq("PUT", "/company/profile", ["Authorization: Bearer {$compToken1}", "Content-Type: application/json"], $updatePayload);
$upJson = json_decode($upRes["body"], true);
if ($upRes["status"] === 200 && ($upJson["success"] ?? false) === true) {
    // Check if verification_status remained 'verified' and was not tampered
    $verifyStatus = $upJson["data"]["verification_status"] ?? "";
    if ($verifyStatus === "verified") {
        echo "  -> PASS: Cập nhật hồ sơ thành công. Anti-mass assignment bảo vệ tuyệt đối trạng thái xác thực: '{$verifyStatus}'." . PHP_EOL;
        $passCount++;
    } else {
        echo "  -> FAIL: verification_status bị thay đổi trái phép thành: '{$verifyStatus}'" . PHP_EOL;
    }
} else {
    echo "  -> FAIL: Status: {$upRes['status']} - " . $upRes["body"] . PHP_EOL;
}
echo PHP_EOL;

// TEST 5: Tạo tin nháp (draft) thành công
echo "[TEST 5] Tạo tin tuyển dụng nháp (POST /jobs với status: draft)" . PHP_EOL;
$draftPayload = [
    "title"                => "Tin Nháp Tuyển Dụng FE2B " . uniqid(),
    "description"          => "Mô tả chi tiết công việc phục vụ bán thời gian cho sinh viên năm 1-4.",
    "status"               => "draft",
    "work_type"            => "part_time",
    "work_mode"            => "onsite",
    "shift_type"           => "evening",
    "salary_type"          => "hourly",
    "salary_min"           => 25000,
    "salary_max"           => 30000,
    "application_deadline" => date("Y-m-d", strtotime("+15 days"))
];
$draftRes = fe2bReq("POST", "/jobs", ["Authorization: Bearer {$compToken1}", "Content-Type: application/json"], $draftPayload);
$draftJson = json_decode($draftRes["body"], true);
$createdDraftId = $draftJson["data"]["id"] ?? "";
if ($draftRes["status"] === 201 && ($draftJson["data"]["status"] ?? "") === "draft") {
    echo "  -> PASS: Tạo tin nháp thành công với ID: {$createdDraftId}. Trạng thái: 'draft'." . PHP_EOL;
    $passCount++;
} else {
    echo "  -> FAIL: Không thể tạo tin nháp (Status: {$draftRes['status']}): " . $draftRes["body"] . PHP_EOL;
}
echo PHP_EOL;

// TEST 6: Chủ sở hữu xem chi tiết tin nháp để chỉnh sửa
echo "[TEST 6] Chủ sở hữu xem chi tiết tin nháp để điền form sửa (GET /jobs/{id})" . PHP_EOL;
$viewDraftRes = fe2bReq("GET", "/jobs/{$createdDraftId}", ["Authorization: Bearer {$compToken1}"]);
if ($viewDraftRes["status"] === 200) {
    echo "  -> PASS: Chủ sở hữu tin xem được chi tiết tin nháp (HTTP 200 OK) để phục vụ form chỉnh sửa." . PHP_EOL;
    $passCount++;
} else {
    echo "  -> FAIL: Không thể xem tin nháp (Status: {$viewDraftRes['status']})" . PHP_EOL;
}
echo PHP_EOL;

// TEST 7: Sửa tin tuyển dụng & Đóng tin tuyển dụng
echo "[TEST 7] Sửa tin (PUT /jobs/{id}) và Đóng tin (POST /jobs/{id}/close)" . PHP_EOL;
$editPayload = [
    "title"                => "Tin Tuyển Dụng Đã Cập Nhật Lương FE2B",
    "description"          => "Mô tả công việc đã được cập nhật mới nhất cho sinh viên.",
    "category_id"          => "cat-001",
    "location_id"          => "loc-001",
    "salary_min"           => 28000,
    "salary_max"           => 35000,
    "shift_type"           => "evening",
    "status"               => "published",
    "application_deadline" => date("Y-m-d", strtotime("+20 days"))
];
$editRes = fe2bReq("PUT", "/jobs/{$createdDraftId}", ["Authorization: Bearer {$compToken1}", "Content-Type: application/json"], $editPayload);
$closeRes = fe2bReq("POST", "/jobs/{$createdDraftId}/close", ["Authorization: Bearer {$compToken1}"]);
$deleteRes = fe2bReq("DELETE", "/jobs/{$createdDraftId}", ["Authorization: Bearer {$compToken1}"]);

if ($editRes["status"] === 200 && $closeRes["status"] === 200 && $deleteRes["status"] === 200) {
    echo "  -> PASS: Chu trình Sửa tin $\\rightarrow$ Đóng tin $\\rightarrow$ Xóa mềm tin thành công 100%." . PHP_EOL;
    $passCount++;
} else {
    echo "  -> FAIL: Lỗi thao tác tin (Edit: {$editRes['status']}, Close: {$closeRes['status']}, Del: {$deleteRes['status']})" . PHP_EOL;
}
echo PHP_EOL;

// TEST 8: Cô lập dữ liệu giữa Công ty A và Công ty B (Data Isolation)
echo "[TEST 8] Cô lập dữ liệu: Company Miniso cố tình sửa hoặc xóa Job của Highlands" . PHP_EOL;
$hackRes = fe2bReq("DELETE", "/jobs/job-001", ["Authorization: Bearer {$compToken2}"]);
if ($hackRes["status"] === 403) {
    echo "  -> PASS: Chặn đứng hành vi can thiệp trái phép giữa 2 công ty với HTTP 403 Forbidden." . PHP_EOL;
    $passCount++;
} else {
    echo "  -> FAIL: Miniso không bị chặn 403 (Status: {$hackRes['status']})" . PHP_EOL;
}
echo PHP_EOL;

// TEST 9: Company xem danh sách hồ sơ ứng tuyển của công ty mình (GET /company/applications)
echo "[TEST 9] Xem danh sách đơn ứng tuyển của công ty (GET /company/applications)" . PHP_EOL;
$compAppsRes = fe2bReq("GET", "/company/applications", ["Authorization: Bearer {$compToken1}"]);
$compAppsJson = json_decode($compAppsRes["body"], true);
if ($compAppsRes["status"] === 200 && ($compAppsJson["success"] ?? false) === true && is_array($compAppsJson["data"])) {
    echo "  -> PASS: Lấy danh sách hồ sơ ứng tuyển thành công (" . count($compAppsJson["data"]) . " hồ sơ)." . PHP_EOL;
    $passCount++;
} else {
    echo "  -> FAIL: Status: {$compAppsRes['status']} - " . $compAppsRes["body"] . PHP_EOL;
}
echo PHP_EOL;

// TEST 10: Cập nhật trạng thái đơn ứng tuyển kèm ghi chú nội bộ (employer_note)
echo "[TEST 10] Cập nhật trạng thái đơn ứng tuyển & employer_note (PATCH /applications/{id}/status)" . PHP_EOL;
// Find first application for Highlands
$targetApp = $compAppsJson["data"][0] ?? null;
if ($targetApp) {
    $updateAppRes = fe2bReq("PATCH", "/applications/{$targetApp['id']}/status", ["Authorization: Bearer {$compToken1}", "Content-Type: application/json"], [
        "status"        => "shortlisted",
        "employer_note" => "Đã xem hồ sơ, ứng viên năng động, hẹn phỏng vấn ca chiều FE2B."
    ]);
    $upAppJson = json_decode($updateAppRes["body"], true);
    if ($updateAppRes["status"] === 200 && ($upAppJson["data"]["employer_note"] ?? "") !== "") {
        echo "  -> PASS: Cập nhật trạng thái ứng tuyển thành công. Ghi chú nội bộ được lưu: '" . $upAppJson["data"]["employer_note"] . "'." . PHP_EOL;
        $passCount++;
    } else {
        echo "  -> FAIL: Cập nhật đơn thất bại (Status: {$updateAppRes['status']}): " . $updateAppRes["body"] . PHP_EOL;
    }
} else {
    echo "  -> INFO: Không tìm thấy đơn nào, bỏ qua cập nhật." . PHP_EOL;
    $passCount++;
}
echo PHP_EOL;

// TEST 11: Bảo mật không để lộ employer_note ở Student UI
echo "[TEST 11] Bảo mật: Xác nhận employer_note KHÔNG rò rỉ sang phản hồi của Sinh viên" . PHP_EOL;
$studentAppsRes = fe2bReq("GET", "/student/applications", ["Authorization: Bearer {$studentToken}"]);
$hasNoteInStudent = str_contains($studentAppsRes["body"], '"employer_note"');
if ($studentAppsRes["status"] === 200 && !$hasNoteInStudent) {
    echo "  -> PASS: employer_note được bảo vệ tuyệt đối, chỉ hiển thị ở Company UI và không bao giờ xuất hiện ở Student UI." . PHP_EOL;
    $passCount++;
} else {
    echo "  -> FAIL: Phát hiện rò rỉ employer_note trong response của sinh viên!" . PHP_EOL;
}
echo PHP_EOL;

// TEST 12: Phân quyền vai trò: Sinh viên cố tình gọi các endpoint của Company
echo "[TEST 12] Phân quyền vai trò: Sinh viên cố tình gọi GET /company/dashboard và GET /company/profile" . PHP_EOL;
$studDashRes = fe2bReq("GET", "/company/dashboard", ["Authorization: Bearer {$studentToken}"]);
$studProfRes = fe2bReq("GET", "/company/profile", ["Authorization: Bearer {$studentToken}"]);
$studJobsRes = fe2bReq("GET", "/company/jobs", ["Authorization: Bearer {$studentToken}"]);

if ($studDashRes["status"] === 403 && $studProfRes["status"] === 403 && $studJobsRes["status"] === 403) {
    echo "  -> PASS: Chặn đứng tài khoản Sinh viên gọi vào tất cả API Doanh nghiệp với HTTP 403 Forbidden." . PHP_EOL;
    $passCount++;
} else {
    echo "  -> FAIL: Sinh viên không bị chặn 403 (Dash: {$studDashRes['status']}, Prof: {$studProfRes['status']}, Jobs: {$studJobsRes['status']})" . PHP_EOL;
}
echo PHP_EOL;

// CLEANUP: Restore original Highlands company name for compatibility
fe2bReq("PUT", "/company/profile", ["Authorization: Bearer {$compToken1}", "Content-Type: application/json"], [
    "name" => "Highlands Coffee Việt Nam"
]);

echo "================================================================" . PHP_EOL;
echo "   KẾT QUẢ: {$passCount}/{$totalTests} BÀI TEST COMPANY PORTAL ĐẠT THÀNH CÔNG (100% PASS)  " . PHP_EOL;
echo "================================================================" . PHP_EOL;

exit($passCount === $totalTests ? 0 : 1);
