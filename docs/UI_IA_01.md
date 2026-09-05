# UI-IA-01 — Role-based information architecture and navigation

## Scope and evidence

This review covers existing web routes, the shared header, `main.js`, and the Student, Company, and Admin portal navigation views. It does not propose new product capabilities or route changes.

The shared header always renders **Trang Chủ** and **Tìm Việc Làm**. `main.js` only replaces the right-side authenticated actions. Consequently, a Company or Admin user still sees public job-search navigation. The authenticated header also repeats portal entry points that already exist as portal tabs:

- Student: global **Cổng Sinh Viên**, linked role badge, bell, and portal tabs.
- Company: global **Cổng Tuyển Dụng**, linked role badge, bell, and portal tabs.
- Admin: global **Cổng Quản Trị**, linked role badge, and portal tabs.

## PUBLIC NAVIGATION

| Menu item | Purpose | Current route | Recommendation |
| --- | --- | --- | --- |
| Trang chủ | Introduce the marketplace and guide first-time users | `/` | Keep. |
| Tìm việc làm | Discover and filter public jobs | `/viec-lam` | Keep as the public primary navigation item. |
| Đăng nhập | Start an authenticated session | `/login` | Keep in the global header. |
| Đăng ký | Create a Student or Company account | `/register` | Keep as the public primary CTA. |

Public navigation should remain short. It must not expose portal-specific management links because those pages require an authenticated role.

## STUDENT NAVIGATION

### Recommended global header after login

| Menu item | Purpose | Current route | Recommendation |
| --- | --- | --- | --- |
| Tìm việc làm | Return to the discovery/search context from any Student page | `/viec-lam` | Keep as the only global task navigation item. |
| Thông báo | Quick access to unread updates | `/student/notifications` | Keep as a global utility bell with unread count. |
| Tài khoản | Identify the signed-in user and expose logout | existing account area | Keep as a utility area; do not make a second portal-entry CTA. |
| Đăng xuất | End the session | existing `handleLogout()` | Keep. |

Remove the global **Cổng Sinh Viên** button and the duplicate clickable role badge. The logo may continue to link to `/`.

### Recommended Student portal navigation

| Menu item | Purpose | Current route | Recommendation |
| --- | --- | --- | --- |
| Tổng quan | Review application and profile summary | `/student/dashboard` | Keep. |
| Hồ sơ cá nhân | Maintain profile, CV link, skills and availability | `/student/profile` | Keep. |
| Đơn ứng tuyển | Track and withdraw eligible applications | `/student/applications` | Keep. |
| Việc đã lưu | Revisit saved jobs | `/student/favorites` | Keep. |
| Tìm kiếm đã lưu | Re-run saved job filters | `/student/saved-searches` | Keep. |
| Thông báo | View all notifications | `/student/notifications` | Move out of the portal tabs because the global bell already owns notification entry and unread count. |

The Student portal header should not repeat **Tìm Việc Làm Mới** as a CTA when **Tìm việc làm** already exists in the logged-in global header. It may retain contextual non-navigation guidance, but no duplicate search entry point.

## EMPLOYER NAVIGATION

### Recommended global header after login

| Menu item | Purpose | Current route | Recommendation |
| --- | --- | --- | --- |
| Thông báo | Quick access to applicant and verification updates | `/company/notifications` | Keep as a global utility bell with unread count. |
| Tài khoản | Identify the signed-in Company user and expose logout | existing account area | Keep as utility only. |
| Đăng xuất | End the session | existing `handleLogout()` | Keep. |

Remove global **Trang Chủ** and **Tìm Việc Làm** for Company accounts. Recruiting is the Company’s primary workflow; job search is neither a primary destination nor an account-management need. Also remove the global **Cổng Tuyển Dụng** button and linked role badge because the user is already inside that portal.

### Recommended Company portal navigation

| Menu item | Purpose | Current route | Recommendation |
| --- | --- | --- | --- |
| Tổng quan | Recruitment summary and pending work | `/company/dashboard` | Keep. |
| Tin tuyển dụng | Create, edit, close and review owned jobs | `/company/jobs` | Keep. |
| Ứng viên | Review applications across owned jobs | `/company/applications` | Rename from **Đơn Ứng Tuyển** to make the recruiter task clear; route stays unchanged. |
| Hồ sơ công ty | Maintain company information and see verification state | `/company/profile` | Keep. |
| Thông báo | View all recruitment/verification notifications | `/company/notifications` | Move out of portal tabs because the global bell owns this utility. |

The portal header CTA **Đăng Tin Tuyển Dụng** belongs here and remains the sole primary Company CTA. It should not be duplicated in the global header or dashboard hero.

## ADMIN NAVIGATION

### Recommended global header after login

| Menu item | Purpose | Current route | Recommendation |
| --- | --- | --- | --- |
| Tài khoản | Identify the administrator and expose logout | existing account area | Keep as utility only. |
| Đăng xuất | End the session | existing `handleLogout()` | Keep. |

Remove global **Trang Chủ**, **Tìm Việc Làm**, **Cổng Quản Trị**, and the duplicate linked role badge for Admin. The Admin role is a moderation workspace; public discovery navigation distracts from the task and grants no useful operational shortcut.

### Recommended Admin portal navigation

| Menu item | Purpose | Current route | Recommendation |
| --- | --- | --- | --- |
| Tổng quan | Review platform health and moderation queue | `/admin/dashboard` | Keep. |
| Doanh nghiệp | Verify or reject company profiles | `/admin/companies` | Keep; place before Users because this is the first onboarding moderation queue. |
| Tin tuyển dụng | Moderate job content and visibility | `/admin/jobs` | Keep. |
| Người dùng | Manage user account status | `/admin/users` | Keep. |
| Nhật ký kiểm duyệt | Audit completed administrator actions | `/admin/audit-logs` | Keep as the final, lower-frequency item. |

Admin has no current notification route. Do not add one for IA work.

## Current role and duplication issues

| Finding | Evidence | Recommended resolution |
| --- | --- | --- |
| Employer sees job-seeking navigation | Shared `layouts/main.php` always renders `/viec-lam`; `main.js` does not replace `.nav-links` by role | Hide public discovery navigation for Company users. |
| Admin sees job-seeking navigation | Same shared header behavior | Hide public discovery navigation for Admin users. |
| Portal entry is repeated | `main.js` renders both a portal button and a linked role badge; each portal’s first tab is its dashboard | Keep portal navigation as the workspace entry; remove duplicate global portal controls. |
| Notification entry is repeated | Global bell and Student/Company portal notification tab lead to the same route | Keep one global bell with unread count; remove the portal notification tab. |
| Student search entry is repeated | Shared `/viec-lam` link and Student portal header CTA both initiate search | Keep discovery in the logged-in global header; remove portal header search CTA. |
| Employer primary CTA is correctly scoped but needs protection | Company portal header owns `/company/jobs/create` | Keep only this portal CTA; do not add it to global header or dashboard hero. |

## CTA placement policy

| Context | CTA policy |
| --- | --- |
| Public global header | Only **Đăng ký** is primary; **Đăng nhập** is secondary. |
| Student global header | **Tìm việc làm** is a navigation item, not a repeated button. Notification and account are utilities. |
| Student portal | Do not repeat the global search CTA. Page-level actions may remain contextual only. |
| Company global header | No workflow CTA. Show only notification, account and logout utilities. |
| Company portal | **Đăng Tin Tuyển Dụng** is the sole primary CTA. |
| Admin global header | No workflow CTA. Show account and logout utilities only. |
| Admin portal | Use contextual moderation links inside dashboard content; do not add a global primary CTA. |

## UI-IA-01

### Implementation scope

1. Make the shared global navigation role-aware from the authenticated user data already available in `TokenStorage`.
2. Preserve the public header for guests.
3. For Student, retain global job discovery and notification utility; remove duplicate portal-entry controls and the portal search CTA.
4. For Company, remove public job-seeking global links; retain notification/account/logout utilities; rename the existing Company applications tab to **Ứng viên**; remove the duplicate portal notification tab.
5. For Admin, remove public job-seeking global links and duplicate portal-entry controls; order existing moderation tabs as Dashboard, Companies, Jobs, Users, Audit Logs.
6. Keep all existing routes and use the existing notification routes/count logic. No API or permission changes are required.

### Do NOT change

- Do not add routes, APIs, roles, permissions, notification types, features, or business workflow.
- Do not change route guards, authentication, logout behavior, unread-count fetching, or backend authorization.
- Do not remove access to an existing destination; relocate or de-duplicate its navigation entry only.
- Do not introduce a separate Employer job-search flow or an Admin notification feature.

### Acceptance criteria

- Guest sees only public discovery/auth navigation.
- Student sees job discovery once globally, one notification entry, and all Student-management destinations once in the portal.
- Company sees no global **Trang Chủ** or **Tìm Việc Làm** item, has exactly one **Đăng Tin Tuyển Dụng** primary CTA in the portal, and can reach all current Company destinations.
- Admin sees only moderation-oriented portal navigation plus account/logout utilities, with no public job-search item.
- No global portal button or linked role badge duplicates a portal dashboard tab.
- Every existing navigation route remains reachable by its appropriate role, and responsive tab scrolling continues to work.
