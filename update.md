# Hướng Dẫn Chi Tiết Đối Chiếu & Tích Hợp Mã Nguồn (Local vs origin/main)
**Dự Án:** JobMarketSV — Nền tảng kết nối việc làm part-time cho sinh viên  
**Repository gốc:** `https://github.com/phi1411/jobmarketsv.git` (Nhánh: `main`)  
**Phiên bản cập nhật:** 2.2.1 (Đồng bộ UI/UX, Bảng màu Emerald Nordic, Custom Select, & Tinh chỉnh Typography)  
**Ngày cập nhật:** 20/09/2026  

---

## I. TỔNG QUAN HIỆN TRẠNG ĐỐI CHIẾU

Hiện tại trên máy của bạn đang có **37 tệp đã sửa đổi (modified)** và **4 tệp/thư mục mới chưa được theo dõi (untracked)** so với commit mới nhất trên `origin/main` (`1cfb340`).

### Bảng phân loại các thay đổi:

| Nhóm | Số lượng | Mô tả chi tiết |
| :--- | :---: | :--- |
| **Tài nguyên mới (Untracked)** | **4 mục** | `custom_select.js`, thư viện icon nội bộ `public/assets/vendor/remixicon/`, `router.php`, và tài liệu `update.md`. |
| **Giao diện & CSS (`public/assets/css/*`)** | **7 tệp** | `style.css`, `theme.css`, `location.css`, `cv_builder.css`, `assistant.css`, `chatbot.css`, `password_security.css`. |
| **Layout & Views (`app/Views/*`)** | **25 tệp** | Giao diện trang chủ, tìm việc, chi tiết việc làm, portal sinh viên, portal doanh nghiệp, admin, auth. |
| **Hạ tầng & Dịch vụ (`app/*`, `public/index.php`)** | **5 tệp** | `Kernel.php`, `AuthMiddleware.php`, `MailService.php`, `GoogleOAuthController.php`, `public/index.php`. |

---

## II. CHI TIẾT TỪNG ĐIỂM KHÁC BIỆT & CODE CHƯA ĐỒNG BỘ

### 1. Thư viện và tài nguyên mới (BẮT BUỘC COPY NGUYÊN VẸN)

1. **`public/assets/vendor/remixicon/`** (Thư mục mới):
   - Chứa `remixicon.css` và `remixicon.woff2`.
   - *Lý do:* Cung cấp toàn bộ icon vector chuẩn (thay thế emoji OS thô ráp và icon SVG rải rác).
2. **`public/assets/js/custom_select.js`** (Tệp mới - 245 dòng):
   - Bộ chuyển đổi tự động mọi thẻ `<select>` thành menu dropdown bo góc cao cấp, đồng bộ 2 chiều sự kiện `change`, hỗ trợ click-outside và Dark Mode.
3. **`router.php`** (Tệp mới):
   - Router phục vụ tài nguyên tĩnh (CSS, JS, Fonts, Images) khi chạy server local qua `php -S 0.0.0.0:8000 router.php`.

---

### 2. Thay đổi Hạ tầng Core & Backend

1. **`public/index.php`**:
   - *Trên Git:* Dùng `define("LOADED", true); define("BASE_PATH", dirname(__DIR__));`.
   - *Local cập nhật:* Dùng `defined("LOADED") || define(...)` để tránh lỗi hằng số đã định nghĩa khi chạy qua router hoặc test suite.
2. **`app/Http/Kernel.php`**:
   - *Trên Git:* Chỉ kiểm tra `$request->wantsHtml()` cho Web Routes.
   - *Local cập nhật:* Bổ sung xử lý HTTP method `HEAD` và kiểm tra header `Accept` để trình duyệt tải trang HTML trực tiếp mà không bị nhầm lẫn với JSON API.
3. **`app/Http/Middlewares/AuthMiddleware.php`**:
   - *Local cập nhật:* Chuẩn hóa method `HEAD` thành `GET` trong auth flow để không chặn browser pre-check.
4. **`app/Domain/MailService.php`**:
   - *Trên Git:* Header và nút bấm trong email thông báo khớp việc, đổi mật khẩu dùng màu xanh dương `#2563eb`.
   - *Local cập nhật:* Đồng bộ sang xanh ngọc `#059669` và nền tag `#ecfdf5`.
5. **`app/Http/Controllers/GoogleOAuthController.php`**:
   - *Local cập nhật:* Đổi màu spinner xoay chờ OAuth từ xanh dương `#3b82f6` sang Emerald `#059669`.

---

### 3. Chuẩn hóa Bảng màu & Typography trong CSS (`public/assets/css/`)

1. **`style.css`**:
   - **Bảng màu `:root`**:
     - `--primary`: Đổi thành `#059669`, hover `#047857`, light `#ecfdf5`, border `#a7f3d0`, text `#065f46`.
     - `--secondary`: `#10b981` (Mint).
     - `--dark`: Chuyển từ than xanh navy `#0f172a` sang **than rêu trầm `#071f18`**.
     - `--bg`: Chuyển từ xám xanh `#f8fafc` sang **xám ngà ánh rêu `#f7faf9`** (êm mắt hơn).
     - `--surface-hover`: Đổi sang `#f0fdf4`.
   - **Typography Scale**: Thiết lập font Plus Jakarta Sans, khử răng cưa `antialiased`, và chuẩn hóa kích thước/độ đậm cho `h1` đến `h6`.
   - **CTA Banner (`.cta-banner`)**: Chuyển từ xanh đen navy sang dải ngọc bích `linear-gradient(135deg, #064e3b 0%, #065f46 50%, #047857 100%)`.
   - **Footer (`.site-footer`)**: Chuyển sang nền Deep Forest Charcoal `linear-gradient(180deg, #071e17 0%, #051611 100%)` có viền sáng Mint.
   - **Custom Select CSS**: Bổ sung bộ selector `.custom-select-*` phục vụ dropdown.
2. **`theme.css` (Dark Mode)**:
   - Đồng bộ `--primary: #10b981`, viền và màu active tabs, dropdown trong giao diện tối.
   - Bổ sung quy tắc Dark Mode cho `.cta-banner` và `.site-footer`.
3. **`location.css`**:
   - Thay toàn bộ `#2563eb`, `#1d4ed8` ở nút "Tìm việc gần tôi", chip bán kính, ô gợi ý GoongJS sang `var(--primary)` và `var(--primary-light)`.
4. **`cv_builder.css`**:
   - Đổi hero template catalog, pill bộ lọc, viền focus input sang Emerald.
5. **`assistant.css` & `chatbot.css`**:
   - Đổi nút avatar, prompt chip, nút gửi và viền chat sang `#059669`.
6. **`password_security.css`**:
   - Đổi màu bước xác thực và link gửi lại mã sang `var(--primary)`.

---

### 4. Tinh chỉnh trên các Views (`app/Views/`)

1. **`app/Views/layouts/main.php`**:
   - Đã nhúng `remixicon.css` và `custom_select.js`.
   - Cập nhật text mô tả footer sang màu `#a7b8b2`.
2. **Xóa triệt để ký tự emoji**:
   - `home.php`: Xóa emoji `🔍`, dùng `<i class="ri-search-line"></i>`. Sửa icon danh mục và badge hero sang Emerald.
   - `jobs/index.php`: Xóa `🔍` tại nút xác thực GPS/địa chỉ, thay bằng `<i class="ri-map-pin-user-line"></i>`. Sửa màu thanh lọc gần tôi.
   - `student/recommendations.php`: Xóa emoji `🔎`, thay bằng `<i class="ri-sparkling-fill"></i>`. Sửa thanh tiến trình match sang Emerald.
3. **Đồng bộ màu sắc các trang còn lại**:
   - `jobs/show.php`: Đổi màu marker bản đồ GoongJS và spinner loading AI sang Emerald.
   - `student/profile.php`, `student/applications.php`, `student/saved_searches.php`, `student/notifications.php`: Đồng bộ màu badge trạng thái, nút AI và checkbox sang Emerald.
   - `company/applications.php`, `admin/support.php`, `admin/dashboard.php`, `admin/audit_logs.php`: Đồng bộ màu thẻ lọc và avatar sang Emerald.

---

## III. HƯỚNG DẪN TỪNG BƯỚC ĐỂ BẠN TÍCH HỢP CODE CHÍNH XÁC

Để bạn của bạn tích hợp nhanh nhất và không gặp xung đột (conflict), có 2 phương án thực hiện:

### PHƯƠNG ÁN A: Đóng gói và gửi nhánh Git (Khuyến nghị chuẩn nhất)

Trên máy của bạn:
1. Tạo nhánh mới và commit toàn bộ các thay đổi:
   ```bash
   git checkout -b feat/ui-emerald-nordic-2.2.1
   git add .
   git commit -m "feat(ui): standardize emerald nordic theme, custom select dropdowns and typography hierarchy"
   ```
2. Đẩy nhánh lên repository GitHub (nếu có quyền push):
   ```bash
   git push origin feat/ui-emerald-nordic-2.2.1
   ```
3. Sau đó bạn của bạn chỉ cần:
   ```bash
   git fetch origin
   git checkout feat/ui-emerald-nordic-2.2.1
   # hoặc merge vào nhánh của bạn đó:
   git merge feat/ui-emerald-nordic-2.2.1
   ```

---

### PHƯƠNG ÁN B: Xuất file Patch / Diff để gửi trực tiếp

Nếu bạn muốn gửi file diff để bạn của bạn tự `git apply`:

1. **Trên máy bạn: Tạo file patch (bao gồm cả file untracked)**:
   ```bash
   # Thêm toàn bộ file mới vào index tạm thời
   git add -N public/assets/js/custom_select.js public/assets/vendor router.php update.md
   
   # Xuất toàn bộ diff ra 1 file patch
   git diff origin/main > jobmarket_ui_update_v2.2.1.patch
   ```
2. **Gửi file `jobmarket_ui_update_v2.2.1.patch` cho bạn của bạn.**
3. **Trên máy bạn của bạn: Áp dụng patch**:
   ```bash
   # 1. Đảm bảo nhánh sạch (đã pull origin/main mới nhất)
   git pull origin main
   
   # 2. Kiểm tra tính hợp lệ của patch
   git apply --check jobmarket_ui_update_v2.2.1.patch
   
   # 3. Áp dụng patch vào code
   git apply jobmarket_ui_update_v2.2.1.patch
   ```

---

### PHƯƠNG ÁN C: Copy đè thủ công theo danh mục tệp

Nếu bạn muốn copy paste file thủ công:

1. **Bước 1: Copy các tệp và thư mục mới**:
   - `public/assets/vendor/` (chứa toàn bộ RemixIcon font & css).
   - `public/assets/js/custom_select.js`.
   - `router.php` (ở thư mục gốc).
   - `update.md`.
2. **Bước 2: Copy đè toàn bộ thư mục CSS**:
   - Copy đè tất cả tệp trong `public/assets/css/` (bao gồm `style.css`, `theme.css`, `location.css`, `cv_builder.css`, `assistant.css`, `chatbot.css`, `password_security.css`).
3. **Bước 3: Copy đè thư mục Views**:
   - Copy đè `app/Views/layouts/main.php`.
   - Copy đè các file views đã sửa: `home.php`, `jobs/`, `student/`, `company/`, `admin/`.
4. **Bước 4: Copy đè 3 file backend**:
   - `public/index.php`.
   - `app/Http/Kernel.php`.
   - `app/Domain/MailService.php`.

---

## IV. BƯỚC KIỂM TRA SAU KHI TÍCH HỢP

Sau khi tích hợp xong, chạy lệnh sau để kiểm tra:

```bash
# 1. Khởi động server
php -S 0.0.0.0:8000 router.php

# 2. Mở trình duyệt và kiểm tra:
# - Trang chủ: http://localhost:8000/
# - Kiểm tra: Banner màu ngọc bích, footer nền than rêu trầm, không còn emoji 🔍.
# - Kiểm tra dropdown: Bấm vào thẻ chọn ca làm việc xem đã bo góc đẹp mắt chưa.
```
