<?php include __DIR__ . "/nav.php"; ?>

<div class="container" style="margin-bottom:3rem;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;flex-wrap:wrap;gap:1rem;">
        <div>
            <h2 style="font-size:1.25rem;font-weight:700;color:var(--dark);margin:0;">
                🔔 Thông Báo Nhà Tuyển Dụng
            </h2>
            <span id="notif-count-text" style="font-size:0.88rem;color:var(--text-muted);">Đang tải thông báo...</span>
        </div>
        <div>
            <button id="btn-read-all" onclick="handleMarkAllRead()" class="btn btn-outline btn-sm">
                ✅ Đánh Dấu Tất Cả Đã Đọc
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
    <div id="notif-empty" style="display:none;background:#fff;border-radius:var(--radius);border:1px solid var(--border);padding:3.5rem 1.5rem;text-align:center;">
        <div style="font-size:3rem;margin-bottom:1rem;">📭</div>
        <h3 style="font-size:1.25rem;font-weight:700;color:var(--dark);margin-bottom:0.5rem;">Hộp Thư Thông Báo Trống</h3>
        <p style="color:var(--text-muted);max-width:450px;margin:0 auto;">
            Hiện tại bạn chưa có thông báo mới nào từ hệ thống hoặc ứng viên.
        </p>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {
    // Auth UX Guard
    if (!TokenStorage.isLoggedIn()) {
        showToast("Vui lòng đăng nhập với tài khoản Doanh nghiệp.", "error");
        window.location.href = `/login?login_required=1&redirect=${encodeURIComponent(window.location.pathname)}`;
        return;
    }
    const user = TokenStorage.getUser();
    if (!user || user.role !== "company") {
        showToast("Chỉ tài khoản Nhà tuyển dụng mới có quyền truy cập trang này.", "error");
        setTimeout(() => {
            window.location.href = (user && user.role === "student") ? "/student/dashboard" : "/";
        }, 800);
        return;
    }

    loadCompanyNotifications();
});

async function loadCompanyNotifications() {
    const loadingEl = document.getElementById("notif-loading");
    const container = document.getElementById("notif-container");
    const emptyEl = document.getElementById("notif-empty");
    const countText = document.getElementById("notif-count-text");

    loadingEl.style.display = "block";
    container.innerHTML = "";
    emptyEl.style.display = "none";

    const res = await apiRequest("/notifications", { requireAuth: true });
    loadingEl.style.display = "none";

    if (res && res.success && Array.isArray(res.data)) {
        const notifs = res.data;
        const unreadCount = notifs.filter(n => !n.read_at).length;
        countText.innerText = `Có ${notifs.length} thông báo (${unreadCount} chưa đọc)`;

        // Sync badges
        updateBadgeUI(unreadCount);

        if (notifs.length === 0) {
            emptyEl.style.display = "block";
            return;
        }

        container.innerHTML = notifs.map(n => {
            const isUnread = !n.read_at;
            return `
                <div class="job-card" style="padding:1.25rem;margin:0;display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;background:${isUnread ? '#f8fafc' : '#fff'};border-left:${isUnread ? '4px solid var(--primary)' : '1px solid var(--border)'};">
                    <div style="display:flex;gap:1rem;flex:1;">
                        <div style="font-size:1.5rem;line-height:1;">
                            ${isUnread ? '📩' : '✉️'}
                        </div>
                        <div style="flex:1;">
                            <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:0.25rem;">
                                <h4 style="font-size:1rem;font-weight:${isUnread ? '800' : '600'};color:var(--dark);margin:0;">
                                    ${escapeHtml(n.title)}
                                </h4>
                                ${isUnread ? '<span style="width:8px;height:8px;background:var(--primary);border-radius:50%;display:inline-block;" title="Chưa đọc"></span>' : ''}
                            </div>
                            <div style="font-size:0.88rem;color:var(--text);line-height:1.5;margin-bottom:0.5rem;">
                                ${escapeHtml(n.message)}
                            </div>
                            <div style="font-size:0.75rem;color:var(--text-muted);">
                                🕒 ${formatDate(n.created_at)}
                            </div>
                        </div>
                    </div>

                    ${isUnread ? `
                        <div>
                            <button onclick="handleMarkRead('${escapeHtml(n.id)}')" class="btn btn-outline btn-sm" style="font-size:0.78rem;white-space:nowrap;">
                                Đánh dấu đã đọc
                            </button>
                        </div>
                    ` : ''}
                </div>
            `;
        }).join("");
    } else {
        emptyEl.style.display = "block";
        countText.innerText = "Không thể tải thông báo.";
    }
}

function updateBadgeUI(count) {
    const badgeTab = document.getElementById("nav-company-notif-badge");
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
        loadCompanyNotifications();
    }
}

async function handleMarkAllRead() {
    const res = await apiRequest("/notifications/read-all", {
        method: "PATCH",
        requireAuth: true
    });

    if (res && res.success) {
        showToast("Đã đánh dấu tất cả thông báo là đã đọc.", "success");
        loadCompanyNotifications();
    } else {
        showToast((res && res.message) ? res.message : "Thao tác thất bại.", "error");
    }
}
</script>
