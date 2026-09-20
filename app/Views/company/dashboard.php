<?php include __DIR__ . "/nav.php"; ?>

<div class="container" style="margin-bottom:3rem;">
    <!-- Loading Skeleton -->
    <div id="comp-dash-loading" style="display:block;">
        <div class="stat-grid" style="margin-bottom:2rem;">
            <div class="job-card skeleton" style="height:110px;"></div>
            <div class="job-card skeleton" style="height:110px;"></div>
            <div class="job-card skeleton" style="height:110px;"></div>
            <div class="job-card skeleton" style="height:110px;"></div>
        </div>
        <div class="job-card skeleton" style="height:250px;margin-bottom:2rem;"></div>
        <div class="job-card skeleton" style="height:200px;"></div>
    </div>

    <!-- Error State -->
    <div id="comp-dash-error" style="display:none;background:var(--surface);border-radius:var(--radius);padding:3rem 1.5rem;text-align:center;border:1px solid var(--danger);margin-bottom:2rem;">
        <div style="font-size:3rem;margin-bottom:1rem;color:var(--danger);"><i class="ri-alert-line"></i></div>
        <h2 style="font-size:1.4rem;font-weight:700;color:var(--dark);margin-bottom:0.5rem;">Không Thể Tải Bảng Điều Khiển</h2>
        <p id="comp-dash-err-msg" style="color:var(--text-muted);max-width:500px;margin:0 auto 1.5rem;">Đã xảy ra lỗi khi lấy dữ liệu tổng quan nhà tuyển dụng.</p>
        <button onclick="loadCompanyDashboard()" class="btn btn-primary btn-sm">Thử Lại</button>
    </div>

    <!-- Main Content -->
    <div id="comp-dash-content" style="display:none;">
        <!-- Welcome banner -->
        <div class="dashboard-hero dashboard-hero--company">
            <div class="dashboard-hero-content">
                <h2 class="dashboard-hero-title">
                    Xin chào, <span id="dash-company-name">Nhà tuyển dụng</span>!
                </h2>
                <p class="dashboard-hero-desc">
                    Dưới đây là thống kê tình hình tin tuyển dụng và hồ sơ ứng viên mới nhất.
                </p>
            </div>
            <div class="dashboard-hero-actions">
                <a href="/company/applications" class="btn btn-outline btn-sm">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                    <span>Xem Ứng Viên</span>
                </a>
            </div>
        </div>

        <!-- 4 Key Stat Cards -->
        <div class="stat-grid">
            <!-- Card 1: Active Jobs -->
            <div class="stat-card">
                <div class="stat-card-icon stat-card-icon--primary">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
                </div>
                <div class="stat-card-body">
                    <div class="stat-card-label">Tin Đang Tuyển</div>
                    <div id="stat-active-jobs" class="stat-card-value">0</div>
                    <div class="stat-card-subtext">Tin đang hiển thị công khai</div>
                </div>
            </div>

            <!-- Card 2: Total Applications -->
            <div class="stat-card">
                <div class="stat-card-icon stat-card-icon--info">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                </div>
                <div class="stat-card-body">
                    <div class="stat-card-label">Tổng Hồ Sơ Nhận</div>
                    <div id="stat-total-apps" class="stat-card-value">0</div>
                    <div class="stat-card-subtext">Tất cả ứng viên đã nộp đơn</div>
                </div>
            </div>

            <!-- Card 3: Pending Review -->
            <div class="stat-card">
                <div class="stat-card-icon stat-card-icon--warning">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                </div>
                <div class="stat-card-body">
                    <div class="stat-card-label">Hồ Sơ Chờ Duyệt</div>
                    <div id="stat-pending-apps" class="stat-card-value stat-card-value--warning">0</div>
                    <div class="stat-card-subtext">Cần xem xét và phản hồi</div>
                </div>
            </div>

            <!-- Card 4: Shortlisted / Accepted -->
            <div class="stat-card">
                <div class="stat-card-icon stat-card-icon--success">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                </div>
                <div class="stat-card-body">
                    <div class="stat-card-label">Đã Chọn / Mời Phỏng Vấn</div>
                    <div id="stat-shortlisted-apps" class="stat-card-value stat-card-value--success">0</div>
                    <div class="stat-card-subtext">Ứng viên phù hợp & trúng tuyển</div>
                </div>
            </div>
        </div>

        <!-- Section 2: Recent Applications Table -->
        <div class="surface-card" style="margin-bottom:2rem;">
            <div class="section-header">
                <div class="section-header-main">
                    <h3 class="section-title">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                        <span>Đơn Ứng Tuyển Mới Nhất</span>
                    </h3>
                    <p class="section-desc">Danh sách ứng viên nộp đơn vào các vị trí tuyển dụng gần đây</p>
                </div>
                <a href="/company/applications" class="section-action-link">
                    <span>Xem tất cả</span>
                    <span>&rarr;</span>
                </a>
            </div>

            <div id="recent-apps-container" class="table-responsive">
                <!-- Rendered dynamically -->
            </div>

            <div id="recent-apps-empty" class="empty-state" style="display:none;">
                <div class="empty-state-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                </div>
                <p class="empty-state-text">Chưa có đơn ứng tuyển nào được nộp vào các tin việc làm của bạn.</p>
                <div class="empty-state-action">
                    <a href="/company/jobs" class="btn btn-outline btn-sm">Quản lý tin tuyển dụng</a>
                </div>
            </div>
        </div>

        <!-- Section 3: Top Jobs & Job Status Breakdown -->
        <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(320px, 1fr));gap:1.5rem;align-items:start;">
            <!-- Top Jobs -->
            <div class="surface-card">
                <div class="section-header">
                    <div class="section-header-main">
                        <h3 class="section-title">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20v-6M6 20V10M18 20V4"/></svg>
                            <span>Top Việc Làm Nhiều Ứng Viên</span>
                        </h3>
                        <p class="section-desc">Tin tuyển dụng thu hút nhiều hồ sơ nhất</p>
                    </div>
                    <a href="/company/jobs" class="section-action-link">
                        <span>Quản lý tin</span>
                        <span>&rarr;</span>
                    </a>
                </div>

                <div id="top-jobs-container" style="display:flex;flex-direction:column;gap:0.75rem;">
                    <!-- Rendered dynamically -->
                </div>

                <div id="top-jobs-empty" class="empty-state" style="display:none;">
                    <div class="empty-state-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
                    </div>
                    <p class="empty-state-text">Chưa có việc làm nào có ứng viên nộp đơn.</p>
                    <div class="empty-state-action">
                        <a href="/company/jobs" class="btn btn-outline btn-sm">Xem danh sách tin</a>
                    </div>
                </div>
            </div>

            <!-- Job Status Distribution -->
            <div class="surface-card">
                <div class="section-header">
                    <div class="section-header-main">
                        <h3 class="section-title">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
                            <span>Tình Trạng Tin Tuyển Dụng</span>
                        </h3>
                        <p class="section-desc">Phân bố tin theo trạng thái kiểm duyệt và hiển thị</p>
                    </div>
                </div>

                <div class="status-dist-list">
                    <div class="status-dist-item">
                        <span class="status-dist-label">
                            <span class="status-dot" style="background:var(--success);"></span>
                            <span>Đang hiển thị (Published)</span>
                        </span>
                        <span id="dist-published" class="status-badge status-badge--success">0</span>
                    </div>
                    <div class="status-dist-item">
                        <span class="status-dist-label">
                            <span class="status-dot" style="background:var(--neutral);"></span>
                            <span>Bản nháp (Draft)</span>
                        </span>
                        <span id="dist-draft" class="status-badge status-badge--neutral">0</span>
                    </div>
                    <div class="status-dist-item">
                        <span class="status-dist-label">
                            <span class="status-dot" style="background:var(--warning);"></span>
                            <span>Chờ duyệt (Pending Approval)</span>
                        </span>
                        <span id="dist-pending" class="status-badge status-badge--warning">0</span>
                    </div>
                    <div class="status-dist-item">
                        <span class="status-dist-label">
                            <span class="status-dot" style="background:var(--neutral);"></span>
                            <span>Đã đóng tuyển (Closed)</span>
                        </span>
                        <span id="dist-closed" class="status-badge status-badge--neutral">0</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function initCompanyDashboardPage() {
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

    loadCompanyDashboard();
}

if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initCompanyDashboardPage);
} else {
    initCompanyDashboardPage();
}

async function loadCompanyDashboard() {
    const loadingEl = document.getElementById("comp-dash-loading");
    const errorEl = document.getElementById("comp-dash-error");
    const contentEl = document.getElementById("comp-dash-content");

    if (loadingEl) loadingEl.style.display = "block";
    if (errorEl) errorEl.style.display = "none";
    if (contentEl) contentEl.style.display = "none";

    try {
        const res = await apiRequest("/company/dashboard", { requireAuth: true });

        if (res && res.success && res.data) {
            if (contentEl) contentEl.style.display = "block";
            const data = res.data;

        // Company Name
        if (data.company_name) {
            document.getElementById("dash-company-name").innerText = data.company_name;
        }

        // 1. Metric Cards
        const jobs = data.jobs || {};
        const apps = data.applications || {};
        document.getElementById("stat-active-jobs").innerText = jobs.published || 0;
        document.getElementById("stat-total-apps").innerText = apps.total || 0;
        document.getElementById("stat-pending-apps").innerText = apps.pending || 0;
        document.getElementById("stat-shortlisted-apps").innerText = (apps.shortlisted || 0) + (apps.interview || 0) + (apps.accepted || 0);

        // Job Distribution
        document.getElementById("dist-published").innerText = jobs.published || 0;
        document.getElementById("dist-draft").innerText = jobs.draft || 0;
        document.getElementById("dist-pending").innerText = jobs.pending_approval || 0;
        document.getElementById("dist-closed").innerText = jobs.closed || 0;

        // Sync notif badge
        const unreads = data.unread_notifications || 0;
        const notifBadge = document.getElementById("nav-company-notif-badge");
        if (notifBadge) {
            notifBadge.innerText = unreads;
            notifBadge.style.display = unreads > 0 ? "inline-block" : "none";
        }

        // 2. Recent Applications Table
        const recentApps = data.recent_applications || [];
        const recentContainer = document.getElementById("recent-apps-container");
        const recentEmpty = document.getElementById("recent-apps-empty");

        if (recentApps.length === 0) {
            recentContainer.style.display = "none";
            recentEmpty.style.display = "block";
        } else {
            recentContainer.style.display = "block";
            recentEmpty.style.display = "none";

            recentContainer.innerHTML = `
                <table style="width:100%;min-width:620px;border-collapse:collapse;font-size:0.9rem;text-align:left;">
                    <thead>
                        <tr style="border-bottom:2px solid var(--border);color:var(--text-muted);font-size:0.8rem;text-transform:uppercase;">
                            <th style="padding:0.75rem 0.5rem;">Ứng viên</th>
                            <th style="padding:0.75rem 0.5rem;">Trường & Ngành</th>
                            <th style="padding:0.75rem 0.5rem;">Vị trí ứng tuyển</th>
                            <th style="padding:0.75rem 0.5rem;">Ngày nộp</th>
                            <th style="padding:0.75rem 0.5rem;">Trạng thái</th>
                            <th style="padding:0.75rem 0.5rem;text-align:right;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${recentApps.map(app => `
                            <tr style="border-bottom:1px solid var(--border);">
                                <td style="padding:0.85rem 0.5rem;font-weight:700;color:var(--dark);">
                                    ${escapeHtml(app.student_name || "Ứng viên")}
                                </td>
                                <td style="padding:0.85rem 0.5rem;color:var(--text-muted);font-size:0.85rem;">
                                    ${escapeHtml(app.university || "Chưa cập nhật")}<br>
                                    <span style="color:var(--text);">${escapeHtml(app.major || "")}</span>
                                </td>
                                <td style="padding:0.85rem 0.5rem;">
                                    <a href="/viec-lam/${encodeURIComponent(app.job_id)}" target="_blank" style="color:var(--primary);font-weight:600;">
                                        ${escapeHtml(app.job_title || "Vị trí việc làm")}
                                    </a>
                                </td>
                                <td style="padding:0.85rem 0.5rem;color:var(--text-muted);font-size:0.85rem;">
                                    ${formatDate(app.applied_at)}
                                </td>
                                <td style="padding:0.85rem 0.5rem;">
                                    ${getAppStatusBadge(app.status)}
                                </td>
                                <td style="padding:0.85rem 0.5rem;text-align:right;">
                                    <a href="/company/applications?job_id=${encodeURIComponent(app.job_id)}" class="btn btn-outline btn-sm" style="font-size:0.78rem;padding:0.25rem 0.6rem;">
                                        Xem đơn &rarr;
                                    </a>
                                </td>
                            </tr>
                        `).join("")}
                    </tbody>
                </table>
            `;
        }

        // 3. Top Jobs
        const topJobs = data.top_jobs || [];
        const topContainer = document.getElementById("top-jobs-container");
        const topEmpty = document.getElementById("top-jobs-empty");

        if (topJobs.length === 0) {
            topContainer.style.display = "none";
            topEmpty.style.display = "block";
        } else {
            topContainer.style.display = "flex";
            topEmpty.style.display = "none";

            topContainer.innerHTML = topJobs.map(job => `
                <div style="display:flex;justify-content:space-between;align-items:center;padding:0.75rem;border:1px solid var(--border);border-radius:var(--radius-sm);background:var(--bg);">
                    <div style="flex:1;min-width:0;margin-right:0.75rem;">
                        <a href="/viec-lam/${encodeURIComponent(job.id)}" target="_blank" style="font-weight:700;font-size:0.92rem;color:var(--dark);display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                            ${escapeHtml(job.title)}
                        </a>
                        <div style="margin-top:0.25rem;">
                            ${getJobStatusBadge(job.status)}
                        </div>
                    </div>
                    <div style="text-align:right;white-space:nowrap;">
                        <span class="status-badge status-badge--info" style="font-size:0.8rem;font-weight:700;">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:3px;"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                            <span>${job.application_count || 0} hồ sơ</span>
                        </span>
                    </div>
                </div>
            `).join("");
        }

        } else {
            if (errorEl) errorEl.style.display = "block";
            const msgEl = document.getElementById("comp-dash-err-msg");
            if (msgEl) msgEl.innerText = (res && res.message) ? res.message : "Đã có lỗi xảy ra.";
        }
    } catch (err) {
        console.error("Error loading company dashboard:", err);
        if (errorEl) errorEl.style.display = "block";
        const msgEl = document.getElementById("comp-dash-err-msg");
        if (msgEl) msgEl.innerText = "Lỗi kết nối máy chủ.";
    } finally {
        if (loadingEl) loadingEl.style.display = "none";
    }
}

// Inherit getAppStatusBadge(status) from api.js
</script>
