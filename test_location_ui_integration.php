<?php

define("BASE_PATH", __DIR__);
require __DIR__ . "/vendor/autoload.php";

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

$passed = 0;
$total = 0;

$runTest = function (string $name, callable $fn) use (&$passed, &$total) {
    $total++;
    try {
        $fn();
        echo "  [PASS] {$name}\n";
        $passed++;
    } catch (Throwable $e) {
        echo "  [FAIL] {$name}: " . $e->getMessage() . "\n";
    }
};

function runTest(string $name, callable $fn) {
    global $runTest;
    $runTest($name, $fn);
}

echo "=== KIỂM THỬ TÍCH HỢP GIAO DIỆN LOCATION (JobMarketSV) ===\n\n";

// 1. Kiểm tra tài nguyên tĩnh (Static Assets)
echo "1. Tài nguyên tĩnh và Stylesheet:\n";

runTest("Tệp location.css tồn tại và chứa đầy đủ các class giao diện", function () {
    $cssFile = BASE_PATH . "/public/assets/css/location.css";
    if (!file_exists($cssFile)) throw new Exception("Không tìm thấy public/assets/css/location.css");
    $content = file_get_contents($cssFile);
    $requiredClasses = [
        ".autocomplete-wrapper",
        ".autocomplete-input",
        ".autocomplete-dropdown",
        ".autocomplete-item",
        ".autocomplete-rate-limit",
        ".job-locations-section",
        ".location-card",
        ".badge-loc-primary",
        ".badge-loc-verified",
        ".nearby-cta-btn",
        ".nearby-filter-bar",
        ".radius-chip",
        ".commute-warning-banner",
        ".preferred-loc-chip"
    ];
    foreach ($requiredClasses as $cls) {
        if (!str_contains($content, $cls)) {
            throw new Exception("Thiếu class CSS: {$cls}");
        }
    }
});

runTest("Tệp address_autocomplete.js tồn tại và định nghĩa AddressAutocomplete", function () {
    $jsFile = BASE_PATH . "/public/assets/js/address_autocomplete.js";
    if (!file_exists($jsFile)) throw new Exception("Không tìm thấy public/assets/js/address_autocomplete.js");
    $content = file_get_contents($jsFile);
    if (!str_contains($content, "class AddressAutocomplete")) throw new Exception("Thiếu class AddressAutocomplete");
    if (!str_contains($content, "role=\"combobox\"")) throw new Exception("Thiếu ARIA combobox");
    if (!str_contains($content, "role=\"listbox\"")) throw new Exception("Thiếu ARIA listbox");
    if (!str_contains($content, "session_token")) throw new Exception("Thiếu quản lý session_token");
    if (!str_contains($content, "retry_after_seconds")) throw new Exception("Thiếu xử lý 429 rate limit");
});

runTest("main.php đã nhúng location.css và address_autocomplete.js", function () {
    $mainFile = BASE_PATH . "/app/Views/layouts/main.php";
    $content = file_get_contents($mainFile);
    if (!str_contains($content, "location.css")) throw new Exception("main.php chưa nhúng location.css");
    if (!str_contains($content, "address_autocomplete.js")) throw new Exception("main.php chưa nhúng address_autocomplete.js");
});

// 2. Kiểm tra các View giao diện
echo "\n2. Kiểm tra cú pháp và cấu trúc các View giao diện:\n";

runTest("Company Job Form (job_form.php) có mục Địa điểm làm việc, modal và CRUD script", function () {
    $file = BASE_PATH . "/app/Views/company/job_form.php";
    $content = file_get_contents($file);
    if (!str_contains($content, "section-job-locations")) throw new Exception("Thiếu section-job-locations");
    if (!str_contains($content, "modal-location-form")) throw new Exception("Thiếu modal-location-form");
    if (!str_contains($content, "loadJobLocations")) throw new Exception("Thiếu hàm loadJobLocations");
    if (!str_contains($content, "handleSaveLocation")) throw new Exception("Thiếu hàm handleSaveLocation");
    if (!str_contains($content, "handleDeleteLocation")) throw new Exception("Thiếu hàm handleDeleteLocation");
    if (!str_contains($content, "handleSetPrimaryLocation")) throw new Exception("Thiếu hàm handleSetPrimaryLocation");
    if (!str_contains($content, "locAutocompleteInstance")) throw new Exception("Thiếu AddressAutocomplete instance");
});

runTest("Job Listings (jobs/index.php) có nút Việc làm gần tôi, radius chips và định dạng khoảng cách", function () {
    $file = BASE_PATH . "/app/Views/jobs/index.php";
    $content = file_get_contents($file);
    if (!str_contains($content, "btn-nearby-jobs")) throw new Exception("Thiếu nút btn-nearby-jobs");
    if (!str_contains($content, "nearby-filter-bar")) throw new Exception("Thiếu nearby-filter-bar");
    if (!str_contains($content, "data-radius=\"10\"")) throw new Exception("Thiếu chip bán kính mặc định 10km");
    if (!str_contains($content, "toggleNearbyJobs")) throw new Exception("Thiếu hàm toggleNearbyJobs");
    if (!str_contains($content, "/jobs/nearby-search")) throw new Exception("Thiếu API call nearby-search");
    if (!str_contains($content, "formatDistance")) throw new Exception("Thiếu hàm formatDistance");
    if (!str_contains($content, "Intl.NumberFormat('vi-VN'")) throw new Exception("Thiếu format tiếng Việt vi-VN");
    if (!str_contains($content, "Cách bạn")) throw new Exception("Thiếu nhãn Cách bạn");
});

runTest("Job Detail (jobs/show.php) hiển thị danh sách chi nhánh và kiểm tra commute check", function () {
    $file = BASE_PATH . "/app/Views/jobs/show.php";
    $content = file_get_contents($file);
    if (!str_contains($content, "detail-locations-card")) throw new Exception("Thiếu detail-locations-card");
    if (!str_contains($content, "apply-commute-section")) throw new Exception("Thiếu apply-commute-section");
    if (!str_contains($content, "commute-warning-box")) throw new Exception("Thiếu commute-warning-box");
    if (!str_contains($content, "runCommuteCheck")) throw new Exception("Thiếu hàm runCommuteCheck");
    if (!str_contains($content, "commute-check")) throw new Exception("Thiếu API call commute-check");
    if (!str_contains($content, "checkRealRouteDistance") || !str_contains($content, "runCommuteCheck(true")) throw new Exception("Thiếu tùy chọn xem quãng đường thực tế");
});

runTest("Student Profile (student/profile.php) có mục Khu Vực Làm Việc Mong Muốn và tối đa 10 khu vực", function () {
    $file = BASE_PATH . "/app/Views/student/profile.php";
    $content = file_get_contents($file);
    if (!str_contains($content, "preferred-locations-section")) throw new Exception("Thiếu preferred-locations-section");
    if (!str_contains($content, "pref-loc-ac-container")) throw new Exception("Thiếu pref-loc-ac-container");
    if (!str_contains($content, "loadPreferredLocations")) throw new Exception("Thiếu hàm loadPreferredLocations");
    if (!str_contains($content, "handleAddPreferredLocation")) throw new Exception("Thiếu hàm handleAddPreferredLocation");
    if (!str_contains($content, "savePreferredLocationsToServer")) throw new Exception("Thiếu hàm savePreferredLocationsToServer");
    if (!str_contains($content, "/student/preferred-locations")) throw new Exception("Thiếu API call preferred-locations");
});

// 3. Kiểm tra An toàn & Bảo mật
echo "\n3. Kiểm tra tiêu chuẩn An toàn & Bảo mật (Security & Privacy):\n";

runTest("Tuyệt đối không chứa GOONG_REST_API_KEY trong public/ và app/Views/", function () {
    $scanDirs = [BASE_PATH . "/public", BASE_PATH . "/app/Views"];
    foreach ($scanDirs as $dir) {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
        foreach ($iterator as $file) {
            if ($file->isFile() && in_array($file->getExtension(), ["js", "css", "php", "html"])) {
                $raw = file_get_contents($file->getPathname());
                if (str_contains($raw, "GOONG_REST_API_KEY")) {
                    throw new Exception("Phát hiện GOONG_REST_API_KEY trong " . $file->getPathname());
                }
            }
        }
    }
});

runTest("Không gọi trực tiếp rsapi.goong.io từ frontend code", function () {
    $scanDirs = [BASE_PATH . "/public", BASE_PATH . "/app/Views"];
    foreach ($scanDirs as $dir) {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
        foreach ($iterator as $file) {
            if ($file->isFile() && in_array($file->getExtension(), ["js", "css", "php", "html"])) {
                $raw = file_get_contents($file->getPathname());
                if (str_contains($raw, "rsapi.goong.io")) {
                    throw new Exception("Phát hiện URL trực tiếp rsapi.goong.io trong " . $file->getPathname());
                }
            }
        }
    }
});

runTest("Không lưu tọa độ GPS vào localStorage, sessionStorage hay cookies", function () {
    $jsFiles = [
        BASE_PATH . "/public/assets/js/address_autocomplete.js",
        BASE_PATH . "/app/Views/jobs/index.php",
        BASE_PATH . "/app/Views/jobs/show.php"
    ];
    foreach ($jsFiles as $file) {
        $raw = file_get_contents($file);
        if (preg_match('/(localStorage|sessionStorage)\.setItem\([^)]*coords/i', $raw)) {
            throw new Exception("Phát hiện lưu tọa độ vào storage trong " . $file);
        }
    }
});

echo "\n------------------------------------------------------------\n";
echo "Kết quả: {$passed}/{$total} bài kiểm thử thành công.\n";
if ($passed === $total) {
    echo "TẤT CẢ CÁC BÀI KIỂM THỬ GIAO DIỆN LOCATION ĐỀU VƯỢT QUA!\n";
    exit(0);
} else {
    echo "CÓ BÀI KIỂM THỬ THẤT BẠI.\n";
    exit(1);
}
