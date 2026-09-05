<?php include __DIR__ . "/nav.php"; ?>

<div class="container" style="margin-bottom:3rem;">
    <!-- Loading State -->
    <div id="student-dash-loading" style="text-align:center;padding:3rem 0;">
        <div class="job-card skeleton" style="height:120px;margin-bottom:1rem;"></div>
        <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(200px, 1fr));gap:1rem;margin-bottom:1.5rem;">
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
        <div style="background:linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);border:1px solid #bfdbfe;border-radius:var(--radius);padding:1.5rem;margin-bottom:2rem;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;">
            <div>
                <h2 style="font-size:1.3rem;font-weight:800;color:#1e3a8a;margin-bottom:0.25rem;">
                    Xin chào, <span id="dash-user-name">Sinh viên</span>! 👋
                </h2>
                <p style="color:#3b82f6;font-size:0.9rem;margin:0;">
                    Theo dõi tiến độ đơn ứng tuyển và các cơ hội việc làm part-time phù hợp nhất hôm nay.
                </p>
            </div>
            <div style="display:flex;gap:0.5rem;flex-wrap:wrap;">
                <a href="/student/profile" class="btn btn-outline btn-sm" style="background:#fff;">Cập Nhật Hồ Sơ</a>
                <a href="/student/applications" class="btn btn-primary btn-sm">Xem Đơn Ứng Tuyển</a>
            </div>
        </div>

        <!-- 4 Metric Cards -->
        <div class="stat-grid">
            <div class="stat-card">
                <div class="stat-card-icon" style="background:var(--primary-light);color:var(--primary);">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                </div>
                <div class="stat-card-body">
                    <div class="stat-card-label">Tổng Đơn Đã Nộp</div>
                    <div id="stat-total-apps" class="stat-card-value">0</div>
                    <div class="stat-card-subtext">Tất cả các vị trí đã apply</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-card-icon" style="background:var(--warning-light);color:var(--warning-text);">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                </div>
                <div class="stat-card-body">
                    <div class="stat-card-label">Đang Chờ Duyệt</div>
                    <div id="stat-pending-apps" class="stat-card-value" style="color:var(--warning-text);">0</div>
                    <div class="stat-card-subtext">Nhà tuyển dụng chưa xem</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-card-icon" style="background:var(--success-light);color:var(--success-text);">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                </div>
                <div class="stat-card-body">
                    <div class="stat-card-label">Được Chọn / Phù Hợp</div>
                    <div id="stat-shortlisted-apps" class="stat-card-value" style="color:var(--success-text);">0</div>
                    <div class="stat-card-subtext">Đã vào vòng phỏng vấn / nhận việc</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-card-icon" style="background:#f3e8ff;color:#7c3aed;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                </div>
                <div class="stat-card-body">
                    <div class="stat-card-label">Thông Báo Mới</div>
                    <div id="stat-unread-notifs" class="stat-card-value" style="color:#7c3aed;">0</div>
                    <div class="stat-card-subtext"><a href="/student/notifications" style="color:#7c3aed;text-decoration:underline;">Xem thông báo</a></div>
                </div>
            </div>
        </div>

        <!-- Application Status Pipeline -->
        <div class="surface-card" style="margin-bottom:2rem;">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem;">
                <h3 style="font-size:1.1rem;font-weight:700;color:var(--dark);margin:0;">
                    📊 Tiến Trình Đơn Ứng Tuyển
                </h3>
                <a href="/student/applications" style="font-size:0.85rem;font-weight:600;color:var(--primary);">
                    Chi tiết &rarr;
                </a>
            </div>

            <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(140px, 1fr));gap:0.75rem;text-align:center;">
                <div style="padding:0.75rem;border-radius:var(--radius-sm);background:var(--bg);">
                    <div style="font-size:0.8rem;color:var(--text-muted);">Chờ duyệt</div>
                    <div id="pipe-pending" style="font-size:1.3rem;font-weight:700;color:var(--warning-text);">0</div>
                </div>
                <div style="padding:0.75rem;border-radius:var(--radius-sm);background:var(--bg);">
                    <div style="font-size:0.8rem;color:var(--text-muted);">Đã xem</div>
                    <div id="pipe-viewed" style="font-size:1.3rem;font-weight:700;color:var(--primary);">0</div>
                </div>
                <div style="padding:0.75rem;border-radius:var(--radius-sm);background:var(--bg);">
                    <div style="font-size:0.8rem;color:var(--text-muted);">Phù hợp</div>
                    <div id="pipe-shortlisted" style="font-size:1.3rem;font-weight:700;color:var(--secondary);">0</div>
                </div>
                <div style="padding:0.75rem;border-radius:var(--radius-sm);background:var(--bg);">
                    <div style="font-size:0.8rem;color:var(--text-muted);">Trúng tuyển</div>
                    <div id="pipe-accepted" style="font-size:1.3rem;font-weight:700;color:var(--success);">0</div>
                </div>
                <div style="padding:0.75rem;border-radius:var(--radius-sm);background:var(--bg);">
                    <div style="font-size:0.8rem;color:var(--text-muted);">Từ chối</div>
                    <div id="pipe-rejected" style="font-size:1.3rem;font-weight:700;color:var(--danger);">0</div>
                </div>
                <div style="padding:0.75rem;border-radius:var(--radius-sm);background:var(--bg);">
                    <div style="font-size:0.8rem;color:var(--text-muted);">Đã rút</div>
                    <div id="pipe-withdrawn" style="font-size:1.3rem;font-weight:700;color:var(--neutral-text);">0</div>
                </div>
            </div>
        </div>

        <!-- 2 Columns: Expiring Favorites & Recent Notifications -->
        <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(320px, 1fr));gap:1.5rem;">
            <!-- Expiring Favorites -->
            <div class="surface-card">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem;">
                    <h3 style="font-size:1.1rem;font-weight:700;color:var(--dark);margin:0;">
                        ⭐ Việc Đã Lưu Sắp Hết Hạn
                    </h3>
                    <a href="/student/favorites" style="font-size:0.85rem;font-weight:600;color:var(--primary);">
                        Tất cả &rarr;
                    </a>
                </div>
                <div id="expiring-favs-container">
                    <!-- Dynamic rendering -->
                </div>
            </div>

            <!-- Recent Notifications -->
            <div class="surface-card">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem;">
                    <h3 style="font-size:1.1rem;font-weight:700;color:var(--dark);margin:0;">
                        🔔 Thông Báo Gần Đây
                    </h3>
                    <a href="/student/notifications" style="font-size:0.85rem;font-weight:600;color:var(--primary);">
                        Tất cả &rarr;
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
                <div style="text-align:center;padding:2rem 1rem;color:var(--text-muted);font-size:0.9rem;">
                    <div>📂 Không có việc yêu thích nào sắp hết hạn.</div>
                    <a href="/viec-lam" class="btn btn-outline btn-sm" style="margin-top:0.75rem;">Tìm việc ngay</a>
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
                            ${escapeHtml(job.company_name || "Doanh nghiệp")} &bull; ⏰ ${getShiftLabel(job.shift_type)}
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
                <div style="text-align:center;padding:2rem 1rem;color:var(--text-muted);font-size:0.9rem;">
                    <div>🎉 Không có thông báo mới nào.</div>
                </div>
            `;
        } else {
            notifContainer.innerHTML = notifs.map(n => `
                <div style="border-bottom:1px solid var(--border);padding:0.75rem 0;display:flex;gap:0.75rem;align-items:flex-start;">
                    <span style="font-size:1.1rem;">${n.read_at ? "✉️" : "📩"}</span>
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
