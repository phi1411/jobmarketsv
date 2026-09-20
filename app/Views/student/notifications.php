<?php include __DIR__ . "/nav.php"; ?>

<div class="container" style="margin-bottom:3rem;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;flex-wrap:wrap;gap:1rem;">
        <div>
            <h2 style="font-size:1.25rem;font-weight:700;color:var(--dark);margin:0;">
                Thông Báo Của Tôi
            </h2>
            <span id="notif-count-text" style="font-size:0.88rem;color:var(--text-muted);">Đang tải thông báo...</span>
        </div>
        <div>
            <button id="btn-read-all" onclick="handleMarkAllRead()" class="btn btn-outline btn-sm">
                Đánh Dấu Tất Cả Đã Đọc
            </button>
        </div>
    </div>

    <!-- Loading State -->
    <div id="notif-loading" style="text-align:center;padding:2rem 0;">
        <div class="job-card skeleton" style="height:90px;margin-bottom:0.75rem;"></div>
        <div class="job-card skeleton" style="height:90px;margin-bottom:0.75rem;"></div>
        <div class="job-card skeleton" style="height:90px;"></div>
    </div>

    <!-- Container -->
    <div id="notif-container" style="display:flex;flex-direction:column;gap:0.75rem;"></div>

    <!-- Empty State -->
    <div id="notif-empty" class="surface-card empty-state" style="display:none;">
        <div class="empty-state-icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
        </div>
        <h3 class="empty-state-title">Hộp Thư Thông Báo Trống</h3>
        <p class="empty-state-text">
            Hiện tại bạn chưa có thông báo mới nào từ nhà tuyển dụng hoặc hệ thống.
        </p>
    </div>
</div>

<script>
function initStudentNotificationsPage() {
    if (!TokenStorage.isLoggedIn()) {
        showToast("Vui lòng đăng nhập để xem thông báo.", "error");
        window.location.href = `/login?login_required=1&redirect=${encodeURIComponent(window.location.pathname)}`;
        return;
    }
    const user = TokenStorage.getUser();
    if (!user || (user.role !== "student" && user.role !== "developer")) {
        showToast("Chỉ tài khoản Sinh viên mới có quyền xem trang này.", "error");
        setTimeout(() => { window.location.href = "/viec-lam"; }, 1000);
        return;
    }

    loadNotifications();
}

if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initStudentNotificationsPage);
} else {
    initStudentNotificationsPage();
}

async function loadNotifications() {
    const loadingEl = document.getElementById("notif-loading");
    const container = document.getElementById("notif-container");
    const emptyEl = document.getElementById("notif-empty");
    const countText = document.getElementById("notif-count-text");

    if (loadingEl) loadingEl.style.display = "block";
    if (container) container.innerHTML = "";
    if (emptyEl) emptyEl.style.display = "none";

    try {
        const res = await apiRequest("/notifications", { requireAuth: true });

        if (res && res.success && Array.isArray(res.data)) {
            const notifs = res.data;
            const unreadCount = notifs.filter(n => !n.read_at).length;
            if (countText) countText.innerText = `Có ${notifs.length} thông báo (${unreadCount} chưa đọc)`;

            // Sync navbar badges
            updateBadgeUI(unreadCount);

            if (notifs.length === 0) {
                if (emptyEl) emptyEl.style.display = "block";
                return;
            }

            container.innerHTML = notifs.map(n => {
                const isUnread = !n.read_at;
                const matchScore = n.data && Number.isFinite(Number(n.data.match_score)) ? parseInt(n.data.match_score) : null;
                const targetUrl = safeNotificationUrl(n.data && n.data.url);
                return `
                    <div class="notification-item ${isUnread ? 'notification-item--unread' : ''}">
                        <div style="display:flex;gap:var(--space-3);flex:1;min-width:0;">
                            <div class="notification-item-icon">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                            </div>
                            <div class="notification-item-content">
                                <div class="notification-item-header">
                                    <h4 class="notification-item-title">
                                        ${escapeHtml(n.title)}
                                    </h4>
                                    ${isUnread ? '<span class="status-dot" style="background:var(--primary);" title="Chưa đọc" aria-label="Chưa đọc"></span>' : ''}
                                </div>
                                <div class="notification-item-desc">
                                    ${escapeHtml(n.message)}
                                </div>
                                <div class="notification-item-meta">
                                    <span>${formatDate(n.created_at)}</span>
                                    ${matchScore !== null ? `<span class="badge" style="background:var(--primary-light);color:var(--primary-text);border:1px solid var(--primary-border);">Phù hợp ${matchScore}%</span>` : ''}
                                </div>
                            </div>
                        </div>

                        ${(isUnread || targetUrl) ? `
                            <div class="row-actions" style="display:flex;gap:.5rem;flex-wrap:wrap;">
                                ${targetUrl ? `<a href="${escapeHtml(targetUrl)}" class="btn btn-primary btn-sm row-action-btn">${targetUrl === '/student/applications' ? 'Xem đơn ứng tuyển' : 'Xem việc làm'}</a>` : ''}
                                ${isUnread ? `
                                <button onclick="handleMarkRead('${escapeHtml(n.id)}')" class="btn btn-outline btn-sm row-action-btn" style="white-space:nowrap;" aria-label="Đánh dấu thông báo đã đọc">
                                    Đánh dấu đã đọc
                                </button>
                                ` : ''}
                            </div>
                        ` : ''}
                    </div>
                `;
            }).join("");
        } else {
            if (emptyEl) emptyEl.style.display = "block";
            if (countText) countText.innerText = "Không thể tải thông báo.";
        }
    } catch (err) {
        console.error("Error loading notifications:", err);
        if (emptyEl) emptyEl.style.display = "block";
        if (countText) countText.innerText = "Lỗi kết nối máy chủ.";
    } finally {
        if (loadingEl) loadingEl.style.display = "none";
    }
}

function safeNotificationUrl(value) {
    return typeof value === "string" && (value === "/student/applications" || /^\/viec-lam\/[a-zA-Z0-9_-]+$/.test(value)) ? value : null;
}

function updateBadgeUI(count) {
    const badgeTab = document.getElementById("nav-student-notif-badge");
    if (badgeTab) {
        badgeTab.innerText = count;
        badgeTab.style.display = count > 0 ? "inline-block" : "none";
    }
    const navBadge = document.getElementById("nav-unread-badge");
    if (navBadge) {
        navBadge.innerText = count;
        navBadge.style.display = count > 0 ? "inline-block" : "none";
    }
}

async function handleMarkRead(id) {
    const res = await apiRequest(`/notifications/${encodeURIComponent(id)}/read`, {
        method: "PATCH",
        requireAuth: true
    });

    if (res && res.success) {
        loadNotifications();
    }
}

async function handleMarkAllRead() {
    const res = await apiRequest("/notifications/read-all", {
        method: "PATCH",
        requireAuth: true
    });

    if (res && res.success) {
        showToast("Đã đánh dấu tất cả thông báo là đã đọc.", "success");
        loadNotifications();
    } else {
        showToast((res && res.message) ? res.message : "Thao tác thất bại.", "error");
    }
}
</script>
