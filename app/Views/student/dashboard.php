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
        <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(220px, 1fr));gap:1rem;margin-bottom:2rem;">
            <div class="job-card" style="padding:1.25rem;border-left:4px solid var(--primary);margin:0;">
                <div style="display:flex;align-items:center;justify-content:space-between;">
                    <span style="font-size:0.85rem;color:var(--text-muted);font-weight:600;">Tổng Đơn Đã Nộp</span>
                    <span style="font-size:1.4rem;">📝</span>
                </div>
                <div id="stat-total-apps" style="font-size:1.8rem;font-weight:800;color:var(--dark);margin-top:0.5rem;">0</div>
                <span style="font-size:0.75rem;color:var(--text-muted);">Tất cả các vị trí đã apply</span>
            </div>

            <div class="job-card" style="padding:1.25rem;border-left:4px solid #f59e0b;margin:0;">
                <div style="display:flex;align-items:center;justify-content:space-between;">
                    <span style="font-size:0.85rem;color:var(--text-muted);font-weight:600;">Đang Chờ Duyệt</span>
                    <span style="font-size:1.4rem;">⏳</span>
                </div>
                <div id="stat-pending-apps" style="font-size:1.8rem;font-weight:800;color:#d97706;margin-top:0.5rem;">0</div>
                <span style="font-size:0.75rem;color:var(--text-muted);">Nhà tuyển dụng chưa xem</span>
            </div>

            <div class="job-card" style="padding:1.25rem;border-left:4px solid var(--success);margin:0;">
                <div style="display:flex;align-items:center;justify-content:space-between;">
                    <span style="font-size:0.85rem;color:var(--text-muted);font-weight:600;">Được Chọn / Phù Hợp</span>
                    <span style="font-size:1.4rem;">🎉</span>
                </div>
                <div id="stat-shortlisted-apps" style="font-size:1.8rem;font-weight:800;color:var(--success);margin-top:0.5rem;">0</div>
                <span style="font-size:0.75rem;color:var(--text-muted);">Đã vào vòng phỏng vấn / nhận việc</span>
            </div>

            <div class="job-card" style="padding:1.25rem;border-left:4px solid #8b5cf6;margin:0;">
                <div style="display:flex;align-items:center;justify-content:space-between;">
                    <span style="font-size:0.85rem;color:var(--text-muted);font-weight:600;">Thông Báo Mới</span>
                    <span style="font-size:1.4rem;">🔔</span>
                </div>
                <div id="stat-unread-notifs" style="font-size:1.8rem;font-weight:800;color:#7c3aed;margin-top:0.5rem;">0</div>
                <span style="font-size:0.75rem;color:var(--text-muted);"><a href="/student/notifications" style="color:#7c3aed;text-decoration:underline;">Xem thông báo</a></span>
            </div>
        </div>

        <!-- Application Status Pipeline -->
        <div style="background:#fff;border-radius:var(--radius);border:1px solid var(--border);padding:1.5rem;margin-bottom:2rem;">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem;">
                <h3 style="font-size:1.1rem;font-weight:700;color:var(--dark);margin:0;">
                    📊 Tiến Trình Đơn Ứng Tuyển
                </h3>
                <a href="/student/applications" style="font-size:0.85rem;font-weight:600;color:var(--primary);">
                    Chi tiết &rarr;
                </a>
            </div>

            <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(140px, 1fr));gap:0.75rem;text-align:center;">
                <div style="padding:0.75rem;border-radius:var(--radius);background:#f8fafc;">
                    <div style="font-size:0.8rem;color:var(--text-muted);">Chờ duyệt</div>
                    <div id="pipe-pending" style="font-size:1.3rem;font-weight:700;color:#d97706;">0</div>
                </div>
                <div style="padding:0.75rem;border-radius:var(--radius);background:#f8fafc;">
                    <div style="font-size:0.8rem;color:var(--text-muted);">Đã xem</div>
                    <div id="pipe-viewed" style="font-size:1.3rem;font-weight:700;color:#2563eb;">0</div>
                </div>
                <div style="padding:0.75rem;border-radius:var(--radius);background:#f8fafc;">
                    <div style="font-size:0.8rem;color:var(--text-muted);">Phù hợp</div>
                    <div id="pipe-shortlisted" style="font-size:1.3rem;font-weight:700;color:#059669;">0</div>
                </div>
                <div style="padding:0.75rem;border-radius:var(--radius);background:#f8fafc;">
                    <div style="font-size:0.8rem;color:var(--text-muted);">Trúng tuyển</div>
                    <div id="pipe-accepted" style="font-size:1.3rem;font-weight:700;color:#10b981;">0</div>
                </div>
                <div style="padding:0.75rem;border-radius:var(--radius);background:#f8fafc;">
                    <div style="font-size:0.8rem;color:var(--text-muted);">Từ chối</div>
                    <div id="pipe-rejected" style="font-size:1.3rem;font-weight:700;color:#ef4444;">0</div>
                </div>
                <div style="padding:0.75rem;border-radius:var(--radius);background:#f8fafc;">
                    <div style="font-size:0.8rem;color:var(--text-muted);">Đã rút</div>
                    <div id="pipe-withdrawn" style="font-size:1.3rem;font-weight:700;color:#64748b;">0</div>
                </div>
            </div>
        </div>

        <!-- 2 Columns: Expiring Favorites & Recent Notifications -->
        <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(320px, 1fr));gap:1.5rem;">
            <!-- Expiring Favorites -->
            <div style="background:#fff;border-radius:var(--radius);border:1px solid var(--border);padding:1.5rem;">
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
            <div style="background:#fff;border-radius:var(--radius);border:1px solid var(--border);padding:1.5rem;">
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
