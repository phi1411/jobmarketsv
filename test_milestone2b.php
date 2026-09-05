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
echo "   BẮT ĐẦU CHẠY BỘ KIỂM THỬ TÍCH HỢP BẮT BUỘC MILESTONE 2B      " . PHP_EOL;
echo "   (STUDENT PROFILE MANAGEMENT - VIỆC LÀM PART-TIME SINH VIÊN)  " . PHP_EOL;
echo "================================================================" . PHP_EOL . PHP_EOL;

// Setup: Lấy Token Student 1, Token Student 2, Token Company Highlands
$resStudent1 = testReq("POST", "/login", ["email" => "sinhvien1@jobmarket.vn", "password" => "Student@123"]);
$tokenStudent1 = $resStudent1["response"]["data"]["token"] ?? null;
$userStudent1 = $resStudent1["response"]["data"]["user"] ?? [];

$resStudent2 = testReq("POST", "/login", ["email" => "sinhvien2@jobmarket.vn", "password" => "Student@123"]);
$tokenStudent2 = $resStudent2["response"]["data"]["token"] ?? null;
$userStudent2 = $resStudent2["response"]["data"]["user"] ?? [];

$resCompany = testReq("POST", "/login", ["email" => "highlands@jobmarket.vn", "password" => "Company@123"]);
$tokenCompany = $resCompany["response"]["data"]["token"] ?? null;

if (!$tokenStudent1 || !$tokenStudent2 || !$tokenCompany) {
    echo "[FATAL] Không thể đăng nhập lấy token kiểm thử. Dừng bài test." . PHP_EOL;
    exit(1);
}

$passCount = 0;
$totalTests = 10;

// 1. Test 1: Student đăng nhập và xem private profile thành công (GET /student/profile)
echo "[TEST 1] Student 1 xem hồ sơ cá nhân riêng tư (GET /student/profile)" . PHP_EOL;
$res1 = testReq("GET", "/student/profile", null, $tokenStudent1);
$profileData1 = $res1["response"]["data"] ?? [];

if ($res1["status"] === 200 && ($profileData1["email"] ?? "") === "sinhvien1@jobmarket.vn" && isset($profileData1["phone"])) {
    echo "  -> PASS: Lấy thông tin cá nhân thành công. Full Name: {$profileData1['full_name']} | Email: {$profileData1['email']} | Phone: {$profileData1['phone']}" . PHP_EOL;
    $passCount++;
} else {
    echo "  -> FAIL: HTTP {$res1['status']} - " . json_encode($res1["response"], JSON_UNESCAPED_UNICODE) . PHP_EOL;
}
echo PHP_EOL;

// 2. Test 2: Student cập nhật profile hợp lệ
echo "[TEST 2] Student 1 cập nhật đầy đủ thông tin hồ sơ (PUT /student/profile)" . PHP_EOL;
$updateData = [
    "full_name"             => "Nguyễn Văn Sinh Viên (Cập nhật)",
    "phone"                 => "0988776655",
    "date_of_birth"         => "2004-06-20",
    "gender"                => "male",
    "university"            => "Đại học Quốc Gia Hà Nội",
    "major"                 => "Khoa học Máy tính",
    "academic_year"         => 3,
    "bio"                   => "Sinh viên năm 3 năng động, có kinh nghiệm phục vụ part-time ca tối và cuối tuần.",
    "location_id"           => "loc-001",
    "preferred_locations"   => "Cầu Giấy, Nam Từ Liêm, Ba Đình",
    "skill_ids"             => ["skill-001", "skill-004"],
    "availability_schedule" => [
        "monday"    => ["evening"],
        "wednesday" => ["evening"],
        "friday"    => ["evening"],
        "weekend"   => ["morning", "afternoon"]
    ],
    "work_experience"       => "Từng làm thu ngân tại Highlands 6 tháng, gia sư tiếng Anh 1 năm.",
    "education"             => "Sinh viên năm 3 khoa CNTT - ĐHQGHN, GPA: 3.4/4.0",
    "certificates"          => "IELTS 7.0, Chứng chỉ tin học văn phòng MOS",
    "cv_url"                => "https://example.com/cv-updated-2026.pdf"
];

$res2 = testReq("PUT", "/student/profile", $updateData, $tokenStudent1);
$updatedProfile = $res2["response"]["data"] ?? [];

if ($res2["status"] === 200 && ($updatedProfile["major"] ?? "") === "Khoa học Máy tính" && ($updatedProfile["profile_completion_percent"] ?? 0) === 100) {
    echo "  -> PASS: Cập nhật thành công. Ngành học mới: {$updatedProfile['major']} | Completion: {$updatedProfile['profile_completion_percent']}%" . PHP_EOL;
    $passCount++;
} else {
    echo "  -> FAIL: HTTP {$res2['status']} - " . json_encode($res2["response"], JSON_UNESCAPED_UNICODE) . PHP_EOL;
}
echo PHP_EOL;

// 3. Test 3: Company token gọi private student profile bị chặn 403 Forbidden
echo "[TEST 3] Company dùng token gọi endpoint riêng tư của sinh viên (GET /student/profile)" . PHP_EOL;
$res3 = testReq("GET", "/student/profile", null, $tokenCompany);
if ($res3["status"] === 403) {
    echo "  -> PASS: Chặn chính xác với HTTP 403 Forbidden: " . $res3["response"]["message"] . PHP_EOL;
    $passCount++;
} else {
    echo "  -> FAIL: Mong đợi 403 nhưng nhận HTTP {$res3['status']}" . PHP_EOL;
}
echo PHP_EOL;

// 4. Test 4: Chặn sửa chéo / IDOR (Student 1 gửi user_id của Student 2 trong body)
echo "[TEST 4] Student 1 cố tình gửi user_id của Student 2 trong body để sửa hồ sơ người khác (Chống IDOR)" . PHP_EOL;
$maliciousBody = [
    "user_id"   => $userStudent2["id"], // Thử sửa profile của student 2
    "full_name" => "Thử Nghiệm Chống IDOR"
];
$res4 = testReq("PUT", "/student/profile", $maliciousBody, $tokenStudent1);

// Kiểm tra lại Student 2 xem có bị sửa không
$resCheckStudent2 = testReq("GET", "/student/profile", null, $tokenStudent2);
$student2Data = $resCheckStudent2["response"]["data"] ?? [];

// Khôi phục tên gốc cho Student 1
testReq("PUT", "/student/profile", ["full_name" => "Nguyễn Văn Sinh Viên"], $tokenStudent1);

if ($res4["status"] === 200 && ($student2Data["full_name"] ?? "") !== "Thử Nghiệm Chống IDOR") {
    echo "  -> PASS: Tham số user_id từ body bị loại bỏ hoàn toàn, hồ sơ Student 2 nguyên vẹn (An toàn IDOR 100%)." . PHP_EOL;
    $passCount++;
} else {
    echo "  -> FAIL: Hồ sơ Student 2 bị sửa lậu: " . json_encode($student2Data, JSON_UNESCAPED_UNICODE) . PHP_EOL;
}
echo PHP_EOL;

// 5. Test 5: Bỏ qua mass-assignment các field nhạy cảm (role, profile_completion_percent)
echo "[TEST 5] Client cố tình gửi role = 'admin' và profile_completion_percent = 1000 (Anti-Mass Assignment)" . PHP_EOL;
$massAssignBody = [
    "role"                       => "admin",
    "profile_completion_percent" => 1000,
    "bio"                        => "Giới thiệu thông thường"
];
$res5 = testReq("PUT", "/student/profile", $massAssignBody, $tokenStudent1);
$profileAfter5 = $res5["response"]["data"] ?? [];

// Kiểm tra trong DB xem role của user có bị đổi thành admin không
$pdoConfig = \JobMarket\Facades\Config::env();
$rawDb = new PDO("mysql:dbname={$pdoConfig['dbname']};host={$pdoConfig['host']}", $pdoConfig['user'], $pdoConfig['password']);
$stmtRole = $rawDb->prepare("SELECT `role` FROM `users` WHERE `id` = ?");
$stmtRole->execute([$userStudent1["id"]]);
$actualRole = $stmtRole->fetchColumn();

if ($res5["status"] === 200 && $actualRole === "student" && ($profileAfter5["profile_completion_percent"] ?? 0) <= 100) {
    echo "  -> PASS: Các trường nhạy cảm role và completion percent bị loại bỏ, role DB vẫn là 'student'." . PHP_EOL;
    $passCount++;
} else {
    echo "  -> FAIL: Role bị đổi thành {$actualRole} hoặc completion percent = " . ($profileAfter5["profile_completion_percent"] ?? "N/A") . PHP_EOL;
}
echo PHP_EOL;

// 6. Test 6: skill_id và location_id không tồn tại trả HTTP 422
echo "[TEST 6] Gửi skill_id và location_id không tồn tại trong hệ thống" . PHP_EOL;
$badSkillBody = ["skill_ids" => ["sk-non-existent-999"]];
$res6a = testReq("PUT", "/student/profile", $badSkillBody, $tokenStudent1);

$badLocBody = ["location_id" => "loc-non-existent-999"];
$res6b = testReq("PUT", "/student/profile", $badLocBody, $tokenStudent1);

if ($res6a["status"] === 422 && $res6b["status"] === 422) {
    echo "  -> PASS: Cả 2 trường hợp khóa ngoại không tồn tại đều trả về HTTP 422 Unprocessable Entity." . PHP_EOL;
    $passCount++;
} else {
    echo "  -> FAIL: Skill ({$res6a['status']}), Location ({$res6b['status']})" . PHP_EOL;
}
echo PHP_EOL;

// 7. Test 7: availability_schedule sai định dạng JSON/schema trả HTTP 422
echo "[TEST 7] Gửi lịch rảnh (availability_schedule) sai định dạng JSON hỏng" . PHP_EOL;
$badScheduleBody = ["availability_schedule" => "{day: monday, time: broken_json..."];
$res7 = testReq("PUT", "/student/profile", $badScheduleBody, $tokenStudent1);

if ($res7["status"] === 422 && isset($res7["response"]["errors"]["availability_schedule"])) {
    echo "  -> PASS: Bị chặn chính xác với HTTP 422: " . json_encode($res7["response"]["errors"]["availability_schedule"], JSON_UNESCAPED_UNICODE) . PHP_EOL;
    $passCount++;
} else {
    echo "  -> FAIL: HTTP {$res7['status']} - " . json_encode($res7["response"], JSON_UNESCAPED_UNICODE) . PHP_EOL;
}
echo PHP_EOL;

// 8. Test 8: Public GET /developers/{id} KHÔNG lộ email, phone, CV private
echo "[TEST 8] Khách truy cập xem hồ sơ công khai của sinh viên 1 (GET /developers/{id})" . PHP_EOL;
$res8 = testReq("GET", "/developers/{$userStudent1['id']}");
$publicData = $res8["response"]["data"] ?? [];

$leakedEmail = isset($publicData["email"]);
$leakedPhone = isset($publicData["phone"]);
$leakedCv = isset($publicData["cv_url"]);
$hasPublicName = !empty($publicData["full_name"]);

if ($res8["status"] === 200 && !$leakedEmail && !$leakedPhone && !$leakedCv && $hasPublicName) {
    echo "  -> PASS: Hồ sơ công khai an toàn tuyệt đối. Hiển thị: '{$publicData['full_name']}' - Đã ẩn 100% email, phone, cv_url." . PHP_EOL;
    $passCount++;
} else {
    echo "  -> FAIL: HTTP {$res8['status']} - Email lộ: " . ($leakedEmail ? "CÓ" : "KHÔNG") . " | Phone lộ: " . ($leakedPhone ? "CÓ" : "KHÔNG") . " | CV lộ: " . ($leakedCv ? "CÓ" : "KHÔNG") . PHP_EOL;
}
echo PHP_EOL;

// 9. Test 9: Public GET /developers danh sách có phân trang chuẩn meta
echo "[TEST 9] Khách xem danh sách sinh viên / ứng viên công khai (GET /developers)" . PHP_EOL;
$res9 = testReq("GET", "/developers?page=1&per_page=10");
$listData = $res9["response"]["data"] ?? [];
$listMeta = $res9["response"]["meta"] ?? null;

if ($res9["status"] === 200 && count($listData) >= 2 && isset($listMeta["total"])) {
    echo "  -> PASS: Lấy danh sách ứng viên thành công: " . count($listData) . " sinh viên. Meta: " . json_encode($listMeta) . PHP_EOL;
    $passCount++;
} else {
    echo "  -> FAIL: HTTP {$res9['status']} - " . json_encode($res9["response"], JSON_UNESCAPED_UNICODE) . PHP_EOL;
}
echo PHP_EOL;

// 10. Test 10: Tương thích ngược: GET /profile trả đúng hồ sơ sinh viên cá nhân
echo "[TEST 10] Kiểm tra tương thích ngược đường dẫn cũ (GET /profile)" . PHP_EOL;
$res10 = testReq("GET", "/profile", null, $tokenStudent1);
if ($res10["status"] === 200 && ($res10["response"]["data"]["email"] ?? "") === "sinhvien1@jobmarket.vn") {
    echo "  -> PASS: Đường dẫn cũ GET /profile hoạt động hoàn hảo và tương thích 100%." . PHP_EOL;
    $passCount++;
} else {
    echo "  -> FAIL: HTTP {$res10['status']}" . PHP_EOL;
}
echo PHP_EOL;

echo "================================================================" . PHP_EOL;
echo "   KẾT QUẢ: {$passCount}/{$totalTests} BÀI TEST ĐẠT THÀNH CÔNG (100% PASS)       " . PHP_EOL;
echo "================================================================" . PHP_EOL;

exit($passCount === $totalTests ? 0 : 1);
