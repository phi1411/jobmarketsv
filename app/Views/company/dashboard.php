<?php include __DIR__ . "/nav.php"; ?>

<div class="container" style="margin-bottom:3rem;">
    <!-- Loading Skeleton -->
    <div id="comp-dash-loading" style="display:block;">
        <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(220px, 1fr));gap:1.25rem;margin-bottom:2rem;">
            <div class="job-card skeleton" style="height:110px;"></div>
            <div class="job-card skeleton" style="height:110px;"></div>
            <div class="job-card skeleton" style="height:110px;"></div>
            <div class="job-card skeleton" style="height:110px;"></div>
        </div>
        <div class="job-card skeleton" style="height:250px;margin-bottom:2rem;"></div>
        <div class="job-card skeleton" style="height:200px;"></div>
    </div>

    <!-- Error State -->
    <div id="comp-dash-error" style="display:none;background:#fff;border-radius:var(--radius);padding:3rem 1.5rem;text-align:center;border:1px solid var(--danger);margin-bottom:2rem;">
        <div style="font-size:3rem;margin-bottom:1rem;">⚠️</div>
        <h2 style="font-size:1.4rem;font-weight:700;color:var(--dark);margin-bottom:0.5rem;">Không Thể Tải Bảng Điều Khiển</h2>
        <p id="comp-dash-err-msg" style="color:var(--text-muted);max-width:500px;margin:0 auto 1.5rem;">Đã xảy ra lỗi khi lấy dữ liệu tổng quan nhà tuyển dụng.</p>
        <button onclick="loadCompanyDashboard()" class="btn btn-primary btn-sm">Thử Lại</button>
    </div>

    <!-- Main Content -->
    <div id="comp-dash-content" style="display:none;">
        <!-- Welcome banner -->
        <div style="background:linear-gradient(135deg, #1e293b 0%, #0f172a 100%);color:#fff;border-radius:var(--radius-md);padding:1.5rem 2rem;margin-bottom:2rem;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;">
            <div>
                <h2 style="font-size:1.35rem;font-weight:800;color:#fff;margin-bottom:0.25rem;">
                    Xin chào, <span id="dash-company-name">Nhà tuyển dụng</span>!
                </h2>
                <p style="color:#94a3b8;font-size:0.9rem;margin:0;">
                    Dưới đây là thống kê tình hình tin tuyển dụng và hồ sơ ứng viên mới nhất.
                </p>
            </div>
            <div style="display:flex;gap:0.75rem;">
                <a href="/company/jobs/create" class="btn btn-outline btn-sm" style="color:#fff;border-color:rgba(255,255,255,0.35);">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    <span>Đăng Tin Mới</span>
                </a>
                <a href="/company/applications" class="btn btn-outline btn-sm" style="color:#fff;border-color:rgba(255,255,255,0.35);">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                    <span>Xem Ứng Viên</span>
                </a>
            </div>
        </div>

        <!-- 4 Key Stat Cards -->
        <div class="stat-grid">
            <!-- Card 1: Active Jobs -->
            <div class="stat-card">
                <div class="stat-card-icon" style="background:#e0e7ff;color:#4338ca;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
                </div>
                <div class="stat-card-body">
                    <div class="stat-card-label">Tin Đang Tuyển</div>
                    <div id="stat-active-jobs" class="stat-card-value">0</div>
                </div>
            </div>

            <!-- Card 2: Total Applications -->
            <div class="stat-card">
                <div class="stat-card-icon" style="background:#dbeafe;color:#1d4ed8;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                </div>
                <div class="stat-card-body">
                    <div class="stat-card-label">Tổng Hồ Sơ Nhận</div>
                    <div id="stat-total-apps" class="stat-card-value">0</div>
                </div>
            </div>

            <!-- Card 3: Pending Review -->
            <div class="stat-card">
                <div class="stat-card-icon" style="background:var(--warning-light);color:var(--warning-text);">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                </div>
                <div class="stat-card-body">
                    <div class="stat-card-label">Hồ Sơ Chờ Duyệt</div>
                    <div id="stat-pending-apps" class="stat-card-value" style="color:var(--warning-text);">0</div>
                </div>
            </div>

            <!-- Card 4: Shortlisted / Accepted -->
            <div class="stat-card">
                <div class="stat-card-icon" style="background:var(--success-light);color:var(--success-text);">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                </div>
                <div class="stat-card-body">
                    <div class="stat-card-label">Đã Chọn / Mời Phỏng Vấn</div>
                    <div id="stat-shortlisted-apps" class="stat-card-value" style="color:var(--success-text);">0</div>
                </div>
            </div>
        </div>

        <!-- Section 2: Recent Applications Table -->
        <div class="surface-card" style="margin-bottom:2rem;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.25rem;flex-wrap:wrap;gap:0.5rem;">
                <h3 style="font-size:1.15rem;font-weight:700;color:var(--dark);margin:0;">
                    📥 Đơn Ứng Tuyển Mới Nhất
                </h3>
                <a href="/company/applications" class="btn btn-outline btn-sm" style="font-size:0.82rem;">
                    Xem Tất Cả &rarr;
                </a>
            </div>

            <div id="recent-apps-container" style="overflow-x:auto;">
                <!-- Rendered dynamically -->
            </div>

            <div id="recent-apps-empty" style="display:none;padding:2.5rem 1rem;text-align:center;color:var(--text-muted);">
                <div style="font-size:2rem;margin-bottom:0.5rem;">📭</div>
                <p style="margin:0;font-size:0.9rem;">Chưa có đơn ứng tuyển nào được nộp vào các tin việc làm của bạn.</p>
            </div>
        </div>

        <!-- Section 3: Top Jobs & Job Status Breakdown -->
        <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(320px, 1fr));gap:1.5rem;align-items:start;">
            <!-- Top Jobs -->
            <div class="surface-card">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.25rem;">
                    <h3 style="font-size:1.1rem;font-weight:700;color:var(--dark);margin:0;">
                        🔥 Top Việc Làm Nhiều Ứng Viên
                    </h3>
                    <a href="/company/jobs" class="btn btn-outline btn-sm" style="font-size:0.8rem;">Quản lý tin</a>
                </div>

                <div id="top-jobs-container" style="display:flex;flex-direction:column;gap:0.75rem;">
                    <!-- Rendered dynamically -->
                </div>

                <div id="top-jobs-empty" style="display:none;padding:2rem 1rem;text-align:center;color:var(--text-muted);">
                    <p style="margin:0;font-size:0.88rem;">Chưa có việc làm nào có ứng viên nộp đơn.</p>
                </div>
            </div>

            <!-- Job Status Distribution -->
            <div class="surface-card">
                <h3 style="font-size:1.1rem;font-weight:700;color:var(--dark);margin-bottom:1.25rem;">
                    📊 Tình Trạng Tin Tuyển Dụng
                </h3>

                <div style="display:flex;flex-direction:column;gap:0.85rem;">
                    <div style="display:flex;justify-content:space-between;align-items:center;padding:0.6rem 0.75rem;background:var(--bg);border-radius:var(--radius-sm);">
                        <span style="font-size:0.9rem;font-weight:600;color:var(--dark);">🟢 Đang hiển thị (Published)</span>
                        <span id="dist-published" class="badge badge-success" style="font-size:0.85rem;">0</span>
                    </div>
                    <div style="display:flex;justify-content:space-between;align-items:center;padding:0.6rem 0.75rem;background:var(--bg);border-radius:var(--radius-sm);">
                        <span style="font-size:0.9rem;font-weight:600;color:var(--dark);">📝 Bản nháp (Draft)</span>
                        <span id="dist-draft" class="badge" style="background:#f1f5f9;color:#475569;font-size:0.85rem;">0</span>
                    </div>
                    <div style="display:flex;justify-content:space-between;align-items:center;padding:0.6rem 0.75rem;background:var(--bg);border-radius:var(--radius-sm);">
                        <span style="font-size:0.9rem;font-weight:600;color:var(--dark);">⏳ Chờ duyệt (Pending Approval)</span>
                        <span id="dist-pending" class="badge" style="background:#fef3c7;color:#92400e;font-size:0.85rem;">0</span>
                    </div>
                    <div style="display:flex;justify-content:space-between;align-items:center;padding:0.6rem 0.75rem;background:var(--bg);border-radius:var(--radius-sm);">
                        <span style="font-size:0.9rem;font-weight:600;color:var(--dark);">🔒 Đã đóng tuyển (Closed)</span>
                        <span id="dist-closed" class="badge" style="background:#fee2e2;color:#991b1b;font-size:0.85rem;">0</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {
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
});

async function loadCompanyDashboard() {
    const loadingEl = document.getElementById("comp-dash-loading");
    const errorEl = document.getElementById("comp-dash-error");
    const contentEl = document.getElementById("comp-dash-content");

    loadingEl.style.display = "block";
    errorEl.style.display = "none";
    contentEl.style.display = "none";

    const res = await apiRequest("/company/dashboard", { requireAuth: true });
    loadingEl.style.display = "none";

    if (res && res.success && res.data) {
        contentEl.style.display = "block";
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
        document.getElementById("stat-shortlisted-apps").innerText = (apps.shortlisted || 0) + (apps.accepted || 0);

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
                <table style="width:100%;border-collapse:collapse;font-size:0.9rem;text-align:left;">
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
                        <span class="badge badge-primary" style="font-size:0.82rem;font-weight:700;">
                            👥 ${job.application_count || 0} hồ sơ
                        </span>
                    </div>
                </div>
            `).join("");
        }

    } else {
        errorEl.style.display = "block";
        document.getElementById("comp-dash-err-msg").innerText = (res && res.message) ? res.message : "Đã có lỗi xảy ra.";
    }
}

// Inherit getAppStatusBadge(status) from api.js
</script>
