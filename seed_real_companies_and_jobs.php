<?php

define("BASE_PATH", __DIR__);
require_once __DIR__ . "/vendor/autoload.php";

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

use JobMarket\Facades\Config;

$config = Config::env();
foreach ($argv ?? [] as $arg) {
    if (str_starts_with($arg, "--dbname=")) {
        $config["dbname"] = substr($arg, 9);
        $_ENV["DB_NAME"] = $config["dbname"];
    }
}

$db = new PDO(
    "mysql:dbname={$config['dbname']};host={$config['host']}",
    $config["user"],
    $config["password"],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

echo "=================================================================\n";
echo "   NẠP 15 CÔNG TY CÓ THẬT & VIỆC LÀM PART-TIME CHO SINH VIÊN    \n";
echo "   Database: {$config['dbname']}\n";
echo "=================================================================\n\n";

// 1. Cập nhật Locations mở rộng cho cả Hà Nội và TP.HCM
$locations = [
    ["loc-001", "Hà Nội - Cầu Giấy"],
    ["loc-002", "Hà Nội - Đống Đa"],
    ["loc-003", "Hà Nội - Thanh Xuân"],
    ["loc-004", "TP. Hồ Chí Minh - Quận 1"],
    ["loc-005", "TP. Hồ Chí Minh - Quận 10"],
    ["loc-006", "TP. Hồ Chí Minh - Bình Thạnh"],
    ["loc-007", "TP. Hồ Chí Minh - Quận 5"],
    ["loc-008", "Hà Nội - Hai Bà Trưng"],
    ["loc-009", "TP. Hồ Chí Minh - TP. Thủ Đức"],
    ["loc-010", "TP. Hồ Chí Minh - Quận 3"],
    ["loc-011", "TP. Hồ Chí Minh - Gò Vấp"],
    ["loc-012", "TP. Hồ Chí Minh - Phú Nhuận"]
];

$locStmt = $db->prepare(
    "INSERT INTO `locations` (`id`, `name`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `name` = VALUES(`name`)"
);
foreach ($locations as $l) {
    $locStmt->execute($l);
}
echo "[1/4] Đã cập nhật " . count($locations) . " địa điểm (Quận/Huyện) Hà Nội & TP.HCM.\n";

// Cập nhật logo cho 2 công ty có sẵn (Highlands Coffee và Miniso)
$db->exec("UPDATE `companies` SET `logo_url` = '/assets/images/companies/highlands.svg' WHERE `id` = 'comp-001'");
$db->exec("UPDATE `companies` SET `logo_url` = '/assets/images/companies/miniso.svg' WHERE `id` = 'comp-002'");

// 2. Định nghĩa 15 Công ty thực tế
$companiesData = [
    [
        "user_id"        => "user-comp-phuclong-lm81",
        "email"          => "phuclong.lm81@jobmarket.vn",
        "company_id"     => "comp-phuclong-lm81",
        "name"           => "Phúc Long Coffee & Tea - Chi nhánh Landmark 81",
        "description"    => "Chuỗi trà & cà phê Phúc Long tại TTTM Landmark 81 với lượng khách đông đúc, môi trường làm việc chuyên nghiệp, thân thiện. Tuyển dụng sinh viên làm thêm linh hoạt xoay ca theo lịch học.",
        "contact_person" => "Anh Hoàng (Quản lý sảnh)",
        "contact_phone"  => "02871001968",
        "address"        => "Tầng B1, TTTM Vincom Center Landmark 81, 720A Điện Biên Phủ, Phường 22",
        "district"       => "Bình Thạnh",
        "city"           => "TP. Hồ Chí Minh",
        "location_id"    => "loc-006",
        "logo_url"       => "/assets/images/companies/phuclong.svg",
        "website"        => "https://phuclong.com.vn"
    ],
    [
        "user_id"        => "user-comp-phuclong-cg",
        "email"          => "phuclong.caugiay@jobmarket.vn",
        "company_id"     => "comp-phuclong-cg",
        "name"           => "Phúc Long Coffee & Tea - Chi nhánh Cầu Giấy",
        "description"    => "Chi nhánh Phúc Long gần các trường Đại học Sư Phạm, Quốc Gia, Báo Chí. Môi trường trẻ trung, đồng nghiệp vui vẻ, hỗ trợ tối đa cho sinh viên năm 1 - năm 4 đăng ký ca linh hoạt.",
        "contact_person" => "Chị Mai (Cửa hàng trưởng)",
        "contact_phone"  => "02471001968",
        "address"        => "Số 241 Xuân Thủy, Phường Dịch Vọng Hậu",
        "district"       => "Cầu Giấy",
        "city"           => "Hà Nội",
        "location_id"    => "loc-001",
        "logo_url"       => "/assets/images/companies/phuclong.svg",
        "website"        => "https://phuclong.com.vn"
    ],
    [
        "user_id"        => "user-comp-tch-nvc",
        "email"          => "tch.nvc@jobmarket.vn",
        "company_id"     => "comp-tch-nvc",
        "name"           => "The Coffee House - Chi nhánh Nguyễn Văn Cừ",
        "description"    => "The Coffee House Nguyễn Văn Cừ là không gian học tập và làm việc yêu thích của sinh viên khu vực Quận 5. Không yêu cầu kinh nghiệm pha chế, được đào tạo bài bản từ đầu.",
        "contact_person" => "Anh Quân (Trưởng ca tuyển dụng)",
        "contact_phone"  => "18006936",
        "address"        => "Số 249 Nguyễn Văn Cừ, Phường 4",
        "district"       => "Quận 5",
        "city"           => "TP. Hồ Chí Minh",
        "location_id"    => "loc-007",
        "logo_url"       => "/assets/images/companies/thecoffeehouse.svg",
        "website"        => "https://thecoffeehouse.com"
    ],
    [
        "user_id"        => "user-comp-kfc-tayson",
        "email"          => "kfc.tayson@jobmarket.vn",
        "company_id"     => "comp-kfc-tayson",
        "name"           => "KFC Việt Nam - Chi nhánh Tây Sơn",
        "description"    => "Nhà hàng gà rán KFC Tây Sơn tuyển nhân sự part-time sinh viên: thu ngân, bếp chiên, sảnh đón tiếp. Chế độ đãi ngộ tốt, lương thưởng theo giờ rõ ràng, phát đồng phục miễn phí.",
        "contact_person" => "Chị Lan (Phụ trách nhân sự)",
        "contact_phone"  => "19006886",
        "address"        => "Số 292 Tây Sơn, Phường Ngã Tư Sở",
        "district"       => "Đống Đa",
        "city"           => "Hà Nội",
        "location_id"    => "loc-002",
        "logo_url"       => "/assets/images/companies/kfc.svg",
        "website"        => "https://kfcvietnam.com.vn"
    ],
    [
        "user_id"        => "user-comp-mixue-bk",
        "email"          => "mixue.bachkhoa@jobmarket.vn",
        "company_id"     => "comp-mixue-bk",
        "name"           => "Mixue - Chi nhánh Bách Khoa",
        "description"    => "Cửa hàng kem và trà sữa Mixue đối diện cổng Ký túc xá Bách Khoa. Công việc nhẹ nhàng, nhanh nhẹn, môi trường năng động rất phù hợp cho sinh viên muốn kiếm thêm thu nhập.",
        "contact_person" => "Anh Thành (Chủ cơ sở)",
        "contact_phone"  => "0966882244",
        "address"        => "Số 104 Tạ Quang Bửu, Phường Bách Khoa",
        "district"       => "Hai Bà Trưng",
        "city"           => "Hà Nội",
        "location_id"    => "loc-008",
        "logo_url"       => "/assets/images/companies/mixue.svg",
        "website"        => "https://mixue.vn"
    ],
    [
        "user_id"        => "user-comp-circlek-hcr",
        "email"          => "circlek.hoconrua@jobmarket.vn",
        "company_id"     => "comp-circlek-hcr",
        "name"           => "Circle K Việt Nam - Chi nhánh Hồ Con Rùa",
        "description"    => "Cửa hàng tiện lợi 24/7 Circle K Hồ Con Rùa tuyển nhân viên bán hàng và thu ngân theo ca linh hoạt, có phụ cấp làm ca đêm cực kỳ hấp dẫn cho sinh viên.",
        "contact_person" => "Anh Bình (Cửa hàng trưởng)",
        "contact_phone"  => "19003110",
        "address"        => "Số 18 Phạm Ngọc Thạch, Phường Võ Thị Sáu",
        "district"       => "Quận 3",
        "city"           => "TP. Hồ Chí Minh",
        "location_id"    => "loc-010",
        "logo_url"       => "/assets/images/companies/circlek.svg",
        "website"        => "https://circlek.com.vn"
    ],
    [
        "user_id"        => "user-comp-circlek-cl",
        "email"          => "circlek.chualang@jobmarket.vn",
        "company_id"     => "comp-circlek-cl",
        "name"           => "Circle K Việt Nam - Chi nhánh Chùa Láng",
        "description"    => "Cơ sở Circle K tại phố sinh viên Chùa Láng (gần Ngoại Thương, Ngoại Giao, Luật). Đăng ký lịch làm hàng tuần theo thời khóa biểu học tập, không sợ trùng lịch thi.",
        "contact_person" => "Chị Thảo (Tuyển dụng)",
        "contact_phone"  => "19003110",
        "address"        => "Số 91 Chùa Láng, Phường Láng Thượng",
        "district"       => "Đống Đa",
        "city"           => "Hà Nội",
        "location_id"    => "loc-002",
        "logo_url"       => "/assets/images/companies/circlek.svg",
        "website"        => "https://circlek.com.vn"
    ],
    [
        "user_id"        => "user-comp-gs25-ktx",
        "email"          => "gs25.dhqg@jobmarket.vn",
        "company_id"     => "comp-gs25-ktx",
        "name"           => "GS25 Việt Nam - Chi nhánh Ký Túc Xá ĐHQG",
        "description"    => "Cửa hàng tiện lợi chuẩn Hàn Quốc GS25 ngay trong khuôn viên KTX ĐHQG. Ưu tiên 100% sinh viên lưu trú trong KTX, đi bộ đi làm, môi trường hiện đại và văn minh.",
        "contact_person" => "Anh Dũng (Cửa hàng trưởng)",
        "contact_phone"  => "02873022525",
        "address"        => "Ký túc xá Khu B Đại học Quốc Gia, Phường Đông Hòa",
        "district"       => "TP. Thủ Đức",
        "city"           => "TP. Hồ Chí Minh",
        "location_id"    => "loc-009",
        "logo_url"       => "/assets/images/companies/gs25.svg",
        "website"        => "https://gs25.com.vn"
    ],
    [
        "user_id"        => "user-comp-familymart-mdc",
        "email"          => "familymart.mdc@jobmarket.vn",
        "company_id"     => "comp-familymart-mdc",
        "name"           => "FamilyMart - Chi nhánh Mạc Đĩnh Chi",
        "description"    => "FamilyMart Mạc Đĩnh Chi phục vụ nhân viên văn phòng và sinh viên trung tâm Quận 1. Được hưởng phụ cấp ăn uống, lương thưởng chuyên cần hàng tháng.",
        "contact_person" => "Chị Ngọc (Quản lý cửa hàng)",
        "contact_phone"  => "02839308888",
        "address"        => "Số 40 Mạc Đĩnh Chi, Phường Đa Kao",
        "district"       => "Quận 1",
        "city"           => "TP. Hồ Chí Minh",
        "location_id"    => "loc-004",
        "logo_url"       => "/assets/images/companies/familymart.svg",
        "website"        => "https://famima.vn"
    ],
    [
        "user_id"        => "user-comp-winmart-kdt",
        "email"          => "winmart.kdt@jobmarket.vn",
        "company_id"     => "comp-winmart-kdt",
        "name"           => "WinMart+ - Chi nhánh Khuất Duy Tiến",
        "description"    => "Siêu thị mini WinMart+ Khuất Duy Tiến tuyển nhân viên thu ngân, trưng bày hàng hóa và kiểm soát hạn sử dụng. Công việc ổn định, giờ giấc cố định theo ca đăng ký.",
        "contact_person" => "Anh Hùng (Trưởng cửa hàng)",
        "contact_phone"  => "02471066866",
        "address"        => "Số 82 Khuất Duy Tiến, Phường Thanh Xuân Bắc",
        "district"       => "Thanh Xuân",
        "city"           => "Hà Nội",
        "location_id"    => "loc-003",
        "logo_url"       => "/assets/images/companies/winmart.svg",
        "website"        => "https://winmart.vn"
    ],
    [
        "user_id"        => "user-comp-cgv-batrieu",
        "email"          => "cgv.batrieu@jobmarket.vn",
        "company_id"     => "comp-cgv-batrieu",
        "name"           => "CGV Cinemas - Vincom Center Bà Triệu",
        "description"    => "Cụm rạp chiếu phim CGV Bà Triệu tuyển nhân viên bán vé, bắp nước và soát vé. Môi trường giải trí đỉnh cao, được xem phim miễn phí hàng tháng và đào tạo tác phong dịch vụ khách hàng 5 sao.",
        "contact_person" => "Anh Khoa (Bộ phận Tuyển dụng CGV)",
        "contact_phone"  => "19006017",
        "address"        => "Tầng 6, Vincom Center, 191 Bà Triệu, Phường Lê Đại Hành",
        "district"       => "Hai Bà Trưng",
        "city"           => "Hà Nội",
        "location_id"    => "loc-008",
        "logo_url"       => "/assets/images/companies/cgv.svg",
        "website"        => "https://cgv.vn"
    ],
    [
        "user_id"        => "user-comp-lotte-nowzone",
        "email"          => "lotte.nowzone@jobmarket.vn",
        "company_id"     => "comp-lotte-nowzone",
        "name"           => "Lotte Cinema - Nowzone Quận 5",
        "description"    => "Cụm rạp Lotte Cinema Nowzone tuyển dụng các bạn sinh viên năng động, hoạt bát làm việc tại sảnh, quầy vé và kiểm soát phòng chiếu. Được linh hoạt đổi ca thi cử.",
        "contact_person" => "Chị Trâm (Quản lý cụm rạp)",
        "contact_phone"  => "02838328404",
        "address"        => "Tầng 5, TTTM Nowzone, 235 Nguyễn Văn Cừ, Phường 4",
        "district"       => "Quận 5",
        "city"           => "TP. Hồ Chí Minh",
        "location_id"    => "loc-007",
        "logo_url"       => "/assets/images/companies/lottecinema.svg",
        "website"        => "https://lottecinemavn.com"
    ],
    [
        "user_id"        => "user-comp-vus-qt",
        "email"          => "vus.quangtrung@jobmarket.vn",
        "company_id"     => "comp-vus-qt",
        "name"           => "Hệ Thống Anh Văn Hội Việt Mỹ (VUS) - Chi nhánh Quang Trung",
        "description"    => "Hệ thống Anh ngữ hàng đầu VUS tuyển trợ giảng tiếng Anh (Teaching Assistant) các lớp thiếu nhi và thiếu niên. Cơ hội rèn luyện tiếng Anh cùng giáo viên bản ngữ và phát triển kỹ năng sư phạm.",
        "contact_person" => "Thầy Tâm (Trưởng nhóm Học thuật)",
        "contact_phone"  => "02873083333",
        "address"        => "Số 651 Quang Trung, Phường 11",
        "district"       => "Gò Vấp",
        "city"           => "TP. Hồ Chí Minh",
        "location_id"    => "loc-011",
        "logo_url"       => "/assets/images/companies/vus.svg",
        "website"        => "https://vus.edu.vn"
    ],
    [
        "user_id"        => "user-comp-ila-cg",
        "email"          => "ila.caugiay@jobmarket.vn",
        "company_id"     => "comp-ila-cg",
        "name"           => "Trung Tâm Anh Ngữ ILA - Chi nhánh Cầu Giấy",
        "description"    => "Tổ chức giáo dục Anh ngữ ILA tuyển trợ giảng và cộng tác viên học vụ part-time. Mức lương theo giờ hấp dẫn, có chứng chỉ kinh nghiệm sau thời gian làm việc.",
        "contact_person" => "Chị Phương (Điều phối viên TA)",
        "contact_phone"  => "19006965",
        "address"        => "Tầng 3, Tòa nhà Golden Palace, Lê Văn Lương",
        "district"       => "Cầu Giấy",
        "city"           => "Hà Nội",
        "location_id"    => "loc-001",
        "logo_url"       => "/assets/images/companies/ila.svg",
        "website"        => "https://ila.edu.vn"
    ],
    [
        "user_id"        => "user-comp-whitepalace",
        "email"          => "whitepalace.tuyendung@jobmarket.vn",
        "company_id"     => "comp-whitepalace",
        "name"           => "Trung Tâm Hội Nghị Tiệc Cưới White Palace",
        "description"    => "Trung tâm hội nghị yến tiệc White Palace Hoàng Văn Thụ tuyển nhân viên phục vụ tiệc, lễ tân và khánh tiết đón khách cuối tuần. Trả lương liền sau ca, bao cơm giữa ca chất lượng cao.",
        "contact_person" => "Anh Phong (Phụ trách Nhân sự Sự kiện)",
        "contact_phone"  => "02838447266",
        "address"        => "Số 194 Hoàng Văn Thụ, Phường 9",
        "district"       => "Phú Nhuận",
        "city"           => "TP. Hồ Chí Minh",
        "location_id"    => "loc-012",
        "logo_url"       => "/assets/images/companies/whitepalace.svg",
        "website"        => "https://whitepalace.com.vn"
    ]
];

// 3. Thực hiện nạp Users và Companies
$userStmt = $db->prepare(
    "INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `status`)
     VALUES (?, ?, ?, ?, 'company', 'active')
     ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `status` = 'active'"
);

$compStmt = $db->prepare(
    "INSERT INTO `companies` (
        `id`, `user_id`, `name`, `description`, `contact_person`, `contact_phone`, 
        `address`, `city`, `district`, `logo_url`, `website`, `verification_status`
    ) VALUES (
        ?, ?, ?, ?, ?, ?, 
        ?, ?, ?, ?, ?, 'verified'
    ) ON DUPLICATE KEY UPDATE 
        `name` = VALUES(`name`), 
        `description` = VALUES(`description`),
        `address` = VALUES(`address`),
        `city` = VALUES(`city`),
        `district` = VALUES(`district`),
        `logo_url` = VALUES(`logo_url`),
        `website` = VALUES(`website`),
        `verification_status` = 'verified'"
);

$defaultPasswordHash = password_hash("Company@123", PASSWORD_DEFAULT);

foreach ($companiesData as $c) {
    $userStmt->execute([$c["user_id"], $c["name"], $c["email"], $defaultPasswordHash]);
    $compStmt->execute([
        $c["company_id"], $c["user_id"], $c["name"], $c["description"], $c["contact_person"],
        $c["contact_phone"], $c["address"], $c["city"], $c["district"], $c["logo_url"], $c["website"]
    ]);
}
echo "[2/4] Đã nạp thành công " . count($companiesData) . " công ty và tài khoản đăng nhập.\n";

// 4. Định nghĩa 36 Tin Tuyển Dụng chi tiết (2-3 tin/công ty)
$jobsData = [
    // --- 1. Phúc Long Landmark 81 (3 tin) ---
    [
        "id" => "job-pl-01", "company_id" => "comp-phuclong-lm81", "category_id" => "cat-001", "location_id" => "loc-006",
        "title" => "Nhân viên Pha chế (Barista) Part-time Ca Linh Hoạt",
        "description" => "Pha chế các dòng trà sữa, trà trái cây và cà phê truyền thống theo đúng công thức định lượng của Phúc Long. Vệ sinh và sắp xếp quầy bar ngăn nắp, sạch sẽ.",
        "requirements" => "Nhanh nhẹn, cẩn thận, có trách nhiệm trong công việc. Không yêu cầu kinh nghiệm, ứng viên sẽ được đào tạo bài bản 3 ngày trước khi nhận ca.",
        "benefits" => "Lương 26.000đ - 32.000đ/giờ + thưởng doanh số ca. Giảm giá 50% thức uống cho nhân viên. Hỗ trợ đăng ký ca theo thời khóa biểu học tập của sinh viên.",
        "salary_min" => 26000, "salary_max" => 32000, "shift_type" => "flexible",
        "shift_information" => "Ca sáng (7h00 - 12h00), Ca chiều (12h00 - 17h00). Đăng ký tối thiểu 4 ca/tuần.",
        "working_schedule" => "Linh hoạt xoay ca từ Thứ 2 đến Chủ Nhật theo lịch học.",
        "required_skills" => "Pha chế đồ uống, Giao tiếp khách hàng, Làm việc nhóm",
        "city" => "TP. Hồ Chí Minh", "district" => "Bình Thạnh", "address" => "Tầng B1, TTTM Vincom Center Landmark 81, 720A Điện Biên Phủ"
    ],
    [
        "id" => "job-pl-02", "company_id" => "comp-phuclong-lm81", "category_id" => "cat-001", "location_id" => "loc-006",
        "title" => "Nhân viên Thu ngân & Order Đồ uống Ca Tối",
        "description" => "Chào đón khách hàng, tư vấn menu đồ uống và bánh ngọt, nhập thông tin order vào hệ thống POS và thực hiện thanh toán tiền mặt/chuyển khoản chính xác.",
        "requirements" => "Giao tiếp vui vẻ, niềm nở, trung thực và có tinh thần phục vụ khách hàng. Ưu tiên sinh viên các trường ĐH khu vực Bình Thạnh, Quận 1, Quận 2.",
        "benefits" => "Lương 28.000đ - 35.000đ/giờ. Có phụ cấp gửi xe và thưởng chuyên cần hàng tháng.",
        "salary_min" => 28000, "salary_max" => 35000, "shift_type" => "evening",
        "shift_information" => "Ca tối: 17h30 - 22h30. Rất thích hợp cho sinh viên học buổi sáng/chiều.",
        "working_schedule" => "Đăng ký từ 4 đến 6 buổi tối trong tuần.",
        "required_skills" => "Thu ngân & POS, Giao tiếp khách hàng, Tiếng Anh cơ bản",
        "city" => "TP. Hồ Chí Minh", "district" => "Bình Thạnh", "address" => "Tầng B1, TTTM Vincom Center Landmark 81, 720A Điện Biên Phủ"
    ],
    [
        "id" => "job-pl-03", "company_id" => "comp-phuclong-lm81", "category_id" => "cat-001", "location_id" => "loc-006",
        "title" => "Nhân viên Phục vụ Sảnh & Dọn Bàn Cuối Tuần (Thứ 7 & CN)",
        "description" => "Hỗ trợ bưng bê thức uống cho khách tại bàn, lau dọn bàn ghế, giữ gìn không gian cửa hàng luôn sạch sẽ, thoáng mát trong những khung giờ cao điểm.",
        "requirements" => "Sức khỏe tốt, chăm chỉ, nhiệt tình. Phù hợp cho các bạn sinh viên trong tuần bận học chỉ rảnh ngày Thứ 7 và Chủ Nhật.",
        "benefits" => "Mức lương ưu đãi cuối tuần: 30.000đ - 36.000đ/giờ. Được bao 1 phần ăn nhẹ và 1 ly nước mỗi ca.",
        "salary_min" => 30000, "salary_max" => 36000, "shift_type" => "weekend",
        "shift_information" => "Ca sáng Thứ 7/CN (8h00 - 14h00) hoặc Ca chiều Thứ 7/CN (14h00 - 21h00).",
        "working_schedule" => "Thứ 7 và Chủ Nhật hàng tuần.",
        "required_skills" => "Phục vụ bàn, Nhanh nhẹn, Chăm chỉ",
        "city" => "TP. Hồ Chí Minh", "district" => "Bình Thạnh", "address" => "Tầng B1, TTTM Vincom Center Landmark 81, 720A Điện Biên Phủ"
    ],

    // --- 2. Phúc Long Cầu Giấy (2 tin) ---
    [
        "id" => "job-plcg-01", "company_id" => "comp-phuclong-cg", "category_id" => "cat-001", "location_id" => "loc-001",
        "title" => "Nhân viên Phục vụ Ca Sáng cho Sinh Viên (Xuân Thủy)",
        "description" => "Đón khách, dọn bàn, hỗ trợ chuyển đơn hàng mang đi cho các tài xế công nghệ (Grab, ShopeeFood). Kiểm tra đồ dùng tại quầy tự phục vụ.",
        "requirements" => "Sinh viên năm 1 - năm 3 các trường ĐH Quốc Gia, Sư Phạm, Báo Chí. Đúng giờ, tác phong nhanh nhẹn, hòa đồng.",
        "benefits" => "Lương 25.000đ - 30.000đ/giờ. Giảm 50% đồ uống. Được nghỉ phép linh hoạt khi có lịch thi giữa kỳ, cuối kỳ.",
        "salary_min" => 25000, "salary_max" => 30000, "shift_type" => "morning",
        "shift_information" => "Ca sáng: 07h00 - 11h30 (nghỉ trưa kịp giờ học chiều).",
        "working_schedule" => "Đăng ký từ 3 - 5 ca sáng/tuần.",
        "required_skills" => "Phục vụ bàn, Đúng giờ, Nhanh nhẹn",
        "city" => "Hà Nội", "district" => "Cầu Giấy", "address" => "Số 241 Xuân Thủy, Phường Dịch Vọng Hậu"
    ],
    [
        "id" => "job-plcg-02", "company_id" => "comp-phuclong-cg", "category_id" => "cat-001", "location_id" => "loc-001",
        "title" => "Nhân viên Phụ quầy & Đóng gói đồ uống mang đi (Ca Chiều)",
        "description" => "Hỗ trợ dán tem đơn hàng, đóng nắp ly, bỏ ống hút và giao đồ uống đúng đơn cho khách hàng mua mang về hoặc tài xế giao hàng.",
        "requirements" => "Cẩn thận, phân biệt tốt các loại topping và đồ uống theo tem in. Ưu tiên các bạn mong muốn gắn bó từ 3 tháng trở lên.",
        "benefits" => "Lương 25.000đ - 30.000đ/giờ. Môi trường làm việc có điều hòa mát mẻ, nhân viên thân thiện.",
        "salary_min" => 25000, "salary_max" => 30000, "shift_type" => "afternoon",
        "shift_information" => "Ca chiều: 12h30 - 17h30.",
        "working_schedule" => "Đăng ký từ 4 ca/tuần.",
        "required_skills" => "Đóng gói, Cẩn thận, Giao tiếp khách hàng",
        "city" => "Hà Nội", "district" => "Cầu Giấy", "address" => "Số 241 Xuân Thủy, Phường Dịch Vọng Hậu"
    ],

    // --- 3. The Coffee House Nguyễn Văn Cừ (2 tin) ---
    [
        "id" => "job-tch-01", "company_id" => "comp-tch-nvc", "category_id" => "cat-001", "location_id" => "loc-007",
        "title" => "Barista Part-time Đào Tạo Từ Đầu (Nhà Cà Phê Q5)",
        "description" => "Học và thực hành pha chế cà phê máy Espresso, trà đào cam sả, trà sữa và đá xay theo tiêu chuẩn thương hiệu The Coffee House. Bảo quản nguyên vật liệu tươi ngon.",
        "requirements" => "Đam mê cà phê và nghệ thuật pha chế. Không cần kinh nghiệm, Nhà sẽ đào tạo miễn phí và cấp chứng nhận Barista nội bộ sau khi hoàn thành khóa học.",
        "benefits" => "Lương 26.000đ - 32.000đ/giờ. Môi trường làm việc trẻ trung, cơ hội lên Barista chính thức sau 6 tháng.",
        "salary_min" => 26000, "salary_max" => 32000, "shift_type" => "rotating",
        "shift_information" => "Ca xoay: Sáng (7h00 - 12h30), Chiều (12h30 - 18h00), Tối (18h00 - 23h00).",
        "working_schedule" => "Xếp lịch theo tuần dựa trên thời khóa biểu sinh viên.",
        "required_skills" => "Pha chế đồ uống, Học hỏi nhanh, Làm việc nhóm",
        "city" => "TP. Hồ Chí Minh", "district" => "Quận 5", "address" => "Số 249 Nguyễn Văn Cừ, Phường 4"
    ],
    [
        "id" => "job-tch-02", "company_id" => "comp-tch-nvc", "category_id" => "cat-001", "location_id" => "loc-007",
        "title" => "Nhân viên Phục vụ & Chăm sóc khách hàng Ca Tối",
        "description" => "Chào đón khách, bưng trà nước mời khách, dọn dẹp không gian quán, hỗ trợ khách kết nối wifi và tìm vị trí ngồi phù hợp để học bài, làm việc.",
        "requirements" => "Nụ cười thân thiện, giao tiếp lịch thiệp, yêu thích không gian yên tĩnh và văn minh tại The Coffee House.",
        "benefits" => "Lương 27.000đ - 33.000đ/giờ + Tip. Thưởng doanh số ngày lễ, tết nhân đôi, nhân ba.",
        "salary_min" => 27000, "salary_max" => 33000, "shift_type" => "evening",
        "shift_information" => "Ca tối: 18h00 - 22h30.",
        "working_schedule" => "Đăng ký 4 - 5 buổi tối/tuần.",
        "required_skills" => "Giao tiếp khách hàng, Phục vụ bàn, Thân thiện",
        "city" => "TP. Hồ Chí Minh", "district" => "Quận 5", "address" => "Số 249 Nguyễn Văn Cừ, Phường 4"
    ],

    // --- 4. KFC Tây Sơn (3 tin) ---
    [
        "id" => "job-kfc-01", "company_id" => "comp-kfc-tayson", "category_id" => "cat-001", "location_id" => "loc-002",
        "title" => "Nhân viên Thu ngân Sảnh Nhà Hàng KFC Tây Sơn",
        "description" => "Tư vấn các combo gà rán và thức ăn nhanh tại quầy thu ngân, nhận tiền và trả tiền thừa chuẩn xác, phối hợp cùng nhân viên tiếp thực bàn giao khay thức ăn cho khách.",
        "requirements" => "Trung thực, nhanh nhẹn, gương mặt sáng. Không yêu cầu kinh nghiệm bán hàng.",
        "benefits" => "Lương 25.000đ - 30.000đ/giờ. Ăn trưa/tối miễn phí tại nhà hàng theo ca. Đồng phục được cấp phát đầy đủ.",
        "salary_min" => 25000, "salary_max" => 30000, "shift_type" => "flexible",
        "shift_information" => "Ca 4 tiếng hoặc 6 tiếng linh hoạt theo từng ngày trong tuần.",
        "working_schedule" => "Đăng ký lịch vào Chủ Nhật hàng tuần cho tuần kế tiếp.",
        "required_skills" => "Thu ngân & POS, Giao tiếp khách hàng, Nhanh nhẹn",
        "city" => "Hà Nội", "district" => "Đống Đa", "address" => "Số 292 Tây Sơn, Phường Ngã Tư Sở"
    ],
    [
        "id" => "job-kfc-02", "company_id" => "comp-kfc-tayson", "category_id" => "cat-001", "location_id" => "loc-002",
        "title" => "Nhân viên Bếp chiên & Phụ việc ca tối (KFC Tây Sơn)",
        "description" => "Chuẩn bị nguyên liệu gà tươi, tẩm ướp bột và chiên gà theo quy trình chuẩn của KFC toàn cầu. Đóng hộp gà rán và giữ vệ sinh khu vực chế biến.",
        "requirements" => "Chăm chỉ, chịu khó, có thể làm việc trong môi trường bếp ấm. Ưu tiên các bạn nam sinh viên có sức khỏe tốt.",
        "benefits" => "Lương 27.000đ - 33.000đ/giờ. Thưởng theo năng suất ca và phụ cấp ca tối.",
        "salary_min" => 27000, "salary_max" => 33000, "shift_type" => "evening",
        "shift_information" => "Ca tối: 17h00 - 22h30.",
        "working_schedule" => "4 - 5 buổi tối trong tuần.",
        "required_skills" => "Chế biến thực phẩm, Chăm chỉ, An toàn vệ sinh",
        "city" => "Hà Nội", "district" => "Đống Đa", "address" => "Số 292 Tây Sơn, Phường Ngã Tư Sở"
    ],
    [
        "id" => "job-kfc-03", "company_id" => "comp-kfc-tayson", "category_id" => "cat-001", "location_id" => "loc-002",
        "title" => "Nhân viên Giao hàng nội bộ cửa hàng Part-time",
        "description" => "Giao đồ ăn KFC cho khách hàng trong bán kính 3km xung quanh cửa hàng Tây Sơn theo đơn đặt trực tiếp qua hotline/app KFC.",
        "requirements" => "Có xe máy riêng và điện thoại thông minh, thông thạo đường phố khu vực Đống Đa, Thanh Xuân.",
        "benefits" => "Lương cứng 30.000đ/giờ + Phí phụ cấp 8.000đ - 12.000đ trên mỗi đơn giao thành công.",
        "salary_min" => 30000, "salary_max" => 38000, "shift_type" => "rotating",
        "shift_information" => "Ca trưa (11h00 - 14h00) hoặc Ca tối (18h00 - 21h30).",
        "working_schedule" => "Linh hoạt từ 3 ca/tuần.",
        "required_skills" => "Giao hàng, Tìm đường, Cẩn thận",
        "city" => "Hà Nội", "district" => "Đống Đa", "address" => "Số 292 Tây Sơn, Phường Ngã Tư Sở"
    ],

    // --- 5. Mixue Bách Khoa (2 tin) ---
    [
        "id" => "job-mx-01", "company_id" => "comp-mixue-bk", "category_id" => "cat-001", "location_id" => "loc-008",
        "title" => "Nhân viên Bán Kem & Trà Sữa Sinh Viên (Mixue Tạ Quang Bửu)",
        "description" => "Lấy kem ốc quế, pha trà chanh bách hoa, trà sữa trân châu theo công thức có sẵn. Thu tiền và giao hóa đơn cho khách.",
        "requirements" => "Nhanh nhẹn, vui tính, hòa đồng với các bạn sinh viên Bách Khoa, Kinh Tế, Xây Dựng. Nhận cả sinh viên năm nhất chưa có kinh nghiệm.",
        "benefits" => "Lương 23.000đ - 28.000đ/giờ. Được uống trà sữa và ăn kem miễn phí trong ca làm việc.",
        "salary_min" => 23000, "salary_max" => 28000, "shift_type" => "morning",
        "shift_information" => "Ca sáng: 08h00 - 12h30.",
        "working_schedule" => "Đăng ký 4 buổi sáng/tuần.",
        "required_skills" => "Bán hàng, Vui vẻ, Pha chế đồ uống",
        "city" => "Hà Nội", "district" => "Hai Bà Trưng", "address" => "Số 104 Tạ Quang Bửu, Phường Bách Khoa"
    ],
    [
        "id" => "job-mx-02", "company_id" => "comp-mixue-bk", "category_id" => "cat-001", "location_id" => "loc-008",
        "title" => "Nhân viên Phụ Quầy & Vệ Sinh Dụng Cụ Ca Tối",
        "description" => "Hỗ trợ nấu trân châu, pha cốt trà, chuẩn bị nguyên liệu cho ca sau và dọn dẹp vệ sinh máy làm kem cuối ngày trước khi đóng cửa.",
        "requirements" => "Cẩn thận, sạch sẽ, có trách nhiệm. Làm xong sớm được về sớm.",
        "benefits" => "Lương 25.000đ - 30.000đ/giờ. Thưởng chuyên cần tháng 300.000đ nếu không đi muộn ca nào.",
        "salary_min" => 25000, "salary_max" => 30000, "shift_type" => "evening",
        "shift_information" => "Ca tối: 17h30 - 22h30.",
        "working_schedule" => "Đăng ký từ 3 - 5 ca tối/tuần.",
        "required_skills" => "Chăm chỉ, Vệ sinh sạch sẽ, Đúng giờ",
        "city" => "Hà Nội", "district" => "Hai Bà Trưng", "address" => "Số 104 Tạ Quang Bửu, Phường Bách Khoa"
    ],

    // --- 6. Circle K Hồ Con Rùa (2 tin) ---
    [
        "id" => "job-ck-01", "company_id" => "comp-circlek-hcr", "category_id" => "cat-002", "location_id" => "loc-010",
        "title" => "Nhân viên Cửa Hàng Tiện Lợi Ca Đêm (Circle K Hồ Con Rùa)",
        "description" => "Kiểm tra hàng tồn, nhập hàng từ xe tải giao hàng đêm, sắp xếp hàng hóa lên kệ, tính tiền thu ngân và giữ an ninh trật tự cửa hàng.",
        "requirements" => "Sức khỏe tốt, có thể làm ca đêm, tính tình điềm đạm, trung thực.",
        "benefits" => "Phụ cấp ca đêm cực cao: 32.000đ - 42.000đ/giờ. Được nghỉ giữa ca 45 phút có phòng nghỉ nhân viên riêng.",
        "salary_min" => 32000, "salary_max" => 42000, "shift_type" => "night",
        "shift_information" => "Ca đêm: 22h00 - 06h00 sáng hôm sau.",
        "working_schedule" => "3 - 4 đêm/tuần (xoay ca linh hoạt).",
        "required_skills" => "Thu ngân & POS, Trung thực, Sắp xếp kho",
        "city" => "TP. Hồ Chí Minh", "district" => "Quận 3", "address" => "Số 18 Phạm Ngọc Thạch, Phường Võ Thị Sáu"
    ],
    [
        "id" => "job-ck-02", "company_id" => "comp-circlek-hcr", "category_id" => "cat-002", "location_id" => "loc-010",
        "title" => "Nhân viên Bán Hàng & Thu Ngân Ca Linh Hoạt",
        "description" => "Tư vấn sản phẩm tiện ích, làm mì xào/xúc xích nóng cho khách hàng, quét mã vạch thanh toán và chào đón khách hàng theo tiêu chuẩn Circle K.",
        "requirements" => "Nhanh nhẹn, thân thiện, biết sử dụng máy tính cơ bản. Nhận sinh viên đăng ký ca theo tuần.",
        "benefits" => "Lương 25.000đ - 30.000đ/giờ. Cơ hội thăng tiến lên Trưởng ca bán lẻ sau 6 tháng gắn bó.",
        "salary_min" => 25000, "salary_max" => 30000, "shift_type" => "flexible",
        "shift_information" => "Ca 4 tiếng - 6 tiếng/ngày, đăng ký tự do trong khung giờ từ 6h00 đến 22h00.",
        "working_schedule" => "Linh hoạt từ Thứ 2 đến Chủ Nhật.",
        "required_skills" => "Thu ngân & POS, Giao tiếp khách hàng, Nhiệt tình",
        "city" => "TP. Hồ Chí Minh", "district" => "Quận 3", "address" => "Số 18 Phạm Ngọc Thạch, Phường Võ Thị Sáu"
    ],

    // --- 7. Circle K Chùa Láng (2 tin) ---
    [
        "id" => "job-ckcl-01", "company_id" => "comp-circlek-cl", "category_id" => "cat-002", "location_id" => "loc-002",
        "title" => "Nhân viên Bán Hàng Part-time Theo Lịch Học (Chùa Láng)",
        "description" => "Bán hàng tiêu dùng nhanh, làm đồ ăn nóng (bánh bao, xúc xích), giữ quầy thu ngân và khu vực ăn uống của sinh viên luôn sạch sẽ.",
        "requirements" => "Sinh viên các trường ĐH Ngoại Thương, Ngoại Giao, Luật, Giao Thông. Chăm chỉ, đúng giờ, hòa đồng.",
        "benefits" => "Lương 25.000đ - 30.000đ/giờ. Được chủ động đổi ca với đồng nghiệp khi có lịch học bù đột xuất.",
        "salary_min" => 25000, "salary_max" => 30000, "shift_type" => "afternoon",
        "shift_information" => "Ca chiều: 13h00 - 18h00.",
        "working_schedule" => "Đăng ký từ 3 - 5 buổi chiều/tuần.",
        "required_skills" => "Bán lẻ & Thu ngân, Chăm chỉ, Thân thiện",
        "city" => "Hà Nội", "district" => "Đống Đa", "address" => "Số 91 Chùa Láng, Phường Láng Thượng"
    ],
    [
        "id" => "job-ckcl-02", "company_id" => "comp-circlek-cl", "category_id" => "cat-002", "location_id" => "loc-002",
        "title" => "Nhân viên Thu Ngân Ca Tối Sinh Viên (18h - 22h30)",
        "description" => "Trực quầy thanh toán, in hóa đơn, hỗ trợ nhận đơn hàng và bảo quản tiền mặt trong két an toàn theo hướng dẫn của quản lý.",
        "requirements" => "Trung thực, cẩn trọng, thao tác nhanh nhẹn trên phần mềm tính tiền.",
        "benefits" => "Lương 27.000đ - 33.000đ/giờ. Phụ cấp gửi xe và thưởng nhân viên xuất sắc mỗi quý.",
        "salary_min" => 27000, "salary_max" => 33000, "shift_type" => "evening",
        "shift_information" => "Ca tối: 18h00 - 22h30.",
        "working_schedule" => "Đăng ký từ 4 ca tối/tuần.",
        "required_skills" => "Thu ngân & POS, Trung thực, Cẩn thận",
        "city" => "Hà Nội", "district" => "Đống Đa", "address" => "Số 91 Chùa Láng, Phường Láng Thượng"
    ],

    // --- 8. GS25 KTX ĐHQG (2 tin) ---
    [
        "id" => "job-gs-01", "company_id" => "comp-gs25-ktx", "category_id" => "cat-002", "location_id" => "loc-009",
        "title" => "Nhân viên Bán Hàng GS25 Ký Túc Xá Khu B (Ưu Tiên Sinh Viên KTX)",
        "description" => "Bán hàng đồ ăn vặt Hàn Quốc, cơm nắm tam giác, lẩu tokbokki tự nấu. Tư vấn thẻ thành viên GS25 cho các bạn sinh viên trong Làng Đại học.",
        "requirements" => "Sinh viên đang học tại các trường thành viên ĐHQG TP.HCM (Bách Khoa, KHTN, KHXH&NV, CNTT, Kinh Tế - Luật). Vui vẻ, nhiệt tình.",
        "benefits" => "Lương 25.000đ - 30.000đ/giờ. Đi làm ngay trong khuôn viên ký túc xá, không tốn xăng xe di chuyển, giảm giá đồ ăn GS25.",
        "salary_min" => 25000, "salary_max" => 30000, "shift_type" => "flexible",
        "shift_information" => "Ca linh hoạt 4 tiếng: 07h00-11h00, 11h00-15h00, 15h00-19h00, 19h00-23h00.",
        "working_schedule" => "Đăng ký theo kỳ học mới nhất.",
        "required_skills" => "Bán hàng, Giao tiếp sinh viên, Vui vẻ",
        "city" => "TP. Hồ Chí Minh", "district" => "TP. Thủ Đức", "address" => "Ký túc xá Khu B Đại học Quốc Gia, Phường Đông Hòa"
    ],
    [
        "id" => "job-gs-02", "company_id" => "comp-gs25-ktx", "category_id" => "cat-002", "location_id" => "loc-009",
        "title" => "Nhân viên Sắp Xếp Hàng Hóa & Thu Ngân Ca Tối",
        "description" => "Hỗ trợ dỡ hàng từ pallet, phân loại mì hộp, nước ngọt lên quầy kệ bắt mắt, kiểm tra hạn sử dụng và tính tiền cho khách hàng giờ tan học.",
        "requirements" => "Sức khỏe tốt, có trách nhiệm, chủ động trong công việc.",
        "benefits" => "Lương 27.000đ - 33.000đ/giờ. Có thưởng doanh số cửa hàng nếu vượt chỉ tiêu tháng.",
        "salary_min" => 27000, "salary_max" => 33000, "shift_type" => "evening",
        "shift_information" => "Ca tối: 18h00 - 22h30.",
        "working_schedule" => "4 - 5 buổi tối/tuần.",
        "required_skills" => "Sắp xếp kho, Thu ngân, Cẩn thận",
        "city" => "TP. Hồ Chí Minh", "district" => "TP. Thủ Đức", "address" => "Ký túc xá Khu B Đại học Quốc Gia, Phường Đông Hòa"
    ],

    // --- 9. FamilyMart Mạc Đĩnh Chi (2 tin) ---
    [
        "id" => "job-fm-01", "company_id" => "comp-familymart-mdc", "category_id" => "cat-002", "location_id" => "loc-004",
        "title" => "Nhân viên Phụ Quầy Oden & Fast-food Ca Sáng (FamilyMart Q1)",
        "description" => "Nấu nước lẩu Oden Nhật Bản, chiên đùi gà, làm bánh mì sandwich tam giác buổi sáng và phục vụ bữa sáng nhanh cho khách văn phòng Quận 1.",
        "requirements" => "Gọn gàng, sạch sẽ, đúng giờ buổi sáng. Được đào tạo quy trình vệ sinh an toàn thực phẩm chuẩn Nhật.",
        "benefits" => "Lương 26.000đ - 32.000đ/giờ. Bao ăn sáng 1 suất đồ ăn tại cửa hàng.",
        "salary_min" => 26000, "salary_max" => 32000, "shift_type" => "morning",
        "shift_information" => "Ca sáng: 06h30 - 11h30.",
        "working_schedule" => "Đăng ký từ 3 - 5 ca sáng/tuần.",
        "required_skills" => "Nấu ăn cơ bản, Đúng giờ, Nhanh nhẹn",
        "city" => "TP. Hồ Chí Minh", "district" => "Quận 1", "address" => "Số 40 Mạc Đĩnh Chi, Phường Đa Kao"
    ],
    [
        "id" => "job-fm-02", "company_id" => "comp-familymart-mdc", "category_id" => "cat-002", "location_id" => "loc-004",
        "title" => "Nhân viên Ca Đêm Hỗ Trợ Kiểm Kê & Bán Hàng",
        "description" => "Bán hàng ca đêm, nhập số liệu kiểm kê thực tế đối chiếu phần mềm kho, lau chùi kính và sàn nhà trước khi mở bán ca sáng.",
        "requirements" => "Độ tuổi từ 18 - 25, không ngại thức đêm, trung thực và tỉ mỉ.",
        "benefits" => "Lương ca đêm 34.000đ - 42.000đ/giờ. Có phòng nghỉ riêng cho nhân viên và trợ cấp gửi xe.",
        "salary_min" => 34000, "salary_max" => 42000, "shift_type" => "night",
        "shift_information" => "Ca đêm: 22h30 - 06h30.",
        "working_schedule" => "3 - 4 đêm trong tuần.",
        "required_skills" => "Kiểm kê kho, Thu ngân & POS, Trung thực",
        "city" => "TP. Hồ Chí Minh", "district" => "Quận 1", "address" => "Số 40 Mạc Đĩnh Chi, Phường Đa Kao"
    ],

    // --- 10. WinMart+ Khuất Duy Tiến (2 tin) ---
    [
        "id" => "job-wm-01", "company_id" => "comp-winmart-kdt", "category_id" => "cat-002", "location_id" => "loc-003",
        "title" => "Nhân viên Thu Ngân & Kiểm Tra Hạn Dùng (WinMart+ Thanh Xuân)",
        "description" => "Tính tiền cho khách hàng, quét mã tích điểm hội viên Win, kiểm tra hạn sử dụng trên bao bì thực phẩm và loại bỏ sản phẩm cận date.",
        "requirements" => "Trung thực, tính toán cẩn thận, thái độ lịch sự với khách hàng khu dân cư.",
        "benefits" => "Lương 24.000đ - 29.000đ/giờ. Giảm giá 10% khi mua sắm thực phẩm cho gia đình/bản thân tại chuỗi WinMart.",
        "salary_min" => 24000, "salary_max" => 29000, "shift_type" => "morning",
        "shift_information" => "Ca sáng: 07h30 - 12h00.",
        "working_schedule" => "Đăng ký từ 4 buổi sáng/tuần.",
        "required_skills" => "Thu ngân & POS, Cẩn thận, Giao tiếp",
        "city" => "Hà Nội", "district" => "Thanh Xuân", "address" => "Số 82 Khuất Duy Tiến, Phường Thanh Xuân Bắc"
    ],
    [
        "id" => "job-wm-02", "company_id" => "comp-winmart-kdt", "category_id" => "cat-002", "location_id" => "loc-003",
        "title" => "Nhân viên Sơ Chế Nông Sản & Trưng Bày Hàng Hóa",
        "description" => "Đóng gói rau củ quả, dán tem cân điện tử, xếp thịt cá vào tủ mát và dọn dẹp kệ hàng hóa gọn gàng, đẹp mắt.",
        "requirements" => "Chăm chỉ, khéo tay, cẩn thận. Không yêu cầu kinh nghiệm chuyên môn.",
        "benefits" => "Lương 25.000đ - 30.000đ/giờ. Có cơ hội chuyển sang nhân viên chính thức sau khi tốt nghiệp đại học.",
        "salary_min" => 25000, "salary_max" => 30000, "shift_type" => "afternoon",
        "shift_information" => "Ca chiều: 13h00 - 18h00.",
        "working_schedule" => "Đăng ký 4 buổi chiều/tuần.",
        "required_skills" => "Sơ chế thực phẩm, Khéo tay, Nhanh nhẹn",
        "city" => "Hà Nội", "district" => "Thanh Xuân", "address" => "Số 82 Khuất Duy Tiến, Phường Thanh Xuân Bắc"
    ],

    // --- 11. CGV Vincom Bà Triệu (3 tin) ---
    [
        "id" => "job-cgv-01", "company_id" => "comp-cgv-batrieu", "category_id" => "cat-001", "location_id" => "loc-008",
        "title" => "Nhân viên Quầy Vé & Bắp Nước (Concession) Part-time CGV",
        "description" => "Tư vấn suất chiếu, bán vé xem phim, múc bắp rang bơ, rót nước ngọt và upsell các combo quà tặng phim bom tấn cho khán giả.",
        "requirements" => "Ngoại hình sáng, nụ cười tươi tắn, kỹ năng giao tiếp hoạt bát. Yêu thích phim ảnh là một lợi thế lớn.",
        "benefits" => "Lương 26.000đ - 32.000đ/giờ. Tặng 4 vé xem phim CGV miễn phí mỗi tháng (áp dụng cả định dạng 2D, 3D, IMAX).",
        "salary_min" => 26000, "salary_max" => 32000, "shift_type" => "flexible",
        "shift_information" => "Ca 4 tiếng - 6 tiếng, xoay ca linh hoạt: Sáng (8h-13h), Chiều (13h-18h), Tối (18h-23h).",
        "working_schedule" => "Đăng ký từ 4 ca/tuần.",
        "required_skills" => "Bán hàng & Thu ngân, Giao tiếp hoạt bát, Tiếng Anh cơ bản",
        "city" => "Hà Nội", "district" => "Hai Bà Trưng", "address" => "Tầng 6, Vincom Center, 191 Bà Triệu, Phường Lê Đại Hành"
    ],
    [
        "id" => "job-cgv-02", "company_id" => "comp-cgv-batrieu", "category_id" => "cat-001", "location_id" => "loc-008",
        "title" => "Nhân viên Soát Vé & Hướng Dẫn Phòng Chiếu Ca Tối",
        "description" => "Kiểm tra vé xem phim của khách trước khi vào rạp, hướng dẫn khách tìm đúng số ghế, kiểm soát trật tự trong phòng chiếu và dọn dẹp rác sau mỗi suất chiếu.",
        "requirements" => "Tác phong nhanh nhẹn, hòa nhã, có tinh thần trách nhiệm cao. Có khả năng xử lý tình huống khéo léo.",
        "benefits" => "Lương 27.000đ - 33.000đ/giờ + Phụ cấp ca đêm nếu làm quá 23h30.",
        "salary_min" => 27000, "salary_max" => 33000, "shift_type" => "evening",
        "shift_information" => "Ca tối: 18h00 - 23h30.",
        "working_schedule" => "Đăng ký từ 4 buổi tối/tuần.",
        "required_skills" => "Kiểm soát vé, Dịch vụ khách hàng, Nhanh nhẹn",
        "city" => "Hà Nội", "district" => "Hai Bà Trưng", "address" => "Tầng 6, Vincom Center, 191 Bà Triệu, Phường Lê Đại Hành"
    ],
    [
        "id" => "job-cgv-03", "company_id" => "comp-cgv-batrieu", "category_id" => "cat-004", "location_id" => "loc-008",
        "title" => "Nhân viên Hỗ Trợ Sự Kiện Ra Mắt Phim Cuối Tuần (PG/PB CGV)",
        "description" => "Đón tiếp nghệ sĩ, khách mời thảm đỏ, phát quà lưu niệm và hỗ trợ chụp ảnh check-in tại backdrop các sự kiện Premiere phim chiếu rạp cuối tuần.",
        "requirements" => "Chiều cao: Nam từ 1m70, Nữ từ 1m60. Giao tiếp lưu loát, tự tin trước ống kính, tác phong chuyên nghiệp.",
        "benefits" => "Mức thu nhập hấp dẫn: 32.000đ - 40.000đ/giờ. Cơ hội tiếp xúc và giao lưu trực tiếp với các nghệ sĩ, diễn viên nổi tiếng.",
        "salary_min" => 32000, "salary_max" => 40000, "shift_type" => "weekend",
        "shift_information" => "Ca theo sự kiện: Thứ 6 hoặc Thứ 7 (17h00 - 22h00).",
        "working_schedule" => "Cuối tuần theo lịch công chiếu phim mới.",
        "required_skills" => "Sự kiện & Tiếp thị, Giao tiếp tự tin, Ngoại hình sáng",
        "city" => "Hà Nội", "district" => "Hai Bà Trưng", "address" => "Tầng 6, Vincom Center, 191 Bà Triệu, Phường Lê Đại Hành"
    ],

    // --- 12. Lotte Cinema Nowzone (2 tin) ---
    [
        "id" => "job-lt-01", "company_id" => "comp-lotte-nowzone", "category_id" => "cat-001", "location_id" => "loc-007",
        "title" => "Nhân viên Sảnh & Quầy Vé (Floor & Box Office) Lotte Cinema",
        "description" => "Bán vé xem phim tại quầy, hỗ trợ khách sử dụng máy in vé tự động Kiosk, giải đáp thắc mắc về lịch chiếu và các chương trình khuyến mãi thẻ sinh viên.",
        "requirements" => "Giọng nói truyền cảm, dễ nghe, thái độ phục vụ ân cần. Ưu tiên sinh viên các trường ĐH Sư Phạm, KHTN, Sài Gòn khu vực Quận 5.",
        "benefits" => "Lương 26.000đ - 31.000đ/giờ. Vé xem phim miễn phí mỗi tháng tại hệ thống Lotte Cinema toàn quốc.",
        "salary_min" => 26000, "salary_max" => 31000, "shift_type" => "afternoon",
        "shift_information" => "Ca chiều: 13h00 - 18h00.",
        "working_schedule" => "Đăng ký từ 4 buổi chiều/tuần.",
        "required_skills" => "Bán vé, Thu ngân, Giao tiếp dễ thương",
        "city" => "TP. Hồ Chí Minh", "district" => "Quận 5", "address" => "Tầng 5, TTTM Nowzone, 235 Nguyễn Văn Cừ, Phường 4"
    ],
    [
        "id" => "job-lt-02", "company_id" => "comp-lotte-nowzone", "category_id" => "cat-001", "location_id" => "loc-007",
        "title" => "Nhân viên Kiểm Soát Vé & Dọn Rạp Ca Đêm (Lotte Nowzone)",
        "description" => "Xé cuống vé trước cửa rạp, kiểm tra nhiệt độ và âm thanh rạp chiếu, dọn dẹp ly bắp rơi vãi sau khi hết phim để chuẩn bị rạp sạch cho ngày mới.",
        "requirements" => "Sức khỏe tốt, chăm chỉ, thật thà. Không ngại làm việc buổi tối muộn.",
        "benefits" => "Lương 30.000đ - 38.000đ/giờ (đã bao gồm phụ cấp làm ca khuya). Hỗ trợ taxi về an toàn nếu ca kết thúc sau 24h00.",
        "salary_min" => 30000, "salary_max" => 38000, "shift_type" => "night",
        "shift_information" => "Ca đêm: 20h00 - 00h30.",
        "working_schedule" => "3 - 4 buổi tối/tuần.",
        "required_skills" => "Chăm chỉ, Nhanh nhẹn, Đúng giờ",
        "city" => "TP. Hồ Chí Minh", "district" => "Quận 5", "address" => "Tầng 5, TTTM Nowzone, 235 Nguyễn Văn Cừ, Phường 4"
    ],

    // --- 13. VUS Quang Trung (2 tin) ---
    [
        "id" => "job-vus-01", "company_id" => "comp-vus-qt", "category_id" => "cat-003", "location_id" => "loc-011",
        "title" => "Trợ Giảng Tiếng Anh (Teaching Assistant) Lớp Thiếu Nhi VUS",
        "description" => "Hỗ trợ giáo viên nước ngoài quản lý lớp học từ 15-18 học viên (độ tuổi 6-11), sửa phát âm, hướng dẫn bài tập trên lớp và điểm danh học viên.",
        "requirements" => "Tiếng Anh giao tiếp tốt (IELTS từ 6.0 hoặc tương đương). Yêu quý trẻ em, kiên nhẫn, vui vẻ, có năng lượng tích cực.",
        "benefits" => "Lương theo giờ rất cao: 45.000đ - 65.000đ/giờ. Được làm việc trực tiếp với 100% giáo viên bản ngữ, cải thiện Speaking vượt bậc. Cấp thư giới thiệu thực tập chuẩn xịn.",
        "salary_min" => 45000, "salary_max" => 65000, "shift_type" => "weekend",
        "shift_information" => "Các lớp cuối tuần: Sáng T7/CN (8h00 - 10h30) hoặc Chiều T7/CN (14h00 - 16h30).",
        "working_schedule" => "Thứ 7 và Chủ Nhật cố định theo khóa học 3 tháng.",
        "required_skills" => "Tiếng Anh giao tiếp, Sư phạm, Yêu trẻ em",
        "city" => "TP. Hồ Chí Minh", "district" => "Gò Vấp", "address" => "Số 651 Quang Trung, Phường 11"
    ],
    [
        "id" => "job-vus-02", "company_id" => "comp-vus-qt", "category_id" => "cat-003", "location_id" => "loc-011",
        "title" => "Nhân viên Hỗ Trợ Học Vụ & Chăm Sóc Học Viên Part-time",
        "description" => "Gọi điện thông báo kết quả kiểm tra định kỳ cho phụ huynh, chuẩn bị giáo cụ học tập cho giáo viên, kiểm tra phòng lab máy tính trước giờ học.",
        "requirements" => "Giọng nói nhẹ nhàng, giao tiếp qua điện thoại lưu loát, thành thạo tin học văn phòng Word/Excel cơ bản.",
        "benefits" => "Lương 35.000đ - 45.000đ/giờ. Môi trường giáo dục chuyên nghiệp, máy lạnh 100%, đồng nghiệp văn minh.",
        "salary_min" => 35000, "salary_max" => 45000, "shift_type" => "afternoon",
        "shift_information" => "Ca chiều: 14h00 - 18h30.",
        "working_schedule" => "Đăng ký từ 3 - 5 buổi chiều/tuần.",
        "required_skills" => "Tin học văn phòng, Giao tiếp điện thoại, Chu đáo",
        "city" => "TP. Hồ Chí Minh", "district" => "Gò Vấp", "address" => "Số 651 Quang Trung, Phường 11"
    ],

    // --- 14. ILA Cầu Giấy (2 tin) ---
    [
        "id" => "job-ila-01", "company_id" => "comp-ila-cg", "category_id" => "cat-003", "location_id" => "loc-001",
        "title" => "Trợ Giảng Tiếng Anh Part-time Lớp Thiếu Nhi (ILA Cầu Giấy)",
        "description" => "Đồng giảng cùng chuyên gia nước ngoài, tổ chức trò chơi tương tác tiếng Anh đầu giờ, nhắc nhở bài tập về nhà và hỗ trợ các bé trong suốt buổi học.",
        "requirements" => "Sinh viên các trường ĐH Ngoại Ngữ (ULIS), Hà Nội (HANU), RMIT, Ngoại Thương... Tiếng Anh phát âm chuẩn, tự tin giao tiếp.",
        "benefits" => "Lương 45.000đ - 65.000đ/giờ. Môi trường đào tạo sư phạm chuẩn quốc tế, nhận chứng chỉ kinh nghiệm sau 6 tháng.",
        "salary_min" => 45000, "salary_max" => 65000, "shift_type" => "evening",
        "shift_information" => "Ca tối: 17h30 - 20h30 (các ngày Thứ 2-4-6 hoặc Thứ 3-5-7).",
        "working_schedule" => "Theo lịch khóa học (3 buổi tối/tuần).",
        "required_skills" => "Tiếng Anh giao tiếp, Quản lý lớp, Hoạt náo",
        "city" => "Hà Nội", "district" => "Cầu Giấy", "address" => "Tầng 3, Tòa nhà Golden Palace, Lê Văn Lương"
    ],
    [
        "id" => "job-ila-02", "company_id" => "comp-ila-cg", "category_id" => "cat-003", "location_id" => "loc-001",
        "title" => "Cộng Tác Viên Tư Vấn Khóa Học Theo Ca (Telesales Part-time)",
        "description" => "Liên hệ theo danh sách phụ huynh đăng ký kiểm tra trình độ tiếng Anh miễn phí, tư vấn lộ trình học phù hợp và hẹn lịch đến trung tâm kiểm tra.",
        "requirements" => "Chất giọng ấm áp, có kỹ năng lắng nghe và thuyết phục tốt. Chăm chỉ, có tinh thần cầu tiến.",
        "benefits" => "Lương cứng 35.000đ/giờ + Thưởng nóng từ 150.000đ đến 300.000đ cho mỗi học viên đăng ký khóa học thành công.",
        "salary_min" => 35000, "salary_max" => 50000, "shift_type" => "morning",
        "shift_information" => "Ca sáng: 08h30 - 12h00 hoặc Ca chiều: 13h30 - 17h30.",
        "working_schedule" => "Đăng ký từ 4 ca/tuần.",
        "required_skills" => "Tư vấn khách hàng, Thuyết phục, Kiên nhẫn",
        "city" => "Hà Nội", "district" => "Cầu Giấy", "address" => "Tầng 3, Tòa nhà Golden Palace, Lê Văn Lương"
    ],

    // --- 15. White Palace Phú Nhuận (3 tin) ---
    [
        "id" => "job-wp-01", "company_id" => "comp-whitepalace", "category_id" => "cat-004", "location_id" => "loc-012",
        "title" => "Nhân viên Phục Vụ Tiệc Cưới & Hội Nghị Part-time (Nhận Lương Liền)",
        "description" => "Phục vụ thức ăn và đồ uống tại bàn tiệc theo tiêu chuẩn nhà hàng 5 sao, tiếp đá, rót rượu/nước ngọt và dọn dẹp chén đĩa sau tiệc.",
        "requirements" => "Nam/Nữ sinh viên nhanh nhẹn, ngoại hình dễ nhìn. Đồng phục quần tây đen, áo sơ mi trắng, giày tây/búp bê đen.",
        "benefits" => "Lương 35.000đ - 45.000đ/giờ. Được bao 1 suất ăn tiệc chất lượng cao giữa ca. Nhận tiền mặt hoặc chuyển khoản ngay sau khi hết tiệc.",
        "salary_min" => 35000, "salary_max" => 45000, "shift_type" => "weekend",
        "shift_information" => "Ca trưa (10h00 - 15h00) hoặc Ca tối (16h30 - 22h00) Thứ 7 và Chủ Nhật.",
        "working_schedule" => "Linh hoạt đăng ký theo từng sự kiện cuối tuần.",
        "required_skills" => "Phục vụ bàn tiệc, Nhanh nhẹn, Tác phong 5 sao",
        "city" => "TP. Hồ Chí Minh", "district" => "Phú Nhuận", "address" => "Số 194 Hoàng Văn Thụ, Phường 9"
    ],
    [
        "id" => "job-wp-02", "company_id" => "comp-whitepalace", "category_id" => "cat-004", "location_id" => "loc-012",
        "title" => "Nhân viên Lễ Tân & Khánh Tiết Đón Khách Yến Tiệc",
        "description" => "Đứng sảnh đón tiếp khách mời, cài hoa cài áo, hướng dẫn khách vào đúng sảnh tiệc cưới và hỗ trợ khu vực thùng mừng cưới.",
        "requirements" => "Nữ cao từ 1m62, Nam cao từ 1m72. Gương mặt thanh tú, nụ cười rạng rỡ, giao tiếp duyên dáng và lịch thiệp.",
        "benefits" => "Mức thù lao hấp dẫn: 40.000đ - 55.000đ/giờ. Được cung cấp áo dài / vest khánh tiết trang nhã.",
        "salary_min" => 40000, "salary_max" => 55000, "shift_type" => "evening",
        "shift_information" => "Ca tối: 17h00 - 21h30 các ngày có tiệc lớn.",
        "working_schedule" => "Đăng ký theo lịch biểu diễn/yến tiệc.",
        "required_skills" => "Lễ tân khánh tiết, Giao tiếp duyên dáng, Ngoại hình chuẩn",
        "city" => "TP. Hồ Chí Minh", "district" => "Phú Nhuận", "address" => "Số 194 Hoàng Văn Thụ, Phường 9"
    ],
    [
        "id" => "job-wp-03", "company_id" => "comp-whitepalace", "category_id" => "cat-004", "location_id" => "loc-012",
        "title" => "Nhân viên Kỹ Thuật Hỗ Trợ Setup Âm Thanh & Ánh Sáng Sân Khấu",
        "description" => "Hỗ trợ kỹ sư âm thanh kéo dây cáp micro, kiểm tra đèn follow, chuẩn bị máy khói và đạo cụ múa khai tiệc tại sân khấu chính.",
        "requirements" => "Chăm chỉ, nhanh nhẹn, có đam mê và kiến thức cơ bản về thiết bị âm thanh, ánh sáng sự kiện.",
        "benefits" => "Lương 38.000đ - 48.000đ/giờ. Được học hỏi kinh nghiệm điều khiển thiết bị sân khấu hiện đại bậc nhất TP.HCM.",
        "salary_min" => 38000, "salary_max" => 48000, "shift_type" => "flexible",
        "shift_information" => "Ca setup chiều (13h00 - 18h00) hoặc Ca trực diễn tối (17h00 - 22h00).",
        "working_schedule" => "Theo lịch diễn sự kiện trong tuần.",
        "required_skills" => "Âm thanh ánh sáng, Nhanh nhẹn, Kỹ thuật sự kiện",
        "city" => "TP. Hồ Chí Minh", "district" => "Phú Nhuận", "address" => "Số 194 Hoàng Văn Thụ, Phường 9"
    ]
];

// Chuẩn bị câu lệnh Insert Job
$jobStmt = $db->prepare(
    "INSERT INTO `jobs` (
        `id`, `company_id`, `category_id`, `location_id`, `title`, `description`, 
        `requirements`, `benefits`, `type`, `work_type`, `salary_type`, 
        `salary_min`, `salary_max`, `currency`, `shift_type`, `shift_information`, 
        `working_schedule`, `required_skills`, `work_format`, `work_mode`, `city`, 
        `district`, `address`, `status`, `deadline`, `application_deadline`, 
        `quantity`, `published_at`
    ) VALUES (
        ?, ?, ?, ?, ?, ?, 
        ?, ?, ?, ?, ?, 
        ?, ?, ?, ?, ?, 
        ?, ?, ?, ?, ?, 
        ?, ?, ?, ?, ?, 
        ?, ?
    ) ON DUPLICATE KEY UPDATE 
        `title` = VALUES(`title`), 
        `description` = VALUES(`description`),
        `requirements` = VALUES(`requirements`),
        `benefits` = VALUES(`benefits`),
        `location_id` = VALUES(`location_id`),
        `status` = 'published', 
        `salary_min` = VALUES(`salary_min`),
        `salary_max` = VALUES(`salary_max`),
        `shift_type` = VALUES(`shift_type`),
        `shift_information` = VALUES(`shift_information`),
        `working_schedule` = VALUES(`working_schedule`),
        `required_skills` = VALUES(`required_skills`),
        `application_deadline` = VALUES(`application_deadline`)"
);

$deadlineDate = date("Y-m-d", strtotime("+60 days"));
$publishedAt = date("Y-m-d H:i:s");

foreach ($jobsData as $j) {
    $jobStmt->execute([
        $j["id"], $j["company_id"], $j["category_id"], $j["location_id"], $j["title"], $j["description"],
        $j["requirements"], $j["benefits"], "part-time", "part_time", "hourly",
        $j["salary_min"], $j["salary_max"], "VND", $j["shift_type"], $j["shift_information"],
        $j["working_schedule"], $j["required_skills"], "on-site", "onsite", $j["city"],
        $j["district"], $j["address"], "published", $deadlineDate, $deadlineDate,
        3, $publishedAt
    ]);
}

echo "[3/4] Đã nạp thành công " . count($jobsData) . " tin tuyển dụng part-time sinh viên theo ca/giờ.\n";

// 5. Kiểm tra tổng kết số liệu trong cơ sở dữ liệu
$totalCompanies = $db->query("SELECT COUNT(*) FROM companies")->fetchColumn();
$totalJobs = $db->query("SELECT COUNT(*) FROM jobs WHERE status = 'published' AND deleted_at IS NULL")->fetchColumn();

echo "\n=================================================================\n";
echo "   NẠP DỮ LIỆU HOÀN TẤT THÀNH CÔNG!                              \n";
echo "   - Tổng số công ty trong hệ thống : {$totalCompanies}\n";
echo "   - Tổng số tin tuyển dụng đang mở : {$totalJobs}\n";
echo "=================================================================\n";
