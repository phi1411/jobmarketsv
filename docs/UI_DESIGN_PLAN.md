# UI/UX Design Plan — JobMarketSV

## Phạm vi và nguyên tắc

Kế hoạch này chỉ polish giao diện của các view PHP, CSS và JavaScript hiện có. Không thay đổi API, backend, quyền truy cập, business logic, cấu trúc dữ liệu hay luồng nghiệp vụ. Không thêm UI framework. Tái sử dụng `public/assets/css/style.css`, layout hiện có và Vanilla JavaScript.

## Design audit

Hai portal đã có nền tảng tốt: header chung, CSS variables, button cơ bản, skeleton loading và các trạng thái error/empty ở nhiều trang. Dashboard Company có hierarchy rõ nhờ hero tối và grid chỉ số; Student có dashboard nhẹ, thân thiện hơn. Tuy vậy, chúng chưa nhất quán như một sản phẩm duy nhất.

Các điểm cần xử lý trước:

- **Hierarchy và CTA:** Header trang, banner chào mừng và một số card đều có CTA mạnh. Company dashboard có cả “Đăng Tin Tuyển Dụng” ở page header và “Đăng Tin Mới” trong hero; cần một primary CTA duy nhất theo từng màn hình.
- **Typography và spacing:** Nhiều cỡ chữ, padding, gap, border-radius được khai báo inline. Điều này làm heading, table density và card rhythm không đồng nhất.
- **Iconography:** Emoji được dùng cho navigation, stat cards, badges và action. Cách render không ổn định giữa hệ điều hành, kích thước/weight lạc tông với SVG logo và làm trạng thái khó quét.
- **Cards và màu trạng thái:** Card dashboard cùng dùng `job-card` rồi override inline. Status colors lặp lại ở dashboard, job list và applications, trong khi các nhãn trạng thái chưa có một semantic contract dùng chung.
- **Tables và responsive:** Recent applications đặt trong `overflow-x:auto` nhưng không có table class, row/action pattern hoặc mobile alternative. Tab portal dài, dễ vượt chiều ngang ở tablet/mobile.
- **States:** Skeleton, error và empty state đã tồn tại nhưng markup/visual treatment phân tán theo trang; empty state thường chỉ có emoji và một câu mô tả, CTA không nhất quán.

## Design system đề xuất

Sử dụng token CSS hiện hữu, mở rộng có kiểm soát trong `style.css`. Không đổi brand màu xanh hiện tại.

| Nhóm | Quy ước |
| --- | --- |
| Typography | System font hiện có. `display` 28/34 px, `h1` 24/32 px, `h2` 20/28 px, `h3` 16/24 px, body 14/22 px, meta 12/18 px; heading dùng 700–800, body 400–500. |
| Spacing | Thang 4, 8, 12, 16, 24, 32, 48 px. Card dùng 20 hoặc 24 px; page section dùng 32 px. |
| Container | Giữ max-width 1200 px; desktop padding 24 px, mobile 16 px. Dashboard dùng cùng page container và vertical rhythm. |
| Radius và shadow | `sm` 8 px cho input/badge; `md` 12 px cho button/card; `lg` 16 px cho banner/modal. Card mặc định shadow rất nhẹ; chỉ tăng shadow khi hover hoặc modal. |
| Màu | Primary blue cho hành động chính; secondary green chỉ cho positive/success; slate cho surface/text. Semantic: info blue, warning amber, success green, danger red, neutral slate. Không dùng màu semantic làm CTA mặc định. |
| Buttons | `primary` (một hành động chính mỗi vùng), `secondary/outline` (hành động phụ), `ghost` (toolbar/tab), `danger-outline` (phá hủy), icon-only có `aria-label` và tooltip. Height nhất quán 40 px/36 px small. |
| Cards | `surface-card` cho section/list; `stat-card` cho số liệu; `action-card` chỉ khi card là link. Không tái dùng job card cho dashboard metrics. |
| Form | Label trên input, help/error text bên dưới, 40 px controls, focus ring primary 3 px, required marker và disabled/read-only treatment rõ ràng. |
| Status badge | Một `status-badge` base và modifier theo semantic token. Luôn dùng text đầy đủ, không chỉ dựa màu/emoji; mapping status tập trung ở client. |
| Icons | Dùng SVG inline hiện có hoặc một bộ SVG outline đã vendored trong project; 16 px cho control, 20 px nav, 24 px stat/page heading. Thay emoji ở UI chrome; có thể giữ emoji trong nội dung do người dùng tạo. |

# UI-P0 — Design consistency bắt buộc

## UI-P0-01 — Chuẩn hóa design tokens và primitive CSS

Current problem:
`style.css` có token nền tảng nhưng dashboard/page views còn override màu, radius, shadow, padding và typography bằng inline style.

Desired result:
Một bộ token và primitive class đủ dùng cho page shell, surface card, stat card, section header, button, form control, badge, table và empty/error state.

Affected pages/components:
`public/assets/css/style.css`; shared layout; các view Student và Company sử dụng primitive hiện có.

Design rules:
Giữ palette/brand hiện tại; spacing theo thang 4 px; radius 8/12/16 px; không thêm framework hoặc thay markup nghiệp vụ.

Implementation scope:
Thêm CSS variables và class tái sử dụng; chuyển các giá trị visual lặp lại sang class trong những view được chỉnh ở các task sau.

Do NOT change:
Không đổi endpoint, dữ liệu, text nghiệp vụ, JavaScript API calls hay quyền truy cập.

Acceptance criteria:
Các primitive được document bằng comment CSS; dashboard không cần inline color/radius/shadow lặp lại để hiển thị đúng; focus state và contrast của text/button đạt mức đọc rõ trên nền tương ứng.

## UI-P0-02 — Thống nhất app shell, page header và portal tabs

Current problem:
Student và Company dùng cùng header nhưng page header, tab spacing, badge thông báo và CTA có kích thước/độ ưu tiên khác nhau; tab dài khó dùng trên màn hình hẹp.

Desired result:
Hai portal có cùng page shell: eyebrow/role label, title, description, một primary CTA và tab navigation có active state rõ ràng, cuộn ngang được trên mobile.

Affected pages/components:
`app/Views/layouts/main.php`, `app/Views/student/nav.php`, `app/Views/company/nav.php`, các dashboard và trang portal liên quan.

Design rules:
Portal khác nhau chỉ ở content/accent nhẹ, không đổi brand; nav item có icon SVG, label và notification count theo một pattern; page-level chỉ có một primary CTA.

Implementation scope:
Tạo class page header/tab rail; chuyển icon chrome từ emoji sang SVG; thiết lập active, hover, focus-visible và overflow behavior.

Do NOT change:
Không thay URL, route, role guard, notification count logic hoặc thứ tự chức năng trong portal.

Acceptance criteria:
Student và Company header có cùng height/spacing; tab active dễ nhận biết không chỉ bằng màu; ở 320–768 px tab không vỡ layout và vẫn truy cập được toàn bộ mục.

## UI-P0-03 — Chuẩn hóa icon, badge và màu trạng thái

Current problem:
Emoji và inline color mapping xuất hiện ở dashboard, list, notification và table; cùng trạng thái có thể mang style khác nhau giữa các trang.

Desired result:
Status và icon system nhất quán cho application, job, verification và notification.

Affected pages/components:
Student/Company dashboard, applications, jobs, notifications; `style.css`; các helper render badge hiện có.

Design rules:
Badge gồm icon nhỏ tùy chọn, label, semantic background/text/border; warning không giống danger; status không dùng emoji làm dấu hiệu duy nhất.

Implementation scope:
Tạo badge modifiers và shared icon markup/helper phía frontend; thay các inline badge color map tại các view trong scope.

Do NOT change:
Không thêm hoặc đổi application/job/company status, không đổi điều kiện hiển thị action.

Acceptance criteria:
Một status có cùng label/màu/icon ở cả portal; text trên badge đủ tương phản; badge không làm table row cao bất thường.

# UI-P1 — Dashboard polish

## UI-P1-01 — Polish dashboard header và stat cards cho Student

Current problem:
Banner Student sáng hơn Company nhưng card có viền accent không đồng nhất, icon emoji và nội dung phụ khác mật độ; CTA profile và applications cạnh tranh nhau.

Desired result:
Dashboard Student thân thiện, hướng hành động: hoàn thiện hồ sơ hoặc xem tiến trình ứng tuyển, với metric cards dễ quét.

Affected pages/components:
`app/Views/student/dashboard.php` và student portal header/nav.

Design rules:
Một primary CTA theo trạng thái profile; card có label, value, supporting text và icon SVG trong icon container chuẩn; dùng semantic color chỉ cho metric/status cần nhấn.

Implementation scope:
Áp dụng `dashboard-hero`, `stat-grid`, `stat-card`; cân chỉnh heading, CTA, spacing và icon; giữ toàn bộ data binding hiện có.

Do NOT change:
Không thay dashboard data, link, profile completion rule, application count hoặc business text.

Acceptance criteria:
Desktop có grid 4 card cân bằng; tablet 2 cột, mobile 1 cột; CTA chính rõ ràng; card đọc được khi value là 0 hoặc nhiều chữ số.

## UI-P1-02 — Polish dashboard header và stat cards cho Employer

Current problem:
Company dashboard có hai CTA tạo tin cùng cấp ở page header và welcome banner; stat cards dùng job-card nên visual intent không rõ; banner tối nặng hơn phần còn lại.

Desired result:
Dashboard Employer ưu tiên “Đăng tin” một lần, sau đó là review applicants và trạng thái tuyển dụng.

Affected pages/components:
`app/Views/company/dashboard.php`, company portal header/nav.

Design rules:
Một primary CTA “Đăng tin”; “Xem ứng viên” là secondary; hero dùng surface/gradient tiết chế, tương phản tốt; stat cards dùng cùng primitive với Student.

Implementation scope:
Thay layout/style dashboard header, stat grid và card icon; giữ data fetch/render, URLs và action labels hiện có.

Do NOT change:
Không đổi verification policy, job status, applicant metric hay API calls.

Acceptance criteria:
Không còn hai primary CTA cạnh tranh; metric và màu trạng thái nhất quán với Student; hero/action stack tốt ở mobile.

## UI-P1-03 — Chuẩn hóa application progress và dashboard content sections

Current problem:
Student progress và Company recent applicants/top jobs dùng các visual language khác nhau; progress status dễ bị hiểu là action thay vì information.

Desired result:
Các content section có title, supporting text, action link, data state và empty state cùng cấu trúc; tiến trình ứng tuyển rõ status hiện tại/đã kết thúc.

Affected pages/components:
Student dashboard application progress/recent activity; Company dashboard recent applications, top jobs và job status breakdown.

Design rules:
Section header theo primitive; progress dùng step/legend có accessible label; action “Xem tất cả” là tertiary link; empty state có một next action khi phù hợp.

Implementation scope:
Polish markup/CSS của các section và status mapping, không đổi dữ liệu hay thứ tự workflow.

Do NOT change:
Không thêm workflow application, automation hay tính toán mới.

Acceptance criteria:
Người dùng nhận biết được status hiện tại trong 3 giây; section empty/loading/error vẫn giữ structure ổn định; desktop và mobile không có horizontal clipping ngoài table wrapper.

# UI-P2 — Forms / tables / states

## UI-P2-01 — Chuẩn hóa form shell cho profile, job và apply flows

Current problem:
Form styles có base tốt nhưng section, helper text, required state, action bar và error presentation chưa đồng nhất giữa profile student, profile company, job form và apply modal/form.

Desired result:
Form dễ scan, có spacing và validation state nhất quán, action chính luôn ở vị trí dễ thấy.

Affected pages/components:
Student profile, Company profile, Company job create/edit, application form; `.form-*` styles.

Design rules:
Label trên field; group spacing 16 px; help/error text 12–14 px; destructive/cancel action tách visual khỏi submit; fieldset/card cho nhóm lịch rảnh và job details.

Implementation scope:
Thêm form primitive, chuyển visual markup ở các form; tối ưu focus/hover/disabled state.

Do NOT change:
Không đổi field names, validation rules, submission methods, request payload hay form workflow.

Acceptance criteria:
Mọi field focus rõ ràng; lỗi backend đang có hiển thị gần field; action bar không vỡ ở mobile; không mất dữ liệu đã nhập khi chỉ đổi style.

## UI-P2-02 — Chuẩn hóa table, list row và row actions

Current problem:
Company recent applications và management lists dùng table/list với inline styles khác nhau; actions có thể dày và khó chạm trên màn hình nhỏ.

Desired result:
Một table/list pattern dùng chung cho applications, jobs, saved jobs/searches và notifications liên quan.

Affected pages/components:
Company dashboard/applications/jobs; Student applications/saved jobs/saved searches; shared CSS.

Design rules:
Table header subdued; row height 56–72 px; primary column nổi bật; status badge ở cột riêng; action được gom thành text button/icon menu tùy không gian; minimum target 40 px.

Implementation scope:
Tạo `.data-table`, `.data-row`, `.row-actions`; áp dụng responsive scroll wrapper và compact card fallback khi cần.

Do NOT change:
Không đổi sorting, filtering, pagination, action availability hay endpoint.

Acceptance criteria:
Không mất cột/action ở 320 px; row có hover/focus state; trạng thái và action đọc được không cần zoom; desktop density phù hợp để scan nhiều bản ghi.

## UI-P2-03 — Hợp nhất empty, loading, error và notification states

Current problem:
Skeleton và retry đã có nhưng dùng height/padding/emoji/CTA khác nhau theo từng view; notification unread state dùng inline background/border.

Desired result:
Mỗi danh sách/data section có trạng thái loading, empty, error rõ ràng và nhất quán.

Affected pages/components:
Student/Company dashboards, notifications, applications, jobs, favorites, saved searches.

Design rules:
Loading skeleton phản ánh cấu trúc sẽ tải; empty state có illustration SVG tối giản, title, one-line explanation và tối đa một CTA; error không dùng danger cho lỗi mạng trung tính; unread dùng accent border + background nhẹ.

Implementation scope:
Tạo state classes và thay repeated inline style; giữ điều kiện JS show/hide, retry callback và notification behavior hiện có.

Do NOT change:
Không đổi empty-state logic, retry logic, notification read/unread API hay dữ liệu hiển thị.

Acceptance criteria:
Không có layout jump lớn khi load xong; tất cả state có contrast/focus hợp lệ; empty/error actions dẫn đến action hiện có.

# UI-P3 — Responsive / final polish

## UI-P3-01 — Responsive audit cho portal navigation, dashboard grid và tables

Current problem:
Base responsive CSS có drawer nav và breakpoint, nhưng dashboard section grids và inline flex/table layouts không được chuẩn hóa theo tablet/mobile.

Desired result:
Student và Company portal sử dụng cùng responsive behavior từ 1200 px đến 320 px.

Affected pages/components:
Shared stylesheet; portal nav; dashboards; profile/job/application list pages.

Design rules:
Desktop 4 stat columns, tablet 2, mobile 1; tabs horizontal scroll với visual affordance; header actions wrap có thứ tự; tables scroll hoặc card fallback theo task P2-02.

Implementation scope:
Thêm responsive utility/classes và kiểm tra visual ở 1440, 1024, 768, 480, 320 px.

Do NOT change:
Không ẩn chức năng theo viewport, không thay route hoặc menu information architecture.

Acceptance criteria:
Không horizontal overflow toàn trang; touch targets tối thiểu 40 px; CTA, tabs, badges và table action vẫn truy cập được trên mobile.

## UI-P3-02 — Accessibility và interaction polish

Current problem:
Một số interaction phụ thuộc màu/emoji, focus style không được áp dụng đồng nhất và icon-only controls cần label rõ hơn.

Desired result:
Giao diện dễ dùng bằng keyboard, có trạng thái hover/focus/disabled rõ, và hỗ trợ người dùng không phân biệt màu tốt.

Affected pages/components:
Shared CSS; navigation, buttons, tabs, forms, modal, tables và notification controls.

Design rules:
`focus-visible` ring thống nhất; icon-only có `aria-label`; status gồm text; transition giảm theo `prefers-reduced-motion`; contrast body text tối thiểu 4.5:1.

Implementation scope:
Thêm CSS accessibility utilities và attributes/labels không đổi hành vi; rà các component đã polish.

Do NOT change:
Không thay authentication, modal logic, API, keyboard shortcuts hay nội dung nghiệp vụ.

Acceptance criteria:
Có thể tab qua nav, tab, form và actions với focus thấy rõ; status vẫn hiểu khi bỏ màu; motion không gây khó chịu khi reduced-motion được bật.

## TOP 5 thay đổi mang lại hiệu quả thị giác lớn nhất

1. Chuẩn hóa page shell, portal tab và một primary CTA mỗi màn hình (`UI-P0-02`).
2. Thay dashboard card/banner riêng lẻ bằng stat card và section card dùng chung (`UI-P0-01`, `UI-P1-01`, `UI-P1-02`).
3. Thay emoji UI chrome bằng SVG icon system và status badge semantic (`UI-P0-03`).
4. Chuẩn hóa tables, list rows và actions để Employer/Student portal trông cùng một sản phẩm (`UI-P2-02`).
5. Hợp nhất empty/loading/error states để màn hình dữ liệu thưa vẫn có chủ đích (`UI-P2-03`).

## Thứ tự thực hiện

1. `UI-P0-01` — tạo nền token và primitives.
2. `UI-P0-02` — thống nhất shell/header/tabs.
3. `UI-P0-03` — icon, badge và semantic status.
4. `UI-P1-01` và `UI-P1-02` — polish hai dashboard trên primitives chung.
5. `UI-P1-03` — application progress và content sections.
6. `UI-P2-01` đến `UI-P2-03` — forms, tables và states.
7. `UI-P3-01` và `UI-P3-02` — responsive, accessibility và final QA.
