# Nền Tảng Tuyển Dụng & Tìm Việc Part-time Sinh Viên (Job Marketplace Part-time)

Dự án Backend RESTful API được xây dựng bằng **PHP thuần (PHP 8.2+)**, **MySQL** theo kiến trúc **Domain-Driven Design (DDD) / Clean Architecture**, tối ưu hóa cho bài toán tìm việc làm bán thời gian cho sinh viên tại Việt Nam.

---

## 1. Yêu Cầu Hệ Thống
- **PHP:** $\ge$ 8.2 (Hỗ trợ tốt nhất trên PHP 8.2 hoặc PHP 8.3)
- **PHP Extensions:** `pdo_mysql`, `mbstring`, `openssl`, `json`, `curl`
- **Database:** MySQL 8.0+ / MariaDB (Khuyên dùng MySQL có sẵn trong Laragon)
- **Công cụ quản lý môi trường:** [Laragon](https://laragon.org/) (Khuyên dùng) hoặc PHP CLI
- **Composer:** Composer 2.x

---

## 2. Hướng Dẫn Cài Đặt (Setup Guide)

### Bước 1: Clone repository & Cài đặt dependencies
```bash
git clone https://github.com/BaseMax/JobMarketplaceDDDPHP.git
cd JobMarketplaceDDDPHP
composer install
```

### Bước 2: Thiết lập biến môi trường (.env)
Sao chép tệp mẫu và cập nhật thông số kết nối Database:
```bash
cp .env.example .env
```
Nội dung tệp mẫu [.env.example](file:///.env.example):
```env
APP_NAME="Job Marketplace Part-time"
APP_ENV=development
APP_DEBUG=true
APP_URL=http://manguonmo.test

# Cấu hình Cơ sở dữ liệu
DB_HOST=localhost
DB_PORT=3306
DB_NAME=jobmarket
DB_USER=root
DB_PASSWORD=

# Khóa bí mật JWT (Tối thiểu 32 ký tự theo chuẩn HS256)
SECRET=jobmarket_secret_key_at_least_32_characters_long_123456

# Cấu hình CORS
CORS_ALLOWED_ORIGINS=*
CORS_ALLOWED_METHODS="GET, POST, PUT, DELETE, OPTIONS"
CORS_ALLOWED_HEADERS="Content-Type, Authorization, X-Requested-With"
```

### Bước 3: Chạy Migration (Có cơ chế theo dõi an toàn)
Lệnh này sẽ tự động tạo bảng theo dõi `migrations` và thực thi toàn bộ 23 bảng:
```bash
php migrate.php
```
> *Ghi chú: Lệnh này an toàn để chạy nhiều lần (Idempotent 100%), hệ thống sẽ tự động bỏ qua các migration đã chạy.*

### Bước 4: Nạp Dữ Liệu Phát Triển (Development Seeder)
Nạp sẵn tài khoản mẫu, danh mục việc làm, kỹ năng và tin tuyển dụng part-time:
```bash
php seed.php
```

---

## 3. Hướng Dẫn Chạy Dự Án

### Cách 1: Chạy bằng Laragon (Khuyên dùng)
1. Đặt thư mục dự án vào `C:\laragon\www\manguonmo`.
2. Mở **Laragon** $\rightarrow$ Bấm **Start All** (Bật Apache & MySQL).
3. Mở trình duyệt và truy cập: **`http://manguonmo.test`**
*(Đã có sẵn file `public/.htaccess` để tự động định tuyến URL và chuyển tiếp Header Authorization)*.

### Cách 2: Chạy bằng PHP Built-in Server
```bash
php -S localhost:8000 -t public public/index.php
```
Truy cập qua: `http://localhost:8000`

### 3.1. Danh Sách Các Trang Giao Diện Web Public & Auth (Phase Frontend FE-1)
- **Trang chủ (Home):** `http://manguonmo.test/` (Hero tìm việc, bộ lọc nhanh, danh mục, top việc làm mới).
- **Tìm kiếm việc làm (Jobs Listing):** `http://manguonmo.test/viec-lam` hoặc `http://manguonmo.test/jobs` (Bộ lọc đa tiêu chí theo ca/giờ/địa điểm, sắp xếp, phân trang).
- **Chi tiết việc làm (Job Detail):** `http://manguonmo.test/viec-lam/job-001` hoặc `http://manguonmo.test/jobs/job-001` (Mô tả, yêu cầu, quyền lợi, modal nộp đơn ứng tuyển, lưu tin).
- **Đăng nhập (Login):** `http://manguonmo.test/login` (Hỗ trợ 3 vai trò kèm gợi ý email phát triển, không hardcode mật khẩu).
- **Đăng ký (Register):** `http://manguonmo.test/register` (Đăng ký tài khoản Sinh viên / Doanh nghiệp).
- **Đăng xuất (Logout):** `http://manguonmo.test/logout` (Xóa token client và đưa về trang đăng nhập).

### 3.2. Danh Sách Các Trang Cổng Sinh Viên (Phase Frontend FE-2A)
- **Tổng quan sinh viên (Dashboard):** `http://manguonmo.test/student/dashboard` (Tiến trình đơn ứng tuyển, việc yêu thích sắp hết hạn, thông báo).
- **Hồ sơ cá nhân (Profile):** `http://manguonmo.test/student/profile` (Tiến độ hoàn thiện hồ sơ, bảng ma trận chọn lịch rảnh T2-CN, kỹ năng, link CV).
- **Đơn ứng tuyển (Applications):** `http://manguonmo.test/student/applications` (Lọc theo trạng thái tiếng Việt, theo dõi tiến độ, rút đơn an toàn).
- **Việc làm đã lưu (Favorites):** `http://manguonmo.test/student/favorites` (Danh sách việc yêu thích, bỏ lưu).
- **Bộ lọc đã lưu (Saved Searches):** `http://manguonmo.test/student/saved-searches` (Quản lý các bộ lọc tìm kiếm đã lưu, chạy tìm kiếm nhanh).
- **Thông báo cá nhân (Notifications):** `http://manguonmo.test/student/notifications` (Xem thông báo, đánh dấu đã đọc một / tất cả, đồng bộ badge navbar).

### 3.3. Danh Sách Các Trang Cổng Nhà Tuyển Dụng (Phase Frontend FE-2B)
- **Tổng quan doanh nghiệp (Dashboard):** `http://manguonmo.test/company/dashboard` (Thống kê tin đăng, hồ sơ nhận, phân bố trạng thái, top việc làm).
- **Hồ sơ doanh nghiệp (Profile):** `http://manguonmo.test/company/profile` (Thông tin công ty, người liên hệ, địa chỉ, hiển thị trạng thái xác thực và lý do từ chối).
- **Quản lý tin tuyển dụng (Jobs):** `http://manguonmo.test/company/jobs` (Toàn bộ tin nội bộ, lọc theo trạng thái, đóng tin, xóa mềm tin).
- **Tạo tin việc làm (Create Job):** `http://manguonmo.test/company/jobs/create` (Form chuẩn 100% backend fields, kiểm soát quyền xuất bản theo xác thực).
- **Sửa tin việc làm (Edit Job):** `http://manguonmo.test/company/jobs/{id}/edit` (Chỉnh sửa nội dung tin tuyển dụng của công ty).
- **Quản lý hồ sơ ứng tuyển (Applications):** `http://manguonmo.test/company/applications` (Xem thông tin ứng viên, đổi trạng thái, lưu ghi chú nội bộ `employer_note`).
- **Thông báo doanh nghiệp (Notifications):** `http://manguonmo.test/company/notifications` (Thông báo ứng viên nộp đơn, thông báo duyệt/từ chối từ admin).

### 3.4. Danh Sách Các Trang Cổng Quản Trị Hệ Thống (Phase Frontend FE-2C)
- **Tổng quan quản trị (Dashboard):** `http://manguonmo.test/admin/dashboard` (Thống kê số lượng người dùng, doanh nghiệp chờ duyệt, tin việc làm, đơn ứng tuyển toàn sàn).
- **Quản lý người dùng (Users):** `http://manguonmo.test/admin/users` (Danh sách tài khoản, phân trang, lọc role/status, kích hoạt hoặc tạm khóa tài khoản).
- **Kiểm duyệt doanh nghiệp (Companies):** `http://manguonmo.test/admin/companies` (Xác thực công ty, từ chối kèm lý do bắt buộc, gửi thông báo in-app cho chủ doanh nghiệp).
- **Kiểm duyệt tin tuyển dụng (Jobs):** `http://manguonmo.test/admin/jobs` (Phê duyệt công khai tin, tạm ẩn tin khỏi sàn, từ chối tin kèm lý do bắt buộc).
- **Nhật ký kiểm duyệt (Audit Logs):** `http://manguonmo.test/admin/audit-logs` (Theo dõi lịch sử toàn bộ thao tác quản trị của các Admin).

---

## 4. Công Cụ Kiểm Tra Chất Lượng & Đo Kiểm Tự Động

Dự án được trang bị sẵn 2 công cụ kiểm tra tự động chạy trực tiếp qua terminal:

### 4.1. Kiểm tra Cú pháp toàn bộ Source Tree (Syntax Check)
Quét và kiểm tra lỗi cú pháp PHP trên 100% các tệp trong toàn bộ dự án:
```bash
php check_syntax.php
```

### 4.2. Chạy Bộ Kiểm Thử Tích Hợp Nền Tảng (Milestone 1)
Tự động gửi HTTP requests thực tế đến web server và kiểm tra 12 kịch bản chính:
```bash
php test_api.php
```

### 4.3. Chạy Bộ Kiểm Thử Nghiệp Vụ Job Posting (Milestone 2A)
Kiểm tra tự động các kịch bản nghiệp vụ chuyên sâu của module Đăng tin việc làm part-time:
```bash
php test_milestone2a.php
```

### 4.4. Chạy Bộ Kiểm Thử Nghiệp Vụ Student Profile (Milestone 2B)
Kiểm tra tự động các kịch bản quản lý hồ sơ sinh viên cá nhân và hồ sơ công khai an toàn:
```bash
php test_milestone2b.php
```

### 4.5. Chạy Bộ Kiểm Thử Nghiệp Vụ Application Flow (Milestone 2C)
Kiểm tra tự động toàn bộ quy trình ứng tuyển part-time, chống nộp trùng, rút đơn, và quản lý đơn tuyển dụng:
```bash
php test_milestone2c.php
```

### 4.6. Chạy Bộ Kiểm Thử Nghiệp Vụ Search, Favorites & Saved Searches (Milestone 2D)
Kiểm tra tự động bộ lọc tìm kiếm đa tiêu chí, danh sách yêu thích, và quản lý tìm kiếm đã lưu:
```bash
php test_milestone2d.php
```

### 4.7. Chạy Bộ Kiểm Thử Nghiệp Vụ Notifications & Dashboard (Milestone 2E)
Kiểm tra tự động hệ thống thông báo nội bộ, luồng kích hoạt tự động theo sự kiện, và các bảng Dashboard tổng quan:
```bash
php test_milestone2e.php
```

### 4.8. Chạy Bộ Kiểm Thử Nghiệp Vụ Admin Moderation & Audit (Milestone 2F)
Kiểm tra tự động module quản trị viên, kiểm duyệt tin việc làm, xác thực doanh nghiệp, và ghi nhận audit log:
```bash
php test_milestone2f.php
```

### 4.9. Chạy Bộ Kiểm Thử Toàn Diện End-to-End (Milestone 2G)
Mô phỏng 100% hành trình người dùng thực tế: Đăng ký $\rightarrow$ Đăng tin $\rightarrow$ Kiểm duyệt $\rightarrow$ Cập nhật hồ sơ $\rightarrow$ Tìm kiếm & Lưu yêu thích $\rightarrow$ Ứng tuyển $\rightarrow$ Duyệt đơn $\rightarrow$ Nhận thông báo $\rightarrow$ Xem Dashboard $\rightarrow$ Ma trận an ninh:
```bash
php test_e2e.php
```

### 4.10. Chạy Bộ Kiểm Thử Giao Diện Web (Phase Frontend FE-1)
Kiểm tra tự động các trang web HTML5, phục vụ tệp tĩnh CSS/JS, và bảo toàn 100% các REST API cũ:
```bash
php test_fe1.php
```

### 4.11. Chạy Bộ Kiểm Thử Cổng Sinh Viên (Phase Frontend FE-2A)
Kiểm tra tự động toàn diện Cổng Sinh viên (Dashboard, Hồ sơ, Ứng tuyển, Quản lý đơn, Việc yêu thích, Bộ lọc đã lưu, Thông báo, Phân quyền UX & Bảo mật):
```bash
php test_fe2a.php
```

### 4.12. Chạy Bộ Kiểm Thử Cổng Nhà Tuyển Dụng (Phase Frontend FE-2B)
Kiểm tra tự động toàn diện Cổng Nhà tuyển dụng (Dashboard, Hồ sơ công ty & Trạng thái xác thực, Đăng/sửa/đóng tin tuyển dụng, Xem và duyệt hồ sơ ứng viên, Ghi chú nội bộ `employer_note`, Cô lập dữ liệu giữa các công ty, Phân quyền UX & Bảo mật):
```bash
php test_fe2b.php
```

### 4.13. Chạy Bộ Kiểm Thử Cổng Quản Trị Hệ Thống (Phase Frontend FE-2C)
Kiểm tra tự động toàn diện Cổng Quản Trị & Kiểm Duyệt (Dashboard, Quản lý người dùng, Kiểm duyệt công ty, Kiểm duyệt việc làm, Tạm ẩn/công khai tin, Nhật ký kiểm duyệt Audit Logs, Bảo vệ Last Admin, Phân quyền UX & Bảo mật):
```bash
php test_fe2c.php
```

### 4.14. Chạy Bộ Kiểm Thử Tổng Thể Frontend & Trình Duyệt E2E (Phase Frontend FE-3)
Kiểm tra tự động toàn diện trải nghiệm người dùng theo vai trò, an toàn bảo mật, chống XSS, chống Open Redirect, kiểm tra reload dữ liệu từ database, kiểm tra CSS responsive và ma trận an ninh:
```bash
php test_browser_e2e_fe3.php
```

---

## 5. Tài Khoản Mẫu Để Kiểm Thử (Development Demo Accounts)

> [!NOTE]
> Để bảo đảm an ninh hệ thống và tuân thủ tiêu chuẩn bảo mật, mật khẩu tài khoản mẫu **không được công khai trong tài liệu**. Mật khẩu mẫu phát triển được sinh tự động khi chạy lệnh `php seed.php` trên máy phát triển cục bộ (xem trong tệp nội bộ [`app/Database/Seeder.php`](file:///app/Database/Seeder.php)).

| Vai trò (Role) | Email Tài Khoản | Mục Đích Kiểm Thử |
| :--- | :--- | :--- |
| **Admin** | `admin@jobmarket.vn` | Quản trị viên kiểm duyệt tin, doanh nghiệp và audit logs |
| **Doanh Nghiệp (Company)** | `highlands@jobmarket.vn` | Doanh nghiệp đã được xác thực (Verified) |
| **Doanh Nghiệp (Company)** | `miniso@jobmarket.vn` | Doanh nghiệp đã xác thực, kiểm tra cô lập dữ liệu |
| **Sinh Viên (Student)**| `sinhvien1@jobmarket.vn`| Sinh viên tìm việc, cập nhật hồ sơ và ứng tuyển |
| **Sinh Viên (Student)**| `sinhvien2@jobmarket.vn`| Sinh viên kiểm tra chống thao tác chéo dữ liệu |

---

## 6. Hướng Dẫn Vận Hành Laragon & Triển Khai Production

### 6.1. Hướng Dẫn Cài Đặt Trên Laragon Cục Bộ
1. **Đặt thư mục dự án:** Di chuyển toàn bộ mã nguồn vào thư mục máy chủ web Laragon:
   ```text
   C:\laragon\www\manguonmo
   ```
2. **Kích hoạt dịch vụ:** Mở Laragon Dashboard và nhấn **"Start All"** để khởi động đồng thời cả **Apache** và **MySQL**.
3. **Virtual Host tự động:** Laragon sẽ tự động nhận diện thư mục `public` và kích hoạt Virtual Host tại địa chỉ:
   ```text
   http://manguonmo.test
   ```
   *(Kiểm tra tệp `C:\Windows\System32\drivers\etc\hosts` xem đã có dòng `127.0.0.1 manguonmo.test` do Laragon tự sinh hay chưa)*.
4. **Cài đặt cơ sở dữ liệu:**
   - Tạo database tên `jobmarket` trong MySQL (qua HeidiSQL hoặc phpMyAdmin).
   - Chạy `php migrate.php` để khởi tạo cấu trúc bảng.
   - Chạy `php seed.php` để nạp dữ liệu mẫu phát triển.

### 6.2. Quy Trình Nghiêm Ngặt Khi Đưa Lên Môi Trường Sản Xuất (Production)
Khi triển khai lên máy chủ thật, bắt buộc tuân thủ các nguyên tắc sau:
- **Cấu hình môi trường:** Đặt `APP_ENV=production` và `APP_DEBUG=false` trong `.env`.
- **Khóa bảo mật JWT:** Thay đổi `SECRET` bằng chuỗi ngẫu nhiên có độ dài tối thiểu 64 ký tự (ví dụ sinh từ `openssl rand -base64 48`). Tuyệt đối không dùng secret mặc định.
- **Chứng chỉ HTTPS:** Bắt buộc cài đặt SSL/TLS (Let's Encrypt hoặc Commercial SSL) và cấu hình chuyển hướng HTTP sang HTTPS 100%.
- **Giới hạn CORS:** Thiết lập `CORS_ALLOWED_ORIGINS` bằng chính xác domain frontend được phép (ví dụ `https://jobmarket.vn`), tuyệt đối không dùng `*`.
- **Dữ liệu mầm:** Tuyệt đối **KHÔNG CHẠY `php seed.php`** trên môi trường Production để tránh nạp các tài khoản và dữ liệu kiểm thử.
- **Migration an toàn:** Sử dụng `php migrate.php` để tạo bảng và migration mới; tuyệt đối không dùng các script rollback hoặc drop table phá hủy dữ liệu của người dùng thật.

---

## 6. Quy Chuẩn API & Response Format

Chi tiết toàn bộ đặc tả kỹ thuật xem tại: **[Tài Liệu Chi Tiết API (docs/api.md)](file:///docs/api.md)**.

### 6.1. Response Thành Công (200, 201)
```json
{
  "success": true,
  "message": "Lấy danh sách việc làm thành công.",
  "data": [ ... ],
  "meta": {
    "page": 1,
    "per_page": 10,
    "total": 3,
    "total_pages": 1
  }
}
```

### 6.2. Response Thất Bại (400, 401, 403, 404, 422, 500)
```json
{
  "success": false,
  "message": "Dữ liệu đầu vào không hợp lệ",
  "errors": {
    "email": ["Trường email phải là định dạng email hợp lệ."]
  },
  "code": 422
}
```

---

## 7. Ví Dụ Kiểm Thử API Bằng cURL

### 1. Kiểm tra trạng thái hệ thống (Health Check)
```bash
curl -X GET http://manguonmo.test/
```

### 2. Đăng ký tài khoản sinh viên mới
```bash
curl -X POST http://manguonmo.test/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Lê Hoàng Nam",
    "email": "nam.le@gmail.com",
    "password": "Password@123",
    "role": "student"
  }'
```

### 3. Đăng nhập lấy Token
```bash
curl -X POST http://manguonmo.test/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "highlands@jobmarket.vn",
    "password": "Company@123"
  }'
```

### 4. Tìm kiếm & Lọc việc làm part-time (Kèm phân trang & sắp xếp)
```bash
curl -X GET "http://manguonmo.test/jobs?city=Hanoi&district=CauGiay&shift_type=morning&sort_by=salary_min&sort_dir=DESC&page=1&per_page=10"
```

### 5. Xem chi tiết công việc
```bash
curl -X GET http://manguonmo.test/jobs/job-001
```

### 6. Gọi API yêu cầu xác thực (Kèm JWT Bearer Token)
```bash
curl -X GET http://manguonmo.test/companies \
  -H "Authorization: Bearer <TOKEN_CỦA_BẠN>"
```

---

## 8. Danh Mục Tồn Đọng Kỹ Thuật (Technical Backlog & Risk Matrix)

| Mức độ | Hạng mục | Mô tả kỹ thuật & Hướng giải quyết tiếp theo |
| :---: | :--- | :--- |
| **HIGH** | **JWT Logout & Token Revocation** | Hiện tại JWT hoạt động theo mô hình stateless (phi trạng thái) với thời hạn TTL. Khi người dùng bấm đăng xuất hoặc bị Admin khóa tài khoản (`banned` / `suspended`), token cũ vẫn có hiệu lực cho đến khi hết hạn. Cần triển khai Redis Blacklist / DB Revocation Token Table tại AuthMiddleware. |
| **MEDIUM** | **Email & Mobile Push Notifications** | Hệ thống hiện tại lưu trữ và xử lý In-app Notifications an toàn. Chưa tích hợp dịch vụ SMTP/SendGrid để gửi email thông báo ứng tuyển hoặc Firebase Cloud Messaging (FCM) để gửi push notification tới điện thoại. |
| **MEDIUM** | **Upload Tệp CV Đa Định Dạng (Multipart Upload)** | **Lưu ý phạm vi MVP:** Hệ thống hiện chỉ hỗ trợ lưu trữ liên kết hồ sơ `cv_url` và chụp nhanh snapshot văn bản. **Chưa hỗ trợ** endpoint upload tệp multipart/form-data trực tiếp lên server để bảo đảm an toàn tệp tin (tránh malware). Giai đoạn tiếp theo sẽ bổ sung upload tệp PDF có kiểm tra MIME-type, quét mã độc ClamAV và lưu trữ AWS S3 / MinIO. |
| **MEDIUM** | **Tự Động Đóng Tin Hết Hạn (Cron Auto-expire)** | Tin tuyển dụng có ngày hết hạn `application_deadline < CURDATE()` hiện được lọc ẩn tự động tại tầng truy vấn (`WHERE application_deadline >= CURDATE()`). Cần thiết lập Scheduled Cron Job (`php console jobs:expire`) chạy định kỳ 00:00 hàng ngày để cập nhật trạng thái `status = 'expired'` trong CSDL. |
| **LOW** | **Tìm Kiếm Toàn Văn Nâng Cao (Fulltext Search)** | Tìm kiếm hiện tại sử dụng `LIKE %keyword%` có escape an toàn. Khi quy mô dữ liệu vượt 100.000 tin, cần chuyển đổi sang MySQL FULLTEXT Index với Match...Against hoặc tích hợp Elasticsearch / Meilisearch. |
| **LOW** | **Giới Hạn Tần Suất Yêu Cầu (Rate Limiting / Throttling)** | Chưa tích hợp bộ đếm giới hạn request (Rate Limiter middleware) dựa trên IP/Token để phòng chống brute-force và DDoS. |

