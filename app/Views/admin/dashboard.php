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
        <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(240px, 1fr));gap:1.25rem;margin-bottom:2rem;">
            <!-- Card 1: Users -->
            <div class="job-card" style="padding:1.5rem;margin:0;border-top:4px solid #3b82f6;">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;">
                    <div>
                        <div style="color:var(--text-muted);font-size:0.85rem;font-weight:600;margin-bottom:0.35rem;">TỔNG NGƯỜI DÙNG</div>
                        <div id="stat-total-users" style="font-size:2rem;font-weight:800;color:var(--dark);line-height:1;">0</div>
                    </div>
                    <div style="font-size:2rem;line-height:1;">👥</div>
                </div>
                <div style="margin-top:1rem;font-size:0.82rem;color:var(--text-muted);border-top:1px solid var(--border);padding-top:0.75rem;">
                    Sinh viên: <strong id="stat-student-users" style="color:#1e40af;">0</strong> &bull; 
                    Công ty: <strong id="stat-company-users" style="color:#b45309;">0</strong> &bull; 
                    Admin: <strong id="stat-admin-users" style="color:#991b1b;">0</strong>
                </div>
            </div>

            <!-- Card 2: Companies -->
            <div class="job-card" style="padding:1.5rem;margin:0;border-top:4px solid #f59e0b;">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;">
                    <div>
                        <div style="color:var(--text-muted);font-size:0.85rem;font-weight:600;margin-bottom:0.35rem;">DOANH NGHIỆP</div>
                        <div id="stat-total-comp" style="font-size:2rem;font-weight:800;color:var(--dark);line-height:1;">0</div>
                    </div>
                    <div style="font-size:2rem;line-height:1;">🏢</div>
                </div>
                <div style="margin-top:1rem;font-size:0.82rem;color:var(--text-muted);border-top:1px solid var(--border);padding-top:0.75rem;">
                    Đã duyệt: <strong id="stat-verified-comp" style="color:#166534;">0</strong> &bull; 
                    Chờ duyệt: <strong id="stat-pending-comp" style="color:#d97706;">0</strong>
                </div>
            </div>

            <!-- Card 3: Jobs -->
            <div class="job-card" style="padding:1.5rem;margin:0;border-top:4px solid #10b981;">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;">
                    <div>
                        <div style="color:var(--text-muted);font-size:0.85rem;font-weight:600;margin-bottom:0.35rem;">TIN TUYỂN DỤNG</div>
                        <div id="stat-total-jobs" style="font-size:2rem;font-weight:800;color:var(--dark);line-height:1;">0</div>
                    </div>
                    <div style="font-size:2rem;line-height:1;">💼</div>
                </div>
                <div style="margin-top:1rem;font-size:0.82rem;color:var(--text-muted);border-top:1px solid var(--border);padding-top:0.75rem;">
                    Đang tuyển: <strong id="stat-pub-jobs" style="color:#166534;">0</strong> &bull; 
                    Bản nháp: <strong id="stat-draft-jobs">0</strong> &bull; 
                    Đã đóng: <strong id="stat-closed-jobs" style="color:#991b1b;">0</strong>
                </div>
            </div>

            <!-- Card 4: Applications -->
            <div class="job-card" style="padding:1.5rem;margin:0;border-top:4px solid #8b5cf6;">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;">
                    <div>
                        <div style="color:var(--text-muted);font-size:0.85rem;font-weight:600;margin-bottom:0.35rem;">HỒ SƠ ỨNG TUYỂN</div>
                        <div id="stat-total-apps" style="font-size:2rem;font-weight:800;color:var(--dark);line-height:1;">0</div>
                    </div>
                    <div style="font-size:2rem;line-height:1;">📄</div>
                </div>
                <div style="margin-top:1rem;font-size:0.82rem;color:var(--text-muted);border-top:1px solid var(--border);padding-top:0.75rem;">
                    Chờ xem: <strong id="stat-pending-apps" style="color:#b45309;">0</strong> &bull; 
                    Phù hợp: <strong id="stat-short-apps" style="color:#065f46;">0</strong> &bull; 
                    Nhận việc: <strong id="stat-acc-apps" style="color:#166534;">0</strong>
                </div>
            </div>
        </div>

        <!-- 2 Column Layout: Quick Actions & Application Distribution -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:2rem;">
            <!-- Column 1: Moderation Shortcuts -->
            <div style="background:#fff;border-radius:var(--radius);border:1px solid var(--border);padding:1.5rem;">
                <h3 style="font-size:1.1rem;font-weight:700;color:var(--dark);margin-bottom:1rem;display:flex;align-items:center;gap:0.5rem;">
                    ⚡ Lối Tắt Kiểm Duyệt Cần Xử Lý
                </h3>
                
                <div style="display:flex;flex-direction:column;gap:0.75rem;">
                    <a href="/admin/companies?verification_status=pending" style="display:flex;justify-content:space-between;align-items:center;padding:0.85rem 1rem;background:#fffbeb;border:1px solid #fde68a;border-radius:var(--radius);text-decoration:none;color:#92400e;transition:var(--transition);">
                        <div style="display:flex;align-items:center;gap:0.75rem;">
                            <span style="font-size:1.25rem;">🏢</span>
                            <div>
                                <div style="font-weight:700;font-size:0.92rem;">Doanh nghiệp chờ xác thực</div>
                                <div style="font-size:0.8rem;color:#b45309;">Xét duyệt tính hợp lệ trước khi cấp phép đăng tin công khai</div>
                            </div>
                        </div>
                        <span id="badge-pending-comp" class="badge" style="background:#f59e0b;color:#fff;font-weight:700;font-size:0.85rem;padding:0.25rem 0.65rem;border-radius:20px;">0</span>
                    </a>

                    <a href="/admin/jobs?status=pending_approval" style="display:flex;justify-content:space-between;align-items:center;padding:0.85rem 1rem;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:var(--radius);text-decoration:none;color:#166534;transition:var(--transition);">
                        <div style="display:flex;align-items:center;gap:0.75rem;">
                            <span style="font-size:1.25rem;">💼</span>
                            <div>
                                <div style="font-weight:700;font-size:0.92rem;">Tin tuyển dụng chờ kiểm duyệt</div>
                                <div style="font-size:0.8rem;color:#15803d;">Duyệt nội dung mô tả, quyền lợi, lương an toàn cho sinh viên</div>
                            </div>
                        </div>
                        <span class="badge" style="background:#10b981;color:#fff;font-weight:700;font-size:0.85rem;padding:0.25rem 0.65rem;border-radius:20px;">Kiểm tra</span>
                    </a>

                    <a href="/admin/users" style="display:flex;justify-content:space-between;align-items:center;padding:0.85rem 1rem;background:#f8fafc;border:1px solid var(--border);border-radius:var(--radius);text-decoration:none;color:var(--dark);transition:var(--transition);">
                        <div style="display:flex;align-items:center;gap:0.75rem;">
                            <span style="font-size:1.25rem;">👥</span>
                            <div>
                                <div style="font-weight:700;font-size:0.92rem;">Quản lý tài khoản người dùng</div>
                                <div style="font-size:0.8rem;color:var(--text-muted);">Khóa hoặc kích hoạt tài khoản vi phạm chính sách</div>
                            </div>
                        </div>
                        <span class="badge" style="background:#e2e8f0;color:var(--dark);font-weight:700;font-size:0.85rem;padding:0.25rem 0.65rem;border-radius:20px;">Xem &rarr;</span>
                    </a>

                    <a href="/admin/audit-logs" style="display:flex;justify-content:space-between;align-items:center;padding:0.85rem 1rem;background:#f8fafc;border:1px solid var(--border);border-radius:var(--radius);text-decoration:none;color:var(--dark);transition:var(--transition);">
                        <div style="display:flex;align-items:center;gap:0.75rem;">
                            <span style="font-size:1.25rem;">📜</span>
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
            <div style="background:#fff;border-radius:var(--radius);border:1px solid var(--border);padding:1.5rem;">
                <h3 style="font-size:1.1rem;font-weight:700;color:var(--dark);margin-bottom:1rem;display:flex;align-items:center;gap:0.5rem;">
                    📊 Phân Bố Hồ Sơ Ứng Tuyển Toàn Sàn
                </h3>

                <div style="display:flex;flex-direction:column;gap:0.85rem;" id="apps-distribution-list">
                    <!-- Populated dynamically -->
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {
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
});

async function loadAdminDashboard() {
    const loadingEl = document.getElementById("admin-dash-loading");
    const errorEl = document.getElementById("admin-dash-error");
    const contentEl = document.getElementById("admin-dash-content");

    loadingEl.style.display = "block";
    errorEl.style.display = "none";
    contentEl.style.display = "none";

    const res = await apiRequest("/admin/dashboard", { requireAuth: true });
    loadingEl.style.display = "none";

    if (res && res.success && res.data) {
        contentEl.style.display = "block";
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
        errorEl.style.display = "block";
        document.getElementById("admin-dash-err-msg").innerText = (res && res.message) ? res.message : "Đã có lỗi xảy ra.";
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
