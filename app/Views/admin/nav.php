<?php
$currentTab = $activeTab ?? 'dashboard';
?>

<div style="background:#fff;border-bottom:1px solid var(--border);margin-bottom:2rem;box-shadow:0 1px 3px rgba(0,0,0,0.03);">
    <div class="container" style="padding-top:1.25rem;padding-bottom:0;">
        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;margin-bottom:1rem;">
            <div style="display:flex;align-items:center;gap:0.75rem;">
                <span style="font-size:1.75rem;line-height:1;">🛡️</span>
                <div>
                    <h1 style="font-size:1.4rem;font-weight:800;color:var(--dark);margin:0;">
                        Cổng Quản Trị Hệ Thống
                    </h1>
                    <div style="font-size:0.85rem;color:var(--text-muted);">
                        Kiểm duyệt nội dung, quản lý người dùng & bảo đảm an ninh nền tảng JobMarketplace
                    </div>
                </div>
            </div>

            <div style="display:flex;align-items:center;gap:0.75rem;">
                <span class="badge" style="background:#fee2e2;color:#991b1b;font-size:0.85rem;padding:0.4rem 0.85rem;border-radius:20px;font-weight:700;">
                    🛡️ Administrator
                </span>
            </div>
        </div>

        <!-- Navigation Tabs -->
        <div style="display:flex;gap:0.5rem;overflow-x:auto;padding-bottom:0;border-bottom:1px solid transparent;">
            <a href="/admin/dashboard" class="tab-link <?= $currentTab === 'dashboard' ? 'active' : '' ?>">
                📊 Tổng Quan
            </a>
            <a href="/admin/users" class="tab-link <?= $currentTab === 'users' ? 'active' : '' ?>">
                👥 Người Dùng
            </a>
            <a href="/admin/companies" class="tab-link <?= $currentTab === 'companies' ? 'active' : '' ?>">
                🏢 Doanh Nghiệp
            </a>
            <a href="/admin/jobs" class="tab-link <?= $currentTab === 'jobs' ? 'active' : '' ?>">
                💼 Tin Tuyển Dụng
            </a>
            <a href="/admin/audit-logs" class="tab-link <?= $currentTab === 'audit_logs' ? 'active' : '' ?>">
                📜 Nhật Ký Kiểm Duyệt
            </a>
        </div>
    </div>
</div>

<style>
.tab-link {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.65rem 1.15rem;
    font-size: 0.92rem;
    font-weight: 600;
    color: var(--text-muted);
    text-decoration: none;
    border-bottom: 3px solid transparent;
    transition: var(--transition);
    white-space: nowrap;
}
.tab-link:hover {
    color: var(--primary);
}
.tab-link.active {
    color: #b91c1c;
    border-bottom-color: #b91c1c;
    font-weight: 700;
}
</style>
