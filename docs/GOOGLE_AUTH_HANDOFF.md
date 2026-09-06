# Google Auth — Handoff Log

_Cập nhật: 2026-09-05_

## Mục tiêu đã chốt

- Google Sign-In cho `student` và `company`.
- Không có public Google registration/linking cho `admin`.
- Giữ email/password login và JWT nội bộ hiện tại.
- Không tự động liên kết Google với local account trùng email.
- Company đăng ký Google tạo company skeleton `pending`; Admin verification giữ nguyên.
- Chỉ dùng scopes `openid email profile`; không lưu Google access token, refresh token hoặc ID token.

## Đã hoàn thành và commit

| Task | Commit | Kết quả |
| --- | --- | --- |
| GOOGLE-AUTH-P0-01 | `cb42a19` | Migration `oauth_identities`, unique/FK, transaction tạo user/identity/company pending, test isolated. |
| GOOGLE-AUTH-P0-02 | `ec17ab6` | OAuth start/callback, state/nonce one-time, verify Google ID token fail-closed, JWT nội bộ, return URL allowlist, test isolated. |

## Đang chờ commit

### GOOGLE-AUTH-P1-01 — UI Login/Register và client handoff

**Review status:** APPROVED, chưa commit tại thời điểm cập nhật log.

Bao gồm:

- Nút **Tiếp tục với Google** ở Login và Register.
- Register bind role `student`/`company`; không có Admin.
- Login không cần role, chỉ cho linked account; user mới được hướng dẫn Register chọn role.
- OAuth callback lỗi chỉ xóa local JWT khi callback mang state hợp lệ đã được consume, chống forced-logout CSRF.
- Test isolated P1-01 pass 18/18.

Khi commit, chỉ stage các file của P1-01 theo review prompt; không stage file handoff này trong cùng commit nếu chưa review riêng.

## Task tiếp theo

### GOOGLE-AUTH-P1-02 — Regression test hardening và tài liệu vận hành

Mục tiêu:

- Chốt test environment/test DB guard và cleanup fixture an toàn.
- Tài liệu hóa Google Cloud consent screen, OAuth Web Client, redirect URI và environment variables:
  - `GOOGLE_OAUTH_CLIENT_ID`
  - `GOOGLE_OAUTH_CLIENT_SECRET`
  - `GOOGLE_OAUTH_REDIRECT_URI`
- Chỉ dùng placeholder credentials; không commit secrets.
- Không thay OAuth routes, UI, JWT, API hay business logic.

## Quy trình tiếp tục ngày mai

1. Chạy `git status --short` và xác định P1-01 chưa/đã commit.
2. Nếu P1-01 chưa commit: commit đúng scope P1-01 trước.
3. Implement GOOGLE-AUTH-P1-02, không làm feature mới.
4. Gửi git diff để review theo workflow hiện tại.
5. Chỉ commit P1-02 sau khi verdict APPROVED.

## Các điều không được phá

- Google ID token phải validate signature, `RS256`, `kid`, JWK, issuer, audience, expiry, nonce, subject, email verified.
- OAuth state có TTL ngắn, dùng một lần và consume atomic.
- Chỉ state hợp lệ mới có thể kích hoạt `oauth_error=1` để UI xóa local JWT.
- Callback không trả Google token/code/secret cho browser.
- Return URL chỉ là internal path hợp lệ; không cho admin/external/encoded traversal.
- Không có request client nào tạo hoặc nâng role thành Admin.

## Tài liệu liên quan

- [Google Auth plan](GOOGLE_AUTH_PLAN.md)
- [Project context](PROJECT_CONTEXT.md)
