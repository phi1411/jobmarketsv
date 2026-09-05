# TÀI LIỆU CHI TIẾT API (RESTFUL API SPECIFICATION)
## Nền Tảng Tuyển Dụng & Tìm Việc Part-time Sinh Viên

- **Base URL:** `http://manguonmo.test` (hoặc `http://localhost:8000`)
- **Định dạng dữ liệu:** `application/json`
- **Tiêu chuẩn xác thực:** `Authorization: Bearer <JWT_TOKEN>`
- **Ghi chú Role:** Vai trò Nhà tuyển dụng / Công ty trong hệ thống được định danh là role `company` (tương đương `employer`).

---

## 1. QUY ƯỚC CHUNG

### 1.1. Cấu trúc Response Thành công (HTTP 200, 201)
```json
{
  "success": true,
  "message": "Thông điệp mô tả kết quả thành công.",
  "data": { ... },
  "meta": {
    "page": 1,
    "per_page": 10,
    "total": 50,
    "total_pages": 5
  }
}
```

### 1.2. Cấu trúc Response Thất bại (HTTP 400, 401, 403, 404, 422, 500)
```json
{
  "success": false,
  "message": "Thông điệp mô tả nguyên nhân lỗi.",
  "errors": {
    "field_name": [
      "Chi tiết lỗi của trường dữ liệu này."
    ]
  },
  "code": 422
}
```

---

## 2. BẢNG MÃ HTTP STATUS CODE ĐƯỢC SỬ DỤNG

| Mã HTTP | Tên chuẩn | Ý nghĩa trong hệ thống |
| :---: | :--- | :--- |
| **200** | OK | Xử lý yêu cầu đọc / cập nhật / đóng / đăng nhập thành công. |
| **201** | Created | Tạo mới tài nguyên thành công (Đăng ký tài khoản, Tạo Job mới). |
| **204** | No Content | Phản hồi thành công không có nội dung body (CORS Pre-flight `OPTIONS`). |
| **400** | Bad Request | Yêu cầu không hợp lệ hoặc dữ liệu sai quy cách. |
| **401** | Unauthorized | Thiếu token, token hết hạn, chữ ký sai, hoặc sai mật khẩu đăng nhập. |
| **403** | Forbidden | Người dùng không đủ quyền truy cập (Role không phải company hoặc sửa job công ty khác). |
| **404** | Not Found | Đường dẫn (Route) hoặc việc làm (ID) không tồn tại. |
| **405** | Method Not Allowed | Sai phương thức HTTP trên đường dẫn hợp lệ. |
| **422** | Unprocessable Entity | Lỗi dữ liệu đầu vào không vượt qua bộ quy tắc Validation. |
| **500** | Internal Server Error | Lỗi hệ thống nội bộ (được ghi log an toàn vào `storage/logs/app.log`). |

---

## 3. DANH SÁCH ENDPOINTS CHI TIẾT

### 3.1. HỆ THỐNG & SỨC KHỎE (SYSTEM)

#### `GET /`
- **Mô tả:** Kiểm tra trạng thái hoạt động của hệ thống (Health check).
- **Quyền truy cập:** Công khai (Public).

---

### 3.2. XÁC THỰC NGƯỜI DÙNG (AUTHENTICATION)

#### `POST /register`
- **Mô tả:** Đăng ký tài khoản mới (Sinh viên `student`, Công ty `company`).
- **Quyền truy cập:** Công khai (Public).

#### `POST /login`
- **Mô tả:** Đăng nhập hệ thống bằng Email và Mật khẩu để nhận JWT Bearer Token.
- **Quyền truy cập:** Công khai (Public).

---

### 3.3. MODULE JOB POSTING CHO VIỆC LÀM PART-TIME (MILESTONE 2A)

#### 1. `POST /jobs` – Đăng tin tuyển dụng mới
- **Quyền truy cập:** Yêu cầu xác thực (`Authorization: Bearer <TOKEN>`), chỉ tài khoản có role `company`.
- **Ràng buộc nghiệp vụ:**
  - `company_id` tự động lấy từ thông tin công ty của tài khoản đăng nhập (chống IDOR).
  - Công ty phải có `verification_status = 'verified'` mới được để trạng thái `published` (nếu chưa verified chỉ được để `draft` hoặc `pending_approval`).
  - `application_deadline` không được ở trong quá khứ.
  - `salary_min` không được lớn hơn `salary_max`.
  - Bắt buộc các trường khi publish: `title` ($\ge 5$ ký tự), `description` ($\ge 10$ ký tự), `category_id`, `application_deadline`.
- **Request Body mẫu:**
```json
{
  "title": "Nhân viên pha chế Part-time ca chiều",
  "description": "Thực hiện pha chế trà sữa, cà phê theo công thức của quán, vệ sinh quầy bar.",
  "requirements": "Nhanh nhẹn, trung thực, ưu tiên sinh viên có kinh nghiệm pha chế.",
  "benefits": "Hỗ trợ bữa ăn giữa ca, giảm 50% đồ uống, thưởng doanh số hàng tháng.",
  "category_id": "cat-001",
  "location_id": "loc-001",
  "city": "Hà Nội",
  "district": "Cầu Giấy",
  "address": "102 Trần Thái Tông, Cầu Giấy",
  "work_type": "part_time",
  "work_mode": "onsite",
  "salary_type": "hourly",
  "salary_min": 26000,
  "salary_max": 32000,
  "currency": "VND",
  "shift_type": "afternoon",
  "shift_information": "Ca từ 13h00 đến 18h00 các ngày trong tuần",
  "working_schedule": "Đăng ký lịch linh hoạt theo tuần",
  "required_skills": "Pha chế, Giao tiếp, Tiếng Anh cơ bản",
  "quantity": 3,
  "application_deadline": "2026-10-15",
  "status": "published"
}
```
- **Response Thành công (201 Created):** Trả về toàn bộ chi tiết tin việc làm vừa tạo.

---

#### 2. `GET /jobs` – Tìm kiếm & Lọc việc làm (Public)
- **Quyền truy cập:** Công khai (Public).
- **Quy tắc hiển thị:** Chỉ trả về các job có `status = 'published'`, chưa `closed`, và `application_deadline >= ngày hiện tại`.
- **Bộ lọc (Query Parameters):**
  - `keyword`: Tìm kiếm từ khóa trong tiêu đề, mô tả, yêu cầu, quyền lợi.
  - `category_id`: Lọc theo mã ngành nghề.
  - `location_id`: Lọc theo địa điểm.
  - `city`: Lọc theo tên thành phố (LIKE).
  - `district`: Lọc theo quận/huyện (LIKE).
  - `work_type`: `part_time`, `internship`, `freelance`.
  - `work_mode`: `onsite`, `remote`, `hybrid`.
  - `shift_type`: `morning`, `afternoon`, `evening`, `night`, `rotating`, `weekend`, `flexible`.
  - `salary_type`: `hourly`, `daily`, `monthly`, `negotiable`.
  - `salary_min`: Mức lương tối thiểu.
  - `salary_max`: Mức lương tối đa.
  - `skill_id`: Tìm kỹ năng trong danh sách `required_skills`.
  - `sort_by`: Whitelist: `newest` (mặc định), `salary_desc`, `salary_asc`, `created_at`, `title`.
  - `sort_dir`: `DESC` (mặc định) hoặc `ASC`.
  - `page`: Trang hiện tại (mặc định 1).
  - `per_page`: Số lượng tin mỗi trang (mặc định 10, tối đa 100).
- **Response (200 OK):** Trả về danh sách jobs kèm `meta` phân trang.

---

#### 3. `GET /jobs/{id}` – Xem chi tiết việc làm
- **Quyền truy cập:** Công khai (Public).
- **Quy tắc hiển thị:** Trả về chi tiết việc làm JOIN với tên công ty, hotline, địa chỉ, logo và trạng thái xác thực. Nếu job đã đóng hoặc hết hạn, trả về `404 Not Found`.

---

#### 4. `PUT /jobs/{id}` & `PATCH /jobs/{id}` – Cập nhật tin việc làm
- **Quyền truy cập:** Yêu cầu xác thực (`Authorization: Bearer <TOKEN>`).
- **Quy tắc sở hữu (Ownership):** Chỉ công ty tạo ra job này mới có quyền cập nhật (nếu cố tình sửa job công ty khác $\rightarrow$ Trả về `403 Forbidden`).
- **Response (200 OK):** Trả về tin việc làm sau khi cập nhật thành công.

---

#### 5. `POST /jobs/{id}/close` & `PUT /jobs/{id}/close` – Đóng tin việc làm
- **Quyền truy cập:** Yêu cầu xác thực (`Authorization: Bearer <TOKEN>`), kiểm tra ownership.
- **Mô tả:** Chuyển trạng thái job sang `closed`. Ngay lập tức job sẽ không còn xuất hiện trong danh sách `GET /jobs` công khai.
- **Response (200 OK):** Trả về job với `status: "closed"`.

---

#### 6. `DELETE /jobs/{id}` – Xóa tin việc làm
- **Quyền truy cập:** Yêu cầu xác thực (`Authorization: Bearer <TOKEN>`), kiểm tra ownership.
- **Mô tả:** Thực hiện soft-delete (gán `deleted_at = NOW()`, `status = 'closed'`).
- **Response (200 OK):** `{"success": true, "message": "Xóa tin tuyển dụng thành công.", "data": null}`.

---

#### 7. `GET /company/jobs` – Danh sách việc làm của chính công ty đang đăng nhập
- **Quyền truy cập:** Yêu cầu xác thực (`Authorization: Bearer <TOKEN>`), role `company`.
- **Mô tả:** Trả về toàn bộ các tin tuyển dụng của công ty (kể cả tin `draft`, `pending_approval`, `closed`, `expired`) phục vụ quản lý nội bộ.
- **Hỗ trợ Query:** Phân trang (`page`, `per_page`), lọc theo `status`.

---

#### 8. `GET /companies/{id}/jobs` – Danh sách việc làm công khai của một công ty
- **Quyền truy cập:** Công khai (Public).
- **Mô tả:** Người tìm việc / sinh viên xem danh sách các tin việc làm đang công khai (`published`) của một công ty đối tác.

---

---

### 3.4. MODULE QUẢN LÝ HỒ SƠ SINH VIÊN (STUDENT PROFILE - MILESTONE 2B)

#### 1. `GET /student/profile` (hoặc `GET /profile`) – Xem hồ sơ sinh viên cá nhân (Private)
- **Quyền truy cập:** Yêu cầu xác thực (`Authorization: Bearer <TOKEN>`), chỉ tài khoản role `student`.
- **Ràng buộc:** Công ty (`company`) hoặc người dùng khác truy cập sẽ nhận `403 Forbidden`.
- **Dữ liệu trả về (Private Profile):** Trả về đầy đủ thông tin cá nhân bao gồm: `id`, `user_id`, `full_name`, `email`, `phone`, `date_of_birth`, `gender`, `university`, `major`, `academic_year`, `bio`, `location_id`, `preferred_location`, `preferred_locations`, `availability_schedule`, `skill_ids`, `skills`, `work_experience`, `education`, `certificates`, `cv_url`, `profile_completion_percent`.
- **Response Thành công (200 OK):**
```json
{
  "success": true,
  "message": "Thông tin hồ sơ sinh viên cá nhân.",
  "data": {
    "id": "profile-st-01",
    "user_id": "user-student-01",
    "full_name": "Nguyễn Văn Sinh Viên",
    "email": "sinhvien1@jobmarket.vn",
    "phone": "0901112233",
    "date_of_birth": "2004-05-15",
    "gender": "male",
    "university": "Đại học Quốc Gia Hà Nội",
    "major": "Công nghệ thông tin",
    "academic_year": 2,
    "bio": "Sinh viên năm 2 nhiệt tình, cẩn thận...",
    "location_id": "loc-001",
    "preferred_location": "Cầu Giấy",
    "availability_schedule": {
      "shifts": ["evening", "weekend"]
    },
    "skill_ids": ["sk-001", "sk-004"],
    "skills": "Giao tiếp, Tin học văn phòng",
    "cv_url": "https://example.com/cv.pdf",
    "profile_completion_percent": 100
  },
  "meta": null
}
```

---

#### 2. `PUT /student/profile` (hoặc `PATCH /student/profile`, `PUT /profile`) – Cập nhật hồ sơ sinh viên
- **Quyền truy cập:** Yêu cầu xác thực (`Authorization: Bearer <TOKEN>`), role `student`.
- **Bảo mật Anti-Mass Assignment:** Bỏ qua các trường `user_id`, `role`, `profile_completion_percent` nếu client gửi lên.
- **Ràng buộc nghiệp vụ:**
  - `skill_ids`: Mảng các ID kỹ năng, tất cả phải tồn tại trong bảng `skills`.
  - `location_id`: ID địa điểm phải tồn tại trong bảng `locations`.
  - `availability_schedule`: Phải là JSON hoặc cấu trúc mảng hợp lệ.
  - `cv_url`: Phải là URL hợp lệ (chưa hỗ trợ upload file trong milestone này).
  - `profile_completion_percent`: Tự động tính toán từ máy chủ theo tỷ lệ hoàn thiện các trường dữ liệu.
- **Request Body mẫu:**
```json
{
  "full_name": "Nguyễn Văn Sinh Viên",
  "phone": "0901112233",
  "date_of_birth": "2004-05-15",
  "gender": "male",
  "university": "Đại học Quốc Gia Hà Nội",
  "major": "Công nghệ phần mềm",
  "academic_year": 3,
  "bio": "Sinh viên nhiệt tình, có kinh nghiệm phục vụ và giao tiếp tốt.",
  "location_id": "loc-001",
  "preferred_locations": "Cầu Giấy, Bắc Từ Liêm",
  "skill_ids": ["sk-001", "sk-004"],
  "availability_schedule": {
    "monday": ["morning", "afternoon"],
    "weekend": ["all_day"]
  },
  "work_experience": "Từng làm nhân viên thu ngân tại Highlands 6 tháng.",
  "education": "Đang học cử nhân CNTT K67 ĐHQGHN",
  "certificates": "IELTS 6.5, Chứng chỉ tin học MOS",
  "cv_url": "https://example.com/cv-update.pdf"
}
```
- **Response Thành công (200 OK):** Trả về toàn bộ hồ sơ sinh viên sau khi cập nhật kèm % hoàn thiện mới.

---

#### 3. `GET /developers/{id}` – Xem hồ sơ công khai của ứng viên / sinh viên
- **Quyền truy cập:** Công khai (Public).
- **Quy tắc bảo mật:** **ẨN TUYỆT ĐỐI** các thông tin nhạy cảm: `email`, `phone`, `cv_url`, mật khẩu, và token.
- **Dữ liệu trả về (Public Whitelist):** `id`, `user_id`, `full_name`, `university`, `major`, `academic_year`, `bio`, `preferred_location`, `skills`, `availability_schedule`, `work_experience`, `education`, `certificates`, `profile_completion_percent`.
- **Response Thành công (200 OK):**
```json
{
  "success": true,
  "message": "Thông tin hồ sơ ứng viên công khai.",
  "data": {
    "id": "profile-st-01",
    "user_id": "user-student-01",
    "full_name": "Nguyễn Văn Sinh Viên",
    "university": "Đại học Quốc Gia Hà Nội",
    "major": "Công nghệ thông tin",
    "academic_year": 2,
    "bio": "Sinh viên năm 2 nhiệt tình, cẩn thận...",
    "preferred_location": "Cầu Giấy, Nam Từ Liêm",
    "skills": "Giao tiếp, Tin học văn phòng",
    "availability_schedule": {
      "shifts": ["evening", "weekend"]
    },
    "profile_completion_percent": 100
  },
  "meta": null
}
```

---

#### 4. `GET /developers` – Danh sách ứng viên sinh viên công khai
- **Quyền truy cập:** Công khai (Public).
- **Hỗ trợ Query:**
  - `keyword`: Tìm theo tên, ngành học, kỹ năng, giới thiệu.
  - `university`: Lọc theo trường đại học.
  - `academic_year`: Lọc theo năm học (1 - 7).
  - `location_id`: Lọc theo địa điểm.
  - `sort_by`: `created_at`, `profile_completion_percent`, `academic_year`.
  - `page`, `per_page`: Phân trang chuẩn `meta`.

---

---

### 3.5. MODULE QUẢN LÝ ĐƠN ỨNG TUYỂN (APPLICATION MANAGEMENT - MILESTONE 2C)

#### 1. `POST /jobs/{id}/applications` – Sinh viên nộp đơn ứng tuyển
- **Quyền truy cập:** Yêu cầu xác thực (`Authorization: Bearer <TOKEN>`), role `student`.
- **Ràng buộc nghiệp vụ:**
  - Tin tuyển dụng phải ở trạng thái `published`, chưa bị xóa mềm, chưa bị đóng, và hạn nộp chưa kết thúc.
  - Sinh viên chỉ được nộp đơn **1 lần duy nhất** cho mỗi tin việc làm. Nộp lần 2 sẽ bị từ chối với HTTP 409 Conflict.
  - Bỏ qua các trường `status`, `employer_note` nếu client cố tình gửi (Anti-Mass Assignment).
  - Tự động lấy snapshot CV từ hồ sơ sinh viên (`student_profiles.cv_url`).
- **Request Body mẫu:**
```json
{
  "cover_letter": "Em chào anh/chị, em là sinh viên năm 2 ĐHQGHN, mong muốn ứng tuyển vị trí phục vụ part-time...",
  "preferred_shift": "evening",
  "cv_url_snapshot": "https://example.com/cv.pdf"
}
```
- **Response Thành công (201 Created):**
```json
{
  "success": true,
  "message": "Nộp đơn ứng tuyển thành công.",
  "data": {
    "id": "app-6a99701a23b",
    "job_id": "job-001",
    "job_title": "Nhân viên phục vụ & Phụ quầy Part-time (Ca sáng/tối)",
    "company_name": "Highlands Coffee Việt Nam",
    "student_user_id": "user-student-01",
    "cover_letter": "Em chào anh/chị...",
    "cv_url_snapshot": "https://example.com/cv.pdf",
    "preferred_shift": "evening",
    "status": "pending",
    "applied_at": "2026-09-03 19:40:00"
  },
  "meta": null
}
```

---

#### 2. `GET /student/applications` & `GET /applications/me` – Sinh viên xem danh sách đơn đã nộp
- **Quyền truy cập:** Yêu cầu xác thực (`Authorization: Bearer <TOKEN>`), role `student`.
- **Bảo mật:** Tuyệt đối **KHÔNG hiển thị** trường `employer_note`.
- **Hỗ trợ Query:** `status` (pending, viewed, shortlisted, rejected, accepted, withdrawn), phân trang `page`, `per_page`.

---

#### 3. `GET /jobs/{id}/applications` – Nhà tuyển dụng xem danh sách đơn của một công việc
- **Quyền truy cập:** Yêu cầu xác thực (`Authorization: Bearer <TOKEN>`), role `company`.
- **Ràng buộc kiểm tra quyền sở hữu (Company Isolation):** Chỉ công ty sở hữu công việc này mới có quyền xem đơn. Công ty khác truy cập sẽ nhận `403 Forbidden`.
- **Dữ liệu trả về:** Đầy đủ thông tin ứng viên (họ tên, email, sđt, trường, ngành học, thư ứng tuyển, CV snapshot, ca mong muốn, ghi chú tuyển dụng `employer_note`).

---

#### 4. `GET /company/applications` – Nhà tuyển dụng xem tất cả đơn ứng tuyển của các job thuộc công ty mình
- **Quyền truy cập:** Yêu cầu xác thực (`Authorization: Bearer <TOKEN>`), role `company`.
- **Hỗ trợ Query:** `job_id`, `status`, phân trang `page`, `per_page`.

---

#### 5. `GET /applications/{id}` – Xem chi tiết đơn ứng tuyển
- **Quyền truy cập:** Yêu cầu xác thực.
- **Ràng buộc:**
  - Nếu là Sinh viên: Chỉ được xem đơn của chính mình, `employer_note` bị ẩn.
  - Nếu là Nhà tuyển dụng: Chỉ được xem đơn của công việc thuộc công ty mình. Tự động chuyển `status` từ `pending` sang `viewed` khi công ty mở xem lần đầu.

---

#### 6. `PATCH /applications/{id}/status` & `PUT /applications/{id}/status` – Cập nhật trạng thái đơn ứng tuyển
- **Quyền truy cập:** Yêu cầu xác thực, role `company`, kiểm tra quyền sở hữu công việc.
- **Request Body mẫu:**
```json
{
  "status": "shortlisted",
  "employer_note": "Ứng viên giao tiếp tốt, mời phỏng vấn lúc 9h sáng thứ 6."
}
```
- **Cho phép các trạng thái:** `pending`, `viewed`, `shortlisted`, `accepted`, `rejected`.

---

#### 7. `POST /applications/{id}/withdraw` & `PATCH /applications/{id}/withdraw` – Sinh viên rút đơn ứng tuyển
- **Quyền truy cập:** Yêu cầu xác thực, role `student`, kiểm tra quyền sở hữu đơn.
- **Ràng buộc nghiệp vụ:** Chỉ được rút đơn khi trạng thái đang là `pending` hoặc `viewed`. Nếu đơn đã ở trạng thái `shortlisted`, `accepted`, `rejected`, hoặc đã `withdrawn`, hệ thống từ chối với HTTP 422.

---

---

### 3.6. MODULE VIỆC LÀM YÊU THÍCH & LƯU TÌM KIẾM (FAVORITES & SAVED SEARCHES - MILESTONE 2D)

> [!NOTE]
> **Hướng phát triển Search (Backlog kỹ thuật):** Công cụ tìm kiếm `GET /jobs` hiện tại áp dụng thuật toán tìm kiếm đa tiêu chí bằng SQL LIKE đã chuẩn hóa, sử dụng PDO prepared statements và hàm thoát ký tự `QueryHelper::escapeLike()` nhằm chống triệt để tấn công SQL Injection. Khi số lượng việc làm mở rộng trên 100.000 bản ghi, hệ thống sẽ nâng cấp sang MySQL FULLTEXT Search (`MATCH(...) AGAINST(... IN BOOLEAN MODE)`) hoặc tích hợp search engine chuyên dụng (Elasticsearch / Meilisearch).

#### 1. `POST /favorites/jobs/{jobId}` – Lưu việc làm vào danh sách yêu thích
- **Quyền truy cập:** Yêu cầu xác thực (`Authorization: Bearer <TOKEN>`), role `student`.
- **Ràng buộc nghiệp vụ:**
  - Chỉ cho phép lưu các việc làm `published`, chưa bị đóng, chưa hết hạn và chưa bị xóa mềm.
  - Cơ chế **Idempotent**: Nếu việc làm đã được lưu trước đó, hệ thống trả về thông báo đã tồn tại mà không ném lỗi trùng lặp (`{"favorited": true, "is_new": false, ...}`).
- **Response Thành công (201 Created / 200 OK):**
```json
{
  "success": true,
  "message": "Đã lưu việc làm vào danh sách yêu thích thành công.",
  "data": {
    "favorited": true,
    "is_new": true,
    "id": "fav-6a997230b41"
  },
  "meta": null
}
```

---

#### 2. `DELETE /favorites/jobs/{jobId}` – Xóa việc làm khỏi danh sách yêu thích
- **Quyền truy cập:** Yêu cầu xác thực (`Authorization: Bearer <TOKEN>`), role `student`.
- **Response Thành công (200 OK):**
```json
{
  "success": true,
  "message": "Đã xóa việc làm khỏi danh sách yêu thích.",
  "data": null,
  "meta": null
}
```

---

#### 3. `GET /favorites/jobs` – Xem danh sách việc làm yêu thích của sinh viên
- **Quyền truy cập:** Yêu cầu xác thực, role `student`.
- **Dữ liệu trả về:** Danh sách việc làm đã lưu (JOIN với thông tin công ty, địa chỉ, mức lương, ca làm việc) kèm phân trang `meta`.

---

#### 4. `POST /saved-searches` – Lưu bộ lọc tìm kiếm thường dùng
- **Quyền truy cập:** Yêu cầu xác thực, role `student`.
- **Ràng buộc xác thực (Validation):**
  - `name`: Tên bộ lọc là bắt buộc (tối đa 150 ký tự).
  - `category_id`: Phải tồn tại trong bảng `categories` nếu được gửi.
  - `location_id`: Phải tồn tại trong bảng `locations` nếu được gửi.
  - `skill_ids`: Danh sách ID kỹ năng phải tồn tại trong bảng `skills`.
  - `frequency`: `daily` hoặc `weekly`.
- **Request Body mẫu:**
```json
{
  "name": "Việc làm ca tối Cầu Giấy lương trên 25k",
  "keyword": "phục vụ",
  "category_id": "cat-001",
  "location_id": "loc-001",
  "shift_type": "evening",
  "salary_min": 25000,
  "frequency": "daily",
  "notification_enabled": true
}
```
- **Response Thành công (201 Created):**
```json
{
  "success": true,
  "message": "Lưu bộ lọc tìm kiếm thành công.",
  "data": {
    "id": "ss-6a9972314a1",
    "student_user_id": "user-student-01",
    "name": "Việc làm ca tối Cầu Giấy lương trên 25k",
    "keyword": "phục vụ",
    "category_id": "cat-001",
    "location_id": "loc-001",
    "shift_type": "evening",
    "salary_min": 25000,
    "frequency": "daily",
    "notification_enabled": true
  },
  "meta": null
}
```

---

#### 5. `GET /saved-searches` – Xem danh sách các bộ lọc tìm kiếm đã lưu
- **Quyền truy cập:** Yêu cầu xác thực, role `student`.
- **Ràng buộc:** Chỉ xem các bộ lọc của chính mình kèm phân trang `meta`.

---

#### 6. `PATCH /saved-searches/{id}` & `PUT /saved-searches/{id}` – Cập nhật bộ lọc tìm kiếm đã lưu
- **Quyền truy cập:** Yêu cầu xác thực, role `student`.
- **Ràng buộc:** Kiểm tra quyền sở hữu (Sinh viên A không được sửa bộ lọc của Sinh viên B $\rightarrow$ `403 Forbidden`).

---

#### 7. `DELETE /saved-searches/{id}` – Xóa bộ lọc tìm kiếm đã lưu
- **Quyền truy cập:** Yêu cầu xác thực, role `student`.
- **Ràng buộc:** Kiểm tra quyền sở hữu (Sinh viên A không được xóa bộ lọc của Sinh viên B $\rightarrow$ `403 Forbidden`).

---

---

### 3.7. MODULE THÔNG BÁO NỘI BỘ & DASHBOARD TỔNG QUAN (MILESTONE 2E)

#### 1. `GET /notifications` – Xem danh sách thông báo của tài khoản
- **Quyền truy cập:** Yêu cầu xác thực (`Authorization: Bearer <TOKEN>`).
- **Bảo mật:** Chỉ trả về thông báo thuộc về chính tài khoản đang đăng nhập.
- **Hỗ trợ phân trang:** `page`, `per_page` với đối tượng `meta` chuẩn hóa.

---

#### 2. `GET /notifications/unread-count` – Đếm số thông báo chưa đọc
- **Quyền truy cập:** Yêu cầu xác thực.
- **Response Thành công (200 OK):**
```json
{
  "success": true,
  "message": "Số thông báo chưa đọc.",
  "data": {
    "unread_count": 3
  },
  "meta": null
}
```

---

#### 3. `PATCH /notifications/{id}/read` & `PUT /notifications/{id}/read` – Đánh dấu 1 thông báo đã đọc
- **Quyền truy cập:** Yêu cầu xác thực.
- **Ràng buộc:** Kiểm tra quyền sở hữu (Người dùng A không được đánh dấu thông báo của Người dùng B $\rightarrow$ `403 Forbidden`).

---

#### 4. `PATCH /notifications/read-all` & `PUT /notifications/read-all` – Đánh dấu tất cả thông báo đã đọc
- **Quyền truy cập:** Yêu cầu xác thực.
- **Kết quả:** Cập nhật toàn bộ thông báo chưa đọc của người dùng hiện tại sang trạng thái đã đọc (`read_at = NOW()`).

---

#### 5. `GET /student/dashboard` – Bảng tổng quan thông tin dành cho Sinh viên
- **Quyền truy cập:** Yêu cầu xác thực, role `student`.
- **Dữ liệu trả về:**
  - `applications`: Thống kê số lượng đơn ứng tuyển theo trạng thái (`pending`, `viewed`, `shortlisted`, `accepted`, `rejected`, `withdrawn`, `total`).
  - `expiring_favorites`: Top 5 việc làm yêu thích sắp hết hạn nộp hồ sơ.
  - `unread_notifications`: Số lượng thông báo chưa đọc.
  - `recent_notifications`: Danh sách 5 thông báo mới nhất.

---

#### 6. `GET /company/dashboard` – Bảng tổng quan thông tin dành cho Nhà tuyển dụng
- **Quyền truy cập:** Yêu cầu xác thực, role `company`.
- **Dữ liệu trả về:**
  - `jobs`: Thống kê việc làm theo trạng thái (`published`, `draft`, `closed`, `pending_approval`, `total`).
  - `applications`: Thống kê đơn ứng tuyển theo trạng thái.
  - `recent_applications`: Danh sách 5 đơn ứng tuyển mới nhất nộp vào công ty (Ẩn hoàn toàn thông tin cá nhân nhạy cảm).
  - `top_jobs`: Top việc làm có nhiều lượt ứng tuyển nhất.
  - `unread_notifications`: Số lượng thông báo chưa đọc.

---

#### 7. `GET /admin/dashboard` – Bảng tổng quan quản trị hệ thống
- **Quyền truy cập:** Yêu cầu xác thực, role `admin`.
- **Dữ liệu trả về:** Thống kê tổng số người dùng theo vai trò, công ty theo trạng thái xác thực, việc làm và đơn ứng tuyển trên toàn hệ thống.

---

---

### 3.8. MODULE QUẢN TRỊ & KIỂM DUYỆT HỆ THỐNG (ADMIN MODERATION - MILESTONE 2F)

> [!IMPORTANT]
> Toàn bộ các endpoint `/admin/*` bắt buộc yêu cầu xác thực với vai trò **`admin`**. Người dùng vai trò `student`, `company` hoặc khách vãng lai gọi vào sẽ bị từ chối truy cập với mã lỗi **HTTP 403 Forbidden** hoặc **HTTP 401 Unauthorized**.

#### 1. `GET /admin/users` – Quản trị viên xem danh sách người dùng
- **Hỗ trợ Query:** `role` (student, company, admin), `status` (active, suspended, banned), `keyword`, phân trang `page`, `per_page`.
- **Bảo mật:** Loại bỏ hoàn toàn hash mật khẩu và token khỏi response.

---

#### 2. `GET /admin/users/{id}` – Xem chi tiết người dùng

---

#### 3. `PATCH /admin/users/{id}/status` & `PUT /admin/users/{id}/status` – Cập nhật trạng thái người dùng
- **Request Body mẫu:**
```json
{
  "status": "suspended"
}
```
- **Ràng buộc an toàn:** Hệ thống **ngăn chặn** việc vô hiệu hóa tài khoản quản trị viên duy nhất còn hoạt động (`HTTP 422`).

---

#### 4. `GET /admin/companies` – Xem danh sách công ty / nhà tuyển dụng
- **Hỗ trợ Query:** `verification_status` (pending, verified, rejected), `keyword`, phân trang `page`, `per_page`.

---

#### 5. `PATCH /admin/companies/{id}/verification` – Cập nhật trạng thái xác thực công ty
- **Request Body mẫu:**
```json
{
  "verification_status": "rejected",
  "rejection_reason": "Giấy phép đăng ký kinh doanh không rõ ràng, vui lòng gửi lại bản scan có công chứng."
}
```
- **Ràng buộc:** Bắt buộc nhập `rejection_reason` khi `verification_status` là `rejected`.
- **Sự kiện Thông báo:** Tự động gửi thông báo nội bộ cho tài khoản chủ doanh nghiệp.

---

#### 6. `GET /admin/jobs` – Xem toàn bộ tin tuyển dụng toàn hệ thống
- Hiển thị cả tin `draft`, `pending_approval`, `published`, `rejected`, `hidden`, `closed`, `expired`.

---

#### 7. `PATCH /admin/jobs/{id}/moderation` – Kiểm duyệt tin tuyển dụng
- **Request Body mẫu:**
```json
{
  "status": "published"
}
```
- **Cho phép các giá trị:** `published`, `rejected`, `hidden`, `draft`, `pending_approval`, `closed`, `expired`.
- **Ràng buộc:** Bắt buộc nhập `rejection_reason` khi `status` là `rejected`.
- **Sự kiện Thông báo:** Tự động gửi thông báo nội bộ cho tài khoản chủ doanh nghiệp sở hữu tin.

---

#### 8. `GET /admin/audit-logs` – Xem nhật ký thao tác quản trị
- **Dữ liệu trả về:** `actor_id`, `actor_name`, `action`, `target_type`, `target_id`, `metadata`, `created_at` kèm phân trang `meta`.

---

### 3.9. METADATA THAM CHIẾU
- `GET /categories`: Danh sách ngành nghề.
- `GET /skills`: Danh sách kỹ năng.
- `GET /locations`: Danh sách địa điểm.

---

### 3.10. GHI CHÚ PHẠM VI MVP & TỒN ĐỌNG KỸ THUẬT
1. **Quản lý Hồ sơ CV:** Hệ thống hiện tại trong giai đoạn MVP chỉ hỗ trợ lưu trữ liên kết hồ sơ `cv_url` và chụp nhanh snapshot văn bản lúc nộp đơn. **Chưa hỗ trợ** endpoint upload tệp multipart/form-data trực tiếp lên server nhằm đảm bảo an toàn tệp tin (phòng chống mã độc/malware).
2. **Thu hồi Token JWT (Token Revocation):** Token hoạt động theo cơ chế stateless (không lưu trạng thái). Khi người dùng logout hoặc bị Admin đổi trạng thái `suspended`/`banned`, token cũ có hiệu lực đến hết TTL. Hướng xử lý tiếp theo: Bổ sung Redis Blacklist.
3. **Kênh Thông Báo:** Hệ thống hiện chỉ xử lý In-App Notifications; chưa tích hợp SMTP/SendGrid (Email) và FCM (Push Notification).
4. **Tự động Hết Hạn Tin Tuyển Dụng:** Tin tuyển dụng quá hạn được lọc tự động tại câu lệnh SQL (`application_deadline >= CURDATE()`). Cần cron job định kỳ để cập nhật trạng thái `status = 'expired'`.






