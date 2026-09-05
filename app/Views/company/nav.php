<?php
$tab = $activeTab ?? "dashboard";
?>
<div class="portal-header company-header">
    <div class="container">
        <div class="portal-header-top">
            <div class="portal-header-content">
                <div class="portal-eyebrow">Nhà Tuyển Dụng</div>
                <div class="portal-title-row">
                    <h1 class="portal-title">
                        <svg class="portal-title-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
                        <span>Cổng Nhà Tuyển Dụng</span>
                    </h1>
                    <span id="nav-company-verify-badge" class="status-badge" style="display:none;"></span>
                </div>
                <p class="portal-desc">
                    Đăng tin part-time, quản lý ứng viên sinh viên và theo dõi hoạt động tuyển dụng.
                </p>
            </div>
            <div class="portal-actions">
                <a href="/company/jobs/create" class="btn btn-primary btn-sm">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    <span>Đăng Tin Tuyển Dụng</span>
                </a>
            </div>
        </div>

        <nav class="portal-nav-rail student-nav-tabs" aria-label="Điều hướng cổng nhà tuyển dụng">
            <a href="/company/dashboard" class="portal-tab student-tab <?= $tab === 'dashboard' ? 'active' : '' ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/></svg>
                <span>Tổng Quan</span>
            </a>
            <a href="/company/profile" class="portal-tab student-tab <?= $tab === 'profile' ? 'active' : '' ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="2" width="16" height="20" rx="2" ry="2"/><line x1="9" y1="22" x2="9" y2="22.01"/><line x1="15" y1="22" x2="15" y2="22.01"/><line x1="9" y1="6" x2="9" y2="6.01"/><line x1="15" y1="6" x2="15" y2="6.01"/><line x1="9" y1="10" x2="9" y2="10.01"/><line x1="15" y1="10" x2="15" y2="10.01"/><line x1="9" y1="14" x2="9" y2="14.01"/><line x1="15" y1="14" x2="15" y2="14.01"/><line x1="9" y1="18" x2="9" y2="18.01"/><line x1="15" y1="18" x2="15" y2="18.01"/></svg>
                <span>Hồ Sơ Công Ty</span>
            </a>
            <a href="/company/jobs" class="portal-tab student-tab <?= $tab === 'jobs' ? 'active' : '' ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
                <span>Tin Tuyển Dụng</span>
            </a>
            <a href="/company/applications" class="portal-tab student-tab <?= $tab === 'applications' ? 'active' : '' ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                <span>Đơn Ứng Tuyển</span>
            </a>
            <a href="/company/notifications" class="portal-tab student-tab <?= $tab === 'notifications' ? 'active' : '' ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                <span>Thông Báo</span>
                <span id="nav-company-notif-badge" class="portal-tab-badge" style="display:none;">0</span>
            </a>
        </nav>
    </div>
</div>

<script>
// Shared Company Verification Status Fetcher for Navbar
(async function initCompanyNavBadge() {
    if (typeof TokenStorage === "undefined" || !TokenStorage.isLoggedIn()) return;
    const user = TokenStorage.getUser();
    if (!user || user.role !== "company") return;

    try {
        const res = await apiRequest("/company/profile");
        if (res && res.success && res.data) {
            const badge = document.getElementById("nav-company-verify-badge");
            if (!badge) return;
            const status = res.data.verification_status || "pending";
            badge.style.display = "inline-flex";
            if (status === "verified") {
                badge.className = "status-badge status-badge--success";
                badge.innerHTML = '<span class="status-dot"></span> Đã Xác Minh';
            } else if (status === "rejected") {
                badge.className = "status-badge status-badge--danger";
                badge.innerHTML = '<span class="status-dot"></span> Bị Từ Chối';
            } else {
                badge.className = "status-badge status-badge--warning";
                badge.innerHTML = '<span class="status-dot"></span> Chờ Xác Minh';
            }
        }
    } catch (_) {}
})();
</script>
