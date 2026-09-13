# Tạo CV Online / Mẫu CV sinh viên

## 1. Mục tiêu sản phẩm

Tính năng cung cấp một trình tạo CV online cho sinh viên ngay trong JobMarketplace. Người dùng có thể tạo nhiều CV cho các mục tiêu ứng tuyển khác nhau, quản lý nội dung có cấu trúc, đổi mẫu và phong cách mà không làm mất dữ liệu, xem trước, xuất PDF, chọn một CV chính và chia sẻ một CV bằng liên kết công khai có thể tắt bất cứ lúc nào.

Phiên bản đầu ưu tiên ba nhu cầu thường gặp của sinh viên: CV thực tập, CV việc làm đầu tiên và CV ATS đơn giản. Thanh toán, AI viết nội dung, import LinkedIn/PDF và thư xin việc chưa nằm trong phạm vi triển khai.

## 2. Kết quả nghiên cứu và quyết định phạm vi

TopCV mô tả luồng cốt lõi gồm chọn mẫu, chọn ngôn ngữ và màu, nhập hoặc tùy biến các mục, lưu, xem trước và tải PDF.^1 Nền tảng cũng cho phép dùng đường dẫn CV online để gửi nhà tuyển dụng.^2 Đây là các hành vi tạo nên giá trị chính, nên phiên bản này triển khai đầy đủ ở tầng dữ liệu và API.

Kho mẫu TopCV phân loại theo phong cách và đánh dấu các mẫu phù hợp ATS; trang hướng dẫn của họ nhấn mạnh bố cục đơn giản, rõ ràng giúp nội dung dễ đọc hơn đối với hệ thống sàng lọc.^3 Vì dự án hướng đến sinh viên và việc làm bán thời gian, danh mục ban đầu chỉ có ba mẫu có mục đích rõ ràng thay vì sao chép số lượng lớn mẫu:

| Mẫu | Mục đích | Đặc điểm xử lý |
|---|---|---|
| `student-simple` | CV sinh viên phổ thông | Một cột, ưu tiên học vấn, kỹ năng, dự án |
| `student-modern` | Thực tập/việc đầu tiên | Header có màu, nội dung vẫn có cấu trúc rõ |
| `ats-classic` | Nộp qua ATS | Đen trắng, một cột, không ảnh, không tài nguyên từ xa |

TopCV hiện còn có luồng khôi phục bản chưa lưu, tái sử dụng CV đã có, tải tệp và nhập từ LinkedIn.^4 Trong dự án này, hai nhu cầu đầu được giải quyết bằng autosave có `version` và chức năng nhân bản. Nhập tài liệu tự động được để cho giai đoạn sau vì cần một pipeline trích xuất, xác nhận dữ liệu và xử lý sai lệch riêng.

## 3. Phạm vi đã triển khai

- Nhiều CV trên một tài khoản, tối đa 20 CV đang hoạt động.
- CV đầu tiên tự động trở thành CV chính; chỉ một CV có thể là CV chính tại một thời điểm.
- Ba mẫu CV và bộ metadata để giao diện lọc/hiển thị.
- Nội dung CV có cấu trúc: cá nhân, mục tiêu, học vấn, kỹ năng, dự án, kinh nghiệm, hoạt động, chứng chỉ, giải thưởng, ngoại ngữ, sở thích và mục tùy chỉnh.
- Đổi thứ tự mục và ẩn/hiện mục mà không xóa nội dung.
- Tùy chỉnh màu nhấn, phông, cỡ chữ, giãn dòng; khổ giấy A4.
- Tiếng Việt và tiếng Anh.
- Tính phần trăm hoàn thiện trên máy chủ.
- Autosave với optimistic locking bằng `expected_version`; bản lưu cũ nhận HTTP `409` thay vì ghi đè dữ liệu mới.
- Nhân bản CV; bản sao luôn riêng tư và không tự trở thành CV chính.
- Chia sẻ công khai bằng slug ngẫu nhiên; chủ CV có thể tắt liên kết ngay.
- Preview HTML an toàn và xuất PDF A4 bằng Dompdf 3.1.6.
- Chuyển CV online thành PDF hoạt động trong hồ sơ để dùng ngay với luồng nộp đơn hiện có.
- Xóa mềm; CV đã xóa không còn công khai.

## 4. Mô hình dữ liệu

Bảng `online_cvs` lưu một tài liệu CV hoàn chỉnh. Nội dung, phong cách và cấu hình section là JSON để giao diện có thể thay đổi template mà không phải biến đổi dữ liệu nghiệp vụ.

| Cột | Ý nghĩa |
|---|---|
| `id` | ID mờ dạng `cv-...` |
| `user_id` | Chủ sở hữu; khóa ngoại tới `users` |
| `title` | Tên dùng trong trang quản lý CV |
| `template_key` | `student-simple`, `student-modern`, `ats-classic` |
| `language` | `vi` hoặc `en` |
| `content_json` | Nội dung CV có cấu trúc |
| `style_json` | Màu, font, cỡ chữ, giãn dòng, khổ giấy |
| `section_order_json` | Thứ tự render các section |
| `hidden_sections_json` | Section ẩn nhưng không mất dữ liệu |
| `completion_percent` | Điểm hoàn thiện 0–100 do server tính |
| `is_primary` | CV mặc định của sinh viên |
| `is_public` | Trạng thái liên kết chia sẻ |
| `public_slug` | Token ngẫu nhiên cho URL công khai |
| `version` | Phiên bản phục vụ autosave an toàn |
| `deleted_at` | Xóa mềm |

Payload `content` chuẩn:

```json
{
  "personal": {
    "full_name": "Nguyễn Văn An",
    "job_title": "PHP Intern",
    "email": "an@example.com",
    "phone": "0901234567",
    "address": "TP. Hồ Chí Minh",
    "website": "https://portfolio.example.com",
    "linkedin": "https://linkedin.com/in/example",
    "date_of_birth": "2004-10-20"
  },
  "summary": "Mục tiêu nghề nghiệp...",
  "education": [],
  "skills": [],
  "projects": [],
  "experience": [],
  "activities": [],
  "certifications": [],
  "awards": [],
  "languages": [],
  "interests": "",
  "custom_sections": []
}
```

Định dạng từng danh sách:

| Section | Trường |
|---|---|
| `education` | `school`, `degree`, `major`, `start_date`, `end_date`, `current`, `gpa`, `description` |
| `experience` | `organization`, `position`, `start_date`, `end_date`, `current`, `description` |
| `projects` | `name`, `role`, `url`, `start_date`, `end_date`, `current`, `description`, `technologies` |
| `skills` | `name`, `level` |
| `activities` | `organization`, `role`, `start_date`, `end_date`, `current`, `description` |
| `certifications` | `name`, `issuer`, `issued_date`, `url` |
| `awards` | `name`, `issuer`, `issued_date`, `description` |
| `languages` | `name`, `level` |
| `custom_sections` | `id`, `title`, `items[]`; mỗi item có `title`, `subtitle`, `date`, `description` |

## 5. Hợp đồng API

Tất cả endpoint `/student/...` yêu cầu đăng nhập với vai trò `student` hoặc `developer`. Response JSON dùng envelope chuẩn của dự án: `success`, `message`, `data`, `meta`.

### Danh mục và quản lý

| Method | Endpoint | Công dụng |
|---|---|---|
| GET | `/cv/templates` | Danh mục mẫu công khai |
| GET | `/student/cvs` | Danh sách CV của người hiện tại |
| POST | `/student/cvs` | Tạo CV |
| GET | `/student/cvs/{id}` | Chi tiết CV thuộc sở hữu |
| PATCH/PUT | `/student/cvs/{id}` | Lưu một phần hoặc toàn bộ CV |
| DELETE | `/student/cvs/{id}` | Xóa mềm CV |
| POST | `/student/cvs/{id}/duplicate` | Nhân bản |
| POST | `/student/cvs/{id}/primary` | Đặt làm CV chính |
| POST | `/student/cvs/{id}/activate` | Đặt làm CV chính và tạo PDF hoạt động dùng khi ứng tuyển |
| PATCH | `/student/cvs/{id}/visibility` | Bật/tắt chia sẻ |

### Preview, PDF và chia sẻ

| Method | Endpoint | Công dụng |
|---|---|---|
| GET | `/student/cvs/{id}/preview` | HTML dùng cho iframe preview trong editor |
| GET | `/student/cvs/{id}/export.pdf` | Tải PDF riêng tư |
| GET | `/cv/{public_slug}` với `Accept: application/json` | Dữ liệu CV công khai |
| GET | `/cv/{public_slug}` với `Accept: text/html` | Trang CV công khai do server render |
| GET | `/cv/{public_slug}/export.pdf` | Tải PDF của CV công khai |

Ví dụ tạo CV:

```http
POST /student/cvs
Content-Type: application/json

{
  "title": "CV Thực tập Backend",
  "template_key": "student-simple",
  "language": "vi"
}
```

Ví dụ autosave:

```http
PATCH /student/cvs/cv-abc123
Content-Type: application/json

{
  "expected_version": 4,
  "content": {
    "summary": "Sinh viên năm ba định hướng Backend...",
    "skills": [
      {"name": "PHP", "level": "Khá"},
      {"name": "MySQL", "level": "Khá"}
    ]
  },
  "style": {"accent_color": "#2563eb"}
}
```

Server trả CV mới với `version: 5`. Nếu một tab khác đã lưu trước, server trả `409`; giao diện phải giữ bản nháp local và cho người dùng chọn tải bản server hoặc ghi lại sau khi so sánh.

Ví dụ bật link chia sẻ:

```http
PATCH /student/cvs/cv-abc123/visibility
Content-Type: application/json

{"is_public": true, "expected_version": 5}
```

CV chỉ được công khai khi có họ tên và ít nhất email hoặc số điện thoại.

Khi người dùng đã hoàn thiện CV và muốn dùng để nộp đơn, gọi `POST /student/cvs/{id}/activate` với `expected_version`. Server render PDF an toàn, lưu vào private CV storage và cập nhật active CV trong `student_profiles`, nên luồng ứng tuyển hiện có sử dụng được ngay. Đây là một snapshot; sau khi sửa CV online, giao diện cần cho người dùng bấm “Cập nhật CV ứng tuyển” để tạo snapshot mới.

## 6. Quy tắc giao diện phải tuân thủ

- Dữ liệu trên form là nguồn local trong lúc gõ; debounce autosave khoảng 800–1200 ms.
- Mọi lần lưu phải gửi `expected_version` gần nhất.
- Sau khi lưu thành công, thay state bằng object server trả về để nhận `version` và `completion_percent` mới.
- Không tự thử lại vô hạn khi gặp `409`.
- Thay đổi thứ tự dùng `section_order`; phần thông tin cá nhân luôn là header cố định. Nút ẩn mục dùng `hidden_sections`, không xóa nội dung; không cho ẩn toàn bộ `personal`.
- Preview iframe gọi URL `/student/cvs/{id}/preview` sau khi autosave thành công. Có thể dùng preview local để cảm giác gõ tức thì, nhưng PDF/server preview là kết quả chuẩn.
- Nút “Công khai” phải cảnh báo rõ rằng thông tin liên hệ sẽ xuất hiện trên Internet.
- Nút “Tải PDF” dùng endpoint server, không chụp ảnh DOM của editor.
- Nút “Dùng để ứng tuyển” gọi endpoint `activate`; nếu CV đã thay đổi sau lần kích hoạt, cho phép “Cập nhật CV ứng tuyển”.
- Màn hình quản lý hiển thị trạng thái CV chính, công khai/riêng tư, phần trăm hoàn thiện và thời điểm cập nhật.

## 7. An toàn và riêng tư

Input được kiểm tra theo allow-list ở cả cấp request, section và field; OWASP khuyến nghị thực hiện validation càng sớm càng tốt và kiểm tra cả cú pháp lẫn ngữ nghĩa.^5 Giới hạn độ dài và số phần tử ngăn payload quá lớn. URL chỉ chấp nhận `http` và `https`.

Mọi endpoint sửa/xem riêng tư đều truy vấn theo đồng thời `id` và `user_id`; không dựa vào ID do client gửi để xác định chủ sở hữu. Renderer escape toàn bộ nội dung người dùng trước khi tạo HTML. Dompdf tắt tài nguyên từ xa và PHP nội tuyến, vì vậy URL trong CV không được dùng để máy chủ tự tải tài nguyên ngoài.

CV mặc định là riêng tư. Slug được sinh từ 18 byte ngẫu nhiên và chỉ hoạt động khi `is_public = 1`, tài khoản còn active và CV chưa bị xóa. Tắt chia sẻ hoặc xóa CV có hiệu lực ngay ở lần truy cập sau.

## 8. Kiểm thử và tiêu chí nghiệm thu

Script `php test_online_cv_builder.php` kiểm tra:

- tạo CV và tự chọn CV chính;
- autosave tăng version;
- phát hiện xung đột version;
- tính phần trăm hoàn thiện;
- bật chia sẻ và không lộ owner ID ở API public;
- nhân bản luôn riêng tư;
- đảm bảo chỉ một CV chính;
- escape chuỗi chèn script;
- tạo tệp có magic bytes `%PDF-`;
- xóa mềm loại CV khỏi danh sách.

`php test_online_cv_builder_db.php` kiểm tra repository với MySQL thật. `php test_online_cv_activation.php` kiểm tra render PDF, lưu private storage và gắn PDF làm active CV cho luồng ứng tuyển; dữ liệu kiểm thử được dọn sau khi chạy.

Migration chạy bằng quy trình hiện có:

```bash
php migrate.php
```

## 9. Giai đoạn tiếp theo đề xuất

1. Hoàn thiện giao diện catalog, editor và quản lý CV theo prompt bàn giao.
2. Bổ sung upload ảnh đại diện vào private storage nếu nghiên cứu người dùng chứng minh cần ảnh; không bật remote image trong PDF.
3. Thêm lịch sử phiên bản để phục hồi nhiều mốc, hiện tại `version` chỉ chống ghi đè.
4. Kết nối “CV chính” với luồng nộp đơn và snapshot CV đã có trong dự án.
5. Sau khi có bộ dữ liệu thực, đánh giá độ đọc ATS từ PDF bằng pipeline trích xuất text.
6. Chỉ sau đó mới cân nhắc AI gợi ý nội dung, import LinkedIn/PDF và cover letter.

## Sources

1. TopCV. “[TOP 73 mẫu CV đơn giản, chuyên nghiệp nhất cho mọi ngành nghề 2026](https://www.topcv.vn/mau-cv).” Truy cập tháng 9/2026.
2. TopCV. “[Tuyển chọn TOP 21 mẫu CV Đơn giản (tiếng Việt) 2026](https://www.topcv.vn/mau-cv-tieng-viet/mau-don-gian).” Truy cập tháng 9/2026.
3. TopCV. “[Tuyển chọn TOP 21 mẫu CV Đơn giản (tiếng Việt) 2026](https://www.topcv.vn/mau-cv-tieng-viet/mau-don-gian).” Phần tương thích ATS. Truy cập tháng 9/2026.
4. TopCV. “[Mẫu CV phong cách Tối giản - Tiếng Việt 2026](https://www.topcv.vn/mau-cv-tieng-viet/minimalism).” Truy cập tháng 9/2026.
5. OWASP Foundation. “[Input Validation Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Input_Validation_Cheat_Sheet.html).” Truy cập tháng 9/2026.
6. Dompdf. “[Dompdf — HTML to PDF converter for PHP](https://github.com/dompdf/dompdf).” Truy cập tháng 9/2026.
