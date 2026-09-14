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
        ".job-detail-map",
        ".commute-warning-banner",
        ".preferred-loc-chip",
        ".location-picker-trigger",
        ".llp-modal-backdrop",
        ".llp-modal-dialog",
        ".llp-col-provinces",
        ".llp-col-districts",
        ".llp-chip"
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
    if (!str_contains($content, "res.errors && res.errors.retry_after_seconds")) throw new Exception("Chưa đọc retry_after_seconds từ lỗi API chuẩn hóa");
});

runTest("Tệp large_location_picker.js tồn tại và định nghĩa LargeLocationPicker", function () {
    $jsFile = BASE_PATH . "/public/assets/js/large_location_picker.js";
    if (!file_exists($jsFile)) throw new Exception("Không tìm thấy public/assets/js/large_location_picker.js");
    $content = file_get_contents($jsFile);
    if (!str_contains($content, "class LargeLocationPicker")) throw new Exception("Thiếu class LargeLocationPicker");
    if (!str_contains($content, "fetchHierarchy")) throw new Exception("Thiếu phương thức fetchHierarchy");
    if (!str_contains($content, "maxSelect")) throw new Exception("Thiếu giới hạn maxSelect");
    if (!str_contains($content, "selectProvince")) throw new Exception("Thiếu hỗ trợ chọn nhanh theo tỉnh thành");
});

runTest("main.php đã nhúng location.css, address_autocomplete.js và large_location_picker.js", function () {
    $mainFile = BASE_PATH . "/app/Views/layouts/main.php";
    $content = file_get_contents($mainFile);
    if (!str_contains($content, "location.css")) throw new Exception("main.php chưa nhúng location.css");
    if (!str_contains($content, "address_autocomplete.js")) throw new Exception("main.php chưa nhúng address_autocomplete.js");
    if (!str_contains($content, "large_location_picker.js")) throw new Exception("main.php chưa nhúng large_location_picker.js");
    if (!str_contains($content, "@goongmaps/goong-js@1.0.9")) throw new Exception("main.php chưa nhúng Goong JS cho trang bản đồ");
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
    if (!str_contains($content, 'new AddressAutocomplete("#loc-autocomplete-container"')) throw new Exception("Chưa khởi tạo AddressAutocomplete instance");
    if (!str_contains($content, "await loadJobLocations(id)")) throw new Exception("Chưa tải địa điểm khi sửa tin");
    if (!str_contains($content, "persistDraftJobLocations")) throw new Exception("Chưa lưu địa điểm sau khi tạo tin mới");
    if (!str_contains($content, "Vui lòng chọn một địa chỉ trong danh sách gợi ý Goong")) throw new Exception("Chưa bắt buộc chọn địa chỉ đã chuẩn hóa");
    if (!str_contains($content, 'id="btn-company-location-picker"')) throw new Exception("Thiếu nút trigger bộ chọn địa điểm lớn cho nhà tuyển dụng");
    if (!str_contains($content, "new LargeLocationPicker")) throw new Exception("Chưa khởi tạo LargeLocationPicker trong job_form.php");
});

runTest("Job Listings (jobs/index.php) có nút Việc làm gần tôi, radius chips và định dạng khoảng cách", function () {
    $file = BASE_PATH . "/app/Views/jobs/index.php";
    $content = file_get_contents($file);
    if (!str_contains($content, 'id="btn-nearby-jobs"')) throw new Exception("Thiếu nút btn-nearby-jobs trong DOM");
    if (!str_contains($content, "nearby-filter-bar")) throw new Exception("Thiếu nearby-filter-bar");
    if (!str_contains($content, "data-radius=\"10\"")) throw new Exception("Thiếu chip bán kính mặc định 10km");
    if (!str_contains($content, "toggleNearbyJobs")) throw new Exception("Thiếu hàm toggleNearbyJobs");
    if (!str_contains($content, "/jobs/nearby-search")) throw new Exception("Thiếu API call nearby-search");
    if (!str_contains($content, "formatDistance")) throw new Exception("Thiếu hàm formatDistance");
    if (!str_contains($content, "Intl.NumberFormat('vi-VN'")) throw new Exception("Thiếu format tiếng Việt vi-VN");
    if (!str_contains($content, "Cách bạn")) throw new Exception("Thiếu nhãn Cách bạn");
    foreach (["keyword", "shift_type", "salary_min", "location_id"] as $filter) {
        if (!str_contains($content, "payload.{$filter}")) throw new Exception("Nearby chưa gửi bộ lọc {$filter}");
    }
    if (!str_contains($content, 'id="btn-open-location-picker"')) throw new Exception("Thiếu nút trigger bộ chọn địa điểm lớn trong jobs/index.php");
    if (!str_contains($content, "new LargeLocationPicker")) throw new Exception("Chưa khởi tạo LargeLocationPicker trong jobs/index.php");
    if (!str_contains($content, "location_ids")) throw new Exception("Chưa đồng bộ tham số location_ids");
});

runTest("Job Detail (jobs/show.php) hiển thị danh sách chi nhánh và kiểm tra commute check", function () {
    $file = BASE_PATH . "/app/Views/jobs/show.php";
    $content = file_get_contents($file);
    if (!str_contains($content, 'id="detail-locations-card"')) throw new Exception("Thiếu detail-locations-card trong DOM");
    if (!str_contains($content, "renderJobDetailLocations(job.work_locations")) throw new Exception("Chưa render work_locations sau khi tải job");
    if (!str_contains($content, "new window.goongjs.Map")) throw new Exception("Chưa khởi tạo bản đồ Goong");
    if (!str_contains($content, "new window.goongjs.Marker")) throw new Exception("Chưa tạo marker địa điểm");
    if (!str_contains($content, "apply-commute-section")) throw new Exception("Thiếu apply-commute-section");
    if (!str_contains($content, "commute-warning-box")) throw new Exception("Thiếu commute-warning-box");
    if (!str_contains($content, "runCommuteCheck")) throw new Exception("Thiếu hàm runCommuteCheck");
    if (!str_contains($content, "commute-check")) throw new Exception("Thiếu API call commute-check");
    if (!str_contains($content, "checkRealRouteDistance") || !str_contains($content, "runCommuteCheck(true")) throw new Exception("Thiếu tùy chọn xem quãng đường thực tế");
    if (!str_contains($content, 'id="btn-commute-check"')) throw new Exception("Thiếu nút xin quyền GPS trong modal ứng tuyển");
    if (!str_contains($content, "prepareCommuteCheck")) throw new Exception("Thiếu bước chuẩn bị kiểm tra khoảng cách");
    if (!str_contains($content, "void prepareCommuteCheck()")) throw new Exception("Modal ứng tuyển chưa tự khởi động kiểm tra khoảng cách");
    if (!str_contains($content, "window.isSecureContext")) throw new Exception("Chưa giải thích yêu cầu HTTPS cho GPS");
    if (!str_contains($content, "lastCommuteIsFar")) throw new Exception("Chưa giữ trạng thái cảnh báo xa khi CV đang được kiểm tra");
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
    if (!str_contains($content, "if (!selected)")) throw new Exception("Chưa bắt buộc chọn gợi ý Goong");
    if (!str_contains($content, "throw new Error(res && res.message")) throw new Exception("Lỗi lưu khu vực vẫn đang bị bỏ qua");
});

runTest("Nearby backend nhận các bộ lọc đang hiển thị trên giao diện", function () {
    $repo = file_get_contents(BASE_PATH . "/app/Infrastructure/JobLocationRepository.php");
    foreach (["shift_type", "location_id", "salary_min", "keyword"] as $filter) {
        if (!str_contains($repo, '$filters["' . $filter . '"]')) {
            throw new Exception("Backend nearby chưa xử lý {$filter}");
        }
    }
    if (!str_contains($repo, '$filters["location_ids"]')) throw new Exception("Backend nearby chưa hỗ trợ chọn nhiều khu vực");
});

runTest("API location hierarchy sẵn sàng cho bộ chọn địa điểm lớn hai cột", function () {
    $routes = file_get_contents(BASE_PATH . "/app/Routes/api.php");
    $controller = file_get_contents(BASE_PATH . "/app/Http/Controllers/LocationController.php");
    $repository = file_get_contents(BASE_PATH . "/app/Infrastructure/LocationRepository.php");
    if (!str_contains($routes, '"/locations/hierarchy"')) throw new Exception("Thiếu route /locations/hierarchy");
    if (!str_contains($controller, "function hierarchy")) throw new Exception("Thiếu action hierarchy");
    foreach (["province_name", "location_ids", "areas", "area_name"] as $field) {
        if (!str_contains($repository, '"' . $field . '"')) throw new Exception("Hierarchy thiếu trường {$field}");
    }
});

runTest("API chuẩn hóa dùng chung hỗ trợ GPS và form địa chỉ cho sinh viên/doanh nghiệp", function () {
    $routes = file_get_contents(BASE_PATH . "/app/Routes/api.php");
    $service = file_get_contents(BASE_PATH . "/app/Domain/LocationFeatureService.php");
    if (!str_contains($routes, '"/map/resolve-location"')) throw new Exception("Thiếu API resolve-location dùng chung");
    foreach (["source", "gps", "manual", "administrative_mode", "address_detail"] as $contract) {
        if (!str_contains($service, '"' . $contract . '"')) throw new Exception("Resolve-location thiếu contract {$contract}");
    }
});

runTest("Modal địa điểm trong job_form.php có 2 lựa chọn lớn (GPS & Nhập địa chỉ) và hộp preview tọa độ", function () {
    $content = file_get_contents(BASE_PATH . "/app/Views/company/job_form.php");
    if (!str_contains($content, "loc-method-selector")) throw new Exception("Thiếu bộ chọn 2 phương thức lớn (loc-method-selector)");
    if (!str_contains($content, "Dùng vị trí hiện tại")) throw new Exception("Thiếu tab Dùng vị trí hiện tại");
    if (!str_contains($content, "Nhập địa chỉ")) throw new Exception("Thiếu tab Nhập địa chỉ");
    if (!str_contains($content, 'id="btn-get-modal-gps"')) throw new Exception("Thiếu nút Lấy vị trí GPS");
    if (!str_contains($content, "fetchModalGpsLocation")) throw new Exception("Thiếu hàm fetchModalGpsLocation");
    if (!str_contains($content, "resolveManualAddressModal")) throw new Exception("Thiếu hàm resolveManualAddressModal");
    if (!str_contains($content, 'id="loc-resolved-preview"')) throw new Exception("Thiếu hộp xem trước địa chỉ đã chuẩn hóa");
    if (!str_contains($content, "Địa chỉ hiện hành")) throw new Exception("Thiếu tab Địa chỉ hiện hành");
    if (!str_contains($content, "Địa chỉ cũ")) throw new Exception("Thiếu tab Địa chỉ cũ");
    if (!str_contains($content, '"/map/resolve-location"')) throw new Exception("Thiếu gọi API /map/resolve-location");
});

runTest("Giao diện sinh viên trong jobs/index.php hỗ trợ chọn tâm tìm kiếm (GPS / Nhập địa chỉ) và lưu mong muốn", function () {
    $content = file_get_contents(BASE_PATH . "/app/Views/jobs/index.php");
    if (!str_contains($content, 'id="modal-student-nearby"')) throw new Exception("Thiếu modal chọn vị trí tìm việc cho sinh viên");
    if (!str_contains($content, "openStudentNearbyModal")) throw new Exception("Thiếu hàm openStudentNearbyModal");
    if (!str_contains($content, "fetchStudentGpsLocation")) throw new Exception("Thiếu hàm fetchStudentGpsLocation");
    if (!str_contains($content, "resolveStudentManualAddress")) throw new Exception("Thiếu hàm resolveStudentManualAddress");
    if (!str_contains($content, "applyStudentNearbySearch")) throw new Exception("Thiếu hàm applyStudentNearbySearch");
    if (!str_contains($content, 'id="nearby-origin-label"')) throw new Exception("Thiếu nhãn hiển thị tâm tìm kiếm nearby-origin-label");
    if (!str_contains($content, 'id="btn-save-preferred-loc"')) throw new Exception("Thiếu nút lưu khu vực mong muốn");
    if (!str_contains($content, "saveCurrentNearbyToPreferred")) throw new Exception("Thiếu hàm saveCurrentNearbyToPreferred");
    if (!str_contains($content, "/student/preferred-locations")) throw new Exception("Thiếu gọi API preferred-locations khi sinh viên bấm lưu");
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

runTest("Giá trị key thật không bị ghi cứng vào mã nguồn được Git theo dõi", function () {
    $secretValues = array_filter([
        $_ENV["GOONG_REST_API_KEY"] ?? null,
        $_ENV["GOONG_MAPTILES_KEY"] ?? null,
    ], fn($value) => is_string($value) && strlen($value) >= 20);
    $scanDirs = [BASE_PATH . "/public", BASE_PATH . "/app"];
    foreach ($scanDirs as $dir) {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
        foreach ($iterator as $file) {
            if (!$file->isFile()) continue;
            $raw = file_get_contents($file->getPathname());
            foreach ($secretValues as $secret) {
                if (str_contains($raw, $secret)) {
                    throw new Exception("Phát hiện giá trị key thật trong " . $file->getPathname());
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
