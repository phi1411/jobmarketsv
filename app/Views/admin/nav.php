<?php
$currentTab = $activeTab ?? 'dashboard';
?>

<div class="portal-header admin-header">
    <div class="container">
        <div class="portal-header-top">
            <div class="portal-header-content">
                <div class="portal-eyebrow">Quản Trị Hệ Thống</div>
                <div class="portal-title-row">
                    <h1 class="portal-title">
                        <svg class="portal-title-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                        <span>Cổng Quản Trị Hệ Thống</span>
                    </h1>
                </div>
                <p class="portal-desc">
                    Kiểm duyệt nội dung, quản lý người dùng & bảo đảm an ninh nền tảng JobMarketSV.
                </p>
            </div>
            <div class="portal-actions" style="display:flex;align-items:center;gap:0.75rem;">
                <div class="theme-switcher-dropdown" id="admin-portal-theme-dropdown">
                    <button type="button" class="theme-switcher-btn" aria-label="Đổi giao diện: Sáng, Tối hoặc Hệ thống" title="Đổi giao diện">
                        <span class="theme-icon-slot"></span>
                        <span class="theme-btn-label">Hệ thống</span>
                        <svg class="theme-chevron" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                    </button>
                    <div class="theme-menu">
                        <button type="button" class="theme-menu-item" data-theme-val="light">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
                            <span>Sáng</span>
                            <span class="theme-check-icon">✓</span>
                        </button>
                        <button type="button" class="theme-menu-item" data-theme-val="dark">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
                            <span>Tối</span>
                            <span class="theme-check-icon">✓</span>
                        </button>
                        <button type="button" class="theme-menu-item" data-theme-val="system">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                            <span>Hệ thống</span>
                            <span class="theme-check-icon">✓</span>
                        </button>
                    </div>
                </div>
                <span class="badge" style="background:#fee2e2;color:#991b1b;font-size:0.85rem;padding:0.4rem 0.85rem;border-radius:20px;font-weight:700;display:inline-flex;align-items:center;gap:0.4rem;border:1px solid #fecaca;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                    <span>Administrator</span>
                </span>
            </div>
        </div>

        <!-- Navigation Tabs -->
        <nav class="portal-nav-rail" aria-label="Điều hướng cổng quản trị">
            <a href="/admin/dashboard" class="portal-tab <?= $currentTab === 'dashboard' ? 'active' : '' ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/></svg>
                <span>Tổng Quan</span>
            </a>
            <a href="/admin/companies" class="portal-tab <?= $currentTab === 'companies' ? 'active' : '' ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
                <span>Doanh Nghiệp</span>
            </a>
            <a href="/admin/jobs" class="portal-tab <?= $currentTab === 'jobs' ? 'active' : '' ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                <span>Tin Tuyển Dụng</span>
            </a>
            <a href="/admin/job-reports" class="portal-tab <?= $currentTab === 'job_reports' ? 'active' : '' ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><line x1="4" y1="22" x2="4" y2="15"/></svg>
                <span>Báo Cáo Tin</span>
            </a>
            <a href="/admin/users" class="portal-tab <?= $currentTab === 'users' ? 'active' : '' ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                <span>Người Dùng</span>
            </a>
            <a href="/admin/audit-logs" class="portal-tab <?= $currentTab === 'audit_logs' ? 'active' : '' ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                <span>Nhật Ký Kiểm Duyệt</span>
            </a>
            <a href="/admin/support" class="portal-tab <?= $currentTab === 'support' ? 'active' : '' ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                <span>Hỗ Trợ Trực Tuyến</span>
            </a>
        </nav>
    </div>
</div>
