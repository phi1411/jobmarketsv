# Hướng Dẫn Vận Hành Google Sign-In

Tài liệu này hướng dẫn cấu hình Google OAuth 2.0 (OpenID Connect) cho hệ thống JobMarketplaceSV. Tất cả credential và URL trong tài liệu này đều là **placeholder**; không bao giờ commit secret thật vào repository.

---

## 1. Tạo Google Cloud Project

1. Truy cập [Google Cloud Console](https://console.cloud.google.com/).
2. Tạo project mới hoặc chọn project hiện có.
3. Ghi nhận **Project ID** để tham chiếu sau này.

---

## 2. Cấu Hình OAuth Consent Screen

1. Trong Google Cloud Console, vào **APIs & Services** → **OAuth consent screen**.
2. Chọn **User Type**:
   - **Internal**: Chỉ dùng nội bộ Google Workspace (phù hợp development/staging).
   - **External**: Cho phép bất kỳ Google account nào đăng nhập (cần cho production).
3. Điền thông tin bắt buộc:
   - **App name**: `JobMarketplaceSV` (hoặc tên hiển thị phù hợp).
   - **User support email**: email hỗ trợ kỹ thuật.
   - **Developer contact email**: email liên hệ developer.
4. Thêm **Scopes**:
   - `openid`
   - `email`
   - `profile`
   - Không thêm scope nào khác (Gmail, Drive, Calendar, v.v.).
5. Nếu **External**: thêm email test user vào danh sách **Test users** trong giai đoạn phát triển.
6. Lưu và publish consent screen.

> **Lưu ý**: Khi consent screen ở trạng thái "Testing", chỉ test users mới có thể đăng nhập. Chuyển sang "In production" (có thể cần Google review) trước khi deploy cho end-users.

---

## 3. Tạo OAuth 2.0 Client (Web Application)

1. Vào **APIs & Services** → **Credentials** → **Create Credentials** → **OAuth client ID**.
2. Chọn **Application type**: **Web application**.
3. Đặt **Name**: `JobMarketplaceSV Web` (hoặc tên mô tả).
4. Cấu hình **Authorized JavaScript origins** (tùy chọn, không bắt buộc cho server-side flow):
   - Development: `http://localhost` (hoặc `http://localhost:8080` nếu dùng port khác)
   - Production: `https://your-production-domain.example`
5. Cấu hình **Authorized redirect URIs** (bắt buộc, phải **khớp chính xác**):

| Môi trường                  | Redirect URI                                                   |
| --------------------------- | -------------------------------------------------------------- |
| Local (localhost)           | `http://localhost/auth/google/callback`                        |
| Local (custom port)         | `http://localhost:8080/auth/google/callback`                   |
| Local (HTTPS custom domain) | `https://manguonmo.test/auth/google/callback` ¹               |
| Staging                     | `https://your-staging-domain.example/auth/google/callback`     |
| Production                  | `https://your-production-domain.example/auth/google/callback`  |

> ¹ Google chỉ cho phép `http://` cho origin `localhost`. Mọi domain khác (bao gồm `manguonmo.test`) **bắt buộc dùng `https://`** và cần cấu hình HTTPS hợp lệ trên local (ví dụ: mkcert + Laragon SSL).

6. Nhấn **Create** và ghi nhận:
   - **Client ID**: Có dạng `123456789-abc.apps.googleusercontent.com`
   - **Client Secret**: Chuỗi ngẫu nhiên do Google tạo

> **Cảnh báo**: Client Secret là bí mật. Không commit vào git, không ghi vào log, không gửi qua chat/email không mã hóa.

---

## 4. Biến Môi Trường Server

Thêm vào file `.env` trên server (KHÔNG commit file `.env`):

```env
# Google OAuth 2.0 Configuration
GOOGLE_OAUTH_CLIENT_ID=your-google-client-id.apps.googleusercontent.com
GOOGLE_OAUTH_CLIENT_SECRET=your-google-client-secret
GOOGLE_OAUTH_REDIRECT_URI=http://localhost/auth/google/callback
```

### Mô tả biến

| Biến                          | Mô tả                                                        |
| ----------------------------- | ------------------------------------------------------------- |
| `GOOGLE_OAUTH_CLIENT_ID`     | Client ID từ Google Cloud Console                              |
| `GOOGLE_OAUTH_CLIENT_SECRET` | Client Secret từ Google Cloud Console                          |
| `GOOGLE_OAUTH_REDIRECT_URI`  | Redirect URI khớp chính xác với cấu hình Google Cloud Console  |

### Fail-Closed

Nếu bất kỳ biến nào thiếu hoặc rỗng:
- Nút Google Sign-In vẫn hiển thị trên UI (button không bị ẩn tự động).
- Endpoint `/auth/google/start` trả lỗi cấu hình rõ ràng và redirect về `/login` với thông báo lỗi an toàn.
- Hệ thống **không** fallback sang credential hard-coded.
- Password login vẫn hoạt động bình thường.

---

## 5. Redirect URI Matching

Google yêu cầu redirect URI khớp **chính xác** (exact match), bao gồm:
- Protocol (`http` vs `https`)
- Hostname
- Port (nếu không phải default)
- Path (`/auth/google/callback`)
- Không có trailing slash thừa

Ví dụ sai:
- ❌ `http://localhost/auth/google/callback/` (trailing slash)
- ❌ `https://localhost/auth/google/callback` (https vs http cho localhost)
- ❌ `http://localhost:80/auth/google/callback` (explicit default port)
- ❌ `http://manguonmo.test/auth/google/callback` (http với domain không phải localhost)

---

## 6. Kiểm Thử (Testing)

### Yêu cầu môi trường test

1. File `.env.testing` phải tồn tại với:
   ```env
   APP_ENV=testing
   DB_NAME=jobmarket_test

   GOOGLE_OAUTH_CLIENT_ID=test-client-id.apps.googleusercontent.com
   GOOGLE_OAUTH_CLIENT_SECRET=test-client-secret-value
   GOOGLE_OAUTH_REDIRECT_URI=http://localhost/auth/google/callback
   ```
2. Database `jobmarket_test` phải được tạo và migrate:
   ```bash
   # Tạo database (nếu chưa có)
   mysql -u root -e "CREATE DATABASE IF NOT EXISTS jobmarket_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

   # Chạy migration cho test database
   php migrate.php --dbname=jobmarket_test
   ```

### Đảm bảo an toàn

- **Zero network calls**: Tất cả test dùng mock client, không gọi Google API thật.
- **Fail-fast guards**: Test tự động dừng nếu `APP_ENV !== 'testing'` hoặc `DB_NAME !== 'jobmarket_test'`.
- **Fixture isolation**: Mỗi lần chạy tạo prefix duy nhất (e.g. `test_p0_01_abc123_`); `finally` block và `register_shutdown_function` đảm bảo cleanup dù test pass hay fail.
- **Không cần tài khoản Google thật**: Mock adapter mô phỏng toàn bộ Google OAuth response.

### Chạy test

```bash
# Chạy từng suite riêng lẻ
php test_google_auth_p0_01.php    # 8 tests: Schema & repository constraints
php test_google_auth_p0_02.php    # 16 tests: OAuth callback security & claim validation
php test_google_auth_p1_01.php    # 18 tests: UI entry points & CSRF protection
php test_google_auth_p1_02.php    # 5 tests: Regression hardening & isolation verification

# Chạy toàn bộ suite (unified runner)
php run_google_auth_tests.php     # 47 tests total, consolidated report
```

### Kết quả mong đợi

```
=================================================================
   CONSOLIDATED RESULTS
=================================================================
  [✓] GOOGLE-AUTH-P0-01 (OAuth Identity Schema & Repository)
  [✓] GOOGLE-AUTH-P0-02 (Server OAuth Callback & JWT Issuance)
  [✓] GOOGLE-AUTH-P1-01 (UI Entry Points & Client Handoff)
  [✓] GOOGLE-AUTH-P1-02 (Regression Hardening & Isolation)

  Total: 4 suites | Passed: 4 | Failed: 0 | Skipped: 0
=================================================================
  ALL GOOGLE AUTH TEST SUITES PASSED!
=================================================================
```

---

## 7. Checklist Trước Khi Deploy

- [ ] Google Cloud project đã tạo và OAuth consent screen đã publish.
- [ ] OAuth client loại **Web application** đã tạo.
- [ ] Redirect URI production (`https://...`) đã đăng ký chính xác trong Google Cloud Console.
- [ ] Biến `GOOGLE_OAUTH_CLIENT_ID`, `GOOGLE_OAUTH_CLIENT_SECRET`, `GOOGLE_OAUTH_REDIRECT_URI` đã đặt trên server production.
- [ ] Secret chỉ tồn tại trong server environment, không có trong git repository hay log.
- [ ] Test suite chạy thành công: `php run_google_auth_tests.php` → exit code 0.
- [ ] Password login vẫn hoạt động bình thường sau khi bật Google Sign-In.
- [ ] Nút Google Sign-In hiển thị đúng trên trang Login và Register.
- [ ] Admin không có nút Google Sign-In và không có callback nào tạo role `admin`.
- [ ] Company đăng ký qua Google nhận `verification_status = pending`.
