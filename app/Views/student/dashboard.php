<?php include __DIR__ . "/nav.php"; ?>

<div class="container" style="margin-bottom:3rem;">
    <!-- Loading State -->
    <div id="student-dash-loading" style="text-align:center;padding:3rem 0;">
        <div class="job-card skeleton" style="height:120px;margin-bottom:1rem;"></div>
        <div class="stat-grid" style="margin-bottom:1.5rem;">
            <div class="job-card skeleton" style="height:100px;"></div>
            <div class="job-card skeleton" style="height:100px;"></div>
            <div class="job-card skeleton" style="height:100px;"></div>
            <div class="job-card skeleton" style="height:100px;"></div>
        </div>
        <div class="job-card skeleton" style="height:250px;"></div>
    </div>

    <!-- Error State -->
    <div id="student-dash-error" style="display:none;background:#fff;border-radius:var(--radius);padding:2rem;border:1px solid var(--danger);text-align:center;">
        <div style="font-size:2.5rem;color:var(--danger);margin-bottom:1rem;">⚠️</div>
        <h3 style="font-weight:700;color:var(--dark);margin-bottom:0.5rem;">Không Thể Tải Bảng Tổng Quan</h3>
        <p id="student-dash-err-msg" style="color:var(--text-muted);margin-bottom:1rem;">Đã xảy ra lỗi khi kết nối với máy chủ.</p>
        <button onclick="loadDashboard()" class="btn btn-primary btn-sm">Thử Lại</button>
    </div>

    <!-- Main Content -->
    <div id="student-dash-content" style="display:none;">
        <!-- Welcome Banner -->
        <div class="dashboard-hero dashboard-hero--student">
            <div class="dashboard-hero-content">
                <h2 class="dashboard-hero-title">
                    Xin chào, <span id="dash-user-name">Sinh viên</span>!
                </h2>
                <p class="dashboard-hero-desc">
                    Theo dõi tiến độ đơn ứng tuyển và các cơ hội việc làm part-time phù hợp nhất hôm nay.
                </p>
            </div>
            <div class="dashboard-hero-actions">
                <a href="/student/profile" class="btn btn-outline btn-sm">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    <span>Cập Nhật Hồ Sơ</span>
                </a>
                <a href="/student/applications" class="btn btn-primary btn-sm">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                    <span>Xem Đơn Ứng Tuyển</span>
                </a>
            </div>
        </div>

        <!-- 4 Metric Cards -->
        <div class="stat-grid">
            <div class="stat-card">
                <div class="stat-card-icon stat-card-icon--primary">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                </div>
                <div class="stat-card-body">
                    <div class="stat-card-label">Tổng Đơn Đã Nộp</div>
                    <div id="stat-total-apps" class="stat-card-value">0</div>
                    <div class="stat-card-subtext">Tất cả các vị trí đã apply</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-card-icon stat-card-icon--warning">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                </div>
                <div class="stat-card-body">
                    <div class="stat-card-label">Đang Chờ Duyệt</div>
                    <div id="stat-pending-apps" class="stat-card-value stat-card-value--warning">0</div>
                    <div class="stat-card-subtext">Nhà tuyển dụng chưa xem</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-card-icon stat-card-icon--success">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                </div>
                <div class="stat-card-body">
                    <div class="stat-card-label">Được Chọn / Phù Hợp</div>
                    <div id="stat-shortlisted-apps" class="stat-card-value stat-card-value--success">0</div>
                    <div class="stat-card-subtext">Đã vào vòng phỏng vấn / nhận việc</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-card-icon stat-card-icon--purple">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                </div>
                <div class="stat-card-body">
                    <div class="stat-card-label">Thông Báo Mới</div>
                    <div id="stat-unread-notifs" class="stat-card-value stat-card-value--purple">0</div>
                    <div class="stat-card-subtext"><a href="/student/notifications" style="color:#7c3aed;text-decoration:underline;">Xem thông báo</a></div>
                </div>
            </div>
        </div>

        <!-- Application Status Pipeline -->
        <div class="surface-card" style="margin-bottom:2rem;">
            <div class="section-header">
                <div class="section-header-main">
                    <h3 class="section-title">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
                        <span>Tiến Trình Đơn Ứng Tuyển</span>
                    </h3>
                    <p class="section-desc">Theo dõi các giai đoạn xét duyệt hồ sơ từ nhà tuyển dụng</p>
                </div>
                <a href="/student/applications" class="section-action-link">
                    <span>Chi tiết</span>
                    <span>&rarr;</span>
                </a>
            </div>

            <div class="pipeline-flow">
                <!-- Group 1: Đang xét duyệt (In-progress) -->
                <div class="pipeline-group">
                    <div class="pipeline-group-label">
                        <span class="status-dot" style="background:var(--primary);"></span>
                        <span>Đang xử lý (Active Steps)</span>
                    </div>
                    <div class="pipeline-steps">
                        <div class="pipeline-step">
                            <span class="pipeline-step-badge">1</span>
                            <span class="pipeline-step-name">Chờ duyệt</span>
                            <span id="pipe-pending" class="pipeline-step-count pipeline-step-count--warning">0</span>
                        </div>
                        <span class="pipeline-step-arrow">&rarr;</span>
                        <div class="pipeline-step">
                            <span class="pipeline-step-badge">2</span>
                            <span class="pipeline-step-name">Đã xem</span>
                            <span id="pipe-viewed" class="pipeline-step-count pipeline-step-count--info">0</span>
                        </div>
                        <span class="pipeline-step-arrow">&rarr;</span>
                        <div class="pipeline-step">
                            <span class="pipeline-step-badge">3</span>
                            <span class="pipeline-step-name">Phù hợp</span>
                            <span id="pipe-shortlisted" class="pipeline-step-count pipeline-step-count--secondary">0</span>
                        </div>
                    </div>
                </div>

                <!-- Group 2: Kết quả cuối cùng (Outcomes / Terminal) -->
                <div class="pipeline-group">
                    <div class="pipeline-group-label">
                        <span class="status-dot" style="background:var(--neutral);"></span>
                        <span>Đã kết thúc (Final Outcomes)</span>
                    </div>
                    <div class="pipeline-outcomes">
                        <div class="pipeline-outcome pipeline-outcome--success">
                            <span class="pipeline-step-name">Trúng tuyển</span>
                            <span id="pipe-accepted" class="pipeline-step-count">0</span>
                        </div>
                        <div class="pipeline-outcome pipeline-outcome--danger">
                            <span class="pipeline-step-name">Từ chối</span>
                            <span id="pipe-rejected" class="pipeline-step-count">0</span>
                        </div>
                        <div class="pipeline-outcome pipeline-outcome--neutral">
                            <span class="pipeline-step-name">Đã rút</span>
                            <span id="pipe-withdrawn" class="pipeline-step-count">0</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2 Columns: Expiring Favorites & Recent Notifications -->
        <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(320px, 1fr));gap:1.5rem;">
            <!-- Expiring Favorites -->
            <div class="surface-card">
                <div class="section-header">
                    <div class="section-header-main">
                        <h3 class="section-title">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                            <span>Việc Đã Lưu Sắp Hết Hạn</span>
                        </h3>
                        <p class="section-desc">Tin đã lưu cần nộp đơn sớm trước khi hết hạn</p>
                    </div>
                    <a href="/student/favorites" class="section-action-link">
                        <span>Tất cả</span>
                        <span>&rarr;</span>
                    </a>
                </div>
                <div id="expiring-favs-container">
                    <!-- Dynamic rendering -->
                </div>
            </div>

            <!-- Recent Notifications -->
            <div class="surface-card">
                <div class="section-header">
                    <div class="section-header-main">
                        <h3 class="section-title">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                            <span>Thông Báo Gần Đây</span>
                        </h3>
                        <p class="section-desc">Cập nhật mới từ nhà tuyển dụng & hệ thống</p>
                    </div>
                    <a href="/student/notifications" class="section-action-link">
                        <span>Tất cả</span>
                        <span>&rarr;</span>
                    </a>
                </div>
                <div id="recent-notifs-container">
                    <!-- Dynamic rendering -->
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {
    // 1. Authorization Guard
    if (!TokenStorage.isLoggedIn()) {
        showToast("Vui lòng đăng nhập để truy cập Cổng Sinh viên.", "error");
        window.location.href = `/login?login_required=1&redirect=${encodeURIComponent(window.location.pathname)}`;
        return;
    }

    const user = TokenStorage.getUser();
    if (!user || (user.role !== "student" && user.role !== "developer")) {
        showToast("Chỉ tài khoản Sinh viên mới có quyền truy cập trang này.", "error");
        setTimeout(() => {
            window.location.href = user && user.role === "company" ? "/viec-lam" : "/";
        }, 1200);
        return;
    }

    document.getElementById("dash-user-name").innerText = user.name || "Sinh viên";
    loadDashboard();
});

async function loadDashboard() {
    const loadingEl = document.getElementById("student-dash-loading");
    const errorEl = document.getElementById("student-dash-error");
    const contentEl = document.getElementById("student-dash-content");

    loadingEl.style.display = "block";
    errorEl.style.display = "none";
    contentEl.style.display = "none";

    const res = await apiRequest("/student/dashboard", { requireAuth: true });

    loadingEl.style.display = "none";

    if (res && res.success && res.data) {
        contentEl.style.display = "block";
        const data = res.data;

        // 1. Metrics & Pipeline
        const counts = data.applications || data.application_counts || {};
        const unreadCount = data.unread_notifications ?? data.unread_notification_count ?? 0;
        document.getElementById("stat-total-apps").innerText = counts.total || 0;
        document.getElementById("stat-pending-apps").innerText = counts.pending || 0;
        document.getElementById("stat-shortlisted-apps").innerText = (counts.shortlisted || 0) + (counts.accepted || 0);
        document.getElementById("stat-unread-notifs").innerText = unreadCount;

        document.getElementById("pipe-pending").innerText = counts.pending || 0;
        document.getElementById("pipe-viewed").innerText = counts.viewed || 0;
        document.getElementById("pipe-shortlisted").innerText = counts.shortlisted || 0;
        document.getElementById("pipe-accepted").innerText = counts.accepted || 0;
        document.getElementById("pipe-rejected").innerText = counts.rejected || 0;
        document.getElementById("pipe-withdrawn").innerText = counts.withdrawn || 0;

        // Navbar badge sync
        const badge = document.getElementById("nav-student-notif-badge");
        if (badge) {
            badge.innerText = unreadCount;
            badge.style.display = (unreadCount > 0) ? "inline-block" : "none";
        }

        // 2. Expiring Favorites
        const favContainer = document.getElementById("expiring-favs-container");
        const favs = data.expiring_favorites || [];
        if (favs.length === 0) {
            favContainer.innerHTML = `
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                    </div>
                    <p class="empty-state-text">Không có việc yêu thích nào sắp hết hạn.</p>
                    <div class="empty-state-action">
                        <a href="/viec-lam" class="btn btn-outline btn-sm">Tìm việc ngay</a>
                    </div>
                </div>
            `;
        } else {
            favContainer.innerHTML = favs.map(job => `
                <div style="border-bottom:1px solid var(--border);padding:0.75rem 0;display:flex;justify-content:space-between;align-items:center;gap:0.75rem;">
                    <div>
                        <h4 style="font-size:0.95rem;font-weight:700;margin-bottom:0.2rem;">
                            <a href="/viec-lam/${encodeURIComponent(job.id)}">${escapeHtml(job.title)}</a>
                        </h4>
                        <div style="font-size:0.8rem;color:var(--text-muted);">
                            ${escapeHtml(job.company_name || "Doanh nghiệp")} &bull; ${getShiftLabel(job.shift_type)}
                        </div>
                    </div>
                    <div style="text-align:right;">
                        <span class="badge badge-salary" style="font-size:0.75rem;">
                            Hạn: ${formatDate(job.application_deadline)}
                        </span>
                    </div>
                </div>
            `).join("");
        }

        // 3. Recent Notifications
        const notifContainer = document.getElementById("recent-notifs-container");
        const notifs = data.recent_notifications || [];
        if (notifs.length === 0) {
            notifContainer.innerHTML = `
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                    </div>
                    <p class="empty-state-text">Chưa có thông báo mới nào.</p>
                </div>
            `;
        } else {
            notifContainer.innerHTML = notifs.map(n => `
                <div style="border-bottom:1px solid var(--border);padding:0.75rem 0;display:flex;gap:0.75rem;align-items:flex-start;">
                    <span style="display:inline-flex;align-items:center;justify-content:center;width:32px;height:32px;border-radius:var(--radius-sm);background:${n.read_at ? 'var(--bg)' : 'var(--primary-light)'};color:${n.read_at ? 'var(--neutral)' : 'var(--primary)'};flex-shrink:0;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                    </span>
                    <div style="flex:1;">
                        <div style="font-size:0.9rem;font-weight:${n.read_at ? '600' : '700'};color:var(--dark);">
                            ${escapeHtml(n.title)}
                        </div>
                        <div style="font-size:0.82rem;color:var(--text-muted);line-height:1.4;margin-top:0.2rem;">
                            ${escapeHtml(n.message)}
                        </div>
                        <div style="font-size:0.75rem;color:var(--text-muted);margin-top:0.25rem;">
                            ${formatDate(n.created_at)}
                        </div>
                    </div>
                </div>
            `).join("");
        }

    } else {
        errorEl.style.display = "block";
        document.getElementById("student-dash-err-msg").innerText = (res && res.message) ? res.message : "Vui lòng kiểm tra lại kết nối.";
    }
}
</script>
