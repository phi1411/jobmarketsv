<?php
$tab = $activeTab ?? "dashboard";
?>
<div class="student-header" style="background:#fff;border-bottom:1px solid var(--border);padding:1.5rem 0 0;margin-bottom:2rem;">
    <div class="container">
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;margin-bottom:1.5rem;">
            <div>
                <h1 style="font-size:1.6rem;font-weight:800;color:var(--dark);margin-bottom:0.25rem;">
                    🎓 Cổng Thông Tin Sinh Viên
                </h1>
                <p style="color:var(--text-muted);font-size:0.92rem;margin:0;">
                    Quản lý hồ sơ, theo dõi trạng thái ứng tuyển và tìm kiếm cơ hội việc làm part-time.
                </p>
            </div>
            <div style="display:flex;gap:0.75rem;">
                <a href="/viec-lam" class="btn btn-primary btn-sm">
                    🔍 Tìm Việc Làm Mới
                </a>
            </div>
        </div>

        <div class="student-nav-tabs" style="display:flex;gap:0.5rem;overflow-x:auto;border-bottom:2px solid transparent;">
            <a href="/student/dashboard" class="student-tab <?= $tab === 'dashboard' ? 'active' : '' ?>">
                📊 Tổng Quan
            </a>
            <a href="/student/profile" class="student-tab <?= $tab === 'profile' ? 'active' : '' ?>">
                👤 Hồ Sơ Cá Nhân
            </a>
            <a href="/student/applications" class="student-tab <?= $tab === 'applications' ? 'active' : '' ?>">
                📝 Đơn Ứng Tuyển
            </a>
            <a href="/student/favorites" class="student-tab <?= $tab === 'favorites' ? 'active' : '' ?>">
                ⭐ Việc Đã Lưu
            </a>
            <a href="/student/saved-searches" class="student-tab <?= $tab === 'saved_searches' ? 'active' : '' ?>">
                🔍 Tìm Kiếm Đã Lưu
            </a>
            <a href="/student/notifications" class="student-tab <?= $tab === 'notifications' ? 'active' : '' ?>">
                🔔 Thông Báo <span id="nav-student-notif-badge" class="badge badge-primary" style="display:none;font-size:0.75rem;padding:0.15rem 0.4rem;border-radius:10px;">0</span>
            </a>
        </div>
    </div>
</div>
