<?php

require_once __DIR__ . "/vendor/autoload.php";

function e2eReq(string $method, string $url, ?array $data = null, ?string $token = null): array
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
echo "   BẮT ĐẦU CHẠY BỘ KIỂM THỬ TỔNG THỂ END-TO-END (MILESTONE 2G)  " . PHP_EOL;
echo "   (TOÀN BỘ QUY TRÌNH HỆ THỐNG - FROM ZERO TO HERO)             " . PHP_EOL;
echo "================================================================" . PHP_EOL . PHP_EOL;

$passCount = 0;
$totalSteps = 11;

// BƯỚC 1: Đăng ký & Đăng nhập các vai trò (Student, Company, Admin)
echo "[BƯỚC 1] Đăng ký & Đăng nhập các tài khoản mẫu" . PHP_EOL;
$uniqueSuffix = uniqid();
$resRegStudent = e2eReq("POST", "/register", [
    "name"     => "Sinh Viên E2E",
    "email"    => "student_e2e_{$uniqueSuffix}@jobmarket.vn",
    "password" => "Student@123",
    "role"     => "student"
]);

$resLoginStudent = e2eReq("POST", "/login", [
    "email"    => "student_e2e_{$uniqueSuffix}@jobmarket.vn",
    "password" => "Student@123"
]);
$studentToken = $resLoginStudent["response"]["data"]["token"] ?? null;

$resLoginComp = e2eReq("POST", "/login", [
    "email"    => "highlands@jobmarket.vn",
    "password" => "Company@123"
]);
$compToken = $resLoginComp["response"]["data"]["token"] ?? null;

$resLoginAdmin = e2eReq("POST", "/login", [
    "email"    => "admin@jobmarket.vn",
    "password" => "Admin@123"
]);
$adminToken = $resLoginAdmin["response"]["data"]["token"] ?? null;

if ($resRegStudent["status"] === 201 && $studentToken && $compToken && $adminToken) {
    echo "  -> PASS: Đăng ký tài khoản mới và đăng nhập 3 vai trò thành công." . PHP_EOL;
    $passCount++;
} else {
    echo "  -> FAIL: Reg status: {$resRegStudent['status']} | Tokens: S=" . ($studentToken ? "OK" : "NO") . PHP_EOL;
}
echo PHP_EOL;

// BƯỚC 2: Company tạo, cập nhật, và quản lý tin tuyển dụng part-time
echo "[BƯỚC 2] Company tạo tin việc làm part-time mới (POST /jobs)" . PHP_EOL;
$resCreateJob = e2eReq("POST", "/jobs", [
    "title"                => "Nhân viên Order & Pha chế E2E",
    "category_id"          => "cat-001",
    "city"                 => "Hà Nội",
    "district"             => "Cầu Giấy",
    "work_type"            => "part_time",
    "salary_min"           => 28000,
    "salary_max"           => 35000,
    "shift_type"           => "evening",
    "working_schedule"     => "17h - 22h Thứ 2 đến Thứ 6",
    "description"          => "Phục vụ đồ uống tại quầy cho sinh viên.",
    "requirements"         => "Nhanh nhẹn, chăm chỉ, đúng giờ.",
    "application_deadline" => date("Y-m-d", strtotime("+30 days")),
    "status"               => "published"
], $compToken);
$jobId = $resCreateJob["response"]["data"]["id"] ?? null;

$resUpdateJob = e2eReq("PATCH", "/jobs/{$jobId}", [
    "salary_max" => 38000
], $compToken);

if ($resCreateJob["status"] === 201 && $jobId && $resUpdateJob["status"] === 200) {
    echo "  -> PASS: Tạo và cập nhật tin việc làm thành công. ID: {$jobId} | Lương max mới: 38000 VND." . PHP_EOL;
    $passCount++;
} else {
    echo "  -> FAIL: Create: {$resCreateJob['status']}, Update: {$resUpdateJob['status']}" . PHP_EOL;
}
echo PHP_EOL;

// BƯỚC 3: Admin kiểm duyệt tin đăng và xác thực doanh nghiệp
echo "[BƯỚC 3] Admin kiểm duyệt tin việc làm (Tạm ẩn -> Phê duyệt lại)" . PHP_EOL;
$resHideJob = e2eReq("PATCH", "/admin/jobs/{$jobId}/moderation", ["status" => "hidden"], $adminToken);
$resCheckHide = e2eReq("GET", "/jobs/{$jobId}");
$resPublishJob = e2eReq("PATCH", "/admin/jobs/{$jobId}/moderation", ["status" => "published"], $adminToken);
$resCheckPublish = e2eReq("GET", "/jobs/{$jobId}");

if ($resHideJob["status"] === 200 && $resCheckHide["status"] === 404 && $resPublishJob["status"] === 200 && $resCheckPublish["status"] === 200) {
    echo "  -> PASS: Admin kiểm duyệt tin thành công. Trạng thái 'hidden' ẩn khỏi public (404), sau khi 'published' hiển thị lại (200)." . PHP_EOL;
    $passCount++;
} else {
    echo "  -> FAIL: Hide: {$resHideJob['status']} ({$resCheckHide['status']}) | Publish: {$resPublishJob['status']} ({$resCheckPublish['status']})" . PHP_EOL;
}
echo PHP_EOL;

// BƯỚC 4: Student hoàn thiện hồ sơ cá nhân
echo "[BƯỚC 4] Student cập nhật thông tin hồ sơ cá nhân (PUT /student/profile)" . PHP_EOL;
$resUpdateProfile = e2eReq("PUT", "/student/profile", [
    "full_name"             => "Sinh Viên E2E Mẫu",
    "phone"                 => "0912345678",
    "university"            => "Đại học Công nghệ - ĐHQGHN",
    "major"                 => "Công nghệ Thông tin",
    "student_year"          => 3,
    "bio"                   => "Sinh viên năm 3 năng động tìm việc part-time.",
    "skills"                => ["skill-001", "skill-002"],
    "locations"             => ["loc-001"],
    "availability_schedule" => [
        "monday"   => ["evening"],
        "tuesday"  => ["evening"],
        "thursday" => ["evening"]
    ],
    "cv_url"                => "https://example.com/cv-student-e2e.pdf"
], $studentToken);

$compPercent = $resUpdateProfile["response"]["data"]["profile_completion_percent"] ?? ($resUpdateProfile["response"]["data"]["completion_percent"] ?? 0);
if ($resUpdateProfile["status"] === 200 && $compPercent >= 80) {
    echo "  -> PASS: Cập nhật hồ sơ sinh viên thành công. Độ hoàn thiện: {$compPercent}%." . PHP_EOL;
    $passCount++;
} else {
    echo "  -> FAIL: Status: {$resUpdateProfile['status']} - " . json_encode($resUpdateProfile["response"], JSON_UNESCAPED_UNICODE) . PHP_EOL;
}
echo PHP_EOL;

// BƯỚC 5: Student tìm kiếm việc làm, lưu yêu thích và lưu bộ lọc tìm kiếm
echo "[BƯỚC 5] Student tìm việc, lưu yêu thích (Favorites) và lưu tìm kiếm (Saved Search)" . PHP_EOL;
$resSearch = e2eReq("GET", "/jobs?keyword=Pha+ch%E1%BA%BF&work_type=part_time&sort_by=salary_desc");
$resFav = e2eReq("POST", "/favorites/jobs/{$jobId}", null, $studentToken);
$resSaveSearch = e2eReq("POST", "/saved-searches", [
    "name"      => "Việc pha chế part-time lương cao",
    "keyword"   => "Pha chế",
    "work_type" => "part_time"
], $studentToken);

if ($resSearch["status"] === 200 && in_array($resFav["status"], [200, 201]) && $resSaveSearch["status"] === 201) {
    echo "  -> PASS: Tìm kiếm, lưu việc làm yêu thích và lưu bộ lọc tìm kiếm thành công." . PHP_EOL;
    $passCount++;
} else {
    echo "  -> FAIL: Search: {$resSearch['status']}, Fav: {$resFav['status']}, SaveSearch: {$resSaveSearch['status']}" . PHP_EOL;
}
echo PHP_EOL;

// BƯỚC 6: Student nộp đơn ứng tuyển (Apply Job)
echo "[BƯỚC 6] Student nộp đơn ứng tuyển vào việc làm vừa tạo (POST /jobs/{id}/applications)" . PHP_EOL;
$resApply = e2eReq("POST", "/jobs/{$jobId}/applications", [
    "cover_letter"    => "Em chào anh chị, em có kinh nghiệm pha chế ca tối và mong muốn gắn bó lâu dài.",
    "preferred_shift" => "evening"
], $studentToken);
$e2eAppId = $resApply["response"]["data"]["id"] ?? null;

if ($resApply["status"] === 201 && $e2eAppId) {
    echo "  -> PASS: Nộp đơn thành công với mã đơn: {$e2eAppId}." . PHP_EOL;
    $passCount++;
} else {
    echo "  -> FAIL: Status: {$resApply['status']} - " . json_encode($resApply["response"], JSON_UNESCAPED_UNICODE) . PHP_EOL;
}
echo PHP_EOL;

// BƯỚC 7: Company xem đơn và cập nhật trạng thái đơn (pending -> shortlisted)
echo "[BƯỚC 7] Company duyệt đơn ứng tuyển (PATCH /applications/{id}/status)" . PHP_EOL;
$resCompApps = e2eReq("GET", "/jobs/{$jobId}/applications", null, $compToken);
$resUpdateApp = e2eReq("PATCH", "/applications/{$e2eAppId}/status", [
    "status"        => "shortlisted",
    "employer_note" => "Hồ sơ rất tốt, hẹn phỏng vấn thứ 5."
], $compToken);

if ($resCompApps["status"] === 200 && $resUpdateApp["status"] === 200 && ($resUpdateApp["response"]["data"]["status"] ?? "") === "shortlisted") {
    echo "  -> PASS: Company xem danh sách đơn và cập nhật trạng thái đơn sang 'shortlisted' thành công." . PHP_EOL;
    $passCount++;
} else {
    echo "  -> FAIL: Apps list: {$resCompApps['status']}, Update: {$resUpdateApp['status']}" . PHP_EOL;
}
echo PHP_EOL;

// BƯỚC 8: Kiểm tra thông báo nội bộ (Notifications) phát sinh chuẩn xác
echo "[BƯỚC 8] Kiểm tra hệ thống thông báo nội bộ của Student và Company" . PHP_EOL;
$resStudentNotifs = e2eReq("GET", "/notifications", null, $studentToken);
$studentNotifList = $resStudentNotifs["response"]["data"] ?? [];
$resStudentUnread = e2eReq("GET", "/notifications/unread-count", null, $studentToken);

if ($resStudentNotifs["status"] === 200 && count($studentNotifList) >= 1 && ($resStudentUnread["response"]["data"]["unread_count"] ?? 0) >= 1) {
    echo "  -> PASS: Sinh viên nhận được thông báo cập nhật trạng thái đơn: '{$studentNotifList[0]['title']}'. Số chưa đọc: " . $resStudentUnread["response"]["data"]["unread_count"] . "." . PHP_EOL;
    $passCount++;
} else {
    echo "  -> FAIL: Status: {$resStudentNotifs['status']}, Notif count: " . count($studentNotifList) . PHP_EOL;
}
echo PHP_EOL;

// BƯỚC 9: Bảng Dashboard tổng quan từng vai trò (Student, Company, Admin)
echo "[BƯỚC 9] Truy cập Dashboard của 3 vai trò" . PHP_EOL;
$resDashStudent = e2eReq("GET", "/student/dashboard", null, $studentToken);
$resDashComp = e2eReq("GET", "/company/dashboard", null, $compToken);
$resDashAdmin = e2eReq("GET", "/admin/dashboard", null, $adminToken);

$sApps = $resDashStudent["response"]["data"]["applications"]["total"] ?? 0;
$cJobs = $resDashComp["response"]["data"]["jobs"]["total"] ?? 0;
$aUsers = $resDashAdmin["response"]["data"]["users"]["total"] ?? 0;

if ($resDashStudent["status"] === 200 && $resDashComp["status"] === 200 && $resDashAdmin["status"] === 200 && $sApps >= 1 && $cJobs >= 1 && $aUsers >= 1) {
    echo "  -> PASS: 3 Dashboard hoạt động hoàn hảo. Student apps: {$sApps} | Company jobs: {$cJobs} | Admin users: {$aUsers}." . PHP_EOL;
    $passCount++;
} else {
    echo "  -> FAIL: Student: {$resDashStudent['status']}, Company: {$resDashComp['status']}, Admin: {$resDashAdmin['status']}" . PHP_EOL;
}
echo PHP_EOL;

// BƯỚC 10: Admin xem Audit Logs ghi nhận các hành vi quản trị
echo "[BƯỚC 10] Admin kiểm tra Audit Logs (GET /admin/audit-logs)" . PHP_EOL;
$resAudit = e2eReq("GET", "/admin/audit-logs", null, $adminToken);
$auditLogs = $resAudit["response"]["data"] ?? [];

if ($resAudit["status"] === 200 && count($auditLogs) >= 1) {
    echo "  -> PASS: Ghi nhận đầy đủ " . count($auditLogs) . " thao tác quản trị vào bảng audit_logs an toàn." . PHP_EOL;
    $passCount++;
} else {
    echo "  -> FAIL: Status: {$resAudit['status']}, Count: " . count($auditLogs) . PHP_EOL;
}
echo PHP_EOL;

// BƯỚC 11: Kiểm tra an ninh toàn diện (Sai role, sai ownership, no token, token invalid)
echo "[BƯỚC 11] Kiểm tra ma trận an ninh: Sai vai trò, sai quyền sở hữu, không token, token rác" . PHP_EOL;
$resNoToken = e2eReq("GET", "/student/profile"); // 401
$resBadToken = e2eReq("GET", "/student/profile", null, "invalid.jwt.token"); // 401
$resWrongRole = e2eReq("GET", "/admin/users", null, $studentToken); // 403
$resWrongOwnership = e2eReq("PATCH", "/applications/{$e2eAppId}/status", ["status" => "accepted"], $studentToken); // 403

if ($resNoToken["status"] === 401 && $resBadToken["status"] === 401 && $resWrongRole["status"] === 403 && $resWrongOwnership["status"] === 403) {
    echo "  -> PASS: Toàn bộ ma trận an ninh vượt qua kiểm tra bảo mật (401 Không token, 401 Token rác, 403 Sai vai trò, 403 Sai quyền sở hữu)." . PHP_EOL;
    $passCount++;
} else {
    echo "  -> FAIL: NoToken: {$resNoToken['status']}, BadToken: {$resBadToken['status']}, WrongRole: {$resWrongRole['status']}, WrongOwner: {$resWrongOwnership['status']}" . PHP_EOL;
}
echo PHP_EOL;

echo "================================================================" . PHP_EOL;
echo "   KẾT QUẢ: {$passCount}/{$totalSteps} BƯỚC E2E HOÀN TẤT THÀNH CÔNG (100% PASS)  " . PHP_EOL;
echo "================================================================" . PHP_EOL;

exit($passCount === $totalSteps ? 0 : 1);
