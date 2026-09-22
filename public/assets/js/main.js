/**
 * Job Marketplace Main Interactions (Vanilla JS)
 * Navbar state, Mobile Drawer, Auth sync
 */

let navbarInitialized = false;

document.addEventListener("DOMContentLoaded", () => {
    // 1. Mobile Menu Toggle
    const menuToggle = document.querySelector(".menu-toggle");
    const navLinks = document.querySelector(".nav-links");

    if (menuToggle && navLinks) {
        menuToggle.addEventListener("click", () => {
            navLinks.classList.toggle("active");
        });
    }

    // 2. Synchronize Navigation with Auth State (single execution guarded)
    updateNavbarAuthState();

    // 3. Check for Flash Message from URL Query
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has("logged_out")) {
        showToast("Bạn đã đăng xuất tài khoản thành công.", "success");
        window.history.replaceState({}, document.title, window.location.pathname);
    }
    if (urlParams.has("login_required")) {
        showToast("Vui lòng đăng nhập để tiếp tục thao tác.", "error");
        window.history.replaceState({}, document.title, window.location.pathname);
    }
    if (urlParams.has("password_updated")) {
        showToast("Mật khẩu đã được cập nhật. Vui lòng đăng nhập lại.", "success");
        window.history.replaceState({}, document.title, window.location.pathname);
    }
});

// Immediate execution if DOM elements exist to prevent visual flash
if (document.querySelector(".nav-actions")) {
    updateNavbarAuthState();
}

function updateNavbarAuthState(force = false) {
    if (navbarInitialized && !force) return;
    const navActions = document.querySelector(".nav-actions");
    if (!navActions) return;

    navbarInitialized = true;

    const navLinks = document.querySelector(".nav-links");
    const menuToggle = document.querySelector(".menu-toggle");
    const navbar = document.querySelector(".navbar");

    const user = TokenStorage.getUser();
    const token = TokenStorage.getToken();
    const path = window.location.pathname;

    const isCurrentHome = path === "/" || path === "";
    const isCurrentJobs = path.startsWith("/viec-lam");
    const brandLogo = document.querySelector(".brand-logo");
    if (brandLogo) {
        if (token && user && user.role === "admin") {
            brandLogo.href = "/admin/dashboard";
        } else if (token && user && user.role === "company") {
            brandLogo.href = "/company/dashboard";
        } else {
            brandLogo.href = "/";
        }
    }

    if (token && user) {
        if (navbar) {
            navbar.classList.add("navbar--auth", "navbar--role");
        }

        if (user.role === "student" || user.role === "developer") {
            // Student: keep daily job actions visible; place personal data and settings in the account menu.
            if (navLinks) {
                navLinks.innerHTML = `
                    <li><a href="/viec-lam" class="nav-link ${isCurrentJobs ? 'active' : ''}">Tìm việc</a></li>
                    <li><a href="/student/recommendations" class="nav-link ${path.startsWith('/student/recommendations') ? 'active' : ''}">Gợi ý phù hợp</a></li>
                    <li><a href="/student/applications" class="nav-link ${path.startsWith('/student/applications') ? 'active' : ''}">Đơn ứng tuyển</a></li>
                    <li><a href="/student/favorites" class="nav-link ${path.startsWith('/student/favorites') ? 'active' : ''}">Việc đã lưu</a></li>
                `;
                navLinks.style.display = "";
            }
            if (menuToggle) {
                menuToggle.style.display = "";
            }

            const initial = (user.name ? user.name.charAt(0) : (user.email ? user.email.charAt(0) : "S")).toUpperCase();

            navActions.innerHTML = `
                <div class="nav-user-cluster">
                    <a href="/student/notifications" class="nav-utility-bell" title="Thông báo" aria-label="Thông báo">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                        <span id="nav-unread-badge" class="nav-unread-badge" style="display:none;">0</span>
                    </a>

                    <!-- TopCV User Profile Dropdown Menu -->
                    <div class="user-profile-menu-wrapper" id="nav-user-menu-wrapper">
                        <button type="button" class="user-avatar-btn" id="nav-user-avatar-btn" aria-haspopup="true" aria-expanded="false" title="Tài khoản cá nhân">
                            <span class="user-avatar-circle">${escapeHtml(initial)}</span>
                            <span class="user-avatar-name">${escapeHtml(user.name || user.email)}</span>
                            <svg class="theme-chevron" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                        </button>

                        <div class="user-profile-dropdown" id="nav-user-profile-dropdown">
                            <div class="profile-dropdown-header">
                                <div class="profile-header-avatar">${escapeHtml(initial)}</div>
                                <div class="profile-header-info">
                                    <div class="profile-header-name">${escapeHtml(user.name || "Sinh viên")}</div>
                                    <div class="profile-header-sub">${escapeHtml(user.email || "")}</div>
                                    <div style="margin-top:0.25rem;"><span class="status-badge status-badge--success" style="font-size:0.7rem;padding:0.1rem 0.4rem;">✓ Đã xác thực</span></div>
                                </div>
                            </div>

                            <div class="profile-dropdown-body">
                                <div class="profile-menu-section">
                                    <div class="profile-section-title">Không gian của bạn</div>
                                    <a href="/student/dashboard" class="profile-menu-item">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/></svg>
                                        <span>Tổng quan hoạt động</span>
                                    </a>
                                    <a href="/student/saved-searches" class="profile-menu-item">
                                        <i class="ri-notification-3-line"></i>
                                        <span>Bộ lọc và cảnh báo việc làm</span>
                                    </a>
                                </div>

                                <div class="profile-menu-section">
                                    <div class="profile-section-title">Hồ sơ & CV</div>
                                    <a href="/student/profile" class="profile-menu-item">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                        <span>Hồ sơ cá nhân</span>
                                    </a>
                                    <a href="/student/cvs" class="profile-menu-item">
                                        <i class="ri-file-user-line"></i>
                                        <span>CV của tôi</span>
                                    </a>
                                    <a href="/mau-cv-sinh-vien" class="profile-menu-item">
                                        <i class="ri-layout-4-line"></i>
                                        <span>Tạo CV từ mẫu</span>
                                    </a>
                                </div>

                                <div class="profile-menu-section">
                                    <div class="profile-section-title">Cài đặt tài khoản</div>
                                    <a href="/student/notifications" class="profile-menu-item">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                                        <span>Thông báo hệ thống</span>
                                    </a>
                                    <button type="button" onclick="openPasswordSecurityModal()" class="profile-menu-item profile-menu-button">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="10" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                                        <span>Đổi mật khẩu</span>
                                    </button>
                                    <a href="javascript:void(0)" onclick="handleLogout()" class="profile-menu-item logout-item">
                                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                                        <span>Đăng xuất</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            initProfileDropdown();
            fetchUnreadCount();
        } else if (user.role === "company") {
            // Employer: expose recruitment operations directly in the global header.
            if (navLinks) {
                navLinks.innerHTML = `
                    <li><a href="/company/dashboard" class="nav-link ${path === '/company/dashboard' ? 'active' : ''}">Tổng quan</a></li>
                    <li><a href="/company/jobs" class="nav-link ${path.startsWith('/company/jobs') && !path.endsWith('/create') ? 'active' : ''}">Tin tuyển dụng</a></li>
                    <li><a href="/company/applications" class="nav-link ${path.startsWith('/company/applications') ? 'active' : ''}">Ứng viên</a></li>
                    <li><a href="/company/jobs/create" class="nav-link nav-link--accent ${path === '/company/jobs/create' ? 'active' : ''}"><i class="ri-add-line"></i> Đăng tin</a></li>
                `;
                navLinks.style.display = "";
            }
            if (menuToggle) {
                menuToggle.style.display = "";
            }

            const initial = (user.name ? user.name.charAt(0) : (user.email ? user.email.charAt(0) : "D")).toUpperCase();

            navActions.innerHTML = `
                <div class="nav-user-cluster">
                    <a href="/company/notifications" class="nav-utility-bell" title="Thông báo" aria-label="Thông báo">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                        <span id="nav-unread-badge" class="nav-unread-badge" style="display:none;">0</span>
                    </a>
                    <div class="user-profile-menu-wrapper" id="nav-user-menu-wrapper">
                        <button type="button" class="user-avatar-btn" id="nav-user-avatar-btn" aria-haspopup="true" aria-expanded="false" title="Tài khoản nhà tuyển dụng">
                            <span class="user-avatar-circle user-avatar-circle--company">${escapeHtml(initial)}</span>
                            <span class="user-avatar-name">${escapeHtml(user.name || user.email)}</span>
                            <svg class="theme-chevron" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                        </button>
                        <div class="user-profile-dropdown" id="nav-user-profile-dropdown">
                            <div class="profile-dropdown-header">
                                <div class="profile-header-avatar user-avatar-circle--company">${escapeHtml(initial)}</div>
                                <div class="profile-header-info">
                                    <div class="profile-header-name">${escapeHtml(user.name || "Nhà tuyển dụng")}</div>
                                    <div class="profile-header-sub">${escapeHtml(user.email || "")}</div>
                                    <div style="margin-top:.3rem"><span id="nav-company-verify-badge" class="status-badge status-badge--warning">Đang kiểm tra</span></div>
                                </div>
                            </div>
                            <div class="profile-dropdown-body">
                                <div class="profile-menu-section">
                                    <div class="profile-section-title">Doanh nghiệp</div>
                                    <a href="/company/profile" class="profile-menu-item"><i class="ri-building-line"></i><span>Hồ sơ công ty</span></a>
                                    <a href="/company/notifications" class="profile-menu-item"><i class="ri-notification-3-line"></i><span>Thông báo tuyển dụng</span></a>
                                </div>
                                <div class="profile-menu-section">
                                    <div class="profile-section-title">Tài khoản</div>
                                    <button type="button" onclick="openPasswordSecurityModal()" class="profile-menu-item profile-menu-button"><i class="ri-lock-password-line"></i><span>Đổi mật khẩu</span></button>
                                    <a href="javascript:void(0)" onclick="handleLogout()" class="profile-menu-item logout-item"><i class="ri-logout-box-r-line"></i><span>Đăng xuất</span></a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            initProfileDropdown();
            fetchUnreadCount();
            fetchCompanyVerificationBadge();
        } else if (user.role === "admin") {
            // Admin: all moderation areas remain one click away in the global header.
            if (navLinks) {
                navLinks.innerHTML = `
                    <li><a href="/admin/dashboard" class="nav-link ${path === '/admin/dashboard' ? 'active' : ''}">Tổng quan</a></li>
                    <li><a href="/admin/companies" class="nav-link ${path.startsWith('/admin/companies') ? 'active' : ''}">Doanh nghiệp</a></li>
                    <li><a href="/admin/jobs" class="nav-link ${path === '/admin/jobs' ? 'active' : ''}">Tin tuyển dụng</a></li>
                    <li><a href="/admin/job-reports" class="nav-link ${path.startsWith('/admin/job-reports') ? 'active' : ''}">Báo cáo tin</a></li>
                    <li><a href="/admin/users" class="nav-link ${path.startsWith('/admin/users') ? 'active' : ''}">Người dùng</a></li>
                    <li><a href="/admin/audit-logs" class="nav-link ${path.startsWith('/admin/audit-logs') ? 'active' : ''}">Nhật ký</a></li>
                    <li><a href="/admin/support" class="nav-link ${path.startsWith('/admin/support') ? 'active' : ''}">Hỗ trợ</a></li>
                `;
                navLinks.style.display = "";
            }
            if (menuToggle) {
                menuToggle.style.display = "";
            }

            const initial = (user.name ? user.name.charAt(0) : (user.email ? user.email.charAt(0) : "A")).toUpperCase();

            navActions.innerHTML = `
                <div class="nav-user-cluster">
                    <div class="user-profile-menu-wrapper" id="nav-user-menu-wrapper">
                        <button type="button" class="user-avatar-btn user-avatar-btn--admin" id="nav-user-avatar-btn" aria-haspopup="true" aria-expanded="false" title="Tài khoản quản trị">
                            <span class="user-avatar-circle user-avatar-circle--admin">${escapeHtml(initial)}</span>
                            <span class="user-avatar-name">${escapeHtml(user.name || user.email)}</span>
                            <svg class="theme-chevron" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                        </button>
                        <div class="user-profile-dropdown" id="nav-user-profile-dropdown">
                            <div class="profile-dropdown-header">
                                <div class="profile-header-avatar user-avatar-circle--admin">${escapeHtml(initial)}</div>
                                <div class="profile-header-info">
                                    <div class="profile-header-name">${escapeHtml(user.name || "Quản trị viên")}</div>
                                    <div class="profile-header-sub">${escapeHtml(user.email || "")}</div>
                                    <div style="margin-top:.3rem"><span class="status-badge status-badge--danger">Quản trị viên</span></div>
                                </div>
                            </div>
                            <div class="profile-dropdown-body">
                                <div class="profile-menu-section">
                                    <div class="profile-section-title">Tài khoản</div>
                                    <button type="button" onclick="openPasswordSecurityModal()" class="profile-menu-item profile-menu-button"><i class="ri-lock-password-line"></i><span>Đổi mật khẩu</span></button>
                                    <a href="javascript:void(0)" onclick="handleLogout()" class="profile-menu-item logout-item"><i class="ri-logout-box-r-line"></i><span>Đăng xuất</span></a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            initProfileDropdown();
        }
    } else {
        if (navbar) {
            navbar.classList.remove("navbar--auth", "navbar--role");
        }
        // GUEST: Public discovery navigation
        if (navLinks) {
            navLinks.innerHTML = `
                <li><a href="/" class="nav-link ${isCurrentHome ? 'active' : ''}">Trang Chủ</a></li>
                <li><a href="/viec-lam" class="nav-link ${isCurrentJobs ? 'active' : ''}">Tìm Việc Làm</a></li>
            `;
            navLinks.style.display = "";
        }
        if (menuToggle) {
            menuToggle.style.display = "";
        }

        navActions.innerHTML = `
            <a href="/login" class="btn btn-outline btn-sm">Đăng nhập</a>
            <a href="/register" class="btn btn-primary btn-sm">Đăng ký</a>
        `;
    }
}

async function fetchUnreadCount() {
    try {
        const res = await apiRequest("/notifications/unread-count");
        if (res && res.success && res.data && res.data.unread_count !== undefined) {
            const count = res.data.unread_count;
            const badge = document.getElementById("nav-unread-badge");
            if (badge) {
                badge.innerText = count;
                badge.style.display = count > 0 ? "inline-block" : "none";
            }
        }
    } catch (_) {}
}

async function fetchCompanyVerificationBadge() {
    const badge = document.getElementById("nav-company-verify-badge");
    if (!badge) return;

    try {
        const res = await apiRequest("/company/profile");
        const status = res && res.success && res.data
            ? (res.data.verification_status || "pending")
            : "pending";

        if (status === "verified") {
            badge.className = "status-badge status-badge--success";
            badge.textContent = "Đã xác minh";
        } else if (status === "rejected") {
            badge.className = "status-badge status-badge--danger";
            badge.textContent = "Cần cập nhật hồ sơ";
        } else {
            badge.className = "status-badge status-badge--warning";
            badge.textContent = "Chờ xác minh";
        }
    } catch (_) {
        badge.className = "status-badge status-badge--warning";
        badge.textContent = "Chưa xác minh";
    }
}

function handleLogout() {
    TokenStorage.clear();
    showToast("Đang đăng xuất...", "success");
    setTimeout(() => {
        window.location.href = "/login?logged_out=1";
    }, 500);
}

function initProfileDropdown() {
    const btn = document.getElementById("nav-user-avatar-btn");
    const dropdown = document.getElementById("nav-user-profile-dropdown");
    if (!btn || !dropdown) return;

    btn.addEventListener("click", (e) => {
        e.stopPropagation();
        const isOpen = dropdown.classList.toggle("open");
        btn.setAttribute("aria-expanded", isOpen ? "true" : "false");
    });

    document.addEventListener("click", (e) => {
        if (!dropdown.contains(e.target) && !btn.contains(e.target)) {
            dropdown.classList.remove("open");
            btn.setAttribute("aria-expanded", "false");
        }
    });
}
