/**
 * Job Marketplace Main Interactions (Vanilla JS)
 * Navbar state, Mobile Drawer, Auth sync
 */

document.addEventListener("DOMContentLoaded", () => {
    // 1. Mobile Menu Toggle
    const menuToggle = document.querySelector(".menu-toggle");
    const navLinks = document.querySelector(".nav-links");

    if (menuToggle && navLinks) {
        menuToggle.addEventListener("click", () => {
            navLinks.classList.toggle("active");
        });
    }

    // 2. Synchronize Navigation with Auth State
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

function updateNavbarAuthState() {
    const navActions = document.querySelector(".nav-actions");
    if (!navActions) return;

    const user = TokenStorage.getUser();
    const token = TokenStorage.getToken();

    if (token && user) {
        let roleBadge = "";
        let dashboardLink = "#";

        if (user.role === "student" || user.role === "developer") {
            roleBadge = `<a href="/student/dashboard" style="text-decoration:none;"><span class="badge badge-shift" style="cursor:pointer;">🎓 Cổng Sinh viên</span></a>`;
            dashboardLink = "/student/dashboard";
            fetchUnreadCount();
        } else if (user.role === "company") {
            roleBadge = `<a href="/company/dashboard" style="text-decoration:none;"><span class="badge badge-salary" style="cursor:pointer;">🏢 Cổng Tuyển Dụng</span></a>`;
            dashboardLink = "/company/dashboard";
            fetchUnreadCount();
        } else if (user.role === "admin") {
            roleBadge = `<a href="/admin/dashboard" style="text-decoration:none;"><span class="badge" style="background:#fee2e2;color:#b91c1c;cursor:pointer;">🛡️ Quản trị</span></a>`;
            dashboardLink = "/admin/dashboard";
        }

        navActions.innerHTML = `
            <div style="display:flex;align-items:center;gap:0.75rem;">
                ${(user.role === "student" || user.role === "developer") ? `
                    <a href="/student/notifications" style="position:relative;display:inline-flex;align-items:center;font-size:1.25rem;text-decoration:none;padding:0.25rem;" title="Thông báo">
                        🔔
                        <span id="nav-unread-badge" style="display:none;position:absolute;top:-2px;right:-4px;background:var(--danger);color:#fff;font-size:0.7rem;font-weight:800;border-radius:10px;padding:0.1rem 0.35rem;line-height:1;">0</span>
                    </a>
                    <a href="/student/dashboard" class="btn btn-outline btn-sm" style="font-weight:600;">Cổng Sinh Viên</a>
                ` : ''}
                ${(user.role === "company") ? `
                    <a href="/company/notifications" style="position:relative;display:inline-flex;align-items:center;font-size:1.25rem;text-decoration:none;padding:0.25rem;" title="Thông báo">
                        🔔
                        <span id="nav-unread-badge" style="display:none;position:absolute;top:-2px;right:-4px;background:var(--danger);color:#fff;font-size:0.7rem;font-weight:800;border-radius:10px;padding:0.1rem 0.35rem;line-height:1;">0</span>
                    </a>
                    <a href="/company/dashboard" class="btn btn-outline btn-sm" style="font-weight:600;">Cổng Tuyển Dụng</a>
                ` : ''}
                ${(user.role === "admin") ? `
                    <a href="/admin/dashboard" class="btn btn-outline btn-sm" style="font-weight:600;border-color:#fca5a5;color:#991b1b;">🛡️ Cổng Quản Trị</a>
                ` : ''}
                <div style="text-align:right;line-height:1.2;">
                    <div style="font-weight:700;font-size:0.9rem;color:var(--dark);">${escapeHtml(user.name || user.email)}</div>
                    <div>${roleBadge}</div>
                </div>
                <button onclick="handleLogout()" class="btn btn-outline btn-sm" title="Đăng xuất">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                    Đăng xuất
                </button>
            </div>
        `;
    } else {
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
