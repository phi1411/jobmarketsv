# P0 — Security / Critical / Broken Core Flow

## TASK-P0-01 — Không cho public registration tạo tài khoản admin

Problem:
Endpoint đăng ký công khai cho phép client chọn `role=admin`, tạo đường leo thang đặc quyền trực tiếp.

Evidence:
`app/Http/Controllers/AuthenticationController.php:23-43` whitelist gồm `admin` và chuyển role đã chọn vào service. Kiểm tra Validator trong session xác nhận payload role `admin` được chấp nhận.

Affected files:
`app/Http/Controllers/AuthenticationController.php`, test authentication/authorization.

Goal:
Chỉ cho đăng ký public tài khoản student hoặc company; việc tạo admin phải là cơ chế nội bộ có kiểm soát.

Implementation scope:
Thu hẹp whitelist role ở public registration, giữ mapping legacy `developer`/`employer` nếu còn cần tương thích, và bổ sung regression test.

Acceptance criteria:
`POST /register` với `admin` trả lỗi 422/403; student và company vẫn đăng ký được; không có endpoint public nào khác tạo admin.

Risk:
Medium — cần kiểm tra seed/bootstrap admin hiện hữu không phụ thuộc endpoint public.

Estimated size: Small

## TASK-P0-02 — Khóa legacy Company CRUD theo role và ownership

Problem:
Mọi tài khoản có JWT có thể sửa, chuyển `user_id` hoặc xóa company bất kỳ qua legacy endpoint. Xóa company có thể cascade jobs và applications.

Evidence:
`app/Http/Controllers/CompanyController.php:35-45` gọi update/delete theo ID URL mà không kiểm tra role hay ownership. `app/Domain/CompanyService.php:48-60` lấy `user_id` từ client; `app/Infrastructure/CompanyRepository.php:75-85` ghi `user_id`, và `:127-134` hard-delete. `app/Migrations/JobMigration.php:27` và `app/Migrations/ApplicationMigration.php:25` có cascade liên quan. `AuthMiddleware` chỉ yêu cầu JWT cho route protected (`app/Http/Middlewares/AuthMiddleware.php:69-74`).

Affected files:
`app/Routes/api.php`, `app/Http/Controllers/CompanyController.php`, `app/Domain/CompanyService.php`, `app/Infrastructure/CompanyRepository.php`, authorization tests.

Goal:
Loại bỏ đường IDOR/takeover và không cho endpoint legacy phá policy ownership của job/application.

Implementation scope:
Vô hiệu hóa các legacy mutation route chưa dùng, hoặc thay bằng policy company owner lấy từ JWT. Không cho client ghi `user_id`; không hard-delete company qua API thường; test toàn bộ alias route.

Acceptance criteria:
Student, company khác và guest không thể PUT/DELETE company không thuộc họ; request không thể đổi owner; jobs/applications của nạn nhân không đổi; company owner hợp lệ vẫn cập nhật qua `/company/profile`.

Risk:
Large — cần bảo đảm không làm hỏng client legacy trước khi gỡ route.

Estimated size: Medium

## TASK-P0-03 — Hoàn thiện employer onboarding để tạo hồ sơ company an toàn

Problem:
Đăng ký company chỉ tạo user. Company mới không có bản ghi company, `/company/profile` trả 404 và tạo job bị chặn vì không resolve được company.

Evidence:
Form đăng ký chỉ gửi `name,email,password,role` tại `app/Views/auth/register.php:97-100`. `CompanyController::updateMyProfile()` tìm company hiện hữu và trả 404 tại `app/Http/Controllers/CompanyController.php:77-80`; `JobService::createJob()` chặn khi không có company tại `app/Domain/JobService.php:69-73`.

Affected files:
`app/Http/Controllers/AuthenticationController.php`, `app/Domain/AuthenticationService.php`, `app/Infrastructure/AuthenticationRepository.php`, company onboarding view/controller, onboarding tests.

Goal:
Company mới có một hồ sơ company thuộc chính user để hoàn tất profile và đăng tin theo policy verification hiện có.

Implementation scope:
Tạo company skeleton trong cùng transaction với registration company, hoặc cung cấp một endpoint onboarding có owner lấy từ JWT. Chỉ chọn một flow; không mở lại legacy company CRUD.

Acceptance criteria:
Company mới đăng ký → đăng nhập → mở và lưu `/company/profile` thành công → tạo draft/pending job được; không tạo profile company cho student; lỗi giữa chừng không để user/company orphan.

Risk:
Medium — cần quyết định trường tối thiểu của profile skeleton.

Estimated size: Medium

## TASK-P0-04 — Đồng nhất kiểu dữ liệu required_skills giữa form và domain

Problem:
Form job luôn gửi `required_skills` là array, còn entity khai báo `?string`, làm tạo/sửa job từ UI lỗi TypeError 500.

Evidence:
`app/Views/company/job_form.php:328-332,345-363` tạo `selectedSkills` array. `app/Domain/Job/Job.php:29,99` gán trực tiếp vào property `?string`. Tái hiện trong session bằng `Job::fromArray()` với `[]` hoặc `['skill-1']` đều trả TypeError.

Affected files:
`app/Views/company/job_form.php`, `app/Domain/Job/Job.php`, `app/Domain/JobService.php`, `app/Infrastructure/JobRepository.php`, job flow tests.

Goal:
Employer có thể tạo và sửa job từ giao diện với hoặc không có kỹ năng được chọn.

Implementation scope:
Chọn một contract duy nhất (JSON/string hoặc array được normalize server-side), validate giới hạn/phần tử, và hydrate lại đúng khi edit. Không tạo bảng hay redesign skills trong task này.

Acceptance criteria:
Create và edit job với zero, one, nhiều skills không 500; dữ liệu đọc lại khôi phục selection; request sai kiểu trả 422 rõ ràng.

Risk:
Medium — cần giữ tương thích các job `required_skills` đã lưu.

Estimated size: Small

## TASK-P0-05 — Enforce JWT revocation và user status ở mỗi request protected

Problem:
JWT chỉ được verify chữ ký/expiry; middleware không đối chiếu token đã logout hay trạng thái user hiện tại. Logout chỉ null `token_expires_at`, do đó JWT cũ vẫn có thể được dùng đến hết hạn; suspended user cũng không bị chặn bởi middleware.

Evidence:
`app/Http/Middlewares/AuthMiddleware.php:32-40` chỉ gọi `JWT::decode()`. `app/Infrastructure/AuthenticationRepository.php:100-108` logout chỉ cập nhật `token_expires_at`; token DB không được middleware dùng. Login chỉ chặn `banned` tại `:69-71`.

Affected files:
`app/Http/Middlewares/AuthMiddleware.php`, `app/Infrastructure/AuthenticationRepository.php`, auth/session tests.

Goal:
Logout và admin suspension/ban có hiệu lực với request tiếp theo, không chỉ với giao diện.

Implementation scope:
Chọn một kiểm tra server-side tối thiểu cho protected request (token/version/status), chuẩn hóa policy active/suspended/banned, và test token cũ sau logout/status change. Không cần refresh-token flow trong task này.

Acceptance criteria:
JWT logout không truy cập protected API; user suspended/banned bị từ chối protected API; JWT hợp lệ của active user tiếp tục hoạt động; public routes không bị ảnh hưởng.

Risk:
Medium — tăng query auth và cần tránh làm sai policy admin hiện hữu.

Estimated size: Medium

## TASK-P0-06 — Áp policy moderation cho trạng thái job

Problem:
Employer đã verified có thể PATCH job `hidden`/`rejected` về `published` và tự ghi/xóa `rejection_reason`, `published_at`, làm bypass moderation.

Evidence:
`app/Domain/JobService.php:110-127` merge toàn bộ request với job hiện hữu; whitelist status tại `:270-275` chứa `rejected`. `app/Domain/Job/Job.php:102-103` hydrate moderation fields từ input; `app/Infrastructure/JobRepository.php:324-325` persist chúng.

Affected files:
`app/Domain/JobService.php`, `app/Domain/Job/Job.php`, `app/Infrastructure/JobRepository.php`, admin/job tests.

Goal:
Chỉ admin được moderate; employer chỉ có các chuyển trạng thái hợp lệ của chính mình.

Implementation scope:
Định nghĩa transition matrix theo actor/current status; whitelist field employer được cập nhật; server quản lý moderation fields/timestamps. Job bị rejected/hidden chỉ có thể quay lại qua resubmit policy đã chọn.

Acceptance criteria:
Employer không publish lại hidden/rejected job, không sửa rejection reason/published timestamp; admin moderation vẫn hoạt động và audit đúng; draft/pending/published flow hợp lệ còn hoạt động.

Risk:
Medium — cần chốt rõ product policy resubmit sau rejection.

Estimated size: Medium

## TASK-P0-07 — Bảo vệ trạng thái withdrawn của application

Problem:
Employer có thể đổi application đã `withdrawn` thành `pending`, `viewed`, `shortlisted`, `accepted` hoặc `rejected`, phủ nhận quyết định rút đơn của student.

Evidence:
`app/Domain/ApplicationService.php:285-303` kiểm tra company ownership và trạng thái đích, nhưng không kiểm tra trạng thái hiện tại. Repository update trạng thái vô điều kiện.

Affected files:
`app/Domain/ApplicationService.php`, `app/Infrastructure/ApplicationRepository.php`, application status tests.

Goal:
Withdrawal là terminal state, trừ khi sau này có một flow apply lại do student chủ động thực hiện.

Implementation scope:
Thêm transition matrix cho employer/student và conditional update theo status hiện tại. Không thay đổi dữ liệu lịch sử trong task này.

Acceptance criteria:
Employer update withdrawn application bị 409/422; student chỉ withdraw pending/viewed theo policy; valid employer transitions vẫn tạo notification; employer không ghi được trường application nhạy cảm ngoài status/note.

Risk:
Small — cần đồng bộ message/status code với UI.

Estimated size: Small

# P1 — MVP Completion

## TASK-P1-01 — Làm apply/close/withdraw atomic

Problem:
Kiểm tra job eligibility nằm ngoài transaction application; read-then-write của auto-view và withdraw có thể ghi đè nhau. Student có thể apply sau khi job vừa close/hide, hoặc application withdrawn có thể bị ghi lại viewed.

Evidence:
`app/Domain/ApplicationService.php:53-61` đọc job trước transaction tại `:106`; Job/Application repositories dùng connection riêng. `:258-260` auto-view và `:343-358` withdraw là read-then-write; repository update không có current-status predicate.

Affected files:
`app/Domain/ApplicationService.php`, `app/Infrastructure/ApplicationRepository.php`, `app/Infrastructure/JobRepository.php`, concurrency integration tests.

Goal:
Mỗi transition quan trọng chỉ commit nếu precondition vẫn đúng tại thời điểm ghi.

Implementation scope:
Sử dụng shared transaction boundary/conditional SQL update and affected-row check; lock hoặc re-check job eligibility trong transaction; giữ unique application pair hiện có.

Acceptance criteria:
Double apply chỉ tạo một record; close/hide/delete thắng race thì apply không commit; withdraw không bị auto-view ghi đè; accept/withdraw race có một kết quả hợp lệ và response rõ ràng.

Risk:
Large — cần test đồng thời trên MySQL thực, không chạy trên shared production-like DB.

Estimated size: Large

## TASK-P1-02 — Enforce dữ liệu lookup cho job

Problem:
Job published có thể tham chiếu category/location không tồn tại, tạo filter/label sai và orphan reference.

Evidence:
`app/Migrations/PartTimeMarketplaceMigration.php:81-82` và `app/Migrations/JobPostingEnhancementMigration.php:49-50` thêm ID nullable không có FK. `app/Domain/JobService.php:278-286` chỉ kiểm tra category không rỗng, không kiểm tra tồn tại.

Affected files:
`app/Domain/JobService.php`, job repository/migrations nếu cần, validation tests.

Goal:
Job public luôn dùng category/location hợp lệ theo rule nghiệp vụ.

Implementation scope:
Server-side verify lookup ID; quyết định nullable cho draft. Chỉ thêm FK sau khi kiểm tra/clean dữ liệu cũ và quyết định cascade phù hợp.

Acceptance criteria:
Unknown category/location bị 422; published job có category hợp lệ; draft theo policy; filter trả kết quả nhất quán.

Risk:
Medium — migration FK có thể bị chặn bởi data legacy.

Estimated size: Small

## TASK-P1-03 — Enforce quan hệ một user–một company

Problem:
Code giả định một company/user nhưng schema không enforce; `findByUserId(... LIMIT 1)` chọn record không xác định nếu có trùng.

Evidence:
`app/Migrations/CompanyMigration.php:18` không có UNIQUE `companies.user_id`; `app/Infrastructure/CompanyRepository.php:61-65` dùng `LIMIT 1`; legacy create cũng nhận user ID từ payload.

Affected files:
`app/Migrations/CompanyMigration.php` hoặc migration mới, `app/Infrastructure/CompanyRepository.php`, onboarding tests.

Goal:
Ownership resolution của company/jobs/applications luôn xác định.

Implementation scope:
Rà dữ liệu trùng, chọn cách xử lý có chủ đích, thêm unique constraint, và dùng onboarding P0-03 làm đường tạo duy nhất.

Acceptance criteria:
Không thể tạo company thứ hai cho một user; lookup owner trả một kết quả xác định; migration fail an toàn khi còn duplicate chưa xử lý.

Risk:
Medium — cần xử lý dữ liệu đã tồn tại trước khi thêm constraint.

Estimated size: Medium

## TASK-P1-04 — Chốt policy khi company mất verification

Problem:
Company bị chuyển pending/rejected vẫn giữ jobs published public và vẫn nhận application.

Evidence:
`app/Domain/AdminService.php:131` chỉ update verification; public search tại `app/Infrastructure/JobRepository.php:88-94`, job detail tại `app/Domain/JobService.php:49-55`, và apply tại `app/Domain/ApplicationService.php:53-61` không xét verification/company status.

Affected files:
`app/Domain/AdminService.php`, `app/Infrastructure/JobRepository.php`, `app/Domain/JobService.php`, `app/Domain/ApplicationService.php`, moderation tests.

Goal:
Verification revoke có hành vi nhất quán và đúng với mục tiêu an toàn của moderation.

Implementation scope:
Chọn policy rõ ràng: ẩn/ngừng nhận đơn khi revoke hoặc có company suspension riêng; áp dụng đồng nhất cho search, detail, apply và dashboard.

Acceptance criteria:
Sau revoke, hành vi public/apply đúng policy; admin thấy trạng thái rõ ràng; khôi phục verification có hành vi được test.

Risk:
Medium — đây là quyết định product policy, không tự suy đoán từ code.

Estimated size: Medium

## TASK-P1-05 — Không công khai availability schedule trong hồ sơ student

Problem:
Public student profile trả về lịch rảnh, trong khi đây là dữ liệu cá nhân có thể dùng để suy ra lịch sinh hoạt.

Evidence:
`app/Domain/Profile/Profile.php:150-172` ghi chú public format nhưng vẫn trả `availability_schedule` ở `:165`. `GET /developers/{id}` là public theo `app/Http/Middlewares/AuthMiddleware.php:53-59`.

Affected files:
`app/Domain/Profile/Profile.php`, public profile tests, employer candidate UI nếu đang cần hiển thị aggregate.

Goal:
Chỉ owner thấy lịch chi tiết; employer chỉ thấy thông tin tối thiểu cần cho tuyển dụng nếu product chọn cho phép.

Implementation scope:
Loại lịch chi tiết khỏi public DTO; nếu cần matching, chỉ trả derived availability trong endpoint authorized sau này.

Acceptance criteria:
Guest không nhận schedule qua `/developers`; student profile private vẫn có dữ liệu; không lộ email/phone/CV/employer note.

Risk:
Small — có thể ảnh hưởng UI public profile nếu đang hiển thị field này.

Estimated size: Small

## TASK-P1-06 — Tách và an toàn hóa test environment

Problem:
Một số test script xóa toàn bảng và không bootstrap `.env`, có thể đụng database dùng chung hoặc database mặc định.

Evidence:
`test_milestone2c.php:58-60` xóa `applications`; `test_milestone2e.php:62-64` xóa `notifications` và `applications`. Các script chỉ require autoload, trong khi `Config::env()` fallback DB tại `app/Facades/Config.php:9-13`; URL HTTP hardcode tại `test_milestone2c.php:7`.

Affected files:
Test bootstrap, `test_milestone2c.php`, `test_milestone2e.php`, config/CI documentation.

Goal:
Regression tests không thể ghi/xóa dữ liệu development/shared database.

Implementation scope:
Tạo bootstrap test với test DB/base URL riêng, guard environment bắt buộc, fixture cleanup theo ID/prefix; không chạy test destructive cho đến khi guard tồn tại.

Acceptance criteria:
Test refuse chạy nếu không phải test environment; cleanup chỉ chạm fixture; suite không hardcode local production-like URL; có hướng dẫn chạy an toàn.

Risk:
Medium — cần hạ tầng MySQL test tách biệt.

Estimated size: Medium

# P2 — Quality Improvements

## TASK-P2-01 — Sửa redirect sau login và open-redirect edge case

Problem:
Login guard không chặn dạng `/%5C...` trên browser có thể normalize thành external URL; query `login_required` bị `main.js` xóa trước khi login page đọc redirect, làm user mất trang job đang apply.

Evidence:
`app/Views/auth/login.php:87-95` chỉ check prefix `/`, `//`, `://`. `public/assets/js/main.js:26-29` xóa toàn query khi có `login_required`; detail job chuyển guest tới login với redirect.

Affected files:
`app/Views/auth/login.php`, `public/assets/js/main.js`, job detail/login browser tests.

Goal:
Redirect chỉ là internal path hợp lệ và apply flow quay lại đúng job.

Implementation scope:
Validate bằng URL parser/origin allowlist, giữ redirect khi xóa flash flag, test encoded slash/backslash và query path.

Acceptance criteria:
Redirect external/encoded bypass bị fallback an toàn; guest click apply → login → trở về đúng job; logout flash vẫn hoạt động.

Risk:
Small — cần chú ý URL query hợp lệ.

Estimated size: Small

## TASK-P2-02 — Sửa lỗi UI khi lưu company profile đổi tên

Problem:
Lưu profile thành công rồi JS gọi `TokenStorage.setUser()` không tồn tại, gây TypeError và làm state navbar không đồng bộ.

Evidence:
`app/Views/company/profile.php:221-228` gọi `setUser`; `public/assets/js/api.js:9-44` không định nghĩa method này.

Affected files:
`app/Views/company/profile.php`, `public/assets/js/api.js`, browser test profile.

Goal:
Company profile save hoàn tất không có client-side exception.

Implementation scope:
Thêm API storage tối thiểu hoặc dùng method hiện có nhất quán; test state sau save.

Acceptance criteria:
Save thành công không lỗi console; user cached/name navbar được cập nhật theo contract chọn; API response vẫn hiển thị đúng.

Risk:
Small.

Estimated size: Small

## TASK-P2-03 — Dùng lookup động và hiển thị đơn vị lương đúng trong job search

Problem:
Sidebar hardcode category/location không khớp seed, không có đủ filter hiện có, và label “đ/giờ” lọc chung dữ liệu hourly/daily/monthly.

Evidence:
`app/Views/jobs/index.php:19-36` hardcode lookup khác `app/Database/Seeder.php:27-32,56-61`. `JobRepository::buildWhereClause()` lọc salary raw tại `app/Infrastructure/JobRepository.php:159-175`, nhưng UI không gửi `salary_type`.

Affected files:
`app/Views/jobs/index.php`, job search JS/view, `app/Infrastructure/JobRepository.php`, search tests.

Goal:
Student hiểu kết quả filter và không so sánh sai lương khác đơn vị.

Implementation scope:
Load category/location từ API hiện có; expose work type/shift/salary type phù hợp; chỉ cho salary range khi đơn vị đã xác định hoặc mô tả rõ semantics.

Acceptance criteria:
Tên lookup khớp DB; weekend/work type/salary type theo product scope được gửi đúng; hourly filter không trộn monthly/daily; empty/loading/error state vẫn rõ ràng.

Risk:
Medium — cần đồng thuận product semantics cho lương negotiable.

Estimated size: Medium

## TASK-P2-04 — Chuẩn hóa canonical work_type/work_mode sau migration

Problem:
Filter `part_time` còn match legacy `type=part-time`, nên internship có thể xuất hiện sai; migration đặt default part_time/onsite cho row cũ mà không map dữ liệu legacy tương ứng.

Evidence:
`app/Infrastructure/JobRepository.php:135-150` dùng OR canonical/legacy. `app/Migrations/JobPostingEnhancementMigration.php:52-57` gán default; chỉ deadline/published_at được backfill ở `:86-88`.

Affected files:
Migration mới, `app/Infrastructure/JobRepository.php`, job filter migration tests.

Goal:
Search/filter phản ánh chính xác loại việc và work mode.

Implementation scope:
Backfill mapping cụ thể từ legacy data, xác minh kết quả, sau đó ưu tiên canonical field; chỉ fallback cho row chưa migrate nếu thật sự cần.

Acceptance criteria:
Internship không xuất hiện khi filter part-time; remote/freelance legacy được map đúng; re-run migration an toàn.

Risk:
Medium — cần backup và kiểm tra data legacy trước migration.

Estimated size: Medium

## TASK-P2-05 — Làm migration rollback fail-safe

Problem:
Production rollback guard không hoạt động vì Config không trả `env`; runner xóa migration record dù down no-op/thất bại và tắt FK checks toàn batch.

Evidence:
`app/Migrations/JobPostingEnhancementMigration.php:108-114` đọc `$config['env']`, nhưng `app/Facades/Config.php:7-15` không trả key này. `migrate.php:55-71` tắt FK, gọi down/fallback drop và xóa tracking record ngay tại `:67`.

Affected files:
`app/Facades/Config.php`, `migrate.php`, enhancement migrations, migration tests/documentation.

Goal:
Rollback không làm schema/tracking lệch hoặc phá production ngoài ý muốn.

Implementation scope:
Centralize production guard qua `Config::isProduction()`, fail khi migration không rollback được, chỉ delete tracking sau success; bỏ fallback nguy hiểm nếu không có down rõ ràng.

Acceptance criteria:
Production rollback bị chặn trước DDL; failed/no-op rollback giữ migration record; test trên DB disposable chứng minh up/down consistency.

Risk:
Large — schema migration là thao tác rủi ro, cần backup trước deploy.

Estimated size: Medium

## TASK-P2-06 — Bổ sung test business/security còn thiếu và browser test thật

Problem:
Coverage hiện có có assertion hữu ích nhưng browser E2E là cURL/string matching, không chạy JS; thiếu test các lỗ hổng/race đã xác nhận.

Evidence:
`test_browser_e2e_fe3.php:8-46,65-69` dùng cURL và kiểm tra HTML string. Test hiện tại không cover public admin registration, legacy company CRUD ownership/cascade, revoke verification, concurrency, hoặc onboarding company mới.

Affected files:
Test harness, API integration tests, browser runner/CI config.

Goal:
Các bug P0/P1 không tái xuất và UI flow chính được kiểm chứng thực tế.

Implementation scope:
Sau P1-06, thêm test API isolated cho từng security/business rule và tối thiểu browser automation cho register company → profile → draft job, student apply/withdraw, login return URL.

Acceptance criteria:
Mỗi task P0/P1 có regression test; browser test thực thi JS/click/navigation; CI chỉ chạy against guarded test DB.

Risk:
Medium — phụ thuộc task test environment.

Estimated size: Large

## TASK-P2-07 — Đồng bộ PROJECT_CONTEXT với implementation/schema thực tế

Problem:
Tài liệu mô tả một số tên bảng/cột khác code migration thực tế, dễ khiến agent hoặc developer sửa sai contract.

Evidence:
`docs/PROJECT_CONTEXT.md` nêu `profiles`, `applications.user_id`, `cv_snapshot_url`; migration/code dùng `student_profiles`, `applications.developer_id`, `resume`/`cv_url`.

Affected files:
`docs/PROJECT_CONTEXT.md`, `docs/api.md` nếu có contract cũ.

Goal:
Documentation phản ánh schema/API thực tế sau khi các P0/P1 contract được chốt.

Implementation scope:
Đối chiếu migration và endpoint hiện hành; cập nhật flow/status/field names, loại bỏ claim chưa kiểm chứng.

Acceptance criteria:
Tên entity/field/endpoint trong docs khớp code; có ghi rõ legacy alias nếu còn hỗ trợ.

Risk:
Small.

Estimated size: Small

# P3 — Optional

## TASK-P3-01 — Upload CV có kiểm soát

Problem:
Student hiện chỉ lưu URL CV, không có upload trực tiếp hay kiểm soát tệp.

Evidence:
`docs/PROJECT_CONTEXT.md` ghi upload CV là partial; `ProfileService` chỉ validate URL tại `app/Domain/ProfileService.php:244-251`.

Affected files:
Profile API/service, storage adapter, UI, file-security tests.

Goal:
Cho student đính kèm CV an toàn khi MVP core ổn định.

Implementation scope:
Multipart endpoint, allowlist MIME/extension/size, file naming/access policy và virus scanning strategy phù hợp hạ tầng. Không đưa vào P1 nếu chưa có storage vận hành.

Acceptance criteria:
Chỉ định dạng/kích thước cho phép được lưu; file không execute/public đoán được; student chỉ sửa/xóa CV của mình; employer chỉ đọc CV qua quyền application.

Risk:
Large — file upload là security surface mới.

Estimated size: Large

## TASK-P3-02 — Email notification cho sự kiện quan trọng

Problem:
Notification hiện chỉ in-app, người dùng có thể bỏ lỡ trạng thái application.

Evidence:
`docs/PROJECT_CONTEXT.md` xác nhận SMTP/email chưa triển khai; `ApplicationService` và AdminService đã có điểm phát notification in-app.

Affected files:
Notification service, config, queue/outbox strategy, preferences UI, tests.

Goal:
Gửi email cho application status và moderation event sau khi reliability core ổn định.

Implementation scope:
Thêm delivery adapter, preference/opt-out tối thiểu và retry/logging; không chặn transaction business vì lỗi email.

Acceptance criteria:
Event core vẫn thành công khi mail fail; email không lộ employer note/PII dư thừa; user có thể tắt loại notification phù hợp.

Risk:
Medium.

Estimated size: Medium

## TASK-P3-03 — Matching lịch rảnh với ca làm

Problem:
Nền tảng đã lưu schedule nhưng chưa dùng để giúp student chọn job phù hợp.

Evidence:
Profile có `available_schedule`; job có `shift_type`/`working_schedule`, nhưng search hiện không match hai nguồn dữ liệu này.

Affected files:
Job search service/repository, profile/job DTO, search UI, privacy policy.

Goal:
Gợi ý độ phù hợp theo ca mà không công khai lịch chi tiết.

Implementation scope:
Chỉ triển khai sau P1-05; bắt đầu với derived match score hoặc filter available shift, không cần AI/microservice.

Acceptance criteria:
Student có thể lọc/gợi ý job phù hợp lịch; schedule raw không public; kết quả giải thích được theo ca.

Risk:
Medium.

Estimated size: Medium

