<?php include __DIR__ . "/nav.php"; ?>

<div class="container" style="margin-bottom:3rem;">
    <!-- Loading State -->
    <div id="admin-dash-loading" style="display:block;">
        <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(220px, 1fr));gap:1.25rem;margin-bottom:2rem;">
            <div class="job-card skeleton" style="height:110px;"></div>
            <div class="job-card skeleton" style="height:110px;"></div>
            <div class="job-card skeleton" style="height:110px;"></div>
            <div class="job-card skeleton" style="height:110px;"></div>
        </div>
        <div class="job-card skeleton" style="height:250px;"></div>
    </div>

    <!-- Error State -->
    <div id="admin-dash-error" style="display:none;background:#fff;border-radius:var(--radius);padding:3rem 1.5rem;text-align:center;border:1px solid var(--danger);margin-bottom:2rem;">
        <div style="font-size:3rem;margin-bottom:1rem;">⚠️</div>
        <h3 style="font-weight:700;color:var(--dark);margin-bottom:0.5rem;">Không Thể Tải Dữ Liệu Tổng Quan Quản Trị</h3>
        <p id="admin-dash-err-msg" style="color:var(--text-muted);margin-bottom:1.5rem;">Đã xảy ra lỗi khi kết nối với máy chủ API.</p>
        <button onclick="loadAdminDashboard()" class="btn btn-primary btn-sm">Thử Lại</button>
    </div>

    <!-- Main Content -->
    <div id="admin-dash-content" style="display:none;">
        <!-- Top 4 Summary Cards -->
        <!-- Top 4 Summary Cards -->
        <div class="stat-grid">
            <!-- Card 1: Users -->
            <div class="stat-card">
                <div class="stat-card-icon stat-card-icon--primary">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                </div>
                <div class="stat-card-body">
                    <div class="stat-card-label">Tổng Người Dùng</div>
                    <div id="stat-total-users" class="stat-card-value">0</div>
                    <div class="stat-card-subtext">
                        SV: <strong id="stat-student-users" style="color:#1e40af;">0</strong> &bull;
                        CT: <strong id="stat-company-users" style="color:#b45309;">0</strong> &bull;
                        Admin: <strong id="stat-admin-users" style="color:#991b1b;">0</strong>
                    </div>
                </div>
            </div>

            <!-- Card 2: Companies -->
            <div class="stat-card">
                <div class="stat-card-icon stat-card-icon--warning">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
                </div>
                <div class="stat-card-body">
                    <div class="stat-card-label">Doanh Nghiệp</div>
                    <div id="stat-total-comp" class="stat-card-value stat-card-value--warning">0</div>
                    <div class="stat-card-subtext">
                        Đã duyệt: <strong id="stat-verified-comp" style="color:#166534;">0</strong> &bull;
                        Chờ duyệt: <strong id="stat-pending-comp" style="color:#d97706;">0</strong>
                    </div>
                </div>
            </div>

            <!-- Card 3: Jobs -->
            <div class="stat-card">
                <div class="stat-card-icon stat-card-icon--success">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
                </div>
                <div class="stat-card-body">
                    <div class="stat-card-label">Tin Tuyển Dụng</div>
                    <div id="stat-total-jobs" class="stat-card-value stat-card-value--success">0</div>
                    <div class="stat-card-subtext">
                        Đang tuyển: <strong id="stat-pub-jobs" style="color:#166534;">0</strong> &bull;
                        Nháp: <strong id="stat-draft-jobs">0</strong> &bull;
                        Đóng: <strong id="stat-closed-jobs" style="color:#991b1b;">0</strong>
                    </div>
                </div>
            </div>

            <!-- Card 4: Applications -->
            <div class="stat-card">
                <div class="stat-card-icon stat-card-icon--purple">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                </div>
                <div class="stat-card-body">
                    <div class="stat-card-label">Hồ Sơ Ứng Tuyển</div>
                    <div id="stat-total-apps" class="stat-card-value stat-card-value--purple">0</div>
                    <div class="stat-card-subtext">
                        Chờ: <strong id="stat-pending-apps" style="color:#b45309;">0</strong> &bull;
                        Chọn: <strong id="stat-short-apps" style="color:#065f46;">0</strong> &bull;
                        Nhận: <strong id="stat-acc-apps" style="color:#166534;">0</strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2 Column Layout: Quick Actions & Application Distribution -->
        <!-- 2 Column Layout: Quick Actions & Application Distribution -->
        <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(320px, 1fr));gap:1.5rem;margin-bottom:2rem;">
            <!-- Column 1: Moderation Shortcuts -->
            <div class="surface-card">
                <div class="section-header" style="margin-bottom:1.25rem;">
                    <div class="section-header-main">
                        <h3 class="section-title">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:var(--warning);"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                            <span>Lối Tắt Kiểm Duyệt Cần Xử Lý</span>
                        </h3>
                    </div>
                </div>
                
                <div style="display:flex;flex-direction:column;gap:0.75rem;">
                    <a href="/admin/companies?verification_status=pending" style="display:flex;justify-content:space-between;align-items:center;padding:0.9rem 1.15rem;background:#fffbeb;border:1px solid #fde68a;border-radius:var(--radius-sm);text-decoration:none;color:#92400e;transition:var(--transition);">
                        <div style="display:flex;align-items:center;gap:0.85rem;">
                            <span style="font-size:1.35rem;">🏢</span>
                            <div>
                                <div style="font-weight:700;font-size:0.92rem;color:#78350f;">Doanh nghiệp chờ xác thực</div>
                                <div style="font-size:0.8rem;color:#b45309;">Xét duyệt tính hợp lệ trước khi cấp phép đăng tin công khai</div>
                            </div>
                        </div>
                        <span id="badge-pending-comp" class="badge" style="background:#f59e0b;color:#fff;font-weight:700;font-size:0.85rem;padding:0.25rem 0.65rem;border-radius:20px;">0</span>
                    </a>

                    <a href="/admin/jobs?status=pending_approval" style="display:flex;justify-content:space-between;align-items:center;padding:0.9rem 1.15rem;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:var(--radius-sm);text-decoration:none;color:#166534;transition:var(--transition);">
                        <div style="display:flex;align-items:center;gap:0.85rem;">
                            <span style="font-size:1.35rem;">💼</span>
                            <div>
                                <div style="font-weight:700;font-size:0.92rem;color:#14532d;">Tin tuyển dụng chờ kiểm duyệt</div>
                                <div style="font-size:0.8rem;color:#15803d;">Duyệt nội dung mô tả, quyền lợi, lương an toàn cho sinh viên</div>
                            </div>
                        </div>
                        <span class="badge" style="background:#10b981;color:#fff;font-weight:700;font-size:0.85rem;padding:0.25rem 0.65rem;border-radius:20px;">Kiểm tra</span>
                    </a>

                    <a href="/admin/users" style="display:flex;justify-content:space-between;align-items:center;padding:0.9rem 1.15rem;background:#f8fafc;border:1px solid var(--border);border-radius:var(--radius-sm);text-decoration:none;color:var(--dark);transition:var(--transition);">
                        <div style="display:flex;align-items:center;gap:0.85rem;">
                            <span style="font-size:1.35rem;">👥</span>
                            <div>
                                <div style="font-weight:700;font-size:0.92rem;">Quản lý tài khoản người dùng</div>
                                <div style="font-size:0.8rem;color:var(--text-muted);">Khóa hoặc kích hoạt tài khoản vi phạm chính sách</div>
                            </div>
                        </div>
                        <span class="badge" style="background:#e2e8f0;color:var(--dark);font-weight:700;font-size:0.85rem;padding:0.25rem 0.65rem;border-radius:20px;">Xem &rarr;</span>
                    </a>

                    <a href="/admin/audit-logs" style="display:flex;justify-content:space-between;align-items:center;padding:0.9rem 1.15rem;background:#f8fafc;border:1px solid var(--border);border-radius:var(--radius-sm);text-decoration:none;color:var(--dark);transition:var(--transition);">
                        <div style="display:flex;align-items:center;gap:0.85rem;">
                            <span style="font-size:1.35rem;">📜</span>
                            <div>
                                <div style="font-weight:700;font-size:0.92rem;">Nhật ký thao tác quản trị</div>
                                <div style="font-size:0.8rem;color:var(--text-muted);">Xem lịch sử kiểm duyệt của các quản trị viên</div>
                            </div>
                        </div>
                        <span class="badge" style="background:#e2e8f0;color:var(--dark);font-weight:700;font-size:0.85rem;padding:0.25rem 0.65rem;border-radius:20px;">Xem &rarr;</span>
                    </a>
                </div>
            </div>

            <!-- Column 2: Application Breakdown -->
            <div class="surface-card">
                <div class="section-header" style="margin-bottom:1.25rem;">
                    <div class="section-header-main">
                        <h3 class="section-title">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:var(--primary);"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
                            <span>Phân Bố Hồ Sơ Ứng Tuyển Toàn Sàn</span>
                        </h3>
                    </div>
                </div>

                <div style="display:flex;flex-direction:column;gap:0.85rem;" id="apps-distribution-list">
                    <!-- Populated dynamically -->
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function initAdminDashboardPage() {
    // Auth UX Guard
    if (!TokenStorage.isLoggedIn()) {
        showToast("Vui lòng đăng nhập với tài khoản Quản trị viên.", "error");
        window.location.href = `/login?login_required=1&redirect=${encodeURIComponent(window.location.pathname)}`;
        return;
    }
    const user = TokenStorage.getUser();
    if (!user || user.role !== "admin") {
        showToast("Chỉ Quản trị viên (Admin) mới có quyền truy cập khu vực này.", "error");
        setTimeout(() => {
            window.location.href = user && user.role === "company" ? "/company/dashboard" : (user && user.role === "student" ? "/student/dashboard" : "/");
        }, 800);
        return;
    }

    loadAdminDashboard();
}

if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initAdminDashboardPage);
} else {
    initAdminDashboardPage();
}

async function loadAdminDashboard() {
    const loadingEl = document.getElementById("admin-dash-loading");
    const errorEl = document.getElementById("admin-dash-error");
    const contentEl = document.getElementById("admin-dash-content");

    if (loadingEl) loadingEl.style.display = "block";
    if (errorEl) errorEl.style.display = "none";
    if (contentEl) contentEl.style.display = "none";

    try {
        const res = await apiRequest("/admin/dashboard", { requireAuth: true });

        if (res && res.success && res.data) {
            if (contentEl) contentEl.style.display = "block";
            const d = res.data;

            // 1. Users
            const u = d.users || {};
            document.getElementById("stat-total-users").innerText = u.total || 0;
            document.getElementById("stat-student-users").innerText = u.student || 0;
            document.getElementById("stat-company-users").innerText = u.company || 0;
            document.getElementById("stat-admin-users").innerText = u.admin || 0;

            // 2. Companies
            const c = d.companies || {};
            document.getElementById("stat-total-comp").innerText = c.total || 0;
            document.getElementById("stat-verified-comp").innerText = c.verified || 0;
            document.getElementById("stat-pending-comp").innerText = c.pending || 0;
            document.getElementById("badge-pending-comp").innerText = c.pending || 0;

            // 3. Jobs
            const j = d.jobs || {};
            document.getElementById("stat-total-jobs").innerText = j.total || 0;
            document.getElementById("stat-pub-jobs").innerText = j.published || 0;
            document.getElementById("stat-draft-jobs").innerText = j.draft || 0;
            document.getElementById("stat-closed-jobs").innerText = j.closed || 0;

            // 4. Applications
            const a = d.applications || {};
            document.getElementById("stat-total-apps").innerText = a.total || 0;
            document.getElementById("stat-pending-apps").innerText = a.pending || 0;
            document.getElementById("stat-short-apps").innerText = a.shortlisted || 0;
            document.getElementById("stat-acc-apps").innerText = a.accepted || 0;

            // Render Application breakdown
            renderAppsDistribution(a);
        } else {
            if (errorEl) errorEl.style.display = "block";
            const msgEl = document.getElementById("admin-dash-err-msg");
            if (msgEl) msgEl.innerText = (res && res.message) ? res.message : "Đã có lỗi xảy ra.";
        }
    } catch (err) {
        console.error("Error loading admin dashboard:", err);
        if (errorEl) errorEl.style.display = "block";
        const msgEl = document.getElementById("admin-dash-err-msg");
        if (msgEl) msgEl.innerText = "Lỗi kết nối máy chủ.";
    } finally {
        if (loadingEl) loadingEl.style.display = "none";
    }
}

function renderAppsDistribution(a) {
    const listEl = document.getElementById("apps-distribution-list");
    const total = a.total || 1;

    const items = [
        { label: "Chờ doanh nghiệp xem (Pending)", count: a.pending || 0, color: "#f59e0b" },
        { label: "Đã xem hồ sơ (Viewed)", count: a.viewed || 0, color: "#3b82f6" },
        { label: "Đã chọn / Phỏng vấn (Shortlisted)", count: a.shortlisted || 0, color: "#10b981" },
        { label: "Trúng tuyển / Nhận việc (Accepted)", count: a.accepted || 0, color: "#059669" },
        { label: "Từ chối (Rejected)", count: a.rejected || 0, color: "#ef4444" },
        { label: "Sinh viên rút đơn (Withdrawn)", count: a.withdrawn || 0, color: "#94a3b8" }
    ];

    listEl.innerHTML = items.map(item => {
        const pct = Math.round((item.count / total) * 100);
        return `
            <div>
                <div style="display:flex;justify-content:space-between;font-size:0.85rem;margin-bottom:0.25rem;">
                    <span style="color:var(--dark);font-weight:600;">${escapeHtml(item.label)}</span>
                    <span style="color:var(--text-muted);">${item.count} đơn (${pct}%)</span>
                </div>
                <div style="background:#f1f5f9;height:8px;border-radius:4px;overflow:hidden;">
                    <div style="background:${item.color};width:${pct}%;height:100%;border-radius:4px;"></div>
                </div>
            </div>
        `;
    }).join("");
}
</script>
