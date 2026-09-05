# Project Context: Nền Tảng Tuyển Dụng & Tìm Việc Part-time Sinh Viên (JobMarketplaceSV)

Tài liệu này tổng hợp toàn bộ kiến trúc, dữ liệu, trạng thái tính năng và các quyết định kỹ thuật của dự án, phục vụ cho quá trình Code Review và đánh giá Kiến trúc Phần mềm (Software Architecture).

---

# 1. Project Overview

* **Mục tiêu của nền tảng:** Xây dựng nền tảng chuyên biệt kết nối nhu cầu tìm việc làm bán thời gian (part-time), linh hoạt theo ca/giờ học tập cho sinh viên tại Việt Nam và hỗ trợ các doanh nghiệp (F&B, bán lẻ, gia sư, sự kiện, v.v.) tuyển dụng nhân sự ca kíp hiệu quả.
* **Đối tượng sử dụng:**
  * **Sinh viên (Student):** Tìm kiếm việc làm part-time phù hợp lịch học, quản lý hồ sơ và lịch rảnh, ứng tuyển việc làm, lưu tin yêu thích và bộ lọc tìm kiếm, nhận thông báo tiến trình tuyển dụng.
  * **Nhà tuyển dụng / Doanh nghiệp (Company / Employer):** Đăng tin tuyển dụng theo ca và mức lương theo giờ, quản lý hồ sơ ứng viên, cập nhật trạng thái tuyển dụng kèm ghi chú nội bộ bảo mật, quản lý thông tin doanh nghiệp.
  * **Quản trị viên (Admin):** Kiểm duyệt tin tuyển dụng, xác thực hồ sơ pháp lý doanh nghiệp, quản trị trạng thái tài khoản người dùng, giám sát hệ thống qua nhật ký audit log.
  * **Khách vãng lai (Guest):** Tra cứu, tìm kiếm và xem chi tiết việc làm công khai, đăng ký tài khoản.
* **Business flow chính:**
  1. Doanh nghiệp đăng ký tài khoản $\rightarrow$ Quản trị viên kiểm duyệt và xác thực (`verified`).
  2. Doanh nghiệp tạo tin tuyển dụng part-time (chọn ca làm việc, mức lương theo giờ, hạn nộp) $\rightarrow$ Trạng thái `published` (hoặc qua kiểm duyệt).
  3. Sinh viên đăng ký tài khoản $\rightarrow$ Hoàn thiện hồ sơ cá nhân và thiết lập ma trận lịch rảnh học tập theo tuần (`availability_schedule`).
  4. Sinh viên tìm việc qua bộ lọc đa tiêu chí (từ khóa, ngành nghề, quận/huyện, ca làm việc, mức lương tối thiểu) $\rightarrow$ Nộp đơn ứng tuyển với ca làm mong muốn và thư giới thiệu.
  5. Nhà tuyển dụng nhận đơn ứng tuyển $\rightarrow$ Xem xét hồ sơ, lưu ghi chú nội bộ (`employer_note`) $\rightarrow$ Cập nhật trạng thái đơn (`viewed`, `shortlisted`, `accepted`, `rejected`).
  6. Hệ thống tự động gửi thông báo nội bộ (In-app Notification) cho các bên khi phát sinh đơn mới hoặc thay đổi trạng thái tuyển dụng / kiểm duyệt.
  7. Sinh viên có thể chủ động rút đơn (`withdrawn`) khi đơn còn ở trạng thái chờ duyệt hoặc đã xem.

---

# 2. User Roles

Hệ thống hiện tại phân quyền dựa trên 3 vai trò (Role) cốt lõi:
1. `student` *(kế thừa/tương thích từ mã cũ `developer`)*: Người tìm việc làm part-time.
2. `company`: Nhà tuyển dụng / Doanh nghiệp đăng tin tuyển dụng.
3. `admin`: Quản trị viên hệ thống có toàn quyền kiểm duyệt và quản lý tài khoản.

*(Không có vai trò nào khác tồn tại trong mã nguồn hiện tại).*

---

# 3. Tech Stack

* **Frontend:**
  * Render Server-side HTML5 template bằng PHP thuần (`app/Views/`).
  * Vanilla JavaScript (ES6+, Fetch API, module hóa, 100% không dùng framework React/Vue hay thư viện ngoài).
  * CSS3 thuần hiện đại (CSS Variables, Flexbox, CSS Grid, Responsive Breakpoints cho Desktop/Tablet/Mobile Drawer Navigation).
* **Backend:**
  * **PHP thuần 8.2+** theo kiến trúc Domain-Driven Design (DDD) và Clean Architecture thu gọn.
  * Không sử dụng Laravel/Symfony framework; Tự xây dựng Kernel, FastRoute Dispatcher, Middleware Pipeline, Centralized Validator, Exception Handler.
* **Database:**
  * **MySQL 8.0+ / MariaDB**, kết nối qua `PDO` với prepared statements 100%.
* **Authentication:**
  * Stateless JWT (JSON Web Token) tự xây dựng với thuật toán ký `HS256` (`app/Facades/JWT.php`).
  * Mật khẩu mã hóa bằng chuẩn `password_hash($pass, PASSWORD_BCRYPT)`.
  * Frontend lưu token và profile trong `localStorage` (`public/assets/js/api.js`).
* **Storage:**
  * Lưu trữ đường dẫn URL chuỗi (ví dụ: `cv_url`, `cv_snapshot_url`, `logo`). Chưa có module tải tệp tin multipart/upload trực tiếp lên S3/Cloud.
* **Third-party Services:**
  * Không phụ thuộc bất kỳ bên thứ 3 nào (Hoàn toàn self-contained).
* **Deployment / Local Server:**
  * Chạy trên máy chủ Apache + MySQL của [Laragon](https://laragon.org/) qua Virtual Host tự động: `http://manguonmo.test` (cấu hình Rewrite qua `public/.htaccess`).

---

# 4. Current Architecture

Hệ thống được tổ chức theo kiến trúc phân lớp rõ ràng (Layered / Clean Architecture):

```text
[ Browser / Client (HTML5 + Vanilla JS + CSS3) ]
                       │
             HTTP Request (JSON / HTML)
                       ▼
          [ public/index.php (Front Controller) ]
                       │
             [ app/Http/Kernel.php ]
            ├── Middlewares Stack (AuthMiddleware, RoleMiddleware)
            └── FastRoute Dispatcher (app/Routes/api.php & web.php)
                       │
       ┌───────────────┴───────────────┐
       ▼                               ▼
[ API Controllers ]           [ Web Controllers ]
  (JSON Response)             (HTML View Render)
       │                               │
       └───────────────┬───────────────┘
                       ▼
            [ app/Domain/ Services ]
       (Business Logic, Validation, Entities)
                       │
                       ▼
        [ app/Infrastructure/ Repositories ]
          (PDO MySQL Prepared Statements)
                       │
                       ▼
            [ MySQL Database Engine ]
```

* **Frontend:** Tách biệt layout dùng chung (`app/Views/layouts/main.php`), các view nghiệp vụ theo module (`auth/`, `jobs/`, `student/`, `company/`, `admin/`). `api.js` đóng vai trò API Client tập trung quản lý JWT, gọi Fetch API và tự động bắt lỗi HTTP 401.
* **Backend:**
  * **Domain Layer (`app/Domain/`):** Chứa các Domain Entities đóng gói dữ liệu và các Domain Services xử lý thuần túy nghiệp vụ.
  * **Infrastructure Layer (`app/Infrastructure/`):** Triển khai các Repository Interfaces, trực tiếp thực thi câu lệnh SQL qua PDO.
  * **Presentation/HTTP Layer (`app/Http/`):** Tiếp nhận Request, xác thực token qua Middleware, gọi Service và trả về Response qua `Response::json()` hoặc `Response::html()`.
* **State Management:** Phía client quản lý trạng thái phiên đăng nhập thông qua đối tượng `TokenStorage` (lưu khóa `jobmarket_token` và `jobmarket_user`).

---

# 5. Database

Hệ thống bao gồm các bảng dữ liệu hoạt động chính và một số bảng stub dự phòng:

### Các Bảng Đang Hoạt Động Chính:
1. `users`: Quản lý tài khoản đăng nhập (`id`, `name`, `email`, `password` hash, `role`, `status` [active, suspended, banned], `created_at`, `updated_at`).
2. `companies`: Hồ sơ nhà tuyển dụng (`id`, `user_id`, `name`, `description`, `address`, `city`, `district`, `website`, `contact_person`, `contact_phone`, `logo`, `verification_status` [pending, verified, rejected], `rejection_reason`).
   * *Quan hệ:* `companies.user_id` $\rightarrow$ `users.id` (1 - 1).
3. `profiles`: Hồ sơ sinh viên (`id`, `user_id`, `full_name`, `phone`, `university`, `major`, `year`, `bio`, `cv_url`, `skills`, `availability_schedule` [JSON], `profile_completion_percent`).
   * *Quan hệ:* `profiles.user_id` $\rightarrow$ `users.id` (1 - 1).
4. `jobs`: Tin tuyển dụng part-time (`id`, `company_id`, `category_id`, `location_id`, `title`, `description`, `requirements`, `benefits`, `work_type`, `work_mode`, `shift_type`, `shift_information`, `working_schedule`, `required_skills`, `salary_min`, `salary_max`, `quantity`, `application_deadline`, `status` [draft, pending_approval, published, hidden, rejected, closed, expired], `rejection_reason`, `published_at`, `deleted_at`).
   * *Quan hệ:* `jobs.company_id` $\rightarrow$ `companies.id` (N - 1).
5. `applications`: Hồ sơ nộp đơn ứng tuyển (`id`, `job_id`, `user_id`, `cover_letter`, `preferred_shift`, `cv_snapshot_url`, `status` [pending, viewed, shortlisted, accepted, rejected, withdrawn], `employer_note`).
   * *Quan hệ:* `applications.job_id` $\rightarrow$ `jobs.id`, `applications.user_id` $\rightarrow$ `users.id` (Unique cặp `job_id` + `user_id`).
6. `favorites`: Danh sách việc làm yêu thích của sinh viên (`id`, `user_id`, `job_id`). Unique `user_id` + `job_id`.
7. `saved_searches`: Bộ lọc tìm kiếm đã lưu của sinh viên (`id`, `user_id`, `name`, `keyword`, `category_id`, `location_id`, `shift_type`, `salary_min`, `work_type`).
8. `notifications`: Thông báo nội bộ in-app (`id`, `user_id`, `type`, `title`, `message`, `data` [JSON], `read_at`).
9. `audit_logs`: Nhật ký kiểm duyệt của quản trị viên (`id`, `admin_id`, `action`, `target_type`, `target_id`, `details` [JSON]).
10. `categories`: Danh mục ngành nghề việc làm (`id`, `name`, `slug`, `description`).
11. `locations`: Danh mục địa phương / quận huyện (`id`, `name`, `city`, `district`).
12. `skills`: Danh mục kỹ năng hệ thống (`id`, `name`).
13. `job_skills`: Bảng liên kết nhiều-nhiều giữa `jobs` và `skills`.
14. `migrations`: Bảng quản lý và theo dõi các phiên bản migration cơ sở dữ liệu đã chạy.

### Các Bảng Stub / Di Sản (Chưa gắn logic API):
* `conversations`, `messages`: Dự phòng tính năng nhắn tin trao đổi trực tiếp.
* `reviews`, `review_developers`: Dự phòng tính năng đánh giá 2 chiều sau làm việc.
* `subscriptions`, `payments`: Dự phòng tính năng thanh toán & đăng ký gói nhà tuyển dụng.
* `reports`: Dự phòng tính năng báo cáo vi phạm.
* `qualifications`, `experiences`: Dự phòng học vấn & kinh nghiệm mở rộng.

---

# 6. API / Main Modules

* **Authentication & Profile:**
  * `POST /register`: Đăng ký tài khoản (sinh viên hoặc doanh nghiệp).
  * `POST /login`: Đăng nhập, trả về Bearer JWT token và thông tin user.
  * `POST /logout`: Đăng xuất.
  * `GET /student/profile`, `PUT /student/profile`: Xem và cập nhật hồ sơ sinh viên kèm lịch rảnh.
  * `GET /company/profile`, `PUT /company/profile`: Xem và cập nhật hồ sơ doanh nghiệp.
* **Job Postings & Search:**
  * `GET /jobs`: Tìm kiếm việc làm công khai đa tiêu chí (keyword, ca, lương, địa điểm, ngành nghề) kèm phân trang và whitelist sắp xếp.
  * `GET /jobs/{id}`: Chi tiết tin việc làm công khai (kèm thông tin công ty).
  * `POST /jobs`: Doanh nghiệp đăng tin tuyển dụng part-time.
  * `PUT /jobs/{id}`, `PATCH /jobs/{id}`: Doanh nghiệp chỉnh sửa tin tuyển dụng của mình.
  * `POST /jobs/{id}/close`: Doanh nghiệp đóng tin tuyển dụng.
  * `DELETE /jobs/{id}`: Doanh nghiệp xóa mềm tin tuyển dụng.
  * `GET /company/jobs`: Danh sách tin tuyển dụng nội bộ của doanh nghiệp.
* **Applications Management:**
  * `POST /jobs/{id}/applications`: Sinh viên nộp đơn ứng tuyển (chống nộp trùng lặp).
  * `GET /student/applications`: Sinh viên xem các đơn đã nộp (đã ẩn `employer_note`).
  * `POST /applications/{id}/withdraw`: Sinh viên rút đơn ứng tuyển (khi đang pending/viewed).
  * `GET /company/applications`: Doanh nghiệp xem toàn bộ ứng viên nộp đơn vào các tin của mình.
  * `PATCH /applications/{id}/status`: Doanh nghiệp cập nhật trạng thái đơn & ghi chú nội bộ `employer_note`.
* **Favorites & Saved Searches:**
  * `GET /favorites/jobs`, `POST /favorites/jobs/{id}`, `DELETE /favorites/jobs/{id}`: Quản lý việc làm yêu thích.
  * `GET /saved-searches`, `POST /saved-searches`, `DELETE /saved-searches/{id}`: Quản lý bộ lọc tìm kiếm đã lưu.
* **Notifications & Dashboards:**
  * `GET /notifications`, `GET /notifications/unread-count`, `PATCH /notifications/{id}/read`, `PATCH /notifications/read-all`: Quản lý thông báo in-app.
  * `GET /student/dashboard`, `GET /company/dashboard`, `GET /admin/dashboard`: Thống kê số liệu thực tế theo vai trò.
* **Admin Moderation & Administration:**
  * `GET /admin/users`, `PATCH /admin/users/{id}/status`: Quản lý người dùng và trạng thái tài khoản (bảo vệ Last Admin).
  * `GET /admin/companies`, `PATCH /admin/companies/{id}/verification`: Duyệt hoặc từ chối xác thực doanh nghiệp.
  * `GET /admin/jobs`, `PATCH /admin/jobs/{id}/moderation`: Duyệt tin, tạm ẩn tin (ẩn khỏi public 404), từ chối tin.
  * `GET /admin/audit-logs`: Truy vấn nhật ký thao tác kiểm duyệt hệ thống.

---

# 7. Features Already Implemented

| Tính Năng / Module | Trạng Thái | Mô Tả Chi Tiết |
| :--- | :---: | :--- |
| Đăng ký, đăng nhập & phân quyền JWT | **COMPLETED** | Hỗ trợ 3 vai trò, mã hóa bcrypt, bảo vệ route web và REST API |
| Quản lý tin tuyển dụng part-time | **COMPLETED** | Đăng tin theo ca, mức lương theo giờ, hạn nộp, đóng tin, xóa mềm |
| Tìm kiếm & Lọc việc làm nâng cao | **COMPLETED** | Lọc theo ca làm, quận huyện, khoảng lương, ngành nghề, phân trang |
| Hồ sơ sinh viên & Ma trận lịch rảnh | **COMPLETED** | Cập nhật thông tin học tập, ma trận JSON lịch rảnh, % hoàn thiện |
| Quy trình nộp đơn & Quản lý ứng viên | **COMPLETED** | One-time apply, lưu snapshot CV, duyệt đơn, bảo mật `employer_note` |
| Rút đơn ứng tuyển an toàn | **COMPLETED** | Sinh viên được phép rút đơn khi đang ở trạng thái pending/viewed |
| Việc làm yêu thích & Bộ lọc đã lưu | **COMPLETED** | Hỗ trợ CRUD tin yêu thích (idempotent) và bộ lọc tìm kiếm đã lưu |
| Thông báo In-app tự động | **COMPLETED** | Tự động phát sinh thông báo khi nộp đơn, đổi trạng thái, kiểm duyệt |
| Dashboard thống kê 3 vai trò | **COMPLETED** | Thống kê số liệu thời gian thực từ database cho từng vai trò |
| Cổng quản trị Admin & Kiểm duyệt | **COMPLETED** | Kiểm duyệt công ty/tin, bảo vệ Last Admin, ghi nhận Audit Log |
| Giao diện Web HTML5/CSS3/JS | **COMPLETED** | Đầy đủ giao diện web cho Khách, Sinh viên, Nhà tuyển dụng, Admin |
| Responsive Mobile & Drawer Menu | **COMPLETED** | Tối ưu hiển thị cho desktop, tablet và mobile (breakpoints $\le$ 768px, 480px) |
| Tải tệp CV PDF trực tiếp | **PARTIAL** | Hiện lưu đường dẫn liên kết (`cv_url`); chưa có upload file multipart |
| Chat / Nhắn tin thời gian thực | **NOT STARTED** | Đã có cấu trúc bảng trong DB nhưng chưa triển khai logic |
| Đánh giá 2 chiều (Review) | **NOT STARTED** | Đã có cấu trúc bảng trong DB nhưng chưa triển khai logic |
| Cổng thanh toán (Payments) | **NOT STARTED** | Đã có cấu trúc bảng trong DB nhưng chưa triển khai logic |
| Gửi Email thông báo qua SMTP | **NOT STARTED** | Hiện tại chỉ hỗ trợ hệ thống In-app Notification nội bộ |

---

# 8. Current User Flows

### 1. Student Flow:
`Khách vào trang chủ` $\rightarrow$ `Tìm kiếm việc làm part-time` $\rightarrow$ `Xem chi tiết việc làm` $\rightarrow$ `Đăng ký / Đăng nhập tài khoản Sinh viên` $\rightarrow$ `Cập nhật thông tin cá nhân & Lịch rảnh học tập` $\rightarrow$ `Lưu việc làm yêu thích / Lưu bộ lọc tìm kiếm` $\rightarrow$ `Ứng tuyển vào việc làm (chọn ca làm mong muốn & gửi cover letter)` $\rightarrow$ `Xem danh sách đơn đã nộp trong Cổng Sinh viên` $\rightarrow$ `Nhận In-app Notification khi Nhà tuyển dụng cập nhật trạng thái đơn` $\rightarrow$ `Rút đơn nếu thay đổi nguyện vọng`.

### 2. Employer Flow:
`Đăng ký tài khoản Doanh nghiệp` $\rightarrow$ `Đăng nhập` $\rightarrow$ `Cập nhật hồ sơ công ty (tên, địa chỉ, website, mô tả)` $\rightarrow$ `Chờ Admin xác thực công ty` $\rightarrow$ `Tạo tin tuyển dụng (chọn ca làm, lương theo giờ, hạn nộp, lưu nháp hoặc công khai)` $\rightarrow$ `Chỉnh sửa hoặc Đóng tin khi đủ người` $\rightarrow$ `Xem danh sách hồ sơ ứng tuyển từ Cổng Doanh nghiệp` $\rightarrow$ `Đổi trạng thái ứng viên (Shortlisted / Accepted / Rejected) kèm ghi chú nội bộ` $\rightarrow$ `Xem thống kê số liệu tại Dashboard Doanh nghiệp`.

### 3. Admin Flow:
`Đăng nhập tài khoản Quản trị viên` $\rightarrow$ `Xem Dashboard tổng quan toàn hệ thống` $\rightarrow$ `Xem danh sách doanh nghiệp chờ xác thực $\rightarrow$ Duyệt (Verified) hoặc Từ chối (bắt buộc nhập lý do)` $\rightarrow$ `Kiểm duyệt tin tuyển dụng $\rightarrow$ Duyệt công khai, Tạm ẩn (trả về 404 phía public) hoặc Từ chối` $\rightarrow$ `Quản lý trạng thái người dùng (Active, Suspend, Ban - không thể khóa tài khoản Admin duy nhất)` $\rightarrow$ `Xem nhật ký kiểm duyệt Audit Logs để đối soát`.

---

# 9. Important Technical Decisions

* **Lưu Trữ & Xử Lý JWT:** JWT được tạo bằng secret key từ file cấu hình `.env`, ký bằng thuật toán HS256. Frontend lưu JWT trong `localStorage` (`jobmarket_token`, `jobmarket_user`). Khi gặp lỗi `401 Unauthorized` hoặc người dùng đăng xuất, toàn bộ token bị xóa sạch và điều hướng về trang `/login`.
* **Phân Quyền Hai Lớp (Defense in Depth):**
  * *Lớp giao diện:* Frontend kiểm tra role để hiển thị đúng menu điều hướng, ngăn chặn truy cập trái vai trò.
  * *Lớp Backend (Chốt chặn tuyệt đối):* `AuthMiddleware` và `RoleMiddleware` kiểm tra token và vai trò trên từng request. Domain Service kiểm tra quyền sở hữu tài nguyên (Ownership Check), trả về `403 Forbidden` nếu cố can thiệp chéo dữ liệu.
* **Quy Tắc Bảo Vệ Dữ Liệu Riêng Tư (Privacy Enforcement):**
  * Trường `employer_note` chỉ trả về cho chủ sở hữu tin tuyển dụng; loại bỏ 100% khi sinh viên truy vấn đơn ứng tuyển.
  * `password` (hash bcrypt) và token tuyệt đối không bao giờ được trả về trong danh sách người dùng của Admin.
  * Hồ sơ công khai của sinh viên (`/developers/{id}`) ẩn toàn bộ email, số điện thoại và đường dẫn CV.
* **Chống IDOR & Anti-Mass Assignment:** Mọi hành vi cập nhật hồ sơ cá nhân, lưu việc làm yêu thích, nộp đơn, lưu bộ lọc đều lấy `user_id` trực tiếp từ JWT payload đã xác thực; tự động loại bỏ các trường client tự gửi như `role`, `user_id`, `id`, `created_at`.
* **An Toàn Tiêu Đề HTTP & Chống Tấn Công Phổ Biến:**
  * Phản hồi HTTP tự động trả về: `X-Content-Type-Options: nosniff`, `X-Frame-Options: SAMEORIGIN`, `Referrer-Policy: strict-origin-when-cross-origin`.
  * Trang đăng nhập kiểm soát tham số `?redirect=`, chỉ chấp nhận đường dẫn tương đối bắt đầu bằng `/` đơn lẻ, chống tấn công Open Redirect.
  * Toàn bộ dữ liệu hiển thị trên HTML đều được lọc qua hàm `escapeHtml()` hoặc gán qua thuộc tính text an toàn (`.innerText` / `.textContent`) chống tấn công XSS.
* **Chuẩn Hóa Format Response API:**
  * *Thành công (200, 201):* `{"success": true, "message": "...", "data": ..., "meta": {...}}`
  * *Thất bại (400, 401, 403, 404, 409, 422, 500):* `{"success": false, "message": "...", "errors": {...}, "code": ...}`
* **Chuẩn Hóa Truy Vấn Cơ Sở Dữ Liệu:** 100% sử dụng PDO Prepared Statements. Xóa mềm (Soft Delete) sử dụng cột `deleted_at IS NULL`.

---

# 10. Known Problems / TODO

1. **Upload File CV Đính Kèm (Multipart File Upload):** Hiện tại hệ thống chỉ nhận chuỗi URL cho trường `cv_url`. Cần bổ sung API upload tệp tin PDF/DOCX kèm kiểm tra định dạng MIME, giới hạn dung lượng và lưu trữ an toàn.
2. **Cơ Chế Lưu Trữ Token:** Việc lưu JWT trong `localStorage` giúp triển khai nhanh cho ứng dụng Vanilla JS, tuy nhiên để đạt mức bảo mật tài chính/nghiệp vụ cao nhất, có thể cân nhắc chuyển sang `HttpOnly; Secure; SameSite=Strict` Cookie trong tương lai.
3. **Các Bảng Dữ Liệu Dư Thừa Chưa Có Logic Nghiệp Vụ:** Các bảng `conversations`, `messages`, `reviews`, `payments`, `subscriptions`, `reports` hiện chỉ tồn tại ở tầng database migration, chưa có API và giao diện tương ứng.
4. **Hệ Thống Thông Báo Qua Email:** Mới chỉ có thông báo hiển thị trong ứng dụng (In-app Notification); chưa kết nối dịch vụ SMTP / SES để gửi email kích hoạt tài khoản hoặc nhắc nhở ca làm việc.
5. **Phân Trang Động Phía Frontend:** Giao diện hiện tại cố định số lượng 10 bản ghi/trang theo mặc định API; chưa có thanh dropdown cho phép người dùng tùy chọn số lượng hiển thị (10, 20, 50).

---

# 11. Important Files

Dưới đây là các tệp tin quan trọng nhất cấu thành khung xương của dự án:

* **Routing & Middleware Pipeline:**
  * `public/index.php`: Entry point duy nhất của toàn bộ ứng dụng.
  * `app/Http/Kernel.php`: Bộ điều phối HTTP, quản lý chuỗi Middleware và routing FastRoute.
  * `app/Routes/api.php`: Khai báo toàn bộ các RESTful API endpoints.
  * `app/Routes/web.php`: Khai báo toàn bộ các routes giao diện web HTML5.
  * `app/Http/Middlewares/AuthMiddleware.php`: Middleware xác thực JWT token và bảo vệ truy cập.
  * `app/Http/Middlewares/RoleMiddleware.php`: Middleware phân quyền vai trò người dùng.
* **Core Utilities & Infrastructure:**
  * `app/Facades/JWT.php`: Logic mã hóa và giải mã JSON Web Token.
  * `app/Http/Response.php`: Chuẩn hóa response JSON, HTML và HTTP Security Headers.
  * `app/Http/Validation/Validator.php`: Bộ máy kiểm tra hợp lệ dữ liệu đầu vào.
  * `migrate.php`: Trình thực thi và quản lý lịch sử migration database.
* **Domain Services & Nghiệp Vụ Chính:**
  * `app/Domain/JobService.php`: Nghiệp vụ đăng tin, tìm kiếm part-time, lọc theo ca và mức lương.
  * `app/Domain/ApplicationService.php`: Nghiệp vụ nộp đơn, rút đơn, duyệt đơn và bảo mật `employer_note`.
  * `app/Domain/ProfileService.php`: Nghiệp vụ hồ sơ sinh viên, lịch rảnh học tập và độ hoàn thiện hồ sơ.
  * `app/Domain/AdminService.php`: Nghiệp vụ kiểm duyệt tin, xác thực doanh nghiệp, bảo vệ Last Admin và audit log.
* **Frontend Assets & Views:**
  * `public/assets/js/api.js`: Client API tập trung bằng Vanilla JS (quản lý token, gọi fetch, escape XSS).
  * `public/assets/js/main.js`: Điều hướng giao diện người dùng, navbar và drawer menu.
  * `public/assets/css/style.css`: Stylesheet responsive dùng chung cho toàn bộ website.
  * `app/Views/layouts/main.php`: Layout HTML5 khung của ứng dụng.
  * `app/Views/`: Các thư mục view giao diện (`jobs/`, `student/`, `company/`, `admin/`, `auth/`).
* **Automated Test Suites (Dùng để kiểm thử hồi quy):**
  * `test_browser_e2e_fe3.php`: Bộ test tự động kiểm tra trọn vẹn 4 luồng người dùng E2E trên trình duyệt.
  * `test_fe2a.php`, `test_fe2b.php`, `test_fe2c.php`: Bộ test chuyên biệt cho từng cổng người dùng (Sinh viên, Doanh nghiệp, Admin).
  * `test_api.php`, `test_e2e.php`: Bộ test kiểm tra nền tảng REST API và tích hợp liên hoàn.
