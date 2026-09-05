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
        <div style="background:linear-gradient(135deg, #1e293b 0%, #0f172a 100%);color:#fff;border-radius:var(--radius);padding:1.5rem 2rem;margin-bottom:2rem;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;">
            <div>
                <h2 style="font-size:1.35rem;font-weight:800;color:#fff;margin-bottom:0.25rem;">
                    Xin chào, <span id="dash-company-name">Nhà tuyển dụng</span>!
                </h2>
                <p style="color:#94a3b8;font-size:0.9rem;margin:0;">
                    Dưới đây là thống kê tình hình tin tuyển dụng và hồ sơ ứng viên mới nhất.
                </p>
            </div>
            <div style="display:flex;gap:0.75rem;">
                <a href="/company/jobs/create" class="btn btn-primary btn-sm" style="background:var(--primary);border:none;">
                    ➕ Đăng Tin Mới
                </a>
                <a href="/company/applications" class="btn btn-outline btn-sm" style="color:#fff;border-color:#475569;">
                    👥 Xem Ứng Viên
                </a>
            </div>
        </div>

        <!-- 4 Key Stat Cards -->
        <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(220px, 1fr));gap:1.25rem;margin-bottom:2rem;">
            <!-- Card 1: Active Jobs -->
            <div class="job-card" style="padding:1.25rem 1.5rem;margin:0;display:flex;align-items:center;gap:1.25rem;">
                <div style="width:48px;height:48px;border-radius:12px;background:#e0e7ff;color:#4338ca;display:flex;align-items:center;justify-content:center;font-size:1.5rem;">
                    📋
                </div>
                <div>
                    <div style="font-size:0.85rem;color:var(--text-muted);font-weight:600;">Tin Đang Tuyển</div>
                    <div id="stat-active-jobs" style="font-size:1.6rem;font-weight:800;color:var(--dark);">0</div>
                </div>
            </div>

            <!-- Card 2: Total Applications -->
            <div class="job-card" style="padding:1.25rem 1.5rem;margin:0;display:flex;align-items:center;gap:1.25rem;">
                <div style="width:48px;height:48px;border-radius:12px;background:#dbeafe;color:#1d4ed8;display:flex;align-items:center;justify-content:center;font-size:1.5rem;">
                    📥
                </div>
                <div>
                    <div style="font-size:0.85rem;color:var(--text-muted);font-weight:600;">Tổng Hồ Sơ Nhận</div>
                    <div id="stat-total-apps" style="font-size:1.6rem;font-weight:800;color:var(--dark);">0</div>
                </div>
            </div>

            <!-- Card 3: Pending Review -->
            <div class="job-card" style="padding:1.25rem 1.5rem;margin:0;display:flex;align-items:center;gap:1.25rem;">
                <div style="width:48px;height:48px;border-radius:12px;background:#fef3c7;color:#b45309;display:flex;align-items:center;justify-content:center;font-size:1.5rem;">
                    ⏳
                </div>
                <div>
                    <div style="font-size:0.85rem;color:var(--text-muted);font-weight:600;">Hồ Sơ Chờ Duyệt</div>
                    <div id="stat-pending-apps" style="font-size:1.6rem;font-weight:800;color:#d97706;">0</div>
                </div>
            </div>

            <!-- Card 4: Shortlisted / Accepted -->
            <div class="job-card" style="padding:1.25rem 1.5rem;margin:0;display:flex;align-items:center;gap:1.25rem;">
                <div style="width:48px;height:48px;border-radius:12px;background:#dcfce7;color:#15803d;display:flex;align-items:center;justify-content:center;font-size:1.5rem;">
                    🌟
                </div>
                <div>
                    <div style="font-size:0.85rem;color:var(--text-muted);font-weight:600;">Đã Chọn / Mời Phỏng Vấn</div>
                    <div id="stat-shortlisted-apps" style="font-size:1.6rem;font-weight:800;color:#16a34a;">0</div>
                </div>
            </div>
        </div>

        <!-- Section 2: Recent Applications Table -->
        <div style="background:#fff;border-radius:var(--radius);border:1px solid var(--border);padding:1.5rem;margin-bottom:2rem;">
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
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;align-items:start;">
            <!-- Top Jobs -->
            <div style="background:#fff;border-radius:var(--radius);border:1px solid var(--border);padding:1.5rem;">
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
            <div style="background:#fff;border-radius:var(--radius);border:1px solid var(--border);padding:1.5rem;">
                <h3 style="font-size:1.1rem;font-weight:700;color:var(--dark);margin-bottom:1.25rem;">
                    📊 Tình Trạng Tin Tuyển Dụng
                </h3>

                <div style="display:flex;flex-direction:column;gap:0.85rem;">
                    <div style="display:flex;justify-content:space-between;align-items:center;padding:0.6rem 0.75rem;background:#f8fafc;border-radius:var(--radius);">
                        <span style="font-size:0.9rem;font-weight:600;color:var(--dark);">🟢 Đang hiển thị (Published)</span>
                        <span id="dist-published" class="badge badge-success" style="font-size:0.85rem;">0</span>
                    </div>
                    <div style="display:flex;justify-content:space-between;align-items:center;padding:0.6rem 0.75rem;background:#f8fafc;border-radius:var(--radius);">
                        <span style="font-size:0.9rem;font-weight:600;color:var(--dark);">📝 Bản nháp (Draft)</span>
                        <span id="dist-draft" class="badge" style="background:#f1f5f9;color:#475569;font-size:0.85rem;">0</span>
                    </div>
                    <div style="display:flex;justify-content:space-between;align-items:center;padding:0.6rem 0.75rem;background:#f8fafc;border-radius:var(--radius);">
                        <span style="font-size:0.9rem;font-weight:600;color:var(--dark);">⏳ Chờ duyệt (Pending Approval)</span>
                        <span id="dist-pending" class="badge" style="background:#fef3c7;color:#92400e;font-size:0.85rem;">0</span>
                    </div>
                    <div style="display:flex;justify-content:space-between;align-items:center;padding:0.6rem 0.75rem;background:#f8fafc;border-radius:var(--radius);">
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
                <div style="display:flex;justify-content:space-between;align-items:center;padding:0.75rem;border:1px solid var(--border);border-radius:var(--radius);background:#f8fafc;">
                    <div style="flex:1;min-width:0;margin-right:0.75rem;">
                        <a href="/viec-lam/${encodeURIComponent(job.id)}" target="_blank" style="font-weight:700;font-size:0.92rem;color:var(--dark);display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                            ${escapeHtml(job.title)}
                        </a>
                        <span class="badge" style="background:#e2e8f0;color:#475569;font-size:0.72rem;margin-top:0.25rem;">
                            Trạng thái: ${escapeHtml(job.status)}
                        </span>
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

function getAppStatusBadge(status) {
    const map = {
        "pending":     { label: "Chờ duyệt", bg: "#fef3c7", color: "#92400e" },
        "viewed":      { label: "Đã xem", bg: "#dbeafe", color: "#1e40af" },
        "shortlisted": { label: "Phù hợp", bg: "#d1fae5", color: "#065f46" },
        "accepted":    { label: "Trúng tuyển", bg: "#bbf7d0", color: "#166534" },
        "rejected":    { label: "Từ chối", bg: "#fee2e2", color: "#991b1b" },
        "withdrawn":   { label: "Đã rút", bg: "#f1f5f9", color: "#475569" }
    };
    const s = map[status] || { label: status, bg: "#f1f5f9", color: "#475569" };
    return `<span class="badge" style="background:${s.bg};color:${s.color};font-size:0.78rem;padding:0.25rem 0.55rem;border-radius:20px;font-weight:700;">${escapeHtml(s.label)}</span>`;
}
</script>
