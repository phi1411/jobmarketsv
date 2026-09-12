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
        <!-- 3D Interactive Hero Intro Banner -->
        <div class="dashboard-hero-3d">
            <div class="hero-3d-card" id="studentHero3d">
                <div class="hero-3d-mesh"></div>
                <div class="hero-3d-glow hero-3d-glow-1"></div>
                <div class="hero-3d-glow hero-3d-glow-2"></div>

                <div class="hero-3d-content">
                    <div class="hero-3d-text">
                        <div class="hero-badge-pill">
                            <span class="hero-pulse-dot"></span>
                            <span>Hệ Thống Gợi Ý AI Match 2.0</span>
                        </div>
                        <h2 class="hero-3d-title">
                            Chào mừng trở lại, <span class="hero-gradient-text" id="dash-user-name">Sinh viên</span>! 👋
                        </h2>
                        <p class="hero-3d-subtitle">
                            Khám phá hàng trăm cơ hội việc làm part-time linh hoạt theo ca học sinh viên, đối sánh kỹ năng tự động bằng AI và theo dõi trạng thái ứng tuyển trực quan theo thời gian thực.
                        </p>
                        <div class="hero-3d-actions">
                            <a href="/viec-lam" class="btn-hero btn-hero--primary">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                                <span>Khám Phá Việc Làm Ngay</span>
                            </a>
                            <a href="/student/recommendations" class="btn-hero btn-hero--glass">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                                <span>Xem Gợi Ý AI</span>
                            </a>
                            <a href="/student/profile" class="btn-hero btn-hero--glass">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                <span>Hồ Sơ CV Cá Nhân</span>
                            </a>
                        </div>
                    </div>

                    <div class="hero-3d-visual">
                        <!-- Floating 3D Animated Badges -->
                        <div class="floating-badge floating-badge--1">
                            <span class="badge-icon">⚡</span>
                            <div class="badge-content">
                                <strong>AI Match 95%</strong>
                                <small>Độ tương thích hồ sơ</small>
                            </div>
                        </div>
                        <div class="floating-badge floating-badge--2">
                            <span class="badge-icon">💼</span>
                            <div class="badge-content">
                                <strong>140+ Việc Part-time</strong>
                                <small>Đang tuyển mới hôm nay</small>
                            </div>
                        </div>
                        <div class="floating-badge floating-badge--3">
                            <span class="badge-icon">⏰</span>
                            <div class="badge-content">
                                <strong>Ca Học Linh Hoạt</strong>
                                <small>Tự do đăng ký ca làm</small>
                            </div>
                        </div>
                    </div>
                </div>
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

        <!-- Recommended Jobs Grid (TopCV-style 3 columns) -->
        <div class="surface-card" style="margin-top:2rem;">
            <div class="section-header" style="margin-bottom:1.25rem;">
                <div class="section-header-main">
                    <h3 class="section-title">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 3-1.9 4.6L5 9.5l4 3.3L8.5 18l3.5-2.3 3.5 2.3-.5-5.2 4-3.3-5.1-1.9z"/><path d="M19 3v4M21 5h-4"/></svg>
                        <span>Việc Làm Phù Hợp Gợi Ý Hôm Nay</span>
                    </h3>
                    <p class="section-desc">Cơ hội việc làm part-time mới nhất được hệ thống đề xuất cho bạn</p>
                </div>
                <a href="/viec-lam" class="section-action-link">
                    <span>Xem tất cả việc làm</span>
                    <span>&rarr;</span>
                </a>
            </div>

            <div id="dash-recommended-jobs-grid" class="topcv-job-grid">
                <div class="job-card skeleton" style="height:140px;"></div>
                <div class="job-card skeleton" style="height:140px;"></div>
                <div class="job-card skeleton" style="height:140px;"></div>
            </div>
        </div>
    </div>
</div>

<script>
function initStudentDashboardPage() {
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

    const nameEl = document.getElementById("dash-user-name");
    if (nameEl) nameEl.innerText = user.name || "Sinh viên";
    loadDashboard();
    initHero3dTilt();
    loadRecommendedJobs();
}

if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initStudentDashboardPage);
} else {
    initStudentDashboardPage();
}

async function loadDashboard() {
    const loadingEl = document.getElementById("student-dash-loading");
    const errorEl = document.getElementById("student-dash-error");
    const contentEl = document.getElementById("student-dash-content");

    if (loadingEl) loadingEl.style.display = "block";
    if (errorEl) errorEl.style.display = "none";
    if (contentEl) contentEl.style.display = "none";

    try {
        const res = await apiRequest("/student/dashboard", { requireAuth: true });

        if (res && res.success && res.data) {
            if (contentEl) contentEl.style.display = "block";
            const data = res.data;

        // 1. Metrics & Pipeline
        const counts = data.applications || data.application_counts || {};
        const unreadCount = data.unread_notifications ?? data.unread_notification_count ?? 0;
        document.getElementById("stat-total-apps").innerText = counts.total || 0;
        document.getElementById("stat-pending-apps").innerText = counts.pending || 0;
        document.getElementById("stat-shortlisted-apps").innerText = (counts.shortlisted || 0) + (counts.accepted || 0);
        document.getElementById("stat-unread-notifs").innerText = unreadCount;

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
            if (errorEl) errorEl.style.display = "block";
            const msgEl = document.getElementById("student-dash-err-msg");
            if (msgEl) msgEl.innerText = (res && res.message) ? res.message : "Vui lòng kiểm tra lại kết nối.";
        }
    } catch (err) {
        console.error("Error loading student dashboard:", err);
        if (errorEl) errorEl.style.display = "block";
        const msgEl = document.getElementById("student-dash-err-msg");
        if (msgEl) msgEl.innerText = "Lỗi kết nối máy chủ.";
    } finally {
        if (loadingEl) loadingEl.style.display = "none";
    }
}

function initHero3dTilt() {
    const card = document.getElementById("studentHero3d");
    if (!card) return;

    card.addEventListener("mousemove", (e) => {
        const rect = card.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const y = e.clientY - rect.top;
        const centerX = rect.width / 2;
        const centerY = rect.height / 2;
        const rotateX = ((y - centerY) / centerY) * -5;
        const rotateY = ((x - centerX) / centerX) * 5;

        card.style.transform = `perspective(1000px) rotateX(${rotateX.toFixed(2)}deg) rotateY(${rotateY.toFixed(2)}deg) translateY(-2px)`;
    });

    card.addEventListener("mouseleave", () => {
        card.style.transform = "perspective(1000px) rotateX(0deg) rotateY(0deg) translateY(0)";
    });
}

async function loadRecommendedJobs() {
    const grid = document.getElementById("dash-recommended-jobs-grid");
    if (!grid) return;

    try {
        const res = await apiRequest("/jobs?per_page=6&sort_by=newest");
        if (res && res.success && Array.isArray(res.data) && res.data.length > 0) {
            const jobs = res.data;
            grid.innerHTML = jobs.map(job => `
                <div class="topcv-job-card" onclick="window.location.href='/viec-lam/${encodeURIComponent(job.id)}'">
                    <div>
                        <div class="topcv-card-top">
                            <div class="topcv-logo-wrapper">
                                ${job.company_logo ? `<img src="${escapeHtml(job.company_logo)}" alt="${escapeHtml(job.company_name)}" class="topcv-logo-img" onerror="this.outerHTML='<div class=\\'topcv-logo-fallback\\'>${escapeHtml(job.company_name ? job.company_name.substring(0, 1) : 'J')}</div>'">` : `<div class="topcv-logo-fallback">${escapeHtml(job.company_name ? job.company_name.substring(0, 1) : "J")}</div>`}
                            </div>
                            <div class="topcv-card-info">
                                <div class="topcv-job-title" title="${escapeHtml(job.title)}">
                                    ${job.is_featured ? '<span class="topcv-badge-hot">HOT</span>' : ''}
                                    ${escapeHtml(job.title)}
                                </div>
                                <div class="topcv-company-name" title="${escapeHtml(job.company_name || 'Nhà tuyển dụng')}">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18M3 7v14M21 7v14M6 11h2M6 15h2M16 11h2M16 15h2M10 21V3h4v18"/></svg>
                                    <span>${escapeHtml(job.company_name || "Nhà tuyển dụng")}</span>
                                </div>
                            </div>
                            <button type="button" class="topcv-bookmark-btn" onclick="event.stopPropagation(); toggleFavoriteJob('${encodeURIComponent(job.id)}', this)" title="Lưu việc làm">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
                            </button>
                        </div>
                    </div>
                    <div class="topcv-pills-row">
                        <span class="topcv-pill topcv-pill-salary">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v12M15 9.5a3.5 3.5 0 1 0-7 0 3.5 3.5 0 1 0 7 0z"/></svg>
                            ${formatCurrency(job.salary_min)} - ${formatCurrency(job.salary_max)}
                        </span>
                        <span class="topcv-pill topcv-pill-shift">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                            ${getShiftLabel(job.shift_type)}
                        </span>
                        <span class="topcv-pill topcv-pill-location">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                            ${escapeHtml(job.location_name || job.city || "Toàn quốc")}
                        </span>
                    </div>
                </div>
            `).join("");
        } else {
            grid.innerHTML = `
                <div style="grid-column:1/-1;text-align:center;padding:2rem;color:var(--text-muted);">
                    Chưa có việc làm nào phù hợp. Hãy cập nhật hồ sơ để nhận gợi ý chính xác hơn.
                </div>
            `;
        }
    } catch (err) {
        console.error("Error loading recommended jobs:", err);
        grid.innerHTML = `
            <div style="grid-column:1/-1;text-align:center;padding:2rem;color:var(--text-muted);">
                Không thể tải danh sách việc làm gợi ý.
            </div>
        `;
    }
}

async function toggleFavoriteJob(jobId, btnEl) {
    if (!TokenStorage.isLoggedIn()) {
        showToast("Vui lòng đăng nhập để lưu việc làm.", "error");
        return;
    }
    const isFavorited = btnEl.classList.contains("active");
    try {
        if (isFavorited) {
            const res = await apiRequest(`/favorites/jobs/${encodeURIComponent(jobId)}`, {
                method: "DELETE",
                requireAuth: true
            });
            if (res && res.success) {
                btnEl.classList.remove("active");
                showToast("Đã bỏ lưu việc làm.", "info");
            }
        } else {
            const res = await apiRequest(`/favorites/jobs/${encodeURIComponent(jobId)}`, {
                method: "POST",
                requireAuth: true
            });
            if (res && res.success) {
                btnEl.classList.add("active");
                showToast("Đã lưu việc làm vào danh sách yêu thích!", "success");
            }
        }
    } catch (err) {
        console.error("Error toggling favorite:", err);
        showToast("Thao tác thất bại.", "error");
    }
}
</script>
