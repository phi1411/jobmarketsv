# Hướng Dẫn Vận Hành, Giám Sát & Bảo Mật Trợ Lý Ảo Gemini (Chatbot Operations & Deployment Guide)

Tài liệu này cung cấp quy chuẩn kỹ thuật cho đội ngũ DevOps, System Administrator và Quản trị vận hành hệ thống JobMarketSV khi quản lý tính năng Trợ lý AI (Gemini Assistant).

---

## 1. Cấu Hình & Kiểm Soát Triển Khai (Launch Controls & Feature Flags)

### 1.1. Các biến môi trường kiểm soát tính năng
Tất cả cấu hình được quản lý qua biến môi trường tại tệp `.env` trên máy chủ:

| Tên biến | Kiểu giá trị | Mặc định | Ý nghĩa & Hành vi |
| :--- | :---: | :---: | :--- |
| `GEMINI_FEATURE_ENABLED` | boolean | `false` | **Công tắc tổng (Global Kill-Switch)**. Khi `false`, mọi endpoint `/assistant/*` đều trả về HTTP 503 và toàn bộ widget client bị vô hiệu hóa. |
| `GEMINI_COMPANY_ENABLED` | boolean | `false` | **Công tắc phân quyền Doanh nghiệp**. Chỉ khi biến này là `true` VÀ `GEMINI_FEATURE_ENABLED=true` thì Cổng Doanh nghiệp (`/company/*`) mới hiển thị widget và xử lý chat cho vai trò `company`. |
| `GEMINI_API_KEY` | string | *(trống)* | Khóa bí mật API Google AI Studio / Gemini API. **Chỉ lưu tại backend, tuyệt đối không lộ ra client/logs**. |
| `GEMINI_MODEL` | string | *(trống)* | Định danh model chính thức (ví dụ: `gemini-1.5-flash` hoặc `gemini-2.0-flash`). Bắt buộc khi bật tính năng (fail-closed, không fallback ngầm). |
| `GEMINI_TIMEOUT_SECONDS`| integer | `15` | Giới hạn thời gian chờ tối đa khi gọi upstream Google AI API (giây). |
| `CHAT_GUEST_RATE_LIMIT` | integer | `10` | Số lượt tương tác tối đa cho mỗi địa chỉ IP khách vãng lai trong 60 giây. |
| `CHAT_AUTH_RATE_LIMIT`  | integer | `30` | Số lượt tương tác tối đa cho mỗi tài khoản sinh viên/doanh nghiệp đã đăng nhập trong 60 giây. |
| `TRUSTED_PROXIES`       | string | *(trống)* | Danh sách IP reverse proxy tin cậy (ngăn chặn IP spoofing qua header X-Forwarded-For). |

> [!IMPORTANT]
> **Nguyên tắc Fail-Closed:**
> Khi bất kỳ thông tin nào trong bộ ba `GEMINI_FEATURE_ENABLED=true`, `GEMINI_API_KEY`, hoặc `GEMINI_MODEL` bị thiếu hoặc không hợp lệ, hệ thống sẽ lập tức từ chối phục vụ một cách an toàn và trả về mã lỗi HTTP 503, không gọi sang Google API.

---

## 2. Quy Trình Vận Hành & Khắc Phục Sự Cố (Operational Procedures)

### 2.1. Quy trình ngắt khẩn cấp khi có sự cố (Immediate Emergency Kill-Switch)
Khi phát hiện sự cố an toàn (Safety Incident), tràn chi phí hoặc API provider bị tấn công/lạm dụng:
1. **Ngắt toàn bộ chatbot ngay lập tức:**
   ```bash
   # Chỉnh sửa file .env trên production:
   GEMINI_FEATURE_ENABLED=false
   ```
2. **Ngắt riêng tính năng trợ lý cho nhà tuyển dụng (nếu sự cố chỉ xảy ra trên cổng Doanh nghiệp):**
   ```bash
   GEMINI_COMPANY_ENABLED=false
   ```
3. Khởi động lại PHP-FPM / OpCache để áp dụng ngay:
   ```bash
   sudo systemctl reload php8.2-fpm
   ```
4. **Hành vi hệ thống:**
   - Client-side: Widget tự động ẩn hoặc thông báo "Trợ lý AI hiện đang tạm tắt hoặc đang được bảo trì".
   - Backend-side: Endpoint `POST /assistant/chat` lập tức trả về HTTP 503 hoặc 403 `COMPANY_CHAT_DISABLED`.

### 2.2. Quy trình xoay vòng khóa API (API Key Rotation Procedure)
1. Đăng nhập vào [Google AI Studio Console](https://aistudio.google.com/).
2. Tạo một khóa API mới (New API Key) với quyền hạn tối thiểu phù hợp.
3. Cập nhật khóa mới vào file `.env` trên máy chủ:
   ```env
   GEMINI_API_KEY=AIzaSyNewGeneratedKeyHere...
   ```
4. Chạy bộ kiểm thử xác thực tự động:
   ```bash
   php test_chat_p0_01_p0_02.php
   ```
5. Sau khi xác nhận hệ thống vận hành trơn tru với khóa mới, tiến hành xóa (Revoke/Delete) khóa cũ trên Google AI Studio.

### 2.3. Quy trình chuyển đổi mô hình (Model Switching Procedure)
1. Tham khảo danh sách mô hình hiện hành từ Google Gemini documentation (ví dụ nâng cấp từ `gemini-1.5-flash` lên `gemini-2.0-flash`).
2. Cập nhật biến môi trường:
   ```env
   GEMINI_MODEL=gemini-2.0-flash
   ```
3. Xác minh tính năng qua test suite:
   ```bash
   php test_chat_p1_02_p2_01.php
   ```
4. Không cần build hay sửa đổi mã nguồn vì client backend đọc tên model động từ `Config::geminiModel()`.

### 2.4. Giám sát hạn mức và chi phí (Quota & Cost Monitoring)
- Đặt ngân sách chi tiêu và cảnh báo ngưỡng (Billing Alerts) trên Google Cloud Console ở mức 50%, 80% và 100% hạn mức tháng.
- Cấu hình rate limiting tại lớp web server (Nginx `limit_req_zone`) kết hợp với `FileRateLimiter` của ứng dụng để ngăn chặn tấn công DDoS làm cạn kiệt token/quota.
- Khi provider trả về lỗi HTTP 429 (`RESOURCE_EXHAUSTED`), hệ thống sẽ tự động map sang mã lỗi `PROVIDER_RATE_LIMIT` và thông báo người dùng quay lại sau mà không retry vô hạn.

### 2.5. Ứng phó sự cố gián đoạn từ phía Provider (Provider Outage Behavior)
- Khi Google Gemini API bị downtime (HTTP 500, 502, 503, 504):
  - Client nhận thông báo tiếng Việt lịch sự: *"Trợ lý AI hiện đang tạm tắt hoặc đang được bảo trì. Vui lòng quay lại sau ít phút."*
  - Tuyệt đối không bao giờ làm rò rỉ stack trace, URL endpoint nội bộ của Google, hay request body gốc ra ngoài giao diện người dùng.

---

## 3. Đo Lường Chất Lượng & Phản Hồi Bảo Toàn Quyền Riêng Tư (Privacy-Preserving Telemetry & Feedback)

Để bảo đảm tính tuân thủ quy định bảo vệ dữ liệu cá nhân (PDPA / GDPR), hệ thống áp dụng kiến trúc **Zero Content Retention**.

### 3.1. Dữ liệu ĐƯỢC THU THẬP (Collected Data)
Hệ thống chỉ ghi nhận các bộ đếm số nguyên tổng hợp theo ngày (Daily Aggregated Counters) lưu tại:
`<project_root>/storage/app/telemetry/chat_metrics_YYYY-MM-DD.json`

Cấu trúc tệp tin:
```json
{
  "date": "2026-09-06",
  "request_count": 1420,
  "success_count": 1380,
  "provider_error_count": 15,
  "rate_limit_count": 22,
  "safety_blocked_count": 3,
  "latency_buckets": {
    "lt_1s": 320,
    "1s_to_3s": 890,
    "3s_to_5s": 180,
    "5s_to_10s": 25,
    "gte_10s": 5
  },
  "roles": {
    "guest": 650,
    "student": 620,
    "company": 150
  },
  "feedback": {
    "thumbs_up": 412,
    "thumbs_down": 28
  }
}
```

### 3.2. Dữ liệu TUYỆT ĐỐI KHÔNG THU THẬP (Intentionally NOT Collected)
- **Nội dung tin nhắn người dùng:** Không lưu trữ bất kỳ ký tự nào của câu hỏi người dùng.
- **Nội dung câu trả lời của AI:** Không lưu trữ câu trả lời, completion, hay token stream.
- **Nhận dạng người dùng (PII):** Không lưu `user_id`, họ tên, email, số điện thoại, mã sinh viên hay CCCD.
- **Địa chỉ mạng & Phiên:** Không lưu IP address, subnet hay Session ID / JWT Token trong telemetry.
- **Hồ sơ & CV:** Tuyệt đối không bao giờ đưa nội dung tệp CV vào pipeline AI hay telemetry.

### 3.3. Chính sách lưu trữ và dọn dẹp (Retention & Rotation Policy)
- Các tệp số liệu tổng hợp `chat_metrics_*.json` được lưu giữ trong vòng **90 ngày** nhằm phục vụ báo cáo xu hướng tải và đánh giá chất lượng.
- Thiết lập cron job định kỳ chạy hằng tuần để dọn dẹp các tệp cũ hơn 90 ngày:
  ```bash
  find /var/www/jobmarket/storage/app/telemetry/ -name "chat_metrics_*.json" -mtime +90 -delete
  ```

### 3.4. Cách Quản Trị Viên kiểm tra sức khỏe của Chatbot (Operator Health Review)
Quản trị viên có thể xem nhanh sức khỏe của chatbot trong ngày bằng cách đọc tệp JSON:
```bash
cat storage/app/telemetry/chat_metrics_$(date +%Y-%m-%d).json
```
Hoặc tính toán tỷ lệ hài lòng (Satisfaction Rate):
$$\text{Tỷ lệ hài lòng} = \frac{\text{thumbs\_up}}{\text{thumbs\_up} + \text{thumbs\_down}} \times 100\%$$
Nếu tỷ lệ lỗi (`provider_error_count`) hoặc nghẽn mạng (`gte_10s`) vượt quá 5%, đội ngũ kỹ thuật cần kiểm tra lại kết nối mạng tới Google API và rà soát quota.
