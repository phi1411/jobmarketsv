# BÁO CÁO TOÀN DIỆN HIỆN TRẠNG & ĐẶC TẢ TÍNH NĂNG HỆ THỐNG
## DỰ ÁN: JOBMARKETSV — NỀN TẢNG TUYỂN DỤNG & TÌM VIỆC PART-TIME CHO SINH VIÊN

---

## MỤC LỤC
1. [TỔNG QUAN DỰ ÁN](#1-tổng-quan-dự-án)
2. [KIẾN TRÚC HỆ THỐNG & CÔNG NGHỆ SỬ DỤNG](#2-kiến-trúc-hệ-thống--công-nghệ-sử-dụng)
3. [MA TRẬN PHÂN QUYỀN & VAI TRÒ NGƯỜI DÙNG](#3-ma-trận-phân-quyền--vai-trò-người-dùng)
4. [DANH MỤC CHI TIẾT CÁC TÍNH NĂNG ĐÃ HOÀN THÀNH (12 MODULES)](#4-danh-mục-chi-tiết-các-tính-năng-đã-hoàn-thành-12-modules)
5. [CẤU TRÚC CƠ SỞ DỮ LIỆU & QUAN HỆ THỰC THỂ (SCHEMA)](#5-cấu-trúc-cơ-sở-dữ-liệu--quan-hệ-thực-thể-schema)
6. [CÁC RÀNG BUỘC NGHIỆP VỤ & QUY TẮC AN TOÀN HỆ THỐNG](#6-các-ràng-buộc-nghiệp-vụ--quy-tắc-an-toàn-hệ-thống)
7. [HỆ THỐNG KIỂM THỬ TỰ ĐỘNG & BẢO ĐẢM CHẤT LƯỢNG](#7-hệ-thống-kiểm-thử-tự-động--bảo-đảm-chất-lượng)
8. [TỔNG KẾT & ĐÁNH GIÁ MỨC ĐỘ HOÀN THIỆN](#8-tổng-kết--đánh-giá-mức-độ-hoàn-thiện)

---

## 1. TỔNG QUAN DỰ ÁN

* **Tên dự án:** JobMarketSV (Job Marketplace Part-time for Students).
* **Mục tiêu cốt lõi:** Xây dựng nền tảng chuyên biệt giải quyết bài toán kết nối việc làm bán thời gian (part-time), thời vụ, linh hoạt theo ca kíp học tập cho sinh viên tại Việt Nam, đồng thời cung cấp giải pháp tuyển dụng nhanh, minh bạch cho các doanh nghiệp (F&B, bán lẻ, gia sư, sự kiện, chuỗi dịch vụ,...).
* **Điểm đột phá của dự án:**
  - Khớp lịch rảnh học tập tuần (Availability Schedule) của sinh viên với ca làm việc thực tế của tin tuyển dụng.
  - Tích hợp bản đồ số Goong Maps & GPS để tìm việc làm gần nơi ở/trường học theo bán kính.
  - Trình tạo CV Online chuẩn hóa (3 templates, xuất PDF bằng Dompdf, chia sẻ public link).
  - Động cơ chấm điểm độ phù hợp tất định (Deterministic Matching Engine) theo 5 tiêu chí kết hợp AI Gemini.
  - Trợ lý ảo AI Gemini hướng dẫn tìm việc và hỗ trợ giải đáp thắc mắc người dùng.
  - Xác thực 2 bước (OTP Email) bảo vệ an toàn tài khoản và đăng nhập 1-chạm qua Google OAuth.

---

## 2. KIẾN TRÚC HỆ THỐNG & CÔNG NGHỆ SỬ DỤNG

### 2.1. Backend Architecture
* **Ngôn ngữ:** **PHP thuần (PHP 8.2+)**. 
* **Framework:** **Không dùng framework nặng (Không dùng Laravel / Symfony / CodeIgniter)**. Hệ thống tự xây dựng kiến trúc theo mô hình **Domain-Driven Design (DDD) / Clean Architecture** thu gọn:
  - `app/Http/Kernel.php`: Bộ tiền xử lý (Front Controller Pipeline), quản lý Middleware stack và tiếp nhận HTTP Request.
  - `nikic/fast-route (^1.3)`: Bộ định tuyến URL hiệu năng cao, phân tách rõ ràng giữa Web Routes (`app/Routes/web.php`) và API RESTful Routes (`app/Routes/api.php`).
  - `app/Domain/`: Chứa toàn bộ nghiệp vụ thuần túy (Business Logic), Domain Entities, Value Objects, Domain Services và Interfaces.
  - `app/Infrastructure/`: Triển khai các Repository giao tiếp CSDL qua PDO, tích hợp API bên ngoài (Goong, Gemini, Google).
* **Cơ sở dữ liệu (Database):** **MySQL 8.0+ / MariaDB**, kết nối qua `PDO` với 100% Prepared Statements chống SQL Injection.
* **Hệ thống Migration tự động:** Cơ chế `php migrate.php` với bảng theo dõi `migrations`, đảm bảo tính Idempotent (chạy nhiều lần an toàn, không sinh lỗi lặp bảng).

### 2.2. Thư viện Composer & Tích hợp Dịch vụ
| Thư viện / Dịch vụ | Phiên bản | Mục đích & Phạm vi sử dụng |
| :--- | :---: | :--- |
| **nikic/fast-route** | `^1.3` | Điều hướng URL và trích xuất tham số Route Regex |
| **vlucas/phpdotenv** | `^5.5` | Quản lý biến môi trường an toàn từ tệp `.env` |
| **firebase/php-jwt** | `^7.0` | Tạo, ký số và xác thực Stateless JSON Web Token (chuẩn HS256) |
| **hybridauth/hybridauth** | `^3.9` | Tích hợp xác thực tài khoản Google OAuth 2.0 |
| **phpmailer/phpmailer** | `^6.10` | Gửi email thông báo việc làm mới, email mã xác nhận OTP đổi mật khẩu |
| **dompdf/dompdf** | `^3.1.6` | Biên dịch HTML/CSS thành tệp PDF chuẩn in A4 cho CV Online |
| **Google Gemini API** | REST API | Trích xuất thông tin CV, hỗ trợ tư vấn việc làm (Chatbot Assistant) |
| **Goong Maps API** | REST API | Autocomplete địa chỉ, Geocoding, Reverse Geocoding, Bounding Box |

### 2.3. Frontend Architecture
* **Giao diện:** Server-Side Rendering (SSR) bằng template PHP thuần kết hợp View Components.
* **JavaScript:** **Vanilla JavaScript (ES6+)**, 100% không dùng React/Vue hay jQuery. Tổ chức dạng Modules theo tính năng (`api.js`, `cv_builder.js`, `large_location_picker.js`, `assistant.js`, `password_security.js`,...).
* **CSS:** **CSS3 hiện đại**, sử dụng CSS Variables, Flexbox, CSS Grid.
* **Hệ thống Design System:**
  - Bảng màu chủ đạo: **Emerald Nordic Theme** (Xanh ngọc bích `#059669`, nền xám ngà `#f7faf9`, than rêu trầm `#071f18`).
  - Hỗ trợ đầy đủ **Chế độ Sáng / Tối (Light / Dark Mode)** tự động lưu trạng thái vào `localStorage`.
  - Thư viện icon vector nội bộ: **RemixIcon** (`remixicon.css`, font woff2), xóa bỏ toàn bộ emoji hệ điều hành.
  - Component tuỳ chỉnh: `custom_select.js` chuyển đổi mọi thẻ `<select>` thành dropdown bo góc cao cấp, hỗ trợ click-outside và đồng bộ 2 chiều sự kiện `change`.

---

## 3. MA TRẬN PHÂN QUYỀN & VAI TRÒ NGƯỜI DÙNG

Hệ thống quản lý chặt chẽ theo 4 đối tượng người dùng:

| Vai trò (Role) | Mô tả đối tượng | Quyền hạn chính trên hệ thống |
| :--- | :--- | :--- |
| **Khách vãng lai (Guest)** | Người chưa đăng nhập | Tra cứu, xem danh sách và chi tiết việc làm công khai, xem kho mẫu CV, tra cứu địa điểm, tương tác trợ lý ảo AI Gemini ở chế độ Read-only. |
| **Sinh viên (Student)** | Người tìm việc làm | Cập nhật hồ sơ cá nhân, thiết lập ma trận lịch rảnh theo tuần, tạo & xuất CV Online, nộp đơn ứng tuyển, rút đơn, lưu việc yêu thích, lưu bộ lọc tìm kiếm, nhận gợi ý việc làm và thông báo in-app/email, chat hỗ trợ admin. |
| **Doanh nghiệp (Company)** | Nhà tuyển dụng | Cập nhật hồ sơ doanh nghiệp, đăng tin tuyển dụng part-time đa chi nhánh, quản lý danh sách tin (đóng/xóa mềm tin), xem và duyệt hồ sơ ứng viên, lưu ghi chú nội bộ bảo mật (`employer_note`). |
| **Quản trị viên (Admin)** | Ban quản trị hệ thống | Kiểm duyệt doanh nghiệp (duyệt/từ chối), kiểm duyệt tin việc làm (công khai/tạm ẩn/từ chối), quản lý trạng thái người dùng (active/suspended/banned), xử lý báo cáo vi phạm, xem nhật ký Audit Logs, hỗ trợ trực tuyến qua Support Chat. |

---

## 4. DANH MỤC CHI TIẾT CÁC TÍNH NĂNG ĐÃ HOÀN THÀNH (12 MODULES)

### Module 1: Quản lý Xác thực & An ninh Tài khoản (Authentication & Security)
* **Đăng ký tài khoản (`/register`):**
  - Đăng ký tài khoản Sinh viên (`student`) hoặc Doanh nghiệp (`company`).
  - **Ràng buộc:** Cấm tuyệt đối chọn quyền `admin` khi đăng ký công khai.
* **Đăng nhập (`/login`):**
  - Xác thực bằng Email & Mật khẩu mã hóa `bcrypt`.
  - Trả về Stateless JWT Token có hạn sử dụng, client lưu trữ an toàn trong `localStorage` qua đối tượng `TokenStorage`.
* **Đăng nhập 1-chạm Google OAuth 2.0:**
  - Tích hợp qua `GoogleOAuthController` và `Hybridauth`.
  - Cơ chế CSRF State Token một lần (`OAuthStateStoreInterface`), tự động hủy sau khi sử dụng.
  - Tự động liên kết tài khoản hoặc tạo mới theo Role người dùng đã chọn khi đăng nhập.
* **Xác thực 2 bước (2FA / OTP qua Email) khi đổi mật khẩu (`AccountSecurityService`):**
  - Kiểm tra trạng thái mật khẩu (tài khoản thường vs tài khoản đăng nhập Google thuần).
  - Tạo mã OTP ngẫu nhiên 6 chữ số, hiệu lực 10 phút, mã hóa trong CSDL.
  - Cơ chế chống Spam (Cooldown 60 giây giữa các lần gửi mã).
  - Tự động thu hồi (Revoke) tất cả JWT token cũ khi mật khẩu thay đổi thành công.
* **Kiểm tra trạng thái tài khoản thời gian thực:**
  - `AuthMiddleware` kiểm tra trạng thái người dùng ở mỗi request. Nếu tài khoản bị Admin chuyển sang `suspended` hoặc `banned`, phiên làm việc bị chặn ngay lập tức.

### Module 2: Quản lý Hồ sơ Sinh viên & Lịch Rảnh (Student Profile)
* **Hồ sơ học tập:** Cập nhật thông tin Họ tên, Số điện thoại, Trường Đại học (`university`), Chuyên ngành (`major`), Năm học (`academic_year`), Giới thiệu bản thân (`bio`).
* **Ma trận lịch rảnh học tập theo tuần (`availability_schedule`):**
  - Bảng ma trận 7 ngày trong tuần (Thứ 2 đến Chủ nhật) x 3 ca (Sáng, Chiều, Tối).
  - Lưu trữ dưới dạng JSON chuẩn hóa, làm căn cứ cốt lõi để đối chiếu ca làm việc của tin tuyển dụng.
* **Kỹ năng & Địa điểm:**
  - Chọn kỹ năng từ danh mục hệ thống (`skill_ids`).
  - Lưu địa điểm làm việc mong muốn (`preferred_locations`).
* **Đo lường mức độ hoàn thiện:**
  - Tự động tính toán tỷ lệ phần trăm hoàn thành hồ sơ (`profile_completion_percent`) từ 0% đến 100% trên server.

### Module 3: Trình tạo CV Online & Quản lý CV Sinh viên (Online CV Builder)
* **Quản lý đa CV:** Một sinh viên có thể tạo tối đa 20 bản CV cho nhiều mục tiêu nghề nghiệp khác nhau.
* **3 Mẫu CV chuẩn hóa:**
  1. `student-simple`: Mẫu sinh viên phổ thông 1 cột, tối ưu đọc nhanh.
  2. `student-modern`: Mẫu thực tập hiện đại, header màu nổi bật, cấu trúc phân cấp.
  3. `ats-classic`: Mẫu chuẩn ATS (Applicant Tracking System), màu đen trắng tối giản, thân thiện với bộ lọc tự động.
* **Tùy biến phong cách (Styling JSON):** Tùy chỉnh màu chủ đạo (Primary Color), Font chữ, Cỡ chữ, Giãn dòng, Khổ giấy in A4.
* **Tùy biến nội dung có cấu trúc:** Thông tin cá nhân, Mục tiêu nghề nghiệp, Học vấn, Kỹ năng, Dự án, Kinh nghiệm làm việc, Hoạt động ngoại khóa, Chứng chỉ, Giải thưởng, Sở thích và các mục tự tạo.
* **Cơ chế Autosave an toàn với Optimistic Locking:**
  - Mỗi bản ghi có trường `version`. Khi cập nhật từ client, hệ thống gửi kèm `expected_version`. Nếu có xung đột dữ liệu, trả mã lỗi `409 Conflict` để bảo vệ dữ liệu không bị ghi đè.
* **Tính năng chuyên sâu:**
  - Kéo thả sắp xếp thứ tự các mục (`section_order_json`).
  - Ẩn/hiện mục (`hidden_sections_json`) mà không làm mất nội dung đã nhập.
  - Nhân bản CV (Duplicate CV).
  - Đặt làm CV chính (Set as Primary CV).
  - Chia sẻ CV bằng liên kết công khai qua slug ngẫu nhiên (`/cv/{slug}`), có thể bật/tắt liên kết bất kỳ lúc nào.
  - Xem trước giao diện HTML (`preview`) và Xuất file PDF chất lượng cao bằng `Dompdf`.
  - Kích hoạt CV Online thành CV ứng tuyển chính thức của hồ sơ cá nhân.
  - Upload file CV PDF từ máy tính, lưu trữ riêng tư tại `storage/app/cvs/`.
  - **Trích xuất thông tin CV bằng AI Gemini (`CvProfileExtractionService`):** Đọc nội dung CV và tự động gợi ý điền nhanh vào hồ sơ sinh viên.

### Module 4: Quản lý Tin Tuyển dụng Part-time (Job Postings)
* **Thuộc tính chuyên biệt cho part-time:**
  - Ca làm việc (`shift_type`): Sáng (`morning`), Chiều (`afternoon`), Tối (`evening`), Xoay ca (`rotating`), Cuối tuần (`weekend`).
  - Thông tin ca chi tiết (`shift_information`), lịch làm việc cụ thể (`working_schedule`).
  - Mức lương linh hoạt: Lương theo giờ (`hourly`), theo ca, theo tháng; mức lương từ - đến (`salary_min`, `salary_max`).
  - Hạn chót nhận hồ sơ (`application_deadline`), số lượng tuyển dụng (`quantity`).
* **Hỗ trợ đa địa điểm làm việc (`job_locations`):**
  - Một tin tuyển dụng có thể gắn **tối đa 20 địa điểm/chi nhánh làm việc**.
  - Mỗi tin bắt buộc có 1 địa điểm chính (`is_primary`).
* **Vòng đời trạng thái tin (State Machine):**
  - `draft` $\rightarrow$ `pending_approval` $\rightarrow$ `published` $\rightarrow$ `closed` / `expired` / `hidden` / `rejected`.
  - Doanh nghiệp có thể chủ động Đóng tin (`close`) khi đã tuyển đủ người.
  - Xóa mềm tin tuyển dụng (Soft Delete qua `deleted_at`) để bảo toàn lịch sử ứng tuyển.

### Module 5: Bản đồ Số, Định vị & Tìm việc gần tôi (Maps & Goong API)
* **Bảo mật API Key tuyệt đối:**
  - Toàn bộ Goong REST API Key được lưu tại backend (`.env`). Trình duyệt hoàn toàn không biết key này mà gọi thông qua API proxy của hệ thống.
* **Tính năng bản đồ:**
  - Gợi ý địa chỉ thông minh (Places Autocomplete) theo thời gian thực.
  - Lấy thông tin chi tiết và tọa độ chính xác của địa điểm (Place Detail).
  - Chuyển đổi qua lại giữa địa chỉ văn bản và tọa độ GPS (Geocoding & Reverse Geocoding).
  - Định vị GPS trình duyệt (`navigator.geolocation`) để tìm việc làm xung quanh (Nearby Search theo công thức Haversine + Bounding Box trong CSDL).
  - Kiểm tra lộ trình và thời gian di chuyển từ vị trí của sinh viên đến nơi làm việc (`commuteCheck`).
* **Bộ chọn địa điểm lớn 2 cột (Large Location Picker):**
  - Thiết kế theo phong cách TopCV: Cột trái chọn Tỉnh/Thành phố, cột phải chọn Quận/Huyện/Khu vực.
  - Hỗ trợ tìm kiếm việc làm đồng thời trên nhiều khu vực (`location_ids=loc-001,loc-002`).
* **Dữ liệu đơn vị hành chính chuẩn Việt Nam:**
  - Hỗ trợ song song cả mô hình hành chính mới từ 01/07/2025 (Tỉnh/TP $\rightarrow$ Phường/Xã) và mô hình hành chính cũ (Tỉnh/TP $\rightarrow$ Quận/Huyện $\rightarrow$ Phường/Xã).
  - Tích hợp trọn bộ dữ liệu hành chính 63 tỉnh thành trong tệp JSON nội bộ.

### Module 6: Tìm kiếm, Lọc Nâng cao, Yêu thích & Lưu bộ lọc
* **Bộ lọc đa tiêu chí:**
  - Lọc theo từ khóa, ngành nghề (`category_id`), khu vực hành chính (`location_id` hoặc danh sách `location_ids`), ca làm việc (`shift_type`), mức lương tối thiểu, hình thức làm việc (`work_mode`).
  - Sắp xếp linh hoạt theo ngày đăng mới nhất, mức lương cao nhất, hạn nộp gần nhất (Whitelist an toàn).
  - Phân trang chuẩn hóa (`page`, `per_page`, `total`, `total_pages`).
* **Việc làm yêu thích (Favorites):**
  - Lưu và bỏ lưu tin việc làm yêu thích (Cơ chế Idempotent).
  - Xem danh sách việc đã lưu kèm cảnh báo hạn nộp hồ sơ.
* **Bộ lọc tìm kiếm đã lưu (Saved Searches):**
  - Lưu lại các điều kiện tìm kiếm thường dùng để chạy lại kết quả chỉ với 1 click.
  - Cấu hình nhận thông báo tự động khi có việc làm mới khớp với bộ lọc.

### Module 7: Quy trình Ứng tuyển & Quản lý Đơn (Application Flow)
* **Quy trình nộp đơn:**
  - Sinh viên chọn ca làm việc mong muốn (`preferred_shift`), viết thư giới thiệu (`cover_letter`).
  - Hệ thống tự động tạo bản sao chụp nhanh (Snapshot) CV của sinh viên tại thời điểm nộp đơn (`cv_snapshot_url` hoặc CV Online đính kèm).
  - **Chống nộp trùng lặp:** Khóa Unique Constraint cặp `(job_id, developer_id)`. Sinh viên chỉ được nộp đơn 1 lần cho mỗi tin tuyển dụng.
* **Rút đơn ứng tuyển an toàn (Withdraw Application):**
  - Sinh viên có quyền chủ động rút đơn (`withdrawn`) khi đơn đang ở trạng thái chờ duyệt (`pending`) hoặc đã xem (`viewed`).
  - **Ràng buộc:** Trạng thái `withdrawn` là trạng thái kết thúc (Terminal State). Nhà tuyển dụng không thể tự ý chuyển ngược lại trạng thái khác.
* **Quản lý đơn phía Doanh nghiệp:**
  - Duyệt hồ sơ ứng viên qua các trạng thái: `pending` $\rightarrow$ `viewed` $\rightarrow$ `shortlisted` $\rightarrow$ `accepted` / `rejected`.
  - Ghi chú đánh giá nội bộ (`employer_note`): Doanh nghiệp lưu ghi chú riêng cho từng ứng viên. **Ràng buộc bảo mật:** Trường này bị loại bỏ hoàn toàn khỏi API và View khi sinh viên xem thông tin đơn ứng tuyển.
* **Tải file CV an toàn:** Endpoint riêng biệt `/applications/{id}/cv` kiểm tra nghiêm ngặt quyền sở hữu của sinh viên và doanh nghiệp trước khi cho phép tải file.

### Module 8: Động cơ Khớp việc & Gợi ý Thông minh (Matching Engine)
* **Động cơ chấm điểm tất định (Deterministic Matcher):**
  - Tự động tính toán điểm phù hợp (Overall Match Score 0 - 100%) giữa Hồ sơ sinh viên và Tin tuyển dụng dựa trên **5 tiêu chí chuẩn hóa**:
    1. **Kỹ năng (`Skills`):** So khớp tập kỹ năng ứng viên có với danh mục kỹ năng yêu cầu của công việc.
    2. **Lịch rảnh & Ca làm (`Schedule / Shift`):** Đối chiếu ma trận rảnh tuần của sinh viên với ca làm việc thực tế của tin đăng.
    3. **Địa điểm làm việc (`Location / Distance`):** Đánh giá khoảng cách di chuyển từ nơi ở/ưu tiên của sinh viên đến các địa điểm làm việc của tin tuyển dụng.
    4. **Ngành học & Danh mục (`Major / Category`):** So sánh mức độ liên quan giữa chuyên ngành học tập và ngành nghề công việc.
    5. **Mức lương & Đãi ngộ (`Salary / Budget`):** Đánh giá mức độ tương thích về thu nhập.
* **Giải trình minh bạch (Explanation Breakdown):** Trả về chi tiết điểm số, phần trăm đáp ứng và lý do đánh giá cho từng tiêu chí để sinh viên biết điểm mạnh/yếu khi ứng tuyển.
* **Bảo vệ quyền riêng tư (PII Redaction):**
  - Trước khi gửi dữ liệu ngữ cảnh cho AI Gemini phân tích ngữ nghĩa, hệ thống `PiiRedactor` sẽ tự động xóa sạch các thông tin nhạy cảm: Họ tên, Email, Số điện thoại, Ngày sinh, Giới tính.
* **Bộ nhớ đệm thông minh (Canonical Hash Caching):**
  - Băm nội dung dữ liệu (Hash) để lưu cache kết quả phân tích. Nếu hồ sơ và tin tuyển dụng không thay đổi, hệ thống trả kết quả ngay lập tức mà không cần tính toán lại.
* **Trang Gợi ý việc làm cá nhân hóa (`/student/recommendations`):** Tự động đề xuất danh sách việc làm phù hợp nhất cho từng sinh viên, loại trừ các công việc đã nộp đơn.

### Module 9: Hệ thống Thông báo & Email Tự động (Notifications & Mail Service)
* **Thông báo In-app thời gian thực:**
  - Tự động phát sinh thông báo khi: Sinh viên nộp đơn mới (báo cho doanh nghiệp), Doanh nghiệp cập nhật trạng thái đơn (báo cho sinh viên), Admin duyệt/từ chối tin hoặc công ty.
  - Quản lý thông báo: Đánh dấu đã đọc một tin hoặc tất cả, hiển thị huy hiệu (Badge) số lượng chưa đọc trên thanh điều hướng.
* **Hệ thống Gửi Email tự động qua SMTP (`PHPMailer`):**
  - **Email thông báo việc làm mới (`dispatch_job_alerts.php`):** Script chạy định kỳ (Daily/Weekly) tự động quét và gửi danh sách các công việc mới phù hợp tới hộp thư sinh viên theo Saved Searches hoặc Profile Alert.
  - **Email nhắc nhở hạn chót nộp đơn (`FavoriteDeadlineReminderService`):** Cảnh báo sinh viên khi các công việc trong danh mục Yêu thích sắp hết hạn nộp hồ sơ.
  - **Email gửi mã OTP xác thực:** Gửi mã bảo mật 6 số khi thực hiện đổi mật khẩu tài khoản.

### Module 10: Trợ lý Ảo AI & Chat Hỗ trợ Trực tuyến (Gemini Assistant & Support Chat)
* **Trợ lý Ảo AI Gemini (`AssistantService`):**
  - Widget chat nổi có thể thu gọn, tích hợp sẵn trên trang tìm việc và cổng sinh viên.
  - **Chế độ Read-only an toàn:** Chỉ hỗ trợ giải đáp thắc mắc, định hướng cách viết CV, hướng dẫn tìm việc và gợi ý các tin tuyển dụng đang công khai (`published`).
  - **Rào chắn bảo vệ (Guardrails):**
    - Cấm tuyệt đối bot thực hiện các hành vi ghi dữ liệu (không nộp đơn, không đổi thông tin).
    - Không gửi API key hay thông tin nhạy cảm ra client.
    - Giới hạn tần suất gọi bot (Rate Limiting) theo địa chỉ IP (đối với khách) và User ID (đối với tài khoản đã đăng nhập).
    - Tự động bắt lỗi an toàn khi Gemini quá tải (HTTP 429), hết thời gian chờ (Timeout) hoặc bị bộ lọc an toàn chặn nội dung.
* **Kênh Chat Hỗ trợ Trực tiếp (`ChatService`):**
  - Cho phép Sinh viên và Doanh nghiệp nhắn tin trực tiếp với Quản trị viên (Admin) để được giải đáp khiếu nại, hỗ trợ xác thực.
  - Quản trị viên có giao diện quản lý hội thoại tập trung (`/admin/support`).

### Module 11: Cổng Quản trị Hệ thống & Kiểm duyệt (Admin Portal & Audit Logs)
* **Bảng điều khiển quản trị (Dashboard):** Thống kê tổng số người dùng, doanh nghiệp, tin tuyển dụng, đơn ứng tuyển toàn hệ thống.
* **Kiểm duyệt Doanh nghiệp:** Xem xét hồ sơ công ty, phê duyệt (`verified`) hoặc từ chối xác thực kèm lý do bắt buộc (`rejection_reason`).
* **Kiểm duyệt Tin tuyển dụng:** Phê duyệt xuất bản tin (`published`), tạm ẩn tin khỏi sàn (`hidden`), hoặc từ chối tin (`rejected`) kèm lý do gửi cho nhà tuyển dụng.
* **Quản lý Tài khoản Người dùng:** Tìm kiếm, phân trang và thay đổi trạng thái tài khoản: Kích hoạt (`active`), Tạm ngưng (`suspended`), Cấm vĩnh viễn (`banned`).
* **Hàng đợi Báo cáo Tin vi phạm (`JobReportService`):** Tiếp nhận và xử lý các báo cáo từ người dùng về tin tuyển dụng lừa đảo, sai sự thật.
* **Nhật ký Kiểm duyệt (Audit Logs):** Ghi nhận tự động mọi thao tác nhạy cảm của các Admin (Ai làm, Lúc nào, Hành động gì, Tác động lên đối tượng nào, Dữ liệu chi tiết trước/sau khi đổi).

### Module 12: Thiết kế Giao diện Người dùng & Trải nghiệm (UI/UX)
* **Phong cách thẩm mỹ hiện đại:** Tone màu Emerald Nordic thanh lịch, bố cục rõ ràng, typography chuẩn font *Plus Jakarta Sans*.
* **Tối ưu Responsive 100%:** Hiển thị mượt mà trên Desktop, Laptop, Máy tính bảng (Tablet) và Điện thoại di động (Mobile) với Menu ngăn kéo (Drawer Navigation).
* **Trải nghiệm Dropdown mượt mà:** Sử dụng `custom_select.js` tự động thay thế giao diện chọn khô cứng của trình duyệt bằng menu bo góc hiện đại, hỗ trợ tìm kiếm và chế độ nền tối.

---

## 5. CẤU TRÚC CƠ SỞ DỮ LIỆU & QUAN HỆ THỰC THỂ (SCHEMA)

Hệ thống sở hữu hơn 23 bảng CSDL hoạt động thực tế:

```text
               ┌──────────────┐
               │    users     │
               └──────┬───────┘
       ┌──────────────┼──────────────────────────┐
       ▼              ▼                          ▼
┌──────────────┐ ┌───────────────┐        ┌──────────────┐
│  companies   │ │student_profiles│       │notifications │
└──────┬───────┘ └──────┬────────┘        └──────────────┘
       │                │
       ▼                ├────────────────────────┐
┌──────────────┐        ▼                        ▼
│     jobs     │  ┌──────────────┐        ┌──────────────┐
└──────┬───────┘  │ online_cvs   │        │  favorites   │
       │          └──────────────┘        └──────────────┘
       ├────────────────┐                        │
       ▼                ▼                        ▼
┌──────────────┐ ┌──────────────┐         ┌──────────────┐
│job_locations │ │ applications │◄────────┤saved_searches│
└──────────────┘ └──────────────┘         └──────────────┘
```

### Các Bảng Dữ Liệu Cốt Lõi:
1. **`users`:** Quản lý tài khoản đăng nhập (`id`, `name`, `email`, `password`, `role`, `status`, `created_at`,...).
2. **`companies`:** Hồ sơ pháp lý doanh nghiệp (`id`, `user_id`, `name`, `address`, `city`, `district`, `logo`, `verification_status`, `rejection_reason`).
3. **`student_profiles`:** Hồ sơ sinh viên (`id`, `user_id`, `university`, `major`, `academic_year`, `available_schedule` JSON, `skills`, `skill_ids` JSON, `cv_storage_path`, `profile_completion_percent`).
4. **`online_cvs`:** Lưu trữ toàn bộ dữ liệu tài liệu CV Online (`id`, `user_id`, `title`, `template_key`, `content_json`, `style_json`, `section_order_json`, `hidden_sections_json`, `is_primary`, `is_public`, `public_slug`, `version`).
5. **`jobs`:** Tin tuyển dụng part-time (`id`, `company_id`, `category_id`, `title`, `description`, `requirements`, `benefits`, `shift_type`, `shift_information`, `working_schedule`, `salary_min`, `salary_max`, `application_deadline`, `status`, `rejection_reason`, `published_at`, `deleted_at`).
6. **`job_locations`:** Bảng phụ trợ lưu tối đa 20 địa điểm làm việc của một tin đăng (`id`, `job_id`, `address_text`, `province`, `commune`, `district_text_legacy`, `latitude`, `longitude`, `is_primary`).
7. **`applications`:** Đơn ứng tuyển (`id`, `job_id`, `developer_id`, `preferred_shift`, `cover_letter`, `cv_snapshot_url`, `status`, `employer_note`, `applied_at`).
8. **`favorites`:** Danh sách việc làm yêu thích của sinh viên (`id`, `user_id`, `job_id`).
9. **`saved_searches`:** Các bộ lọc tìm kiếm đã lưu của người dùng (`id`, `user_id`, `name`, `keyword`, `category_id`, `location_id`, `shift_type`, `salary_min`).
10. **`notifications`:** Thông báo nội bộ in-app (`id`, `user_id`, `type`, `title`, `message`, `data` JSON, `read_at`).
11. **`audit_logs`:** Nhật ký kiểm duyệt của quản trị viên (`id`, `admin_id`, `action`, `target_type`, `target_id`, `details` JSON).
12. **`categories`:** Danh mục ngành nghề việc làm (`id`, `name`, `slug`, `description`).
13. **`locations`:** Danh mục khu vực hành chính (`id`, `name`, `city`, `district`).
14. **`skills`:** Danh mục kỹ năng hệ thống (`id`, `name`).
15. **`job_match_analyses`:** Bảng lưu bộ nhớ đệm điểm khớp việc 5 tiêu chí (`id`, `application_id`, `candidate_hash`, `job_hash`, `overall_score`, `criteria_scores_json`).
16. **`password_change_challenges`:** Lưu trữ mã OTP xác thực đổi mật khẩu (`id`, `user_id`, `code_hash`, `expires_at`, `consumed_at`).
17. **`oauth_identities`:** Liên kết tài khoản với tài khoản mạng xã hội Google (`id`, `user_id`, `provider`, `provider_user_id`).
18. **`conversations` & `messages`:** Quản lý phiên trao đổi và nội dung tin nhắn hỗ trợ trực tuyến.
19. **`job_reports`:** Quản lý báo cáo vi phạm tin tuyển dụng.

---

## 6. CÁC RÀNG BUỘC NGHIỆP VỤ & QUY TẮC AN TOÀN HỆ THỐNG

### 6.1. Ràng buộc An toàn & Bảo mật (Security Constraints)
1. **Không lộ bí mật cấu hình (Zero Leaks):** Toàn bộ API Key (Gemini, Goong Maps, Secret JWT, Thông tin SMTP Mail) chỉ lưu tại tệp `.env` máy chủ. Phía giao diện Frontend hoàn toàn không có quyền truy cập trực tiếp các key này.
2. **Bảo vệ quyền riêng tư khi dùng AI (PII Redaction):** Trước khi gửi dữ liệu sang Google Gemini, toàn bộ thông tin định danh cá nhân (Họ tên, SĐT, Email, Giới tính, Ngày sinh) đều bị xóa để tránh rò rỉ dữ liệu cá nhân của sinh viên.
3. **Chống tấn công leo thang đặc quyền (Privilege Escalation):** Chặn hoàn toàn việc truyền tham số `role=admin` qua form đăng ký công khai.
4. **Bảo vệ tài khoản quản trị cuối cùng (Last Admin Protection):** Hệ thống chặn thao tác khóa hoặc hạ quyền tài khoản Admin cuối cùng còn hoạt động, ngăn ngừa việc hệ thống rơi vào trạng thái mất quyền kiểm soát.
5. **Chống IDOR & Thao tác chéo dữ liệu:** Doanh nghiệp chỉ có thể sửa/xóa tin hoặc xem ứng viên thuộc chính công ty mình. Sinh viên chỉ có thể xem/sửa hồ sơ, đơn nộp và CV của chính mình.
6. **Bảo mật ghi chú nhà tuyển dụng (`employer_note`):** Doanh nghiệp có thể lưu đánh giá ứng viên, nhưng trường này bắt buộc bị ẩn 100% khi sinh viên truy vấn API hoặc xem giao diện.
7. **Kiểm tra phiên đăng nhập tức thời (Token Revocation):** Khi tài khoản bị Admin khóa (`suspended`/`banned`) hoặc khi người dùng đổi mật khẩu, toàn bộ JWT cũ lập tức mất hiệu lực ngay tại request kế tiếp.
8. **Không lưu trữ tọa độ GPS cá nhân:** Tọa độ GPS lấy từ trình duyệt chỉ được dùng trong bộ nhớ tạm để tính toán bán kính tìm kiếm việc làm, tuyệt đối không lưu vào cơ sở dữ liệu.

### 6.2. Ràng buộc Nghiệp vụ (Business Rules Constraints)
1. **Ràng buộc nộp đơn duy nhất (One-time Application):** Một sinh viên chỉ được phép nộp đơn 1 lần duy nhất cho 1 tin tuyển dụng.
2. **Ràng buộc rút đơn (Withdrawal Terminal State):** Sinh viên chỉ được rút đơn khi đơn đang ở trạng thái `pending` hoặc `viewed`. Một khi đã rút đơn (`withdrawn`), đơn ứng tuyển kết thúc và doanh nghiệp không được phép đổi sang trạng thái khác.
3. **Ràng buộc đăng tin của doanh nghiệp:** Tin tuyển dụng chỉ được xuất bản công khai (`published`) khi Doanh nghiệp đã được Admin kiểm duyệt và xác thực hồ sơ (`verified`).
4. **Ràng buộc số lượng địa điểm làm việc:** Mỗi tin tuyển dụng chỉ được có tối đa 20 chi nhánh/địa điểm làm việc, và luôn luôn phải có ít nhất 1 địa điểm chính (`is_primary`).
5. **Ràng buộc số lượng CV Online:** Mỗi tài khoản sinh viên được tạo và lưu trữ tối đa 20 bản CV online.
6. **Ràng buộc Autosave CV (Optimistic Locking):** Khi lưu CV từ trình duyệt, hệ thống kiểm tra số phiên bản (`version`). Nếu hai phiên làm việc cùng ghi đè, hệ thống từ chối yêu cầu cũ với lỗi `409 Conflict`.
7. **Ràng buộc gửi mã OTP:** Giới hạn tối thiểu 60 giây giữa hai lần yêu cầu gửi mã xác nhận đổi mật khẩu để phòng chống spam email.
8. **Ràng buộc Chatbot AI:** Chatbot hoạt động ở chế độ Read-only. Bot chỉ có quyền đọc các tin việc làm đã `published`, tuyệt đối không có quyền thay đổi dữ liệu hay tự ý ứng tuyển thay người dùng.

---

## 7. HỆ THỐNG KIỂM THỬ TỰ ĐỘNG & BẢO ĐẢM CHẤT LƯỢNG

Dự án được trang bị sẵn **hơn 25 kịch bản kiểm thử tự động toàn diện** (Automated Test Suites) viết bằng PHP CLI, cho phép kiểm tra từ cú pháp, tính toàn vẹn CSDL, logic API đến giao diện E2E:

1. **`check_syntax.php`:** Quét và kiểm tra lỗi cú pháp PHP trên 100% các tệp nguồn trong toàn bộ dự án.
2. **`test_api.php`:** Kiểm thử tích hợp 12 kịch bản RESTful API cốt lõi.
3. **`test_milestone2a.php`:** Kiểm tra nghiệp vụ Đăng tin, ca làm việc, mức lương và quản lý vòng đời tin.
4. **`test_milestone2b.php`:** Kiểm tra hồ sơ sinh viên, ma trận lịch rảnh và tính điểm hoàn thiện hồ sơ.
5. **`test_milestone2c.php`:** Kiểm tra quy trình ứng tuyển, chống nộp trùng, rút đơn và bảo mật `employer_note`.
6. **`test_milestone2d.php`:** Kiểm tra bộ lọc tìm kiếm việc làm đa tiêu chí, tin yêu thích và bộ lọc đã lưu.
7. **`test_milestone2e.php`:** Kiểm tra hệ thống thông báo in-app và các chỉ số thống kê Dashboard.
8. **`test_milestone2f.php`:** Kiểm tra phân quyền quản trị, kiểm duyệt công ty/tin, Audit Logs và bảo vệ Last Admin.
9. **`test_e2e.php`:** Mô phỏng 100% toàn bộ hành trình người dùng thực tế từ Đăng ký $\rightarrow$ Đăng tin $\rightarrow$ Kiểm duyệt $\rightarrow$ Nộp đơn $\rightarrow$ Duyệt đơn.
10. **`test_fe1.php`, `test_fe2a.php`, `test_fe2b.php`, `test_fe2c.php`, `test_browser_e2e_fe3.php`:** Kiểm thử giao diện Web HTML5, phục vụ file tĩnh, chống XSS, chống Open Redirect và kiểm tra Responsive.
11. **`test_google_auth_p0_01.php`, `test_google_auth_p0_02.php`, `test_google_auth_p1_01.php`:** Kiểm thử luồng đăng nhập Google OAuth, bảo mật token state và phân luồng vai trò.
12. **`test_online_cv_builder.php`, `test_cv_builder_ui_api.php`:** Kiểm thử CRUD CV Online, bộ chuyển đổi mẫu, xuất PDF Dompdf, chia sẻ public link và autosave versioning.
13. **`test_matching_p0_01.php` đến `test_matching_p1_05.php`:** Kiểm thử động cơ chấm điểm khớp việc 5 tiêu chí, chuẩn hóa dữ liệu, xóa PII và bộ nhớ đệm kết quả.
14. **`test_location_backend.php`, `test_administrative_locations.php`:** Kiểm thử tích hợp bản đồ Goong, tọa độ GPS, tìm việc gần tôi và bộ chọn địa điểm lớn.
15. **`test_password_security.php`:** Kiểm thử luồng đổi mật khẩu xác thực 2 bước (OTP), mã hóa bcrypt và thu hồi token.
16. **`test_saved_search_job_alerts.php`:** Kiểm thử luồng tự động quét và gửi email thông báo việc làm mới qua SMTP.

---

## 8. TỔNG KẾT & ĐÁNH GIÁ MỨC ĐỘ HOÀN THIỆN

* **Đánh giá tổng thể:** Hệ thống đã hoàn thiện **100% các tính năng nghiệp vụ cốt lõi** theo yêu cầu của một nền tảng tuyển dụng sinh viên part-time chuẩn mực.
* **Tính độc lập & Khả năng triển khai:** Mã nguồn hoàn toàn độc lập, không phụ thuộc framework cồng kềnh, dễ dàng vận hành trên cả môi trường Local (Laragon, XAMPP, PHP Built-in Server) lẫn máy chủ Hosting tiêu chuẩn / Cloud Production.
* **Giá trị thực tiễn phục vụ báo cáo:**
  - Kiến trúc phân tầng rõ ràng (DDD / Clean Architecture) thể hiện tư duy thiết kế phần mềm bài bản.
  - Áp dụng các giải pháp công nghệ thời sự: Bản đồ số (Goong Maps), Trí tuệ nhân tạo (Google Gemini AI), Đăng nhập một chạm (Google OAuth 2.0), Xuất bản tài liệu số (Dompdf).
  - Hệ sinh thái kiểm thử tự động hoàn chỉnh, chứng minh tính ổn định, độ tin cậy và sự chặt chẽ trong từng ràng buộc nghiệp vụ.
