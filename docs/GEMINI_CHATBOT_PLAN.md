# Gemini Chatbot MVP Plan

## Mục tiêu

Thêm một trợ lý hội thoại cho JobMarketSV để giúp khách và sinh viên hiểu cách dùng nền tảng, định hướng tìm việc part-time, và tìm các tin công khai phù hợp. Đây là chatbot hỗ trợ, không phải công cụ tự động ra quyết định tuyển dụng.

MVP dùng Gemini qua backend PHP. Trình duyệt chỉ gọi API của JobMarketSV; không bao giờ nhận hoặc gửi trực tiếp Gemini API key.

## Product decisions

| Chủ đề | Chính sách MVP |
| --- | --- |
| Người dùng | Guest và Student; Company có thể dùng sau khi luồng public/student ổn định. Admin không dùng chatbot trong MVP. |
| Mục đích | Hướng dẫn dùng nền tảng, giải thích flow ứng tuyển/CV, gợi ý cách tìm việc và tóm tắt kết quả việc làm công khai. |
| Dữ liệu được gửi sang Gemini | Tin nhắn người dùng, tối đa một số tin `published` công khai đã được server lọc, và system instruction cố định. |
| Dữ liệu bị cấm gửi | JWT/API key, CV/PDF, email, SĐT, profile riêng tư, availability schedule, employer note, application status chi tiết, audit log, dữ liệu doanh nghiệp chưa verified. |
| Hành động | Read-only. Bot không apply, không lưu job, không sửa profile, không tạo tin, không cập nhật application status. |
| Lịch sử chat | Chỉ trong browser memory cho MVP, giới hạn số lượt; không thêm bảng DB hoặc lưu transcript server-side. |
| Giao diện | Widget nổi có thể thu gọn trên public job pages và Student portal; không thay dashboard/navigation hiện có. |
| Ngôn ngữ | Tiếng Việt mặc định; trả lời ngắn, thực tế, dùng link nội bộ khi phù hợp. |

## Security and cost rules

1. Khóa Gemini chỉ ở `.env`, ví dụ `GEMINI_API_KEY`; thêm placeholder vào `.env.example`, không commit key thật.
2. Backend gọi Gemini bằng `x-goog-api-key`; frontend không thấy key, provider URL, hoặc raw provider error.
3. Endpoint chatbot phải có server-side rate limit theo IP cho guest và theo user ID cho account đăng nhập. Giới hạn ban đầu phải cấu hình được qua environment.
4. Giới hạn độ dài message, số message history và output tokens ở backend trước khi gọi provider.
5. Chỉ retry lỗi tạm thời (`429`, `408`, `5xx`) với exponential backoff ngắn, có giới hạn; không retry lỗi `4xx` cấu hình/validation.
6. Dùng Gemini safety settings; nếu provider chặn nội dung, trả lời bằng thông báo an toàn và không suy diễn câu trả lời.
7. Không log API key, authorization header, toàn bộ prompt, CV contents, hoặc raw Gemini response. Chỉ log event kỹ thuật tối thiểu: request ID nội bộ, outcome, latency, provider status class.
8. Bot phải nói rõ rằng đây là gợi ý; thông tin chính thức là nội dung tin tuyển dụng và thao tác người dùng tự xác nhận.
9. Chatbot không được tạo job recommendation giả. Mọi job/link trả về phải được server chọn từ job `published` hiện hữu và kiểm tra lại trước khi trả response.

Google yêu cầu API key trong header `x-goog-api-key`; `generateContent` hỗ trợ `systemInstruction`, `generationConfig` và `safetySettings`. Quota thay đổi theo project/tier, nên phải xem quota thực tế trong AI Studio trước launch. Xem [Gemini API reference](https://ai.google.dev/api), [safety settings](https://ai.google.dev/gemini-api/docs/safety-settings) và [rate limits](https://ai.google.dev/gemini-api/docs/rate-limits).

## Conversation contract

### System instruction

Backend giữ system instruction, không nhận từ browser. Nội dung tối thiểu:

- Bạn là trợ lý JobMarketSV, trả lời tiếng Việt rõ ràng và lịch sự.
- Chỉ hỗ trợ tìm việc part-time, cách dùng sản phẩm và giải thích thông tin công khai được cung cấp trong context.
- Không hứa hẹn được tuyển, không đánh giá con người/CV, không đưa tư vấn pháp lý/tài chính/y tế.
- Không yêu cầu hay hiển thị email, số điện thoại, CV, mật khẩu, token, hoặc dữ liệu cá nhân.
- Khi thiếu dữ liệu, hướng dẫn người dùng dùng bộ lọc hoặc xem chi tiết job thay vì bịa thông tin.

### Request and response boundary

- Browser gửi `message` và tối đa N lượt history dạng text đã được giới hạn độ dài.
- Backend xác thực/normalize input, lấy context công khai từ repository bằng allowlist, gọi Gemini, và trả `answer` cùng mảng `job_links` đã được server tạo.
- Không chuyển raw provider response cho browser.
- Response không chứa API key, prompt hệ thống, private fields, quota details, stack trace, hoặc provider request ID.

## Delivery order

### CHAT-P0-01 — Gemini configuration and server-side provider boundary

Current problem:

Project chưa có third-party AI boundary; nếu frontend gọi Gemini trực tiếp, API key sẽ lộ và không thể áp chính sách dữ liệu/rate limit thống nhất.

Desired result:

Gemini được gọi duy nhất qua một PHP infrastructure client có timeout, cấu hình environment, error mapping và không lộ provider internals.

Affected areas:

- `.env.example`
- `app/Infrastructure/` Gemini client
- `app/Domain/` provider interface/service nhỏ gọn
- bootstrap/composition root đang khởi tạo dependency
- `docs/`

Implementation scope:

- Thêm `GEMINI_API_KEY`, `GEMINI_MODEL`, timeout và feature-flag environment placeholders.
- Tạo một interface/provider client duy nhất cho text generation, dùng cURL/PHP có sẵn; không thêm framework/SDK nếu không cần.
- Đặt timeout kết nối và tổng request; map provider timeout, 429, blocked response, malformed response, và unavailable thành lỗi ứng dụng an toàn.
- Gửi system instruction và generation/safety config từ server.
- Feature flag mặc định tắt khi key/config không hợp lệ.

Do NOT change:

- Frontend widget, routes công khai, database, authentication, application/company/job business logic.
- Không commit API key hay hardcode model quota/price.

Acceptance criteria:

- Không có key hoặc provider URL trong HTML/JS/API response/log lỗi.
- App khởi động và các flow hiện tại vẫn chạy khi Gemini chưa cấu hình.
- Provider error không trả raw body/stack trace cho client.
- Client có unit/isolated tests cho success, timeout, 429, blocked, malformed response.

Estimated size: Medium

### CHAT-P0-02 — Safe chatbot API, policy, and abuse controls

Current problem:

Chưa có endpoint kiểm soát input, privacy boundary hay quota cho chat.

Desired result:

Một endpoint read-only trả lời chatbot với validation, rate limiting và context công khai tối thiểu.

Affected areas:

- `app/Routes/api.php`
- Chat controller/service/validator
- middleware hoặc rate-limit store phù hợp architecture hiện tại
- public job repository/query chỉ khi cần lấy context allowlisted
- tests

Implementation scope:

- Thêm `POST /assistant/chat` với JSON `message` và bounded history.
- Guest dùng IP throttle; signed-in user dùng user-ID throttle. Không tin `user_id` client gửi.
- Validation: text-only, trim, message max length, history max turns/characters, reject unknown fields hoặc bỏ qua chúng rõ ràng.
- Chỉ inject context từ job `published`, với allowlisted field như title, company name, location, shift, salary, deadline và canonical internal URL.
- Gắn response `answer`, server-created safe job links (nếu có), và generic error code/message.
- Không lưu transcript và không tạo migration trong MVP.

Do NOT change:

- JWT/role rules hiện có, search API contract, job visibility, CV/application/company data, notification behavior.
- Không tool calling, không function call Gemini, không cho bot thực hiện mutation.

Acceptance criteria:

- Guest và Student dùng endpoint trong giới hạn; quá giới hạn nhận `429` an toàn.
- Request không thể đưa private profile/CV/employer note vào server context.
- Chỉ job `published` mới có thể được server đưa vào response/link.
- Endpoint fail closed khi feature flag/key không sẵn sàng.
- Tests cover validation, anonymous/auth throttle, provider failure, and no-private-data context.

Estimated size: Large

### CHAT-P1-01 — Public and Student chatbot widget

Current problem:

Người dùng chưa có giao diện để hỏi trợ giúp trong hành trình tìm việc.

Desired result:

Widget chat nhỏ, accessible và responsive, hoạt động trên trang việc làm công khai và Student portal.

Affected pages/components:

- Shared layout / public job pages
- Student portal layout
- New focused frontend JS/CSS component

Implementation scope:

- Nút mở/đóng widget, focus management, keyboard close, mobile-safe height.
- Welcome prompts tĩnh: “Tìm việc theo ca”, “Cách ứng tuyển”, “Chuẩn bị CV”.
- Loading, timeout, rate-limit, provider-blocked và offline states.
- Chỉ render text escaped và server-provided internal job links.
- Giữ history trong memory; clear khi refresh hoặc đóng tab.

Do NOT change:

- Global navigation, dashboard layout, design-system task khác, localStorage token policy, hoặc bất cứ hành động nghiệp vụ nào.

Acceptance criteria:

- Widget không che CTA quan trọng trên mobile và có thể dùng bằng keyboard.
- Không gửi token/profile/CV từ browser vào body chat.
- Loading/error state rõ ràng; nút gửi không tạo request trùng.
- Link job do bot gợi ý chỉ dẫn đến internal published-job route.

Estimated size: Medium

### CHAT-P1-02 — Role-aware help content and launch controls

Current problem:

Bot MVP cần rollout an toàn và câu trả lời phù hợp từng nhóm người dùng mà không mở rộng quyền dữ liệu.

Desired result:

Student nhận hỗ trợ flow tìm việc/CV/apply; Company chỉ nhận trợ giúp dùng portal tuyển dụng sau khi feature được bật riêng.

Affected areas:

- Chat system-instruction builder
- Feature flags/configuration
- `docs/` operations guide
- tests

Implementation scope:

- Tách static instruction theo guest/student/company nhưng cùng privacy policy.
- Company mode chỉ giải thích cách đăng tin, quản lý applicant và verification status hiện hữu; không gửi applicant/CV/note data sang Gemini.
- Feature flag theo environment cho global enable và company enable riêng.
- Document quota monitoring, key rotation, model switch, incident disable switch, and provider outage behavior.

Do NOT change:

- Admin moderation process, company ownership, application status, direct messaging, billing, database persistence.

Acceptance criteria:

- Company mode không xuất hiện khi company feature flag tắt.
- Không role nào nhận private data của role khác trong prompt/context.
- Operations guide có cách disable chatbot ngay khi quota/cost/safety incident xảy ra.

Estimated size: Medium

### CHAT-P2-01 — Conversation quality evaluation and analytics without content retention

Current problem:

Không có cách đo chatbot có hữu ích hay gây lỗi mà không lưu nội dung nhạy cảm.

Desired result:

Team đo được usage/error/feedback tối thiểu và cải thiện prompt bằng dữ liệu không định danh.

Affected areas:

- Minimal telemetry service or existing logging boundary
- Widget feedback UI
- docs/tests

Implementation scope:

- Thêm thumbs up/down tùy chọn và counters tổng hợp theo ngày: request count, latency bucket, success/provider error/rate-limit/blocked count.
- Không lưu full conversation/message hoặc PII.
- Review monthly quota/cost and sampled incident metadata before thay đổi prompt.

Do NOT change:

- Không thêm chat history database, profiling user, tracking cross-site, hoặc personal recommendation engine.

Acceptance criteria:

- Có thể thấy health của chatbot mà không đọc nội dung chat.
- Feedback không gắn với message text hay user identity trong MVP.

Estimated size: Small

## Required test matrix

| Case | Expected result |
| --- | --- |
| Gemini missing key / feature disabled | `/assistant/chat` fails safely; existing product APIs unaffected |
| Browser inspection | No Gemini key, provider endpoint, or system prompt is exposed |
| Overlong message/history | 422; provider is not called |
| Guest burst requests | Server returns 429 after configured threshold |
| Authenticated burst requests | User-ID throttle applies independently from client input |
| Provider timeout/429/5xx | Safe Vietnamese error, no raw provider response, no infinite retry |
| Provider safety block | Safe fallback message, no speculative answer |
| Prompt asks to apply/change status/read CV | Bot refuses and links to user-controlled product flow where appropriate |
| Job recommendation | Only server-verified `published` jobs and internal links appear |
| Company conversation | No applicant, CV, employer note, or other-company data is sent |
| Mobile widget | Usable, dismissible, keyboard accessible, does not cover key CTA |

## Recommended order

1. CHAT-P0-01 — keep the key and provider boundary secure.
2. CHAT-P0-02 — enforce policy, validation and abuse controls before UI exposure.
3. CHAT-P1-01 — launch the public/student widget behind a feature flag.
4. CHAT-P1-02 — add company help content and operating controls.
5. CHAT-P2-01 — measure health and feedback without retaining conversations.

## Definition of done for pilot

The chatbot can be invited to a small pilot only after CHAT-P0-01, CHAT-P0-02 and CHAT-P1-01 are implemented, tested and reviewed. Keep the global feature flag off until a real Gemini key, model choice, quota limit and provider outage message have been configured in the deployment environment.
