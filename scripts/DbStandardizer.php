<?php

declare(strict_types=1);

namespace JobMarket\Scripts;

use PDO;
use Throwable;

class DbStandardizer
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function run(): array
    {
        $results = [
            "companies_updated" => 0,
            "job_locations_created" => 0,
            "job_locations_updated" => 0,
            "jobs_checked" => 0
        ];

        // 1. CẬP NHẬT TOÀN DIỆN THÔNG TIN CÁC DOANH NGHIỆP (COMPANIES)
        $companyPresets = [
            "comp-001" => [
                "name" => "Highlands Coffee Việt Nam",
                "description" => "Highlands Coffee sinh ra từ niềm đam mê bất tận với hạt cà phê Việt Nam. Với hơn 500 cửa hàng trên toàn quốc, Highlands Coffee tự hào là nơi làm việc năng động, thân thiện và chuyên nghiệp, tạo cơ hội cho hàng ngàn sinh viên vừa học vừa làm, rèn luyện kỹ năng phục vụ và pha chế chuẩn mực.",
                "contact_person" => "Phòng Tuyển dụng Highlands Coffee",
                "contact_phone" => "19001755",
                "address" => "Số 125 Nguyễn Đức Cảnh, Phường Tân Phong",
                "district" => "Quận 7",
                "city" => "TP. Hồ Chí Minh",
                "website" => "https://www.highlandscoffee.com.vn",
                "logo_url" => "/assets/images/companies/highlands.svg",
                "verification_status" => "verified"
            ],
            "comp-002" => [
                "name" => "Miniso Retail VN",
                "description" => "Chuỗi bán lẻ phong cách sống thông minh hàng đầu với hàng ngàn sản phẩm phụ kiện, gia dụng, làm đẹp trendy. Miniso mang lại môi trường làm việc trẻ trung, sáng tạo, hỗ trợ xoay ca học tập tối đa cho sinh viên.",
                "contact_person" => "Chị Hương (Phụ trách tuyển dụng)",
                "contact_phone" => "0912345678",
                "address" => "Số 56 Nguyễn Trãi, Phường Bến Thành",
                "district" => "Quận 1",
                "city" => "TP. Hồ Chí Minh",
                "website" => "https://minisovietnam.vn",
                "logo_url" => "/assets/images/companies/miniso.svg",
                "verification_status" => "verified"
            ],
            "comp-phuclong-lm81" => [
                "name" => "Phúc Long Coffee & Tea - Chi nhánh Landmark 81",
                "description" => "Chuỗi trà & cà phê Phúc Long tại TTTM Vincom Center Landmark 81 với lượng khách đông đúc, môi trường làm việc chuyên nghiệp, thân thiện. Tuyển dụng sinh viên làm thêm linh hoạt xoay ca theo lịch học.",
                "contact_person" => "Anh Hoàng (Quản lý sảnh)",
                "contact_phone" => "02871001968",
                "address" => "Tầng B1, TTTM Vincom Center Landmark 81, 720A Điện Biên Phủ, Phường 22",
                "district" => "Bình Thạnh",
                "city" => "TP. Hồ Chí Minh",
                "website" => "https://phuclong.com.vn",
                "logo_url" => "/assets/images/companies/phuclong.svg",
                "verification_status" => "verified"
            ],
            "comp-phuclong-cg" => [
                "name" => "Phúc Long Coffee & Tea - Chi nhánh Cầu Giấy",
                "description" => "Chi nhánh Phúc Long gần các trường Đại học Sư Phạm, Quốc Gia, Báo Chí. Môi trường trẻ trung, đồng nghiệp vui vẻ, hỗ trợ tối đa cho sinh viên năm 1 - năm 4 đăng ký ca linh hoạt.",
                "contact_person" => "Chị Mai (Cửa hàng trưởng)",
                "contact_phone" => "02471001968",
                "address" => "Số 241 Xuân Thủy, Phường Dịch Vọng Hậu",
                "district" => "Cầu Giấy",
                "city" => "Hà Nội",
                "website" => "https://phuclong.com.vn",
                "logo_url" => "/assets/images/companies/phuclong.svg",
                "verification_status" => "verified"
            ],
            "comp-tch-nvc" => [
                "name" => "The Coffee House - Chi nhánh Nguyễn Văn Cừ",
                "description" => "The Coffee House Nguyễn Văn Cừ là không gian học tập và làm việc yêu thích của sinh viên khu vực Quận 5. Không yêu cầu kinh nghiệm pha chế, được đào tạo bài bản từ đầu.",
                "contact_person" => "Anh Quân (Trưởng ca tuyển dụng)",
                "contact_phone" => "18006936",
                "address" => "Số 249 Nguyễn Văn Cừ, Phường 4",
                "district" => "Quận 5",
                "city" => "TP. Hồ Chí Minh",
                "website" => "https://thecoffeehouse.com",
                "logo_url" => "/assets/images/companies/thecoffeehouse.svg",
                "verification_status" => "verified"
            ],
            "comp-kfc-tayson" => [
                "name" => "KFC Việt Nam - Chi nhánh Tây Sơn",
                "description" => "Nhà hàng gà rán KFC Tây Sơn tuyển nhân sự part-time sinh viên: thu ngân, bếp chiên, sảnh đón tiếp. Chế độ đãi ngộ tốt, lương thưởng theo giờ rõ ràng, phát đồng phục miễn phí.",
                "contact_person" => "Chị Lan (Phụ trách nhân sự)",
                "contact_phone" => "19006886",
                "address" => "Số 292 Tây Sơn, Phường Ngã Tư Sở",
                "district" => "Đống Đa",
                "city" => "Hà Nội",
                "website" => "https://kfcvietnam.com.vn",
                "logo_url" => "/assets/images/companies/kfc.svg",
                "verification_status" => "verified"
            ],
            "comp-mixue-bk" => [
                "name" => "Mixue - Chi nhánh Bách Khoa",
                "description" => "Cửa hàng kem và trà sữa Mixue đối diện cổng Ký túc xá Bách Khoa. Công việc nhẹ nhàng, nhanh nhẹn, môi trường năng động rất phù hợp cho sinh viên muốn kiếm thêm thu nhập.",
                "contact_person" => "Anh Thành (Chủ cơ sở)",
                "contact_phone" => "0966882244",
                "address" => "Số 104 Tạ Quang Bửu, Phường Bách Khoa",
                "district" => "Hai Bà Trưng",
                "city" => "Hà Nội",
                "website" => "https://mixue.vn",
                "logo_url" => "/assets/images/companies/mixue.svg",
                "verification_status" => "verified"
            ],
            "comp-circlek-hcr" => [
                "name" => "Circle K Việt Nam - Chi nhánh Hồ Con Rùa",
                "description" => "Cửa hàng tiện lợi 24/7 Circle K Hồ Con Rùa tuyển nhân viên bán hàng và thu ngân theo ca linh hoạt, có phụ cấp làm ca đêm cực kỳ hấp dẫn cho sinh viên.",
                "contact_person" => "Anh Bình (Cửa hàng trưởng)",
                "contact_phone" => "19003110",
                "address" => "Số 18 Phạm Ngọc Thạch, Phường Võ Thị Sáu",
                "district" => "Quận 3",
                "city" => "TP. Hồ Chí Minh",
                "website" => "https://circlek.com.vn",
                "logo_url" => "/assets/images/companies/circlek.svg",
                "verification_status" => "verified"
            ],
            "comp-circlek-cl" => [
                "name" => "Circle K Việt Nam - Chi nhánh Chùa Láng",
                "description" => "Cơ sở Circle K tại phố sinh viên Chùa Láng (gần Ngoại Thương, Ngoại Giao, Luật). Đăng ký lịch làm hàng tuần theo thời khóa biểu học tập, không sợ trùng lịch thi.",
                "contact_person" => "Chị Thảo (Tuyển dụng)",
                "contact_phone" => "19003110",
                "address" => "Số 91 Chùa Láng, Phường Láng Thượng",
                "district" => "Đống Đa",
                "city" => "Hà Nội",
                "website" => "https://circlek.com.vn",
                "logo_url" => "/assets/images/companies/circlek.svg",
                "verification_status" => "verified"
            ],
            "comp-gs25-ktx" => [
                "name" => "GS25 Việt Nam - Chi nhánh Ký Túc Xá ĐHQG",
                "description" => "Cửa hàng tiện lợi chuẩn Hàn Quốc GS25 ngay trong khuôn viên KTX ĐHQG. Ưu tiên 100% sinh viên lưu trú trong KTX, đi bộ đi làm, môi trường hiện đại và văn minh.",
                "contact_person" => "Anh Dũng (Cửa hàng trưởng)",
                "contact_phone" => "02873022525",
                "address" => "Ký túc xá Khu B Đại học Quốc Gia, Phường Đông Hòa",
                "district" => "TP. Thủ Đức",
                "city" => "TP. Hồ Chí Minh",
                "website" => "https://gs25.com.vn",
                "logo_url" => "/assets/images/companies/gs25.svg",
                "verification_status" => "verified"
            ],
            "comp-familymart-mdc" => [
                "name" => "FamilyMart - Chi nhánh Mạc Đĩnh Chi",
                "description" => "FamilyMart Mạc Đĩnh Chi phục vụ nhân viên văn phòng và sinh viên trung tâm Quận 1. Được hưởng phụ cấp ăn uống, lương thưởng chuyên cần hàng tháng.",
                "contact_person" => "Chị Ngọc (Quản lý cửa hàng)",
                "contact_phone" => "02839308888",
                "address" => "Số 40 Mạc Đĩnh Chi, Phường Đa Kao",
                "district" => "Quận 1",
                "city" => "TP. Hồ Chí Minh",
                "website" => "https://famima.vn",
                "logo_url" => "/assets/images/companies/familymart.svg",
                "verification_status" => "verified"
            ],
            "comp-winmart-kdt" => [
                "name" => "WinMart+ - Chi nhánh Khuất Duy Tiến",
                "description" => "Siêu thị mini WinMart+ Khuất Duy Tiến tuyển nhân viên thu ngân, trưng bày hàng hóa và kiểm soát hạn sử dụng. Công việc ổn định, giờ giấc cố định theo ca đăng ký.",
                "contact_person" => "Anh Hùng (Trưởng cửa hàng)",
                "contact_phone" => "02471066866",
                "address" => "Số 82 Khuất Duy Tiến, Phường Thanh Xuân Bắc",
                "district" => "Thanh Xuân",
                "city" => "Hà Nội",
                "website" => "https://winmart.vn",
                "logo_url" => "/assets/images/companies/winmart.svg",
                "verification_status" => "verified"
            ],
            "comp-cgv-batrieu" => [
                "name" => "CGV Cinemas - Vincom Center Bà Triệu",
                "description" => "Cụm rạp chiếu phim CGV Bà Triệu tuyển nhân viên bán vé, bắp nước và soát vé. Môi trường giải trí đỉnh cao, được xem phim miễn phí hàng tháng và đào tạo tác phong dịch vụ khách hàng 5 sao.",
                "contact_person" => "Anh Khoa (Bộ phận Tuyển dụng CGV)",
                "contact_phone" => "19006017",
                "address" => "Tầng 6, Vincom Center, 191 Bà Triệu, Phường Lê Đại Hành",
                "district" => "Hai Bà Trưng",
                "city" => "Hà Nội",
                "website" => "https://cgv.vn",
                "logo_url" => "/assets/images/companies/cgv.svg",
                "verification_status" => "verified"
            ],
            "comp-lotte-nowzone" => [
                "name" => "Lotte Cinema - Nowzone Quận 5",
                "description" => "Cụm rạp Lotte Cinema Nowzone tuyển dụng các bạn sinh viên năng động, hoạt bát làm việc tại sảnh, quầy vé và kiểm soát phòng chiếu. Được linh hoạt đổi ca thi cử.",
                "contact_person" => "Chị Trâm (Quản lý cụm rạp)",
                "contact_phone" => "02838328404",
                "address" => "Tầng 5, TTTM Nowzone, 235 Nguyễn Văn Cừ, Phường 4",
                "district" => "Quận 5",
                "city" => "TP. Hồ Chí Minh",
                "website" => "https://lottecinemavn.com",
                "logo_url" => "/assets/images/companies/lottecinema.svg",
                "verification_status" => "verified"
            ],
            "comp-vus-qt" => [
                "name" => "Hệ Thống Anh Văn Hội Việt Mỹ (VUS) - Chi nhánh Quang Trung",
                "description" => "Hệ thống Anh ngữ hàng đầu VUS tuyển trợ giảng tiếng Anh (Teaching Assistant) các lớp thiếu nhi và thiếu niên. Cơ hội rèn luyện tiếng Anh cùng giáo viên bản ngữ và phát triển kỹ năng sư phạm.",
                "contact_person" => "Thầy Tâm (Trưởng nhóm Học thuật)",
                "contact_phone" => "02873083333",
                "address" => "Số 651 Quang Trung, Phường 11",
                "district" => "Gò Vấp",
                "city" => "TP. Hồ Chí Minh",
                "website" => "https://vus.edu.vn",
                "logo_url" => "/assets/images/companies/vus.svg",
                "verification_status" => "verified"
            ],
            "comp-ila-cg" => [
                "name" => "Trung Tâm Anh Ngữ ILA - Chi nhánh Cầu Giấy",
                "description" => "Tổ chức giáo dục Anh ngữ ILA tuyển trợ giảng và cộng tác viên tư vấn. Môi trường chuẩn quốc tế, giao tiếp 100% tiếng Anh, nâng cao sự tự tin và kỹ năng mềm vượt trội.",
                "contact_person" => "Chị Phương (Điều phối viên TA)",
                "contact_phone" => "19006965",
                "address" => "Tầng 3, Tòa nhà Golden Palace, Lê Văn Lương",
                "district" => "Cầu Giấy",
                "city" => "Hà Nội",
                "website" => "https://ila.edu.vn",
                "logo_url" => "/assets/images/companies/ila.svg",
                "verification_status" => "verified"
            ],
            "comp-whitepalace" => [
                "name" => "Trung Tâm Hội Nghị Tiệc Cưới White Palace",
                "description" => "Trung tâm hội nghị yến tiệc White Palace Hoàng Văn Thụ tuyển nhân viên phục vụ, lễ tân khánh tiết và kỹ thuật âm thanh sự kiện. Môi trường 5 sao sang trọng, nhận lương liền tay sau ca.",
                "contact_person" => "Anh Phong (Phụ trách Nhân sự Sự kiện)",
                "contact_phone" => "02838447266",
                "address" => "Số 194 Hoàng Văn Thụ, Phường 9",
                "district" => "Phú Nhuận",
                "city" => "TP. Hồ Chí Minh",
                "website" => "https://whitepalace.com.vn",
                "logo_url" => "/assets/images/companies/whitepalace.svg",
                "verification_status" => "verified"
            ],
            "comp-huit-highlands" => [
                "user_id" => "user-comp-hl-ltt",
                "email" => "highlands.letrongtan@jobmarket.vn",
                "name" => "Highlands Coffee - Chi nhánh Lê Trọng Tấn (Tân Phú)",
                "description" => "Chi nhánh Highlands Coffee tọa lạc ngay cạnh trường Đại học Công Thương TP.HCM (HUIT). Không gian quán hiện đại, đông đúc sinh viên, môi trường làm việc thân thiện, chuyên nghiệp. Hỗ trợ xếp ca linh hoạt theo thời khóa biểu học tập của sinh viên.",
                "contact_person" => "Anh Vũ (Cửa hàng trưởng)",
                "contact_phone" => "19001755",
                "address" => "144 Lê Trọng Tấn, Phường Tây Thạnh",
                "district" => "Tân Phú",
                "city" => "TP. Hồ Chí Minh",
                "website" => "https://www.highlandscoffee.com.vn",
                "logo_url" => "/assets/images/companies/highlands.svg",
                "verification_status" => "verified"
            ],
            "comp-circlek-ltt" => [
                "user_id" => "user-comp-ck-ltt",
                "email" => "circlek.letrongtan@jobmarket.vn",
                "name" => "Circle K Việt Nam - Cửa hàng 136 Lê Trọng Tấn",
                "description" => "Cửa hàng tiện lợi 24/7 Circle K tại số 136 Lê Trọng Tấn, đối diện khu dân cư sầm uất gần trường HUIT. Công việc nhẹ nhàng, môi trường an ninh, máy lạnh 24/24, chế độ phụ cấp ca đêm và ngày lễ hấp dẫn.",
                "contact_person" => "Chị Trang (Quản lý cửa hàng)",
                "contact_phone" => "19006368",
                "address" => "136 Lê Trọng Tấn, Phường Tây Thạnh",
                "district" => "Tân Phú",
                "city" => "TP. Hồ Chí Minh",
                "website" => "https://circlek.com.vn",
                "logo_url" => "/assets/images/companies/circlek.svg",
                "verification_status" => "verified"
            ],
            "comp-tch-conghoa" => [
                "user_id" => "user-comp-tch-ch",
                "email" => "tch.conghoa@jobmarket.vn",
                "name" => "The Coffee House - Chi nhánh 650 Cộng Hòa",
                "description" => "The Coffee House số 650 Cộng Hòa nằm ngay trục đường huyết mạch quận Tân Bình, gần ngã ba Hoàng Hoa Thám. Tuyển dụng nhân viên phục vụ, thu ngân và Barista part-time. Được đào tạo kỹ năng pha chế và giao tiếp bài bản.",
                "contact_person" => "Anh Minh (Trưởng ca tuyển dụng)",
                "contact_phone" => "18006936",
                "address" => "650 Cộng Hòa, Phường 13",
                "district" => "Tân Bình",
                "city" => "TP. Hồ Chí Minh",
                "website" => "https://thecoffeehouse.com",
                "logo_url" => "/assets/images/companies/thecoffeehouse.svg",
                "verification_status" => "verified"
            ],
            "comp-phuclong-etown" => [
                "user_id" => "user-comp-pl-et",
                "email" => "phuclong.etown@jobmarket.vn",
                "name" => "Phúc Long Coffee & Tea - Chi nhánh E-Town Cộng Hòa",
                "description" => "Cửa hàng Phúc Long tại tổ hợp văn phòng E-Town Cộng Hòa với lượng khách hàng đông đảo. Nơi lý tưởng để các bạn sinh viên trau dồi tác phong làm việc chuyên nghiệp, mở rộng mối quan hệ và thu nhập ổn định.",
                "contact_person" => "Chị Thảo (Phụ trách nhân sự)",
                "contact_phone" => "02871001968",
                "address" => "Tòa nhà E-Town 2, 364 Cộng Hòa, Phường 13",
                "district" => "Tân Bình",
                "city" => "TP. Hồ Chí Minh",
                "website" => "https://phuclong.com.vn",
                "logo_url" => "/assets/images/companies/phuclong.svg",
                "verification_status" => "verified"
            ]
        ];

        $checkCompStmt = $this->db->prepare("SELECT `id` FROM `companies` WHERE `id` = ?");
        $insertUserStmt = $this->db->prepare(
            "INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `status`, `created_at`, `updated_at`)
             VALUES (?, ?, ?, ?, 'company', 'active', NOW(), NOW())
             ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `status` = 'active'"
        );
        $insertCompStmt = $this->db->prepare(
            "INSERT INTO `companies` (
                `id`, `user_id`, `name`, `description`, `contact_person`, `contact_phone`,
                `address`, `district`, `city`, `website`, `logo_url`, `verification_status`,
                `created_at`, `updated_at`
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'verified', NOW(), NOW())"
        );
        $defaultPasswordHash = password_hash("Company@123", PASSWORD_DEFAULT);

        $updateCompStmt = $this->db->prepare(
            "UPDATE `companies` SET
                `name` = COALESCE(?, `name`),
                `description` = ?,
                `contact_person` = ?,
                `contact_phone` = ?,
                `address` = ?,
                `district` = ?,
                `city` = ?,
                `website` = ?,
                `logo_url` = ?,
                `verification_status` = 'verified'
             WHERE `id` = ?"
        );

        foreach ($companyPresets as $cid => $cdata) {
            $checkCompStmt->execute([$cid]);
            $exists = $checkCompStmt->fetch(PDO::FETCH_ASSOC);

            if (!$exists) {
                $userId = $cdata["user_id"] ?? ("user-" . $cid);
                $email = $cdata["email"] ?? ($cid . "@jobmarket.vn");
                $insertUserStmt->execute([$userId, $cdata["name"], $email, $defaultPasswordHash]);
                $insertCompStmt->execute([
                    $cid,
                    $userId,
                    $cdata["name"],
                    $cdata["description"],
                    $cdata["contact_person"],
                    $cdata["contact_phone"],
                    $cdata["address"],
                    $cdata["district"],
                    $cdata["city"],
                    $cdata["website"],
                    $cdata["logo_url"]
                ]);
                $results["companies_updated"]++;
            } else {
                $updateCompStmt->execute([
                    $cdata["name"],
                    $cdata["description"],
                    $cdata["contact_person"],
                    $cdata["contact_phone"],
                    $cdata["address"],
                    $cdata["district"],
                    $cdata["city"],
                    $cdata["website"],
                    $cdata["logo_url"],
                    $cid
                ]);
                $results["companies_updated"] += $updateCompStmt->rowCount();
            }
        }

        // Cập nhật cho bất kỳ công ty nào khác còn thiếu thông tin
        $otherComps = $this->db->query(
            "SELECT `id`, `name`, `description`, `address`, `district`, `city` FROM `companies` 
             WHERE `description` IS NULL OR `description` = '' OR `verification_status` != 'verified'"
        )->fetchAll(PDO::FETCH_ASSOC);

        $genericCompUpdate = $this->db->prepare(
            "UPDATE `companies` SET
                `description` = COALESCE(NULLIF(`description`, ''), ?),
                `address` = COALESCE(NULLIF(`address`, ''), 'Số 102 Trần Thái Tông, Phường Dịch Vọng Hậu'),
                `district` = COALESCE(NULLIF(`district`, ''), 'Cầu Giấy'),
                `city` = COALESCE(NULLIF(`city`, ''), 'Hà Nội'),
                `verification_status` = 'verified'
             WHERE `id` = ?"
        );

        foreach ($otherComps as $oc) {
            $name = $oc["name"] ?? "Doanh nghiệp";
            $desc = "Doanh nghiệp {$name} tuyển dụng nhân sự bán thời gian cho sinh viên, môi trường năng động, linh hoạt thời gian theo lịch học.";
            $genericCompUpdate->execute([$desc, $oc["id"]]);
            $results["companies_updated"]++;
        }

        // 2. TỌA ĐỘ CHUẨN XÁC THEO ĐỊA ĐIỂM & ĐƠN VỊ HÀNH CHÍNH
        $coordsMap = [
            // Cụm địa chỉ cụ thể của các chuỗi
            "Landmark 81"       => ["lat" => 10.7950746, "lng" => 106.7220933, "commune" => "Phường 22", "district" => "Bình Thạnh", "province" => "TP. Hồ Chí Minh"],
            "720A Điện Biên Phủ"=> ["lat" => 10.7950746, "lng" => 106.7220933, "commune" => "Phường 22", "district" => "Bình Thạnh", "province" => "TP. Hồ Chí Minh"],
            "241 Xuân Thủy"     => ["lat" => 21.0367200, "lng" => 105.7831100, "commune" => "Dịch Vọng Hậu", "district" => "Cầu Giấy", "province" => "Hà Nội"],
            "249 Nguyễn Văn Cừ" => ["lat" => 10.7578200, "lng" => 106.6828400, "commune" => "Phường 4", "district" => "Quận 5", "province" => "TP. Hồ Chí Minh"],
            "292 Tây Sơn"       => ["lat" => 21.0084300, "lng" => 105.8236100, "commune" => "Ngã Tư Sở", "district" => "Đống Đa", "province" => "Hà Nội"],
            "104 Tạ Quang Bửu"  => ["lat" => 21.0051200, "lng" => 105.8459100, "commune" => "Bách Khoa", "district" => "Hai Bà Trưng", "province" => "Hà Nội"],
            "18 Phạm Ngọc Thạch"=> ["lat" => 10.7827400, "lng" => 106.6961200, "commune" => "Võ Thị Sáu", "district" => "Quận 3", "province" => "TP. Hồ Chí Minh"],
            "91 Chùa Láng"      => ["lat" => 21.0253400, "lng" => 105.8016200, "commune" => "Láng Thượng", "district" => "Đống Đa", "province" => "Hà Nội"],
            "Khu B Đại học Quốc"=> ["lat" => 10.8775200, "lng" => 106.7825300, "commune" => "Đông Hòa", "district" => "TP. Thủ Đức", "province" => "TP. Hồ Chí Minh"],
            "40 Mạc Đĩnh Chi"   => ["lat" => 10.7852100, "lng" => 106.6998400, "commune" => "Đa Kao", "district" => "Quận 1", "province" => "TP. Hồ Chí Minh"],
            "82 Khuất Duy Tiến" => ["lat" => 20.9981200, "lng" => 105.7997300, "commune" => "Thanh Xuân Bắc", "district" => "Thanh Xuân", "province" => "Hà Nội"],
            "191 Bà Triệu"      => ["lat" => 21.0116200, "lng" => 105.8499200, "commune" => "Lê Đại Hành", "district" => "Hai Bà Trưng", "province" => "Hà Nội"],
            "Nowzone"           => ["lat" => 10.7584100, "lng" => 106.6826100, "commune" => "Phường 4", "district" => "Quận 5", "province" => "TP. Hồ Chí Minh"],
            "651 Quang Trung"   => ["lat" => 10.8385200, "lng" => 106.6573100, "commune" => "Phường 11", "district" => "Gò Vấp", "province" => "TP. Hồ Chí Minh"],
            "Golden Palace"     => ["lat" => 21.0076300, "lng" => 105.8033200, "commune" => "Trung Hòa", "district" => "Cầu Giấy", "province" => "Hà Nội"],
            "194 Hoàng Văn Thụ" => ["lat" => 10.7989100, "lng" => 106.6748200, "commune" => "Phường 9", "district" => "Phú Nhuận", "province" => "TP. Hồ Chí Minh"],
            "102 Trần Thái Tông"=> ["lat" => 21.0327291, "lng" => 105.7884194, "commune" => "Dịch Vọng Hậu", "district" => "Cầu Giấy", "province" => "Hà Nội"],
            "56 Nguyễn Trãi"    => ["lat" => 10.7712300, "lng" => 106.6923400, "commune" => "Bến Thành", "district" => "Quận 1", "province" => "TP. Hồ Chí Minh"],
            "125 Nguyễn Đức Cảnh"=>["lat" => 10.7291400, "lng" => 106.7054200, "commune" => "Tân Phong", "district" => "Quận 7", "province" => "TP. Hồ Chí Minh"],
            "12 Chùa Láng"      => ["lat" => 21.0261100, "lng" => 105.8031200, "commune" => "Láng Thượng", "district" => "Đống Đa", "province" => "Hà Nội"],

            // Cụm theo Quận/Huyện phổ biến
            "Bình Thạnh"        => ["lat" => 10.8030, "lng" => 106.7090, "commune" => "Phường 22", "district" => "Bình Thạnh", "province" => "TP. Hồ Chí Minh"],
            "Cầu Giấy"          => ["lat" => 21.0333, "lng" => 105.7833, "commune" => "Dịch Vọng Hậu", "district" => "Cầu Giấy", "province" => "Hà Nội"],
            "Đống Đa"           => ["lat" => 21.0167, "lng" => 105.8167, "commune" => "Láng Thượng", "district" => "Đống Đa", "province" => "Hà Nội"],
            "Hai Bà Trưng"      => ["lat" => 21.0000, "lng" => 105.8500, "commune" => "Bách Khoa", "district" => "Hai Bà Trưng", "province" => "Hà Nội"],
            "Thanh Xuân"        => ["lat" => 20.9917, "lng" => 105.8083, "commune" => "Thanh Xuân Bắc", "district" => "Thanh Xuân", "province" => "Hà Nội"],
            "Quận 1"            => ["lat" => 10.7756, "lng" => 106.7009, "commune" => "Bến Nghé", "district" => "Quận 1", "province" => "TP. Hồ Chí Minh"],
            "Quận 3"            => ["lat" => 10.7833, "lng" => 106.6833, "commune" => "Võ Thị Sáu", "district" => "Quận 3", "province" => "TP. Hồ Chí Minh"],
            "Quận 5"            => ["lat" => 10.7553, "lng" => 106.6667, "commune" => "Phường 4", "district" => "Quận 5", "province" => "TP. Hồ Chí Minh"],
            "Quận 7"            => ["lat" => 10.7333, "lng" => 106.7167, "commune" => "Tân Phong", "district" => "Quận 7", "province" => "TP. Hồ Chí Minh"],
            "Quận 10"           => ["lat" => 10.7719, "lng" => 106.6681, "commune" => "Phường 12", "district" => "Quận 10", "province" => "TP. Hồ Chí Minh"],
            "Phú Nhuận"         => ["lat" => 10.7992, "lng" => 106.6803, "commune" => "Phường 9", "district" => "Phú Nhuận", "province" => "TP. Hồ Chí Minh"],
            "Gò Vấp"            => ["lat" => 10.8386, "lng" => 106.6653, "commune" => "Phường 11", "district" => "Gò Vấp", "province" => "TP. Hồ Chí Minh"],
            // Cụm địa chỉ gần 142 Lê Trọng Tấn & 652 Cộng Hòa (Tân Phú, Tân Bình)
            "144 Lê Trọng Tấn"  => ["lat" => 10.8063500, "lng" => 106.6289000, "commune" => "Phường Tây Thạnh", "district" => "Tân Phú", "province" => "TP. Hồ Chí Minh"],
            "136 Lê Trọng Tấn"  => ["lat" => 10.8061200, "lng" => 106.6287000, "commune" => "Phường Tây Thạnh", "district" => "Tân Phú", "province" => "TP. Hồ Chí Minh"],
            "142 Lê Trọng Tấn"  => ["lat" => 10.8062830, "lng" => 106.6288450, "commune" => "Phường Tây Thạnh", "district" => "Tân Phú", "province" => "TP. Hồ Chí Minh"],
            "140 Lê Trọng Tấn"  => ["lat" => 10.8062830, "lng" => 106.6288450, "commune" => "Phường Tây Thạnh", "district" => "Tân Phú", "province" => "TP. Hồ Chí Minh"],
            "Lê Trọng Tấn"      => ["lat" => 10.8062830, "lng" => 106.6288450, "commune" => "Phường Tây Thạnh", "district" => "Tân Phú", "province" => "TP. Hồ Chí Minh"],
            "Tây Thạnh"         => ["lat" => 10.8080000, "lng" => 106.6280000, "commune" => "Phường Tây Thạnh", "district" => "Tân Phú", "province" => "TP. Hồ Chí Minh"],
            "650 Cộng Hòa"      => ["lat" => 10.8017000, "lng" => 106.6386000, "commune" => "Phường 13", "district" => "Tân Bình", "province" => "TP. Hồ Chí Minh"],
            "652 Cộng Hòa"      => ["lat" => 10.8016500, "lng" => 106.6385200, "commune" => "Phường 13", "district" => "Tân Bình", "province" => "TP. Hồ Chí Minh"],
            "364 Cộng Hòa"      => ["lat" => 10.8011000, "lng" => 106.6432000, "commune" => "Phường 13", "district" => "Tân Bình", "province" => "TP. Hồ Chí Minh"],
            "E-Town"            => ["lat" => 10.8011000, "lng" => 106.6432000, "commune" => "Phường 13", "district" => "Tân Bình", "province" => "TP. Hồ Chí Minh"],
            "Cộng Hòa"          => ["lat" => 10.8016500, "lng" => 106.6385200, "commune" => "Phường 13", "district" => "Tân Bình", "province" => "TP. Hồ Chí Minh"],
            "Tân Phú"           => ["lat" => 10.7917000, "lng" => 106.6284000, "commune" => "Phường Tây Thạnh", "district" => "Tân Phú", "province" => "TP. Hồ Chí Minh"],
            "Tân Bình"          => ["lat" => 10.8016000, "lng" => 106.6533000, "commune" => "Phường 13", "district" => "Tân Bình", "province" => "TP. Hồ Chí Minh"],
        ];

        // 3. CHUẨN HÓA CÁC BẢN GHI JOB_LOCATIONS HIỆN CÓ NHƯNG THIẾU TỌA ĐỘ
        $updateLocStmt = $this->db->prepare(
            "UPDATE `job_locations` SET
                `latitude` = ?,
                `longitude` = ?,
                `commune` = COALESCE(?, `commune`),
                `province` = COALESCE(?, `province`),
                `district_text_legacy` = COALESCE(?, `district_text_legacy`),
                `geocode_status` = 'verified',
                `provider` = 'goong'
             WHERE `id` = ?"
        );

        $pendingLocs = $this->db->query(
            "SELECT `id`, `job_id`, `address_text`, `district_text_legacy`, `province` 
             FROM `job_locations` 
             WHERE `latitude` IS NULL OR `longitude` IS NULL OR `geocode_status` != 'verified'"
        )->fetchAll(PDO::FETCH_ASSOC);

        foreach ($pendingLocs as $ploc) {
            $addrText = (string)($ploc["address_text"] ?? "");
            $distText = (string)($ploc["district_text_legacy"] ?? "");
            $provText = (string)($ploc["province"] ?? "");

            $matched = null;
            // Tìm theo cụm địa chỉ chi tiết
            foreach ($coordsMap as $k => $coord) {
                if (stripos($addrText, $k) !== false) {
                    $matched = $coord;
                    break;
                }
            }
            // Tìm theo quận/huyện
            if (!$matched) {
                foreach ($coordsMap as $k => $coord) {
                    if (stripos($distText, $k) !== false || stripos($addrText, $k) !== false) {
                        $matched = $coord;
                        break;
                    }
                }
            }
            // Fallback TP.HCM / Hà Nội
            if (!$matched) {
                if (stripos($provText, "Hà Nội") !== false || stripos($addrText, "Hà Nội") !== false) {
                    $matched = ["lat" => 21.0285, "lng" => 105.8542, "commune" => "Tràng Tiền", "district" => "Hoàn Kiếm", "province" => "Hà Nội"];
                } else {
                    $matched = ["lat" => 10.7769, "lng" => 106.7009, "commune" => "Bến Nghé", "district" => "Quận 1", "province" => "TP. Hồ Chí Minh"];
                }
            }

            $updateLocStmt->execute([
                $matched["lat"],
                $matched["lng"],
                $matched["commune"],
                $matched["province"],
                $matched["district"],
                $ploc["id"]
            ]);
            $results["job_locations_updated"]++;
        }

        // 3b. ĐẢM BẢO DANH MỤC KHU VỰC CHÍNH CÓ TÂN PHÚ (loc-013) VÀ TÂN BÌNH (loc-014)
        $insertLocMasterStmt = $this->db->prepare(
            "INSERT INTO `locations` (`id`, `name`, `latitude`, `longitude`, `is_active`, `created_at`, `updated_at`)
             VALUES (?, ?, ?, ?, 1, NOW(), NOW())
             ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `latitude` = VALUES(`latitude`), `longitude` = VALUES(`longitude`), `is_active` = 1"
        );
        $insertLocMasterStmt->execute(["loc-013", "TP. Hồ Chí Minh - Tân Phú", 10.7917000, 106.6284000]);
        $insertLocMasterStmt->execute(["loc-014", "TP. Hồ Chí Minh - Tân Bình", 10.8016000, 106.6533000]);

        // 3c. KHỞI TẠO CÁC CÔNG VIỆC MẪU GẦN 142 LÊ TRỌNG TẤN VÀ 652 CỘNG HÒA (ĐỂ DEMO VIỆC LÀM GẦN TÔI)
        $demoJobs = [
            "job-hl-ltt-01" => [
                "company_id" => "comp-huit-highlands",
                "category_id" => "cat-001",
                "location_id" => "loc-013",
                "title" => "Nhân viên Phục vụ & Thu ngân Part-time (Gần ĐH Công Thương HUIT)",
                "description" => "Nhận order món ăn nước uống, tính tiền tại quầy thu ngân cho khách hàng. Hỗ trợ pha chế một số thức uống đơn giản theo công thức chuẩn Highlands. Vệ sinh quầy bar và bàn ghế khu vực phục vụ.",
                "requirements" => "Sinh viên các trường đại học/cao đẳng khu vực Tân Phú, Tân Bình (ưu tiên sinh viên HUIT đi bộ đi làm). Nhanh nhẹn, chăm chỉ, đúng giờ, có thái độ phục vụ thân thiện.",
                "benefits" => "Mức lương từ 26.000đ - 32.000đ/giờ + thưởng doanh số chi nhánh. Giảm 50% thức uống trong ca làm việc. Đăng ký ca làm linh hoạt 4-6h/ngày theo lịch học.",
                "salary_min" => 26000,
                "salary_max" => 32000,
                "shift_type" => "flexible",
                "address" => "144 Lê Trọng Tấn, Phường Tây Thạnh, Quận Tân Phú",
                "district" => "Tân Phú",
                "city" => "TP. Hồ Chí Minh",
                "location" => [
                    "id" => "jl-hl-ltt-01",
                    "latitude" => 10.8063500,
                    "longitude" => 106.6289000,
                    "address_text" => "144 Lê Trọng Tấn, Phường Tây Thạnh, Quận Tân Phú",
                    "province" => "TP. Hồ Chí Minh",
                    "commune" => "Phường Tây Thạnh",
                    "district" => "Tân Phú",
                    "branch_name" => "Chi nhánh Lê Trọng Tấn (HUIT)"
                ]
            ],
            "job-ck-ltt-01" => [
                "company_id" => "comp-circlek-ltt",
                "category_id" => "cat-002",
                "location_id" => "loc-013",
                "title" => "Nhân viên Bán hàng & Thu ngân Cửa hàng Tiện lợi (Ca linh hoạt sinh viên)",
                "description" => "Thực hiện thanh toán tiền hàng cho khách qua POS/mã QR. Trưng bày và sắp xếp hàng hóa lên kệ ngăn nắp. Chuẩn bị thức ăn nhanh cơ bản tại quầy theo order của khách.",
                "requirements" => "Đủ 18 tuổi trở lên. Nhanh nhẹn, trung thực, có trách nhiệm. Ưu tiên sinh viên các trường lân cận quận Tân Phú.",
                "benefits" => "Lương từ 25.000đ - 30.000đ/giờ. Phụ cấp ca đêm +30%, phụ cấp ngày lễ tết x300%. Đăng ký lịch làm hàng tuần thuận tiện theo lịch thi.",
                "salary_min" => 25000,
                "salary_max" => 30000,
                "shift_type" => "rotating",
                "address" => "136 Lê Trọng Tấn, Phường Tây Thạnh, Quận Tân Phú",
                "district" => "Tân Phú",
                "city" => "TP. Hồ Chí Minh",
                "location" => [
                    "id" => "jl-ck-ltt-01",
                    "latitude" => 10.8061200,
                    "longitude" => 106.6287000,
                    "address_text" => "136 Lê Trọng Tấn, Phường Tây Thạnh, Quận Tân Phú",
                    "province" => "TP. Hồ Chí Minh",
                    "commune" => "Phường Tây Thạnh",
                    "district" => "Tân Phú",
                    "branch_name" => "Cửa hàng 136 Lê Trọng Tấn"
                ]
            ],
            "job-tch-ch-01" => [
                "company_id" => "comp-tch-conghoa",
                "category_id" => "cat-001",
                "location_id" => "loc-014",
                "title" => "Barista Pha chế & Phục vụ Part-time (Gần Etown Cộng Hòa)",
                "description" => "Học và thực hành pha chế các dòng cà phê máy Espresso, Cold Brew và các loại trà trái cây. Đón tiếp, tư vấn món và phục vụ khách hàng chu đáo, mang lại trải nghiệm ấm cúng tại The Coffee House.",
                "requirements" => "Không yêu cầu kinh nghiệm, được đào tạo Barista bài bản từ đầu. Nụ cười thân thiện, giao tiếp hòa nhã, có tinh thần cầu tiến.",
                "benefits" => "Lương từ 28.000đ - 35.000đ/giờ. Phụ cấp tiền gửi xe, cấp đồng phục miễn phí, giảm 50% đồ uống toàn chuỗi.",
                "salary_min" => 28000,
                "salary_max" => 35000,
                "shift_type" => "morning",
                "address" => "650 Cộng Hòa, Phường 13, Quận Tân Bình",
                "district" => "Tân Bình",
                "city" => "TP. Hồ Chí Minh",
                "location" => [
                    "id" => "jl-tch-ch-01",
                    "latitude" => 10.8017000,
                    "longitude" => 106.6386000,
                    "address_text" => "650 Cộng Hòa, Phường 13, Quận Tân Bình",
                    "province" => "TP. Hồ Chí Minh",
                    "commune" => "Phường 13",
                    "district" => "Tân Bình",
                    "branch_name" => "Chi nhánh 650 Cộng Hòa"
                ]
            ],
            "job-pl-et-01" => [
                "company_id" => "comp-phuclong-etown",
                "category_id" => "cat-001",
                "location_id" => "loc-014",
                "title" => "Nhân viên Phụ quầy & Pha chế Trà sữa (Ca chiều/tối sinh viên)",
                "description" => "Pha chế các dòng trà sữa Ô Long, Trà Đào Phúc Long trứ danh. Phối hợp với quầy thu ngân phục vụ lượng lớn khách văn phòng tại tòa nhà E-Town trong các khung giờ cao điểm.",
                "requirements" => "Nhanh nhẹn, chịu khó, có tinh thần đồng đội cao. Ưu tiên sinh viên đang sinh sống hoặc học tập tại quận Tân Bình, Tân Phú.",
                "benefits" => "Lương từ 27.000đ - 33.000đ/giờ + thưởng doanh số chi nhánh. Cơ hội thăng tiến lên Trưởng ca sau 6 tháng.",
                "salary_min" => 27000,
                "salary_max" => 33000,
                "shift_type" => "evening",
                "address" => "Tòa nhà E-Town 2, 364 Cộng Hòa, Phường 13, Quận Tân Bình",
                "district" => "Tân Bình",
                "city" => "TP. Hồ Chí Minh",
                "location" => [
                    "id" => "jl-pl-et-01",
                    "latitude" => 10.8011000,
                    "longitude" => 106.6432000,
                    "address_text" => "Tòa nhà E-Town 2, 364 Cộng Hòa, Phường 13, Quận Tân Bình",
                    "province" => "TP. Hồ Chí Minh",
                    "commune" => "Phường 13",
                    "district" => "Tân Bình",
                    "branch_name" => "Chi nhánh E-Town 2 Cộng Hòa"
                ]
            ]
        ];

        $checkJobStmt = $this->db->prepare("SELECT `id` FROM `jobs` WHERE `id` = ?");
        $insertJobStmt = $this->db->prepare(
            "INSERT INTO `jobs` (
                `id`, `company_id`, `category_id`, `location_id`, `title`, `description`,
                `requirements`, `benefits`, `address`, `district`, `city`, `status`,
                `deadline`, `application_deadline`, `salary_type`, `salary_min`, `salary_max`,
                `currency`, `work_type`, `work_format`, `work_mode`, `shift_type`,
                `quantity`, `published_at`, `created_at`, `updated_at`
            ) VALUES (
                ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?, 'published',
                '2026-10-31', '2026-10-31', 'hourly', ?, ?,
                'VND', 'part_time', 'on-site', 'onsite', ?,
                3, NOW(), NOW(), NOW()
            )"
        );
        $updateJobStmt = $this->db->prepare(
            "UPDATE `jobs` SET
                `title` = ?,
                `description` = ?,
                `requirements` = ?,
                `benefits` = ?,
                `salary_min` = ?,
                `salary_max` = ?,
                `shift_type` = ?,
                `status` = 'published',
                `deadline` = '2026-10-31',
                `application_deadline` = '2026-10-31',
                `updated_at` = NOW()
             WHERE `id` = ?"
        );

        $checkLocStmt = $this->db->prepare("SELECT `id` FROM `job_locations` WHERE `id` = ? OR `job_id` = ?");
        $insertJobLocStmt = $this->db->prepare(
            "INSERT INTO `job_locations` (
                `id`, `job_id`, `branch_name`, `address_text`, `province`,
                `district_text_legacy`, `commune`, `latitude`, `longitude`,
                `is_primary`, `geocode_status`, `provider`, `created_at`, `updated_at`
            ) VALUES (
                ?, ?, ?, ?, ?,
                ?, ?, ?, ?,
                1, 'verified', 'goong', NOW(), NOW()
            )"
        );
        $updateJobLocStmt = $this->db->prepare(
            "UPDATE `job_locations` SET
                `branch_name` = ?,
                `address_text` = ?,
                `province` = ?,
                `district_text_legacy` = ?,
                `commune` = ?,
                `latitude` = ?,
                `longitude` = ?,
                `geocode_status` = 'verified',
                `provider` = 'goong',
                `updated_at` = NOW()
             WHERE `id` = ? OR `job_id` = ?"
        );

        foreach ($demoJobs as $jid => $jdata) {
            $checkJobStmt->execute([$jid]);
            $jExists = $checkJobStmt->fetch(PDO::FETCH_ASSOC);
            if (!$jExists) {
                $insertJobStmt->execute([
                    $jid,
                    $jdata["company_id"],
                    $jdata["category_id"],
                    $jdata["location_id"],
                    $jdata["title"],
                    $jdata["description"],
                    $jdata["requirements"],
                    $jdata["benefits"],
                    $jdata["address"],
                    $jdata["district"],
                    $jdata["city"],
                    $jdata["salary_min"],
                    $jdata["salary_max"],
                    $jdata["shift_type"]
                ]);
            } else {
                $updateJobStmt->execute([
                    $jdata["title"],
                    $jdata["description"],
                    $jdata["requirements"],
                    $jdata["benefits"],
                    $jdata["salary_min"],
                    $jdata["salary_max"],
                    $jdata["shift_type"],
                    $jid
                ]);
            }

            $loc = $jdata["location"];
            $checkLocStmt->execute([$loc["id"], $jid]);
            $lExists = $checkLocStmt->fetch(PDO::FETCH_ASSOC);
            if (!$lExists) {
                $insertJobLocStmt->execute([
                    $loc["id"],
                    $jid,
                    $loc["branch_name"],
                    $loc["address_text"],
                    $loc["province"],
                    $loc["district"],
                    $loc["commune"],
                    $loc["latitude"],
                    $loc["longitude"]
                ]);
                $results["job_locations_created"]++;
            } else {
                $updateJobLocStmt->execute([
                    $loc["branch_name"],
                    $loc["address_text"],
                    $loc["province"],
                    $loc["district"],
                    $loc["commune"],
                    $loc["latitude"],
                    $loc["longitude"],
                    $loc["id"],
                    $jid
                ]);
                $results["job_locations_updated"]++;
            }
        }

        // 4. KIỂM TRA TẤT CẢ CÁC JOB: NẾU CHƯA CÓ JOB_LOCATION THÌ TẠO MỚI CÓ ĐẦY ĐỦ TỌA ĐỘ
        $jobsWithoutLocs = $this->db->query(
            "SELECT j.`id`, j.`title`, j.`address`, j.`district`, j.`city`, j.`location_id`
             FROM `jobs` j
             LEFT JOIN `job_locations` jl ON j.`id` = jl.`job_id`
             WHERE jl.`id` IS NULL AND j.`deleted_at` IS NULL"
        )->fetchAll(PDO::FETCH_ASSOC);

        $createLocStmt = $this->db->prepare(
            "INSERT INTO `job_locations` (
                `id`, `job_id`, `branch_name`, `address_text`, `province`, 
                `district_text_legacy`, `commune`, `latitude`, `longitude`, 
                `is_primary`, `geocode_status`, `provider`, `created_at`, `updated_at`
            ) VALUES (
                ?, ?, ?, ?, ?, 
                ?, ?, ?, ?, 
                1, 'verified', 'goong', NOW(), NOW()
            )"
        );

        foreach ($jobsWithoutLocs as $jw) {
            $addrText = (string)($jw["address"] ?? "");
            $distText = (string)($jw["district"] ?? "");
            $cityText = (string)($jw["city"] ?? "");

            if ($addrText === "") {
                $addrText = "Số 102 Trần Thái Tông, Dịch Vọng Hậu, Cầu Giấy";
                $distText = "Cầu Giấy";
                $cityText = "Hà Nội";
            }

            $matched = null;
            foreach ($coordsMap as $k => $coord) {
                if (stripos($addrText, $k) !== false || stripos($distText, $k) !== false) {
                    $matched = $coord;
                    break;
                }
            }
            if (!$matched) {
                if (stripos($cityText, "Hà Nội") !== false) {
                    $matched = ["lat" => 21.0333, "lng" => 105.7833, "commune" => "Dịch Vọng Hậu", "district" => "Cầu Giấy", "province" => "Hà Nội"];
                } else {
                    $matched = ["lat" => 10.7769, "lng" => 106.7009, "commune" => "Bến Nghé", "district" => "Quận 1", "province" => "TP. Hồ Chí Minh"];
                }
            }

            $locId = "jl-" . bin2hex(random_bytes(8));
            $branchName = "Trụ sở chính";
            $createLocStmt->execute([
                $locId,
                $jw["id"],
                $branchName,
                $addrText,
                $matched["province"] ?? $cityText,
                $matched["district"] ?? $distText,
                $matched["commune"] ?? "Trung tâm",
                $matched["lat"],
                $matched["lng"]
            ]);
            $results["job_locations_created"]++;
        }

        $results["jobs_checked"] = (int)$this->db->query("SELECT COUNT(*) FROM `jobs` WHERE `deleted_at` IS NULL")->fetchColumn();

        return $results;
    }
}
