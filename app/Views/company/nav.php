<?php
$tab = $activeTab ?? "dashboard";
?>
<div class="company-header" style="background:#fff;border-bottom:1px solid var(--border);padding:1.5rem 0 0;margin-bottom:2rem;">
    <div class="container">
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;margin-bottom:1.5rem;">
            <div>
                <div style="display:flex;align-items:center;gap:0.75rem;margin-bottom:0.25rem;">
                    <h1 style="font-size:1.6rem;font-weight:800;color:var(--dark);margin:0;">
                        🏢 Cổng Nhà Tuyển Dụng
                    </h1>
                    <span id="nav-company-verify-badge" class="badge" style="display:none;font-size:0.8rem;padding:0.25rem 0.6rem;border-radius:20px;font-weight:700;"></span>
                </div>
                <p style="color:var(--text-muted);font-size:0.92rem;margin:0;">
                    Đăng tin part-time, quản lý ứng viên sinh viên và theo dõi hoạt động tuyển dụng.
                </p>
            </div>
            <div style="display:flex;gap:0.75rem;">
                <a href="/company/jobs/create" class="btn btn-primary btn-sm">
                    ➕ Đăng Tin Tuyển Dụng
                </a>
            </div>
        </div>

        <div class="student-nav-tabs" style="display:flex;gap:0.5rem;overflow-x:auto;border-bottom:2px solid transparent;">
            <a href="/company/dashboard" class="student-tab <?= $tab === 'dashboard' ? 'active' : '' ?>">
                📊 Tổng Quan
            </a>
            <a href="/company/profile" class="student-tab <?= $tab === 'profile' ? 'active' : '' ?>">
                🏢 Hồ Sơ Công Ty
            </a>
            <a href="/company/jobs" class="student-tab <?= $tab === 'jobs' ? 'active' : '' ?>">
                📋 Tin Tuyển Dụng
            </a>
            <a href="/company/applications" class="student-tab <?= $tab === 'applications' ? 'active' : '' ?>">
                👥 Đơn Ứng Tuyển
            </a>
            <a href="/company/notifications" class="student-tab <?= $tab === 'notifications' ? 'active' : '' ?>">
                🔔 Thông Báo <span id="nav-company-notif-badge" class="badge badge-primary" style="display:none;font-size:0.75rem;padding:0.15rem 0.4rem;border-radius:10px;">0</span>
            </a>
        </div>
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
            badge.style.display = "inline-block";
            if (status === "verified") {
                badge.style.background = "#dcfce7";
                badge.style.color = "#166534";
                badge.innerHTML = "✓ Đã Xác Minh";
            } else if (status === "rejected") {
                badge.style.background = "#fee2e2";
                badge.style.color = "#991b1b";
                badge.innerHTML = "✗ Bị Từ Chối";
            } else {
                badge.style.background = "#fef3c7";
                badge.style.color = "#92400e";
                badge.innerHTML = "⏳ Chờ Xác Minh";
            }
        }
    } catch (_) {}
})();
</script>
