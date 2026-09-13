# Prompt triển khai giao diện Tạo CV Online

Bạn đang làm giao diện trực tiếp trong dự án PHP JobMarketplace hiện có. Hãy xây dựng trọn bộ UI cho tính năng “Tạo CV Online / Mẫu CV sinh viên”, dựa tuyệt đối vào backend và API đã có. Không thay đổi hợp đồng API, không tự tạo dữ liệu giả thay cho API, không viết lại phần xử lý server.

Mục tiêu trải nghiệm: lấy cảm hứng từ sự rõ ràng và tốc độ của TopCV nhưng không sao chép thương hiệu, hình ảnh, câu chữ hay bố cục pixel-perfect. Giao diện phải đồng nhất với design system, header, navigation, màu sắc và responsive behavior hiện có của JobMarketplace.

Đọc trước các tệp sau:

- `docs/CV_ONLINE_RESEARCH_AND_API.md` — hợp đồng dữ liệu/API và quy tắc autosave.
- `app/Routes/api.php` — endpoint thực tế.
- `app/Domain/CvBuilder/CvSchema.php` — field và giới hạn hợp lệ.
- `app/Domain/CvBuilder/CvTemplateCatalog.php` — mẫu CV.
- `app/Views/layouts/main.php`, `public/assets/css/style.css`, `public/assets/js/api.js` — cách dự án dựng trang và gọi API.

Hãy tạo ba màn hình:

1. Trang “Mẫu CV sinh viên” công khai tại `/mau-cv-sinh-vien`. Gọi `GET /cv/templates`; hiển thị hero ngắn, bộ lọc tag (Sinh viên, ATS, Tối giản, Hiện đại), card preview cho ba template, badge “Chuẩn ATS” khi `ats_friendly=true`, nút “Dùng mẫu này”. Nếu chưa đăng nhập thì chuyển sang login theo cơ chế có sẵn; nếu đã đăng nhập thì gọi `POST /student/cvs` và chuyển vào editor.

2. Trang “CV của tôi” tại `/student/cvs`. Gọi `GET /student/cvs`; hiển thị card theo trạng thái CV chính, công khai/riêng tư, phần trăm hoàn thiện, template, cập nhật gần nhất. Có thao tác Sửa, Xem trước, Tải PDF, Nhân bản, Dùng để ứng tuyển, Đặt làm CV chính, Bật/tắt chia sẻ, Sao chép liên kết và Xóa. “Dùng để ứng tuyển” gọi endpoint `activate` để tạo snapshot PDF hoạt động trong hồ sơ. Xóa cần confirm dialog. Empty state dẫn về kho mẫu.

3. Editor tại `/student/cvs/{id}/edit`. Bố cục desktop gồm thanh công cụ trên cùng, form/section navigator bên trái và preview A4 bên phải. Mobile chuyển sang tab “Nội dung / Xem trước”. Form bao phủ đầy đủ các section trong `CvSchema`: personal, summary, education, skills, projects, experience, activities, certifications, awards, languages, interests, custom_sections. Danh sách phải thêm/xóa/sắp xếp được; các section trừ `personal` có thể ẩn/hiện và kéo đổi thứ tự, còn `personal` luôn là header. Có panel thiết kế gồm template, ngôn ngữ, màu nhấn, font, cỡ chữ, giãn dòng.

Autosave là yêu cầu bắt buộc:

- Giữ state local trong khi gõ.
- Debounce 1000 ms rồi `PATCH /student/cvs/{id}`.
- Luôn gửi `expected_version` gần nhất.
- Hiển thị trạng thái “Đang lưu… / Đã lưu / Lỗi lưu”.
- Khi thành công, cập nhật toàn bộ object theo response server, nhất là `version` và `completion_percent`.
- Khi HTTP 409, dừng retry và mở dialog: “CV đã được chỉnh sửa ở nơi khác”. Cho phép tải bản mới từ server hoặc giữ bản local để người dùng sao chép; không âm thầm ghi đè.
- Preview iframe dùng `/student/cvs/{id}/preview` sau autosave. Không render PDF ở trình duyệt.

Hành vi API:

- `GET /cv/templates`
- `GET /student/cvs`
- `POST /student/cvs`
- `GET /student/cvs/{id}`
- `PATCH /student/cvs/{id}`
- `DELETE /student/cvs/{id}`
- `POST /student/cvs/{id}/duplicate`
- `POST /student/cvs/{id}/primary`
- `POST /student/cvs/{id}/activate`
- `PATCH /student/cvs/{id}/visibility`
- `GET /student/cvs/{id}/preview`
- `GET /student/cvs/{id}/export.pdf`

Các yêu cầu chất lượng:

- Dùng PHP view + CSS + JavaScript vanilla theo kiến trúc hiện tại; không thêm framework frontend mới.
- Không dùng inline onclick; tổ chức code thành module/hàm rõ ràng trong asset JS riêng.
- Có loading skeleton, empty state, validation message theo field, toast và error state có retry.
- Accessible: label thật cho input, focus visible, thao tác được bằng bàn phím, dialog quản lý focus, `aria-live` cho autosave, màu đạt độ tương phản tốt.
- Responsive hoàn chỉnh từ 360 px đến desktop rộng; preview A4 có zoom fit-width.
- Nội dung CV là text thuần; không dùng `innerHTML` với dữ liệu từ người dùng.
- Trước khi bật public, hiển thị cảnh báo rằng họ tên và thông tin liên hệ sẽ xuất hiện trên Internet.
- Không đưa tên “TopCV” vào giao diện thành phẩm.
- Không sửa backend trừ khi phát hiện lỗi thực sự; nếu gặp lỗi, ghi rõ bằng chứng và thay đổi nhỏ nhất có thể.

Hoàn tất bằng cách chạy kiểm tra cú pháp PHP, kiểm tra luồng API, kiểm tra responsive bằng trình duyệt ở 360 px/768 px/1440 px và báo danh sách tệp đã thay đổi cùng các trường hợp đã xác minh.
