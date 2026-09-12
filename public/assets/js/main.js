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
    const isCurrentStudent = path.startsWith("/student");
    const isCurrentCompany = path.startsWith("/company");
    const isCurrentAdmin = path.startsWith("/admin");
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
            navbar.classList.add("navbar--auth");
        }

        if (user.role === "student" || user.role === "developer") {
            // STUDENT: Global header keeps job discovery (Tìm việc làm) and portal entry (Cổng Sinh Viên), notification utility, account/logout
            if (navLinks) {
                navLinks.innerHTML = `
                    <li><a href="/viec-lam" class="nav-link ${isCurrentJobs ? 'active' : ''}">Tìm Việc Làm</a></li>
                    <li><a href="/student/dashboard" class="nav-link ${isCurrentStudent ? 'active' : ''}">Cổng Sinh Viên</a></li>
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
                                    <div class="profile-section-title">Quản lý tìm việc</div>
                                    <a href="/viec-lam" class="profile-menu-item">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                                        <span>Bảng tin việc làm</span>
                                    </a>
                                    <a href="/student/dashboard" class="profile-menu-item">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/></svg>
                                        <span>Cổng Sinh Viên</span>
                                    </a>
                                    <a href="/student/applications" class="profile-menu-item">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                                        <span>Việc đã ứng tuyển</span>
                                    </a>
                                    <a href="/student/favorites" class="profile-menu-item">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                                        <span>Việc đã lưu</span>
                                    </a>
                                    <a href="/student/recommendations" class="profile-menu-item">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m12 3-1.9 4.6L5 9.5l4 3.3L8.5 18l3.5-2.3 3.5 2.3-.5-5.2 4-3.3-5.1-1.9z"/><path d="M19 3v4M21 5h-4"/></svg>
                                        <span>Gợi ý việc làm AI</span>
                                    </a>
                                </div>

                                <div class="profile-menu-section">
                                    <div class="profile-section-title">Hồ sơ & CV</div>
                                    <a href="/student/profile" class="profile-menu-item">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                        <span>Hồ sơ cá nhân & CV</span>
                                    </a>
                                </div>

                                <div class="profile-menu-section">
                                    <div class="profile-section-title">Cài đặt tài khoản</div>
                                    <a href="/student/notifications" class="profile-menu-item">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                                        <span>Thông báo hệ thống</span>
                                    </a>
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
            // COMPANY: Remove global Trang Chủ & Tìm Việc Làm, provide Cổng Tuyển Dụng entry, notification & account/logout
            if (navLinks) {
                navLinks.innerHTML = "";
                navLinks.style.display = "none";
            }
            if (menuToggle) {
                menuToggle.style.display = "none";
            }

            navActions.innerHTML = `
                <div class="nav-user-cluster">
                    <a href="/company/notifications" class="nav-utility-bell" title="Thông báo" aria-label="Thông báo">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                        <span id="nav-unread-badge" class="nav-unread-badge" style="display:none;">0</span>
                    </a>
                    <a href="/company/dashboard" class="btn btn-outline btn-sm nav-portal-btn" title="Vào Cổng Tuyển Dụng" aria-label="Cổng Tuyển Dụng">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
                        <span>Cổng Tuyển Dụng</span>
                    </a>
                    <a href="/company/dashboard" class="nav-user-info" title="Vào Cổng Tuyển Dụng">
                        <div class="nav-user-name">${escapeHtml(user.name || user.email)}</div>
                        <div class="nav-user-role"><span class="status-badge status-badge--success">Nhà tuyển dụng</span></div>
                    </a>
                    <button onclick="handleLogout()" class="btn btn-outline btn-sm nav-logout-btn" title="Đăng xuất" aria-label="Đăng xuất">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                        <span>Đăng xuất</span>
                    </button>
                </div>
            `;
            fetchUnreadCount();
        } else if (user.role === "admin") {
            // ADMIN: Remove global Trang Chủ & Tìm Việc Làm, provide Cổng Quản Trị entry, account/logout only
            if (navLinks) {
                navLinks.innerHTML = "";
                navLinks.style.display = "none";
            }
            if (menuToggle) {
                menuToggle.style.display = "none";
            }

            navActions.innerHTML = `
                <div class="nav-user-cluster">
                    <a href="/admin/dashboard" class="btn btn-outline btn-sm nav-portal-btn nav-portal-btn--admin" title="Vào Cổng Quản Trị" aria-label="Cổng Quản Trị">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                        <span>Cổng Quản Trị</span>
                    </a>
                    <a href="/admin/dashboard" class="nav-user-info" title="Vào Cổng Quản Trị">
                        <div class="nav-user-name">${escapeHtml(user.name || user.email)}</div>
                        <div class="nav-user-role"><span class="status-badge status-badge--danger">Quản trị viên</span></div>
                    </a>
                    <button onclick="handleLogout()" class="btn btn-outline btn-sm nav-logout-btn" title="Đăng xuất" aria-label="Đăng xuất">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                        <span>Đăng xuất</span>
                    </button>
                </div>
            `;
        }
    } else {
        if (navbar) {
            navbar.classList.remove("navbar--auth");
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
