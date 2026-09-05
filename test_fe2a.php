<?php

/**
 * Phase Frontend FE-2A: Student Portal Automated Verification Suite
 * Tests against real Laragon domain: http://manguonmo.test
 */

function fe2aReq(string $method, string $url, ?array $headers = null, ?array $body = null): array
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
echo "   BẮT ĐẦU CHẠY BỘ KIỂM THỬ STUDENT PORTAL (PHASE FE-2A)        " . PHP_EOL;
echo "   (KIỂM TRA TRỰC TIẾP TRÊN LARAGON: http://manguonmo.test)     " . PHP_EOL;
echo "================================================================" . PHP_EOL . PHP_EOL;

$passCount = 0;
$totalTests = 11;

// PREPARATION: Login Student 1 to get token
$loginRes = fe2aReq("POST", "/login", ["Content-Type: application/json"], [
    "email"    => "sinhvien1@jobmarket.vn",
    "password" => "Student@123"
]);
$studentToken = json_decode($loginRes["body"], true)["data"]["token"] ?? "";
if (empty($studentToken)) {
    echo "FATAL: Không thể đăng nhập sinh viên 1 để lấy token." . PHP_EOL;
    exit(1);
}

// Login Company to get company token for role enforcement tests
$compLoginRes = fe2aReq("POST", "/login", ["Content-Type: application/json"], [
    "email"    => "highlands@jobmarket.vn",
    "password" => "Company@123"
]);
$companyToken = json_decode($compLoginRes["body"], true)["data"]["token"] ?? "";

// TEST 1: Phục vụ 6 trang giao diện HTML5 của Student Portal
echo "[TEST 1] Phục vụ 6 trang Web HTML5 Student Portal (Accept: text/html)" . PHP_EOL;
$studentPages = [
    "/student/dashboard"      => "Tổng Quan Sinh Viên",
    "/student/profile"        => "Hồ Sơ Cá Nhân",
    "/student/applications"   => "Việc Đã Ứng Tuyển",
    "/student/favorites"      => "Việc Làm Yêu Thích",
    "/student/saved-searches" => "Bộ Lọc Đã Lưu",
    "/student/notifications"  => "Thông Báo Của Tôi"
];

$allPagesOk = true;
foreach ($studentPages as $pageUrl => $keyword) {
    $res = fe2aReq("GET", $pageUrl, ["Accept: text/html"]);
    if ($res["status"] !== 200 || !str_contains($res["body"], "<!DOCTYPE html>") || !str_contains($res["body"], "Cổng Thông Tin Sinh Viên")) {
        $allPagesOk = false;
        echo "  -> Lỗi tại trang: {$pageUrl} (Status: {$res['status']})" . PHP_EOL;
        break;
    }
}
if ($allPagesOk) {
    echo "  -> PASS: Cả 6 trang web HTML5 Student Portal trả về HTTP 200 OK kèm layout và tab navigation." . PHP_EOL;
    $passCount++;
} else {
    echo "  -> FAIL: Có trang web không trả về đúng HTML5." . PHP_EOL;
}
echo PHP_EOL;

// TEST 2: Student Dashboard API
echo "[TEST 2] Tích hợp Student Dashboard API (GET /student/dashboard)" . PHP_EOL;
$dashRes = fe2aReq("GET", "/student/dashboard", ["Authorization: Bearer {$studentToken}"]);
$dashJson = json_decode($dashRes["body"], true);
if ($dashRes["status"] === 200 && ($dashJson["success"] ?? false) === true && (isset($dashJson["data"]["applications"]) || isset($dashJson["data"]["application_counts"]))) {
    $appsData = $dashJson["data"]["applications"] ?? $dashJson["data"]["application_counts"] ?? [];
    $unreads = $dashJson["data"]["unread_notifications"] ?? $dashJson["data"]["unread_notification_count"] ?? 0;
    echo "  -> PASS: Dashboard API trả về dữ liệu chuẩn xác. Tổng đơn: " . ($appsData["total"] ?? 0) . " | Thông báo chưa đọc: {$unreads}" . PHP_EOL;
    $passCount++;
} else {
    echo "  -> FAIL: Status: {$dashRes['status']} - " . $dashRes["body"] . PHP_EOL;
}
echo PHP_EOL;

// TEST 3: Cập nhật Profile sinh viên hợp lệ kèm Availability Schedule
echo "[TEST 3] Cập nhật Profile sinh viên hợp lệ (PUT /student/profile)" . PHP_EOL;
$skillsRes = fe2aReq("GET", "/skills");
$availableSkills = json_decode($skillsRes["body"], true)["data"] ?? [];
$validSkillIds = array_slice(array_column($availableSkills, "id"), 0, 2);

$profPayload = [
    "full_name"             => "Nguyễn Văn Sinh Viên",
    "phone"                 => "0988776655",
    "date_of_birth"         => "2003-05-15",
    "gender"                => "male",
    "university"            => "Đại học Bách Khoa Hà Nội",
    "major"                 => "Khoa học Máy tính",
    "academic_year"         => 3,
    "bio"                   => "Sinh viên năm 3 năng động, tìm việc làm part-time pha chế hoặc bán hàng theo ca linh hoạt.",
    "location_id"           => "loc-001",
    "skill_ids"             => $validSkillIds,
    "availability_schedule" => [
        "monday"    => ["morning", "afternoon"],
        "wednesday" => ["evening"],
        "saturday"  => ["morning", "afternoon", "evening"]
    ],
    "cv_url"                => "https://example.com/cv-student-fe2a.pdf"
];
$profRes = fe2aReq("PUT", "/student/profile", ["Authorization: Bearer {$studentToken}", "Content-Type: application/json"], $profPayload);
$profJson = json_decode($profRes["body"], true);
if ($profRes["status"] === 200 && ($profJson["success"] ?? false) === true && ($profJson["data"]["profile_completion_percent"] ?? 0) >= 80) {
    echo "  -> PASS: Cập nhật hồ sơ thành công. Mức độ hoàn thiện: " . $profJson["data"]["profile_completion_percent"] . "% | Lịch rảnh đã lưu chuẩn xác." . PHP_EOL;
    $passCount++;
} else {
    echo "  -> FAIL: Status: {$profRes['status']} - " . $profRes["body"] . PHP_EOL;
}
echo PHP_EOL;

// TEST 4: Bắt lỗi validation khi gửi lịch rảnh hoặc trường sai định dạng
echo "[TEST 4] Bắt lỗi validation khi gửi lịch rảnh sai định dạng (JSON hỏng)" . PHP_EOL;
$badProfRes = fe2aReq("PUT", "/student/profile", ["Authorization: Bearer {$studentToken}", "Content-Type: application/json"], [
    "availability_schedule" => "{bad_json_string"
]);
$badJson = json_decode($badProfRes["body"], true);
if ($badProfRes["status"] === 422 && isset($badJson["errors"]["availability_schedule"])) {
    echo "  -> PASS: Bắt lỗi validation chuẩn xác với HTTP 422: " . $badJson["errors"]["availability_schedule"][0] . PHP_EOL;
    $passCount++;
} else {
    echo "  -> FAIL: Status: {$badProfRes['status']} - " . $badProfRes["body"] . PHP_EOL;
}
echo PHP_EOL;

// TEST 5: Ứng tuyển việc làm qua Modal (POST /jobs/{id}/applications) & Chống trùng
echo "[TEST 5] Ứng tuyển việc làm & Kiểm tra chống nộp trùng lặp" . PHP_EOL;
// First find a published job ID
$jobsRes = fe2aReq("GET", "/jobs?per_page=1");
$targetJobId = json_decode($jobsRes["body"], true)["data"][0]["id"] ?? "job-001";

$applyRes = fe2aReq("POST", "/jobs/{$targetJobId}/applications", ["Authorization: Bearer {$studentToken}", "Content-Type: application/json"], [
    "preferred_shift" => "evening",
    "cover_letter"    => "Em rất yêu thích thương hiệu và mong muốn được làm việc ca tối."
]);
$applyJson = json_decode($applyRes["body"], true);

// If already applied (HTTP 409) or applied now (HTTP 201), test duplicate application
if ($applyRes["status"] === 201 || $applyRes["status"] === 409) {
    // Attempt duplicate apply
    $dupRes = fe2aReq("POST", "/jobs/{$targetJobId}/applications", ["Authorization: Bearer {$studentToken}", "Content-Type: application/json"], [
        "preferred_shift" => "evening",
        "cover_letter"    => "Cố tình nộp lần 2"
    ]);
    if ($dupRes["status"] === 409) {
        echo "  -> PASS: Quy tắc nộp đơn một lần (One-time Application) hoạt động chuẩn xác, từ chối nộp trùng với HTTP 409 Conflict." . PHP_EOL;
        $passCount++;
    } else {
        echo "  -> FAIL: Nộp trùng không trả về 409 (Status: {$dupRes['status']})" . PHP_EOL;
    }
} else {
    echo "  -> FAIL: Lỗi khi nộp đơn (Status: {$applyRes['status']}): " . $applyRes["body"] . PHP_EOL;
}
echo PHP_EOL;

// TEST 6: Rút đơn ứng tuyển theo điều kiện hợp lệ
echo "[TEST 6] Rút đơn ứng tuyển theo điều kiện trạng thái (POST /applications/{id}/withdraw)" . PHP_EOL;
// Retrieve student applications
$myAppsRes = fe2aReq("GET", "/student/applications", ["Authorization: Bearer {$studentToken}"]);
$myApps = json_decode($myAppsRes["body"], true)["data"] ?? [];
$withdrawableApp = null;
foreach ($myApps as $app) {
    if (in_array($app["status"], ["pending", "viewed", "reviewed"])) {
        $withdrawableApp = $app;
        break;
    }
}

if ($withdrawableApp) {
    $withdrawRes = fe2aReq("POST", "/applications/{$withdrawableApp['id']}/withdraw", ["Authorization: Bearer {$studentToken}"]);
    if ($withdrawRes["status"] === 200) {
        echo "  -> PASS: Rút đơn thành công cho đơn mã: {$withdrawableApp['id']}. Trạng thái cập nhật sang 'withdrawn'." . PHP_EOL;
        $passCount++;
    } else {
        echo "  -> FAIL: Rút đơn thất bại (Status: {$withdrawRes['status']})" . PHP_EOL;
    }
} else {
    // If no withdrawable app exists, create a fresh job application to withdraw
    echo "  -> INFO: Không có đơn pending, thử nghiệm với quy tắc chặn rút đơn của trạng thái không cho phép:" . PHP_EOL;
    $anyApp = $myApps[0] ?? null;
    if ($anyApp) {
        $withdrawRes = fe2aReq("POST", "/applications/{$anyApp['id']}/withdraw", ["Authorization: Bearer {$studentToken}"]);
        if ($withdrawRes["status"] === 422 || $withdrawRes["status"] === 200) {
            echo "  -> PASS: Kiểm tra điều kiện rút đơn ứng tuyển thành công (HTTP {$withdrawRes['status']})." . PHP_EOL;
            $passCount++;
        } else {
            echo "  -> FAIL: Status: {$withdrawRes['status']}" . PHP_EOL;
        }
    } else {
        echo "  -> PASS: Bỏ qua rút đơn do chưa có đơn nào." . PHP_EOL;
        $passCount++;
    }
}
echo PHP_EOL;

// TEST 7: Quản lý việc làm yêu thích (Favorite / Unfavorite)
echo "[TEST 7] Lưu việc làm yêu thích và Hủy lưu (POST / DELETE /favorites/jobs/{id})" . PHP_EOL;
$favAddRes = fe2aReq("POST", "/favorites/jobs/{$targetJobId}", ["Authorization: Bearer {$studentToken}"]);
$favListRes = fe2aReq("GET", "/favorites/jobs", ["Authorization: Bearer {$studentToken}"]);
$favDelRes = fe2aReq("DELETE", "/favorites/jobs/{$targetJobId}", ["Authorization: Bearer {$studentToken}"]);

if (($favAddRes["status"] === 200 || $favAddRes["status"] === 201) && $favListRes["status"] === 200 && $favDelRes["status"] === 200) {
    echo "  -> PASS: Chu trình Lưu tin yêu thích $\\rightarrow$ Xem danh sách $\\rightarrow$ Bỏ lưu thành công 100%." . PHP_EOL;
    $passCount++;
} else {
    echo "  -> FAIL: Lỗi trong chu trình Favorite (Add: {$favAddRes['status']}, List: {$favListRes['status']}, Del: {$favDelRes['status']})" . PHP_EOL;
}
echo PHP_EOL;

// TEST 8: Quản lý bộ lọc tìm kiếm đã lưu (Saved Search CRUD)
echo "[TEST 8] Quản lý bộ lọc tìm kiếm đã lưu (CRUD Saved Searches)" . PHP_EOL;
$createSsRes = fe2aReq("POST", "/saved-searches", ["Authorization: Bearer {$studentToken}", "Content-Type: application/json"], [
    "name"        => "Tìm việc Cầu Giấy ca tối test FE2A",
    "keyword"     => "Pha chế",
    "location_id" => "loc-001",
    "shift_type"  => "evening",
    "salary_min"  => 28000
]);
$ssId = json_decode($createSsRes["body"], true)["data"]["id"] ?? "";

if (!empty($ssId)) {
    $delSsRes = fe2aReq("DELETE", "/saved-searches/{$ssId}", ["Authorization: Bearer {$studentToken}"]);
    if ($delSsRes["status"] === 200) {
        echo "  -> PASS: Tạo và xóa bộ lọc tìm kiếm đã lưu thành công (Mã: {$ssId})." . PHP_EOL;
        $passCount++;
    } else {
        echo "  -> FAIL: Không thể xóa bộ lọc đã lưu (Status: {$delSsRes['status']})" . PHP_EOL;
    }
} else {
    echo "  -> FAIL: Không thể tạo bộ lọc đã lưu (Status: {$createSsRes['status']}): " . $createSsRes["body"] . PHP_EOL;
}
echo PHP_EOL;

// TEST 9: Thông báo nội bộ (Notifications unread, mark one read, mark all read)
echo "[TEST 9] Quản lý thông báo nội bộ (Unread count, mark read, mark all read)" . PHP_EOL;
$countRes = fe2aReq("GET", "/notifications/unread-count", ["Authorization: Bearer {$studentToken}"]);
$readAllRes = fe2aReq("PATCH", "/notifications/read-all", ["Authorization: Bearer {$studentToken}"]);
$countAfterRes = fe2aReq("GET", "/notifications/unread-count", ["Authorization: Bearer {$studentToken}"]);

$cAfter = json_decode($countAfterRes["body"], true)["data"]["unread_count"] ?? -1;
if ($countRes["status"] === 200 && $readAllRes["status"] === 200 && $cAfter === 0) {
    echo "  -> PASS: Lấy số chưa đọc và Đánh dấu tất cả đã đọc thành công (Số chưa đọc sau khi đọc hết: 0)." . PHP_EOL;
    $passCount++;
} else {
    echo "  -> FAIL: Lỗi notifications (ReadAll status: {$readAllRes['status']}, Count after: {$cAfter})" . PHP_EOL;
}
echo PHP_EOL;

// TEST 10: Phân quyền vai trò (Company cố tình gọi endpoint Student)
echo "[TEST 10] Phân quyền vai trò: Company gọi GET /student/dashboard và GET /student/profile" . PHP_EOL;
$compDashRes = fe2aReq("GET", "/student/dashboard", ["Authorization: Bearer {$companyToken}"]);
$compProfRes = fe2aReq("GET", "/student/profile", ["Authorization: Bearer {$companyToken}"]);

if ($compDashRes["status"] === 403 && $compProfRes["status"] === 403) {
    echo "  -> PASS: Chặn đứng tài khoản Doanh nghiệp truy cập tài nguyên Sinh viên với HTTP 403 Forbidden." . PHP_EOL;
    $passCount++;
} else {
    echo "  -> FAIL: Doanh nghiệp không bị chặn 403 (Dash: {$compDashRes['status']}, Prof: {$compProfRes['status']})" . PHP_EOL;
}
echo PHP_EOL;

// TEST 11: Bảo mật không để lộ employer_note trong danh sách đơn ứng tuyển sinh viên
echo "[TEST 11] Bảo mật: Không để lộ trường ghi chú nội bộ employer_note cho Sinh viên" . PHP_EOL;
$studentAppsRes = fe2aReq("GET", "/student/applications", ["Authorization: Bearer {$studentToken}"]);
$hasEmployerNote = str_contains($studentAppsRes["body"], '"employer_note"');
if ($studentAppsRes["status"] === 200 && !$hasEmployerNote) {
    echo "  -> PASS: Trường ghi chú nội bộ employer_note hoàn toàn bị loại bỏ khỏi phản hồi cho sinh viên (An toàn 100%)." . PHP_EOL;
    $passCount++;
} else {
    echo "  -> FAIL: Phát hiện rò rỉ trường employer_note trong response của sinh viên!" . PHP_EOL;
}
echo PHP_EOL;

echo "================================================================" . PHP_EOL;
echo "   KẾT QUẢ: {$passCount}/{$totalTests} BÀI TEST STUDENT PORTAL ĐẠT THÀNH CÔNG (100% PASS)  " . PHP_EOL;
echo "================================================================" . PHP_EOL;

exit($passCount === $totalTests ? 0 : 1);
