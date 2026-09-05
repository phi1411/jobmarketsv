<?php

namespace JobMarket\Database;

use JobMarket\Facades\Config;
use PDO;
use RuntimeException;

class Seeder
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function run(bool $force = false): void
    {
        if (Config::isProduction() && !$force) {
            throw new RuntimeException("CẢNH BÁO: Không thể chạy seeder phát triển trên môi trường PRODUCTION! Sử dụng --force nếu bạn chắc chắn.");
        }

        echo "--- BẮT ĐẦU NẠP DỮ LIỆU SEED (DEVELOPMENT) ---" . PHP_EOL;

        // 1. Seed Categories
        $categories = [
            ["cat-001", "F&B - Nhà hàng / Quán cà phê"],
            ["cat-002", "Bán lẻ / Cửa hàng tiện lợi"],
            ["cat-003", "Gia sư / Trợ giảng"],
            ["cat-004", "Sự kiện / PG - PB"],
            ["cat-005", "Văn phòng / Nhập liệu part-time"]
        ];
        $catStmt = $this->db->prepare("INSERT IGNORE INTO `categories` (`id`, `name`) VALUES (?, ?)");
        foreach ($categories as $cat) {
            $catStmt->execute($cat);
        }
        echo "  [OK] Đã nạp 5 danh mục việc làm." . PHP_EOL;

        // 2. Seed Skills
        $skills = [
            ["skill-001", "Pha chế đồ uống"],
            ["skill-002", "Thu ngân & POS"],
            ["skill-003", "Phục vụ bàn"],
            ["skill-004", "Tiếng Anh giao tiếp"],
            ["skill-005", "Giao tiếp khách hàng"],
            ["skill-006", "Tin học văn phòng"]
        ];
        $skillStmt = $this->db->prepare("INSERT IGNORE INTO `skills` (`id`, `name`) VALUES (?, ?)");
        foreach ($skills as $skill) {
            $skillStmt->execute($skill);
        }
        echo "  [OK] Đã nạp 6 kỹ năng phổ biến." . PHP_EOL;

        // 2b. Seed Locations
        $locations = [
            ["loc-001", "Hà Nội - Cầu Giấy"],
            ["loc-002", "Hà Nội - Đống Đa"],
            ["loc-003", "Hà Nội - Thanh Xuân"],
            ["loc-004", "TP. Hồ Chí Minh - Quận 1"],
            ["loc-005", "TP. Hồ Chí Minh - Quận 10"],
        ];
        $locStmt = $this->db->prepare(
            "INSERT INTO `locations` (`id`, `name`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `name` = VALUES(`name`)"
        );
        foreach ($locations as $loc) {
            $locStmt->execute($loc);
        }
        echo "  [OK] Đã nạp 5 địa điểm phổ biến." . PHP_EOL;

        // 3. Seed Users (Admin, Companies, Students)
        $users = [
            [
                "id"       => "user-admin-01",
                "name"     => "Quản Trị Viên",
                "email"    => "admin@jobmarket.vn",
                "password" => password_hash("Admin@123", PASSWORD_DEFAULT),
                "role"     => "admin",
                "status"   => "active"
            ],
            [
                "id"       => "user-comp-01",
                "name"     => "Highlands Coffee Cầu Giấy",
                "email"    => "highlands@jobmarket.vn",
                "password" => password_hash("Company@123", PASSWORD_DEFAULT),
                "role"     => "company",
                "status"   => "active"
            ],
            [
                "id"       => "user-comp-02",
                "name"     => "Miniso Việt Nam",
                "email"    => "miniso@jobmarket.vn",
                "password" => password_hash("Company@123", PASSWORD_DEFAULT),
                "role"     => "company",
                "status"   => "active"
            ],
            [
                "id"       => "user-student-01",
                "name"     => "Nguyễn Văn Sinh Viên",
                "email"    => "sinhvien1@jobmarket.vn",
                "password" => password_hash("Student@123", PASSWORD_DEFAULT),
                "role"     => "student",
                "status"   => "active"
            ],
            [
                "id"       => "user-student-02",
                "name"     => "Trần Thị Thu Hà",
                "email"    => "sinhvien2@jobmarket.vn",
                "password" => password_hash("Student@123", PASSWORD_DEFAULT),
                "role"     => "student",
                "status"   => "active"
            ],
            [
                "id"       => "user-comp-unverified",
                "name"     => "Cà Phê Mới Mở Tuyển Dụng",
                "email"    => "unverified@jobmarket.vn",
                "password" => password_hash("Company@123", PASSWORD_DEFAULT),
                "role"     => "company",
                "status"   => "active"
            ]
        ];

        $userStmt = $this->db->prepare(
            "INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `status`) 
             VALUES (?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE `password` = VALUES(`password`), `role` = VALUES(`role`), `status` = VALUES(`status`)"
        );

        foreach ($users as $u) {
            $userStmt->execute([$u["id"], $u["name"], $u["email"], $u["password"], $u["role"], $u["status"]]);
        }
        echo "  [OK] Đã nạp 6 tài khoản mẫu (Admin, 3 Công ty, 2 Sinh viên)." . PHP_EOL;

        // 4. Seed Companies
        $companies = [
            [
                "id"                  => "comp-001",
                "user_id"             => "user-comp-01",
                "name"                => "Highlands Coffee Việt Nam",
                "description"         => "Chuỗi cà phê hàng đầu Việt Nam tuyển dụng sinh viên làm thêm linh hoạt theo ca.",
                "contact_person"      => "Anh Tuấn (Quản lý cửa hàng)",
                "contact_phone"       => "0987654321",
                "address"             => "Số 234 Xuân Thủy",
                "city"                => "Hà Nội",
                "district"            => "Cầu Giấy",
                "website"             => "https://highlands.vn",
                "verification_status" => "verified"
            ],
            [
                "id"                  => "comp-002",
                "user_id"             => "user-comp-02",
                "name"                => "Miniso Retail VN",
                "description"         => "Hệ thống bán lẻ phụ kiện tiêu dùng thông minh tuyển nhân viên bán hàng sinh viên.",
                "contact_person"      => "Chị Hương (Phụ trách tuyển dụng)",
                "contact_phone"       => "0912345678",
                "address"             => "Số 56 Nguyễn Trãi",
                "city"                => "Hà Nội",
                "district"            => "Thanh Xuân",
                "website"             => "https://minisovietnam.vn",
                "verification_status" => "verified"
            ],
            [
                "id"                  => "comp-unverified",
                "user_id"             => "user-comp-unverified",
                "name"                => "Cà Phê Sinh Viên Chưa Xác Minh",
                "description"         => "Quán cà phê mới mở chưa hoàn tất hồ sơ pháp lý.",
                "contact_person"      => "Anh Đức",
                "contact_phone"       => "0933334455",
                "address"             => "Số 12 Chùa Láng",
                "city"                => "Hà Nội",
                "district"            => "Đống Đa",
                "website"             => "https://caphesinhvien.vn",
                "verification_status" => "pending"
            ]
        ];

        $compStmt = $this->db->prepare(
            "INSERT INTO `companies` (`id`, `user_id`, `name`, `description`, `contact_person`, `contact_phone`, `address`, `city`, `district`, `website`, `verification_status`)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `verification_status` = VALUES(`verification_status`)"
        );
        foreach ($companies as $c) {
            $compStmt->execute([
                $c["id"], $c["user_id"], $c["name"], $c["description"], $c["contact_person"],
                $c["contact_phone"], $c["address"], $c["city"], $c["district"], $c["website"], $c["verification_status"]
            ]);
        }
        echo "  [OK] Đã nạp " . count($companies) . " hồ sơ công ty đối tác." . PHP_EOL;

        // 5. Seed Student Profiles
        $students = [
            [
                "id"                         => "profile-st-01",
                "user_id"                    => "user-student-01",
                "full_name"                  => "Nguyễn Văn Sinh Viên",
                "phone"                      => "0901112233",
                "date_of_birth"              => "2004-05-15",
                "gender"                     => "male",
                "university"                 => "Đại học Quốc Gia Hà Nội",
                "major"                      => "Công nghệ thông tin",
                "academic_year"              => 2,
                "bio"                        => "Sinh viên năm 2 nhiệt tình, cẩn thận, tìm việc part-time ca tối hoặc cuối tuần.",
                "location_id"                => "loc-001",
                "preferred_location"         => "Cầu Giấy, Nam Từ Liêm",
                "preferred_locations"        => "Cầu Giấy, Bắc Từ Liêm, Nam Từ Liêm",
                "available_schedule"         => json_encode(["shifts" => ["evening", "weekend"]]),
                "skills"                     => "Giao tiếp, Tin học văn phòng",
                "skill_ids"                  => json_encode(["skill-001", "skill-004"]),
                "work_experience"            => "Từng làm gia sư tiếng Anh và phục vụ quán trà sữa 6 tháng.",
                "education"                  => "Sinh viên chính quy khóa K67 - ĐHQGHN",
                "certificates"               => "IELTS 6.5, MOS Excel Specialist",
                "cv_url"                     => "https://example.com/cv-nguyen-van-a.pdf",
                "profile_completion_percent" => 100
            ],
            [
                "id"                         => "profile-st-02",
                "user_id"                    => "user-student-02",
                "full_name"                  => "Trần Thị Thu Hà",
                "phone"                      => "0904445566",
                "date_of_birth"              => "2005-08-20",
                "gender"                     => "female",
                "university"                 => "Đại học Thương Mại",
                "major"                      => "Quản trị kinh doanh",
                "academic_year"              => 1,
                "bio"                        => "Sinh viên năm nhất nhanh nhẹn, hòa đồng, tìm việc phục vụ hoặc thu ngân.",
                "location_id"                => "loc-001",
                "preferred_location"         => "Cầu Giấy",
                "preferred_locations"        => "Cầu Giấy, Đống Đa",
                "available_schedule"         => json_encode(["shifts" => ["morning", "afternoon"]]),
                "skills"                     => "Thu ngân, Tiếng Anh giao tiếp",
                "skill_ids"                  => json_encode(["skill-002", "skill-003"]),
                "work_experience"            => "Chưa có kinh nghiệm thực tế, sẵn sàng học hỏi.",
                "education"                  => "Sinh viên năm 1 ĐH Thương Mại",
                "certificates"               => "TOEIC 650",
                "cv_url"                     => "https://example.com/cv-tran-thi-b.pdf",
                "profile_completion_percent" => 90
            ]
        ];

        $studentStmt = $this->db->prepare(
            "INSERT INTO `student_profiles` (
                `id`, `user_id`, `full_name`, `phone`, `date_of_birth`, `gender`, 
                `university`, `major`, `academic_year`, `bio`, `location_id`, 
                `preferred_location`, `preferred_locations`, `available_schedule`, 
                `skills`, `skill_ids`, `work_experience`, `education`, `certificates`, 
                `cv_url`, `profile_completion_percent`
            ) VALUES (
                ?, ?, ?, ?, ?, ?, 
                ?, ?, ?, ?, ?, 
                ?, ?, ?, 
                ?, ?, ?, ?, ?, 
                ?, ?
            ) ON DUPLICATE KEY UPDATE 
                `full_name` = VALUES(`full_name`),
                `university` = VALUES(`university`), 
                `bio` = VALUES(`bio`),
                `location_id` = VALUES(`location_id`),
                `skill_ids` = VALUES(`skill_ids`),
                `profile_completion_percent` = VALUES(`profile_completion_percent`)"
        );
        foreach ($students as $s) {
            $studentStmt->execute([
                $s["id"], $s["user_id"], $s["full_name"], $s["phone"], $s["date_of_birth"], $s["gender"],
                $s["university"], $s["major"], $s["academic_year"], $s["bio"], $s["location_id"],
                $s["preferred_location"], $s["preferred_locations"], $s["available_schedule"],
                $s["skills"], $s["skill_ids"], $s["work_experience"], $s["education"], $s["certificates"],
                $s["cv_url"], $s["profile_completion_percent"]
            ]);
        }
        echo "  [OK] Đã nạp 2 hồ sơ sinh viên kèm lịch rảnh." . PHP_EOL;

        // 6. Seed Part-time Jobs
        $jobs = [
            [
                "id"           => "job-001",
                "company_id"   => "comp-001",
                "category_id"  => "cat-001",
                "title"        => "Nhân viên phục vụ & Phụ quầy Part-time (Ca sáng/tối)",
                "description"  => "Đón tiếp khách hàng, nhận order nước uống, hỗ trợ pha chế cơ bản và dọn dẹp khu vực quầy.",
                "requirements" => "Nhanh nhẹn, chăm chỉ, đúng giờ. Không yêu cầu kinh nghiệm, được đào tạo bài bản.",
                "type"         => "part-time",
                "salary_type"  => "hourly",
                "salary_min"   => 25000,
                "salary_max"   => 30000,
                "shift_type"   => "morning",
                "work_format"  => "on-site",
                "city"         => "Hà Nội",
                "district"     => "Cầu Giấy",
                "address"      => "234 Xuân Thủy, Cầu Giấy",
                "status"       => "published",
                "deadline"     => date("Y-m-d", strtotime("+30 days"))
            ],
            [
                "id"           => "job-002",
                "company_id"   => "comp-001",
                "category_id"  => "cat-001",
                "title"        => "Nhân viên thu ngân Part-time ca tối (18h - 23h)",
                "description"  => "Thực hiện thanh toán tại quầy thu ngân, in hóa đơn và đối soát số tiền ca làm việc.",
                "requirements" => "Trung thực, cẩn thận, ưu tiên sinh viên có kỹ năng tính toán tốt.",
                "type"         => "part-time",
                "salary_type"  => "hourly",
                "salary_min"   => 28000,
                "salary_max"   => 35000,
                "shift_type"   => "evening",
                "work_format"  => "on-site",
                "city"         => "Hà Nội",
                "district"     => "Cầu Giấy",
                "address"      => "234 Xuân Thủy, Cầu Giấy",
                "status"       => "published",
                "deadline"     => date("Y-m-d", strtotime("+20 days"))
            ],
            [
                "id"           => "job-003",
                "company_id"   => "comp-002",
                "category_id"  => "cat-002",
                "title"        => "Nhân viên bán hàng ca linh hoạt / Cuối tuần",
                "description"  => "Tư vấn sản phẩm tiêu dùng, sắp xếp hàng hóa lên kệ và giữ gìn vệ sinh cửa hàng.",
                "requirements" => "Ngoại hình ưa nhìn, thân thiện, kỹ năng giao tiếp tốt.",
                "type"         => "part-time",
                "salary_type"  => "hourly",
                "salary_min"   => 24000,
                "salary_max"   => 29000,
                "shift_type"   => "weekend",
                "work_format"  => "on-site",
                "city"         => "Hà Nội",
                "district"     => "Thanh Xuân",
                "address"      => "56 Nguyễn Trãi, Thanh Xuân",
                "status"       => "published",
                "deadline"     => date("Y-m-d", strtotime("+25 days"))
            ]
        ];

        $jobStmt = $this->db->prepare(
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
                `location_id` = VALUES(`location_id`),
                `status` = VALUES(`status`), 
                `salary_min` = VALUES(`salary_min`),
                `salary_max` = VALUES(`salary_max`),
                `work_type` = VALUES(`work_type`),
                `work_mode` = VALUES(`work_mode`),
                `application_deadline` = VALUES(`application_deadline`)"
        );

        foreach ($jobs as $j) {
            $deadline = $j["deadline"];
            $locId = ($j["district"] === "Cầu Giấy" ? "loc-001" : "loc-003");
            $jobStmt->execute([
                $j["id"], $j["company_id"], $j["category_id"], $locId, $j["title"], $j["description"],
                $j["requirements"], "Thưởng chuyên cần, hỗ trợ ăn giữa ca, giảm giá 50% đồ uống",
                $j["type"], "part_time", $j["salary_type"],
                $j["salary_min"], $j["salary_max"], "VND", $j["shift_type"], "Ca 4 tiếng - 6 tiếng/ngày",
                "Thứ 2 đến Thứ 6 hoặc Cuối tuần", "Giao tiếp, Pha chế, Chăm chỉ",
                $j["work_format"], "onsite", $j["city"],
                $j["district"], $j["address"], $j["status"], $deadline, $deadline,
                2, date("Y-m-d H:i:s")
            ]);
        }
        echo "  [OK] Đã nạp 3 tin tuyển dụng part-time sinh viên theo ca/giờ." . PHP_EOL;

        echo "--- NẠP DỮ LIỆU SEED HOÀN THÀNH THÀNH CÔNG! ---" . PHP_EOL;
    }
}
