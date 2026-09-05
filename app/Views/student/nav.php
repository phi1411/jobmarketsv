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
            <a href="/student/saved-searches" class="portal-tab student-tab <?= $tab === 'saved_searches' ? 'active' : '' ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                <span>Tìm Kiếm Đã Lưu</span>
            </a>
        </nav>
    </div>
</div>
