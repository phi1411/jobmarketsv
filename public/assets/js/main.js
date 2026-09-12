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

            navActions.innerHTML = `
                <div class="nav-user-cluster">
                    <a href="/student/notifications" class="nav-utility-bell" title="Thông báo" aria-label="Thông báo">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                        <span id="nav-unread-badge" class="nav-unread-badge" style="display:none;">0</span>
                    </a>
                    <a href="/student/dashboard" class="btn btn-outline btn-sm nav-portal-btn" title="Vào Cổng Sinh Viên" aria-label="Cổng Sinh Viên">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
                        <span>Cổng Sinh Viên</span>
                    </a>
                    <a href="/student/dashboard" class="nav-user-info" title="Vào Cổng Sinh Viên">
                        <div class="nav-user-name">${escapeHtml(user.name || user.email)}</div>
                        <div class="nav-user-role"><span class="status-badge status-badge--info">Sinh viên</span></div>
                    </a>
                    <button onclick="handleLogout()" class="btn btn-outline btn-sm nav-logout-btn" title="Đăng xuất" aria-label="Đăng xuất">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                        <span>Đăng xuất</span>
                    </button>
                </div>
            `;
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
