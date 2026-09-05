# Google Sign-In Plan

## Mục tiêu

Cho phép khách đăng ký hoặc đăng nhập bằng Google với hai vai trò công khai: `student` và `company`. Google chỉ xác thực danh tính; hệ thống JobMarketSV vẫn quyết định vai trò, trạng thái tài khoản, quyền truy cập và JWT phiên đăng nhập.

Không hỗ trợ đăng ký hoặc liên kết Google công khai cho `admin`.

## Quyết định đã chốt

| Chủ đề | Quyết định |
| --- | --- |
| Vai trò hỗ trợ | `student`, `company` |
| Admin | Không có nút Google Sign-In, không có callback nào tạo hoặc gán role `admin` |
| Đăng nhập hiện có | Giữ nguyên email/password và JWT nội bộ |
| Role lần đầu | Người dùng chọn Student hoặc Company trên UI trước khi chuyển đến Google; callback chỉ dùng role đã lưu phía server trong state, không tin query/body từ browser |
| Company onboarding | Tạo skeleton company với `verification_status = pending`, như flow đăng ký Company hiện tại; không bypass xác minh admin |
| Email đã tồn tại | Không tự động liên kết với local account. Báo rõ người dùng cần đăng nhập bằng mật khẩu; account linking là feature riêng sau MVP |
| Định danh Google | Dùng OpenID Connect claim `sub` làm khóa định danh bất biến; email chỉ là dữ liệu hiển thị/kiểm tra va chạm |
| Token Google | Không lưu access token/refresh token vì feature chỉ dùng đăng nhập. Chỉ phát và lưu JWT nội bộ theo contract hiện tại |

## Phạm vi và ranh giới

### Trong phạm vi

- Google OAuth/OpenID Connect authorization-code flow phía server.
- Đăng nhập Google cho tài khoản đã liên kết.
- Tạo account Student/Company lần đầu sau khi Google xác minh danh tính.
- Bảo vệ callback, xử lý lỗi và regression tests isolated.
- Nút Google trên trang Login và Register.

### Không thay đổi

- Không thay JWT, middleware phân quyền, API nghiệp vụ hay role model hiện có.
- Không thay đổi flow Company verification, job, application hoặc profile.
- Không thêm Google API scopes ngoài `openid email profile`.
- Không thêm Gmail, Drive, Calendar, contact import, upload CV, password reset hay account-linking UI.
- Không tự động merge tài khoản chỉ vì cùng email.

## Luồng sản phẩm

### 1. Đăng nhập Google cho tài khoản đã liên kết

1. Người dùng chọn **Tiếp tục với Google** tại `/login`.
2. Backend tạo OAuth state, nonce, redirect nội bộ hợp lệ và lưu chúng trong server-side session ngắn hạn.
3. Browser được chuyển tới Google.
4. Callback backend kiểm tra state/nonce và token OIDC, tìm identity theo `provider = google` + `provider_subject = sub`.
5. Nếu user còn `active`, backend phát JWT JobMarketSV theo contract `/login` hiện tại.
6. Browser nhận JWT qua cơ chế callback được thiết kế riêng, lưu bằng `TokenStorage`, rồi điều hướng tới return URL hợp lệ hoặc dashboard theo role.

### 2. Đăng ký Google lần đầu — Student

1. Tại `/register`, người dùng chọn tab **Sinh viên** rồi bấm **Tiếp tục với Google**.
2. Role `student` được bind vào OAuth state ở server.
3. Callback chỉ tạo user khi OIDC identity hợp lệ, email đã xác minh và email chưa thuộc local account khác.
4. Tạo user `student` và Google identity trong một transaction.
5. Phát JWT nội bộ, điều hướng `/student/profile` để hoàn thiện hồ sơ.

### 3. Đăng ký Google lần đầu — Company

1. Tại `/register`, người dùng chọn tab **Nhà tuyển dụng** rồi bấm **Tiếp tục với Google**.
2. Role `company` được bind vào OAuth state ở server.
3. Sau xác thực, tạo user `company`, company skeleton `pending`, và Google identity trong một transaction.
4. Phát JWT nội bộ, điều hướng `/company/profile`.
5. Company không được xem là đã xác minh và không nhận quyền vượt quá flow Company hiện tại.

### 4. Các kết quả bị từ chối

- Callback có `state` thiếu/sai/hết hạn, nonce không khớp, `code` không đổi được token, hoặc token OIDC không hợp lệ: không đăng nhập, không tạo dữ liệu.
- `issuer`, audience/client ID, chữ ký hoặc hạn token không hợp lệ: không đăng nhập, không tạo dữ liệu.
- `email_verified` không phải `true`: không tạo account.
- Google email trùng một account local chưa liên kết: không đăng nhập hay merge; hiển thị thông báo an toàn hướng dẫn dùng password login.
- Bất kỳ role nào ngoài `student`/`company`, gồm `admin`, `developer` hoặc `employer`: bị từ chối ở Google start/callback.

## Thiết kế dữ liệu

Tạo migration mới cho bảng `oauth_identities`; không sửa ý nghĩa dữ liệu của bảng `users`.

| Cột | Mục đích / ràng buộc |
| --- | --- |
| `id` | Primary key theo convention hiện có |
| `user_id` | FK tới `users.id`; xóa identity khi user bị xóa nếu schema hiện có cho phép xóa user |
| `provider` | Giá trị hiện tại chỉ `google` |
| `provider_subject` | Giá trị OIDC `sub`; `NOT NULL`; unique cùng `provider` |
| `email_at_link` | Snapshot email đã xác minh khi liên kết; không dùng để xác thực quyền sở hữu |
| `created_at`, `updated_at` | Audit tối thiểu |

Ràng buộc bắt buộc:

- `UNIQUE(provider, provider_subject)` để một Google identity không thuộc hai users.
- `UNIQUE(user_id, provider)` để một user chỉ có một Google identity ở MVP.
- Foreign key `user_id` để không tạo identity mồ côi.
- Không lưu access token, refresh token, ID token thô hoặc Google client secret trong database.

## Bảo mật bắt buộc

- Dùng authorization-code flow server-side với scopes `openid email profile`.
- Tạo `state` và `nonce` bằng CSPRNG; session phải có TTL ngắn, dùng một lần và bị xóa sau callback thành công/thất bại.
- Xác minh ID token qua thư viện/issuer metadata đáng tin cậy: signature, `iss`, `aud`, `exp`, `nonce`, `sub` và `email_verified`.
- `redirect_uri` phải là URL đã đăng ký, khớp chính xác cấu hình Google Cloud; redirect sau login phải là internal path allowlist, không tin URL callback từ client.
- Google client secret chỉ ở server environment. Không commit `.env`, credentials, token hay dump callback vào repository/log.
- Từ chối user `banned`, `suspended` hay status không `active` trước khi phát JWT, giống password login.
- Không nhận `role`, `user_id`, `provider_subject`, email hay profile Google từ browser để tạo/link account.
- Giới hạn thông tin lỗi hướng ra UI; log server không chứa authorization code/token.

Google yêu cầu redirect URI khớp chính xác cấu hình client và khuyến nghị dùng `state` để chống CSRF trong OAuth flow. ID token cần được kiểm tra issuer, audience, expiry và nonce trước khi dùng. Tham khảo [Google OAuth web-server flow](https://developers.google.com/identity/protocols/oauth2/web-server?authuser=2) và [OpenID Connect reference](https://developers.google.com/identity/openid-connect/reference).

## Cấu hình vận hành cần có trước khi triển khai

- Tạo Google Cloud project và OAuth consent screen phù hợp môi trường.
- Tạo OAuth 2.0 client loại **Web application**.
- Khai báo redirect URI development và production chính xác.
- Thiết lập server environment:
  - `GOOGLE_OAUTH_CLIENT_ID`
  - `GOOGLE_OAUTH_CLIENT_SECRET`
  - `GOOGLE_OAUTH_REDIRECT_URI`
- Thêm cấu hình fail-closed: nếu thiếu một biến, ẩn/disable Google button an toàn hoặc trả lỗi cấu hình rõ ràng; không fallback sang credential hard-code.

`hybridauth/hybridauth` đã có trong `composer.json`; implementation có thể tái sử dụng dependency này nếu nó đáp ứng đầy đủ yêu cầu validate state/nonce/token. Không thêm package OAuth thứ hai chỉ để thay đổi style.

## Kế hoạch triển khai

## GOOGLE-AUTH-P0-01 — OAuth identity schema và repository

**Problem:** `users` hiện chỉ hỗ trợ password login; không có nơi lưu `sub` Google hay ràng buộc chống liên kết trùng identity.

**Affected files:** migration mới trong `app/Migrations/`, migration runner nếu cần đăng ký migration, `app/Infrastructure/`, interface/domain authentication liên quan.

**Implementation scope:**

- Tạo bảng `oauth_identities` và các unique/FK đã nêu.
- Thêm repository methods tối thiểu: tìm theo `(provider, provider_subject)`, tạo identity, tìm local user theo email cho collision check.
- Tạo user + identity (+ company skeleton nếu role company) trong cùng transaction.

**Do NOT change:** bảng/endpoint job, application, profile; không migrate dữ liệu user hiện có; không auto-link email.

**Acceptance criteria:**

- Một `google` + `sub` không thể liên kết với hai users.
- Tạo Company Google tạo đúng một company skeleton `pending` cùng transaction.
- Lỗi ghi identity/company rollback user mới, không để dữ liệu mồ côi.
- Không có Google token/secret được persist.

**Estimated size:** Medium.

## GOOGLE-AUTH-P0-02 — Server OAuth start, callback và phát JWT nội bộ

**Problem:** Chưa có route/service xử lý OAuth và callback có thể trở thành đường bypass role hoặc account takeover nếu tin dữ liệu browser/email.

**Affected files:** `app/Routes/web.php`, `app/Http/Controllers/AuthenticationController.php` hoặc controller OAuth chuyên biệt, authentication domain/service/repository, config/session helper, exception handling.

**Implementation scope:**

- Thêm web routes rõ ràng, ví dụ `GET /auth/google/start` và `GET /auth/google/callback`.
- Start endpoint chỉ chấp nhận intent `student` hoặc `company`, bind intent/return path/state/nonce server-side.
- Callback exchange code server-side, validate đầy đủ OIDC claim, giải quyết identity hoặc tạo account lần đầu theo policy này.
- Sau thành công, phát JWT nội bộ tương đương `POST /login`; callback không trả Google token cho browser.
- Chỉ cho phép return path internal đã validate; fallback dashboard đúng role.

**Do NOT change:** `POST /login`, `POST /register`, JWT claim format, AuthMiddleware/RoleMiddleware, quyền Admin và business flow Company verification.

**Acceptance criteria:**

- Không có request/browser parameter nào tạo hoặc chuyển role thành `admin`.
- Callback chỉ xác thực Google identity đã liên kết hoặc tạo `student`/`company` theo state server-side.
- Account existing/suspended/banned được xử lý như password login.
- State/nonce invalid, token invalid/expired, issuer/audience sai, email unverified và email collision không phát JWT hay tạo user.
- Google callback thành công trả user về đúng portal hoặc return URL nội bộ hợp lệ.

**Estimated size:** Large.

## GOOGLE-AUTH-P1-01 — UI Login/Register và client handoff

**Problem:** Trang Login/Register chưa có entry point Google; role picker của Register phải trở thành đầu vào an toàn cho OAuth intent nhưng không được thay đổi password flow.

**Affected files:** `app/Views/auth/login.php`, `app/Views/auth/register.php`, CSS/component auth liên quan, `public/assets/js/api.js` hoặc helper tối thiểu cho callback handoff.

**Implementation scope:**

- Thêm divider “hoặc” và button **Tiếp tục với Google** tại Login.
- Thêm button Google bên dưới role selector ở Register; dùng role đang chọn để mở `/auth/google/start`.
- Hiển thị lỗi OAuth thân thiện và giữ được login/password form.
- Hoàn tất handoff JWT theo contract đã chọn, rồi dùng `TokenStorage` và điều hướng role-aware.

**Do NOT change:** visual redesign auth page, endpoint API login/register, role selector semantics, form validation password, global navigation.

**Acceptance criteria:**

- Login Google không cần chọn role cho user đã liên kết.
- Register Google dùng đúng role Student/Company đã chọn, không có lựa chọn Admin.
- Password registration/login vẫn hoạt động như trước.
- Button có accessible name, trạng thái loading/disabled hợp lý và không hiển thị client secret/token Google.
- Callback error không làm browser giữ JWT cũ hoặc loop redirect.

**Estimated size:** Small.

## GOOGLE-AUTH-P1-02 — Regression tests isolated và hướng dẫn vận hành

**Problem:** OAuth dễ có regression ở callback validation, account collision và role escalation; test không được gọi Google thật hay đụng development database.

**Affected files:** test bootstrap/config test, authentication/OAuth tests, `.env.example` hoặc documentation vận hành.

**Implementation scope:**

- Mock/adapter hóa Google exchange và ID token verifier để integration test không gọi network.
- Chạy với test environment/test database guard; fixture cleanup theo ID/prefix trong `finally`.
- Bổ sung checklist cấu hình Google Cloud và môi trường, không ghi secret thật.

**Do NOT change:** không chạy destructive test vào dev/shared DB, không cần browser E2E với tài khoản Google thật.

**Acceptance criteria:**

- Test cover: valid linked Google login; first-time Student; first-time Company pending; rejected `admin`; spoof role; bad/expired state; invalid token claims; unverified email; email collision; suspended/banned user; invalid external return URL; password login regression.
- Test assert JWT nội bộ và user role thực tế, không chỉ assert redirect/text HTML.
- Test fail-fast ngoài testing environment và cleanup trong `finally`.
- Hướng dẫn chỉ dùng placeholder secrets và nêu rõ redirect URI theo môi trường.

**Estimated size:** Medium.

## Thứ tự thực hiện

1. **GOOGLE-AUTH-P0-01** — ràng buộc identity và transaction dữ liệu.
2. **GOOGLE-AUTH-P0-02** — OAuth callback an toàn, resolve identity, phát JWT.
3. **GOOGLE-AUTH-P1-01** — UI entry points và client handoff.
4. **GOOGLE-AUTH-P1-02** — test isolated và tài liệu cấu hình trước khi release.

## Tiêu chí phát hành

- OAuth production client dùng redirect URI HTTPS đã đăng ký và secrets chỉ tồn tại trên server.
- Không có đường public nào tạo hoặc liên kết Admin qua Google.
- Company Google mới vẫn `pending` cho tới khi Admin verified.
- Trùng email với local account không thể chiếm hoặc tự merge account.
- Google login và password login đều phát JWT nội bộ có cùng contract role/status.
- Test suite OAuth chạy độc lập, không gọi Google thật và không ghi/xóa dữ liệu development/shared.
