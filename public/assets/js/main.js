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
            // STUDENT: Global header only keeps job discovery (Tìm việc làm), notification utility, account/logout
            if (navLinks) {
                navLinks.innerHTML = `
                    <li><a href="/viec-lam" class="nav-link ${isCurrentJobs ? 'active' : ''}">Tìm Việc Làm</a></li>
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
                    <div class="nav-user-info">
                        <div class="nav-user-name">${escapeHtml(user.name || user.email)}</div>
                        <div class="nav-user-role"><span class="status-badge status-badge--info">Sinh viên</span></div>
                    </div>
                    <button onclick="handleLogout()" class="btn btn-outline btn-sm nav-logout-btn" title="Đăng xuất" aria-label="Đăng xuất">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                        <span>Đăng xuất</span>
                    </button>
                </div>
            `;
            fetchUnreadCount();
        } else if (user.role === "company") {
            // COMPANY: Remove global Trang Chủ & Tìm Việc Làm, remove duplicate Cổng Tuyển Dụng, keep notification & account/logout
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
                    <div class="nav-user-info">
                        <div class="nav-user-name">${escapeHtml(user.name || user.email)}</div>
                        <div class="nav-user-role"><span class="status-badge status-badge--success">Nhà tuyển dụng</span></div>
                    </div>
                    <button onclick="handleLogout()" class="btn btn-outline btn-sm nav-logout-btn" title="Đăng xuất" aria-label="Đăng xuất">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                        <span>Đăng xuất</span>
                    </button>
                </div>
            `;
            fetchUnreadCount();
        } else if (user.role === "admin") {
            // ADMIN: Remove global Trang Chủ & Tìm Việc Làm, remove duplicate Cổng Quản Trị, keep account/logout only, no notification
            if (navLinks) {
                navLinks.innerHTML = "";
                navLinks.style.display = "none";
            }
            if (menuToggle) {
                menuToggle.style.display = "none";
            }

            navActions.innerHTML = `
                <div class="nav-user-cluster">
                    <div class="nav-user-info">
                        <div class="nav-user-name">${escapeHtml(user.name || user.email)}</div>
                        <div class="nav-user-role"><span class="status-badge status-badge--danger">Quản trị viên</span></div>
                    </div>
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
