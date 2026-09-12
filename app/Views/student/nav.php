<?php
$tab = $activeTab ?? "dashboard";
?>
<div class="portal-header student-header">
    <div class="container">
        <div class="portal-header-top">
            <div class="portal-header-content">
                <div class="portal-eyebrow">Sinh Viên</div>
                <div class="portal-title-row">
                    <h1 class="portal-title">
                        <svg class="portal-title-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
                        <span>Cổng Thông Tin Sinh Viên</span>
                    </h1>
                </div>
                <p class="portal-desc">
                    Quản lý hồ sơ, theo dõi trạng thái ứng tuyển và tìm kiếm cơ hội việc làm part-time.
                </p>
            </div>
            <div class="portal-actions">
                <div class="theme-switcher-dropdown" id="student-portal-theme-dropdown">
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
            </div>
        </div>

        <nav class="portal-nav-rail student-nav-tabs" aria-label="Điều hướng cổng sinh viên">
            <a href="/student/dashboard" class="portal-tab student-tab <?= $tab === 'dashboard' ? 'active' : '' ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/></svg>
                <span>Tổng Quan</span>
            </a>
            <a href="/student/profile" class="portal-tab student-tab <?= $tab === 'profile' ? 'active' : '' ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                <span>Hồ Sơ Cá Nhân</span>
            </a>
            <a href="/student/applications" class="portal-tab student-tab <?= $tab === 'applications' ? 'active' : '' ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                <span>Đơn Ứng Tuyển</span>
            </a>
            <a href="/student/favorites" class="portal-tab student-tab <?= $tab === 'favorites' ? 'active' : '' ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                <span>Việc Đã Lưu</span>
            </a>
            <a href="/student/recommendations" class="portal-tab student-tab <?= $tab === 'recommendations' ? 'active' : '' ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 3-1.9 4.6L5 9.5l4 3.3L8.5 18l3.5-2.3 3.5 2.3-.5-5.2 4-3.3-5.1-1.9z"/><path d="M19 3v4M21 5h-4"/></svg>
                <span>Gợi Ý Cho Bạn</span>
            </a>
            <a href="/student/saved-searches" class="portal-tab student-tab <?= $tab === 'saved_searches' ? 'active' : '' ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                <span>Tìm Kiếm Đã Lưu</span>
            </a>
        </nav>
    </div>
</div>
