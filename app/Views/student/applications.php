<?php include __DIR__ . "/nav.php"; ?>

<div class="container" style="margin-bottom:3rem;">
    <!-- Filter bar -->
    <div style="background:#fff;border-radius:var(--radius);border:1px solid var(--border);padding:1rem 1.25rem;margin-bottom:1.5rem;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;">
        <div style="display:flex;align-items:center;gap:0.75rem;">
            <label for="filter-app-status" style="font-size:0.9rem;font-weight:600;color:var(--dark);">Lọc theo trạng thái:</label>
            <select id="filter-app-status" class="form-control" style="width:auto;padding:0.4rem 0.8rem;font-size:0.88rem;" onchange="loadApplications()">
                <option value="">Tất cả trạng thái</option>
                <option value="pending">⏳ Chờ duyệt</option>
                <option value="viewed">👀 Đã xem</option>
                <option value="shortlisted">🌟 Phù hợp / Mời phỏng vấn</option>
                <option value="accepted">🎉 Trúng tuyển</option>
                <option value="rejected">❌ Từ chối</option>
                <option value="withdrawn">↩️ Đã rút đơn</option>
            </select>
        </div>
        <div id="apps-count-text" style="font-size:0.88rem;color:var(--text-muted);font-weight:600;">
            Đang tải dữ liệu...
        </div>
    </div>

    <!-- Loading State -->
    <div id="apps-loading" style="text-align:center;padding:2rem 0;">
        <div class="job-card skeleton" style="height:140px;margin-bottom:1rem;"></div>
        <div class="job-card skeleton" style="height:140px;margin-bottom:1rem;"></div>
        <div class="job-card skeleton" style="height:140px;"></div>
    </div>

    <!-- Error State -->
    <div id="apps-error" style="display:none;background:#fff;border-radius:var(--radius);padding:2rem;border:1px solid var(--danger);text-align:center;">
        <div style="font-size:2.5rem;color:var(--danger);margin-bottom:1rem;">⚠️</div>
        <h3 style="font-weight:700;color:var(--dark);margin-bottom:0.5rem;">Không Thể Tải Đơn Ứng Tuyển</h3>
        <p id="apps-err-msg" style="color:var(--text-muted);margin-bottom:1rem;">Đã xảy ra lỗi khi kết nối.</p>
        <button onclick="loadApplications()" class="btn btn-primary btn-sm">Thử Lại</button>
    </div>

    <!-- Applications Container -->
    <div id="apps-container" style="display:flex;flex-direction:column;gap:1rem;">
        <!-- Dynamic Cards -->
    </div>

    <!-- Empty State -->
    <div id="apps-empty" style="display:none;background:#fff;border-radius:var(--radius);border:1px solid var(--border);padding:3rem 1.5rem;text-align:center;">
        <div style="font-size:3rem;margin-bottom:1rem;">📂</div>
        <h3 style="font-size:1.25rem;font-weight:700;color:var(--dark);margin-bottom:0.5rem;">Chưa Có Đơn Ứng Tuyển Nào</h3>
        <p style="color:var(--text-muted);max-width:450px;margin:0 auto 1.5rem;">
            Bạn chưa gửi đơn ứng tuyển vào công việc part-time nào. Hãy khám phá ngay các công việc phù hợp với lịch học của bạn!
        </p>
        <a href="/viec-lam" class="btn btn-primary">🔍 Khám Phá Việc Làm Ngay</a>
    </div>

    <!-- Pagination -->
    <div id="apps-pagination" style="display:flex;justify-content:center;gap:0.5rem;margin-top:2rem;"></div>
</div>

<script>
let currentAppPage = 1;

document.addEventListener("DOMContentLoaded", () => {
    // Auth Guard
    if (!TokenStorage.isLoggedIn()) {
        showToast("Vui lòng đăng nhập để xem đơn ứng tuyển.", "error");
        window.location.href = `/login?login_required=1&redirect=${encodeURIComponent(window.location.pathname)}`;
        return;
    }
    const user = TokenStorage.getUser();
    if (!user || (user.role !== "student" && user.role !== "developer")) {
        showToast("Chỉ tài khoản Sinh viên mới có quyền xem trang này.", "error");
        setTimeout(() => { window.location.href = "/viec-lam"; }, 1000);
        return;
    }

    loadApplications();
});

function getStatusBadge(status) {
    const map = {
        "pending":     { label: "⏳ Chờ duyệt", bg: "#fef3c7", color: "#92400e" },
        "viewed":      { label: "👀 Đã xem", bg: "#dbeafe", color: "#1e40af" },
        "shortlisted": { label: "🌟 Phù hợp / Đã chọn", bg: "#d1fae5", color: "#065f46" },
        "accepted":    { label: "🎉 Trúng tuyển", bg: "#bbf7d0", color: "#166534" },
        "rejected":    { label: "❌ Từ chối", bg: "#fee2e2", color: "#991b1b" },
        "withdrawn":   { label: "↩️ Đã rút đơn", bg: "#f1f5f9", color: "#475569" }
    };
    const s = map[status] || { label: status, bg: "#f1f5f9", color: "#475569" };
    return `<span class="badge" style="background:${s.bg};color:${s.color};font-size:0.82rem;padding:0.3rem 0.65rem;border-radius:20px;font-weight:700;">${escapeHtml(s.label)}</span>`;
}

async function loadApplications(page = 1) {
    currentAppPage = page;
    const loadingEl = document.getElementById("apps-loading");
    const errorEl = document.getElementById("apps-error");
    const container = document.getElementById("apps-container");
    const emptyEl = document.getElementById("apps-empty");
    const countText = document.getElementById("apps-count-text");
    const paginationEl = document.getElementById("apps-pagination");

    loadingEl.style.display = "block";
    errorEl.style.display = "none";
    container.innerHTML = "";
    emptyEl.style.display = "none";
    paginationEl.innerHTML = "";

    const statusFilter = document.getElementById("filter-app-status").value;
    const params = new URLSearchParams({ page: currentAppPage, per_page: 10 });
    if (statusFilter) params.append("status", statusFilter);

    const res = await apiRequest(`/student/applications?${params.toString()}`, { requireAuth: true });
    loadingEl.style.display = "none";

    if (res && res.success && Array.isArray(res.data)) {
        const apps = res.data;
        const meta = res.meta || {};
        const total = meta.total || apps.length;

        countText.innerText = `Tìm thấy ${total} đơn ứng tuyển`;

        if (apps.length === 0) {
            emptyEl.style.display = "block";
            return;
        }

        container.innerHTML = apps.map(app => {
            const canWithdraw = ["pending", "viewed", "reviewed"].includes(app.status);
            return `
                <div class="job-card" style="padding:1.5rem;margin:0;display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:1.25rem;">
                    <div style="flex:1;min-width:280px;">
                        <div style="display:flex;align-items:center;gap:0.75rem;margin-bottom:0.5rem;flex-wrap:wrap;">
                            <h3 style="font-size:1.15rem;font-weight:700;margin:0;color:var(--dark);">
                                <a href="/viec-lam/${encodeURIComponent(app.job_id)}">${escapeHtml(app.job_title || "Vị trí việc làm")}</a>
                            </h3>
                            ${getStatusBadge(app.status)}
                        </div>

                        <div style="color:var(--text-muted);font-size:0.88rem;margin-bottom:0.75rem;">
                            🏢 <strong>${escapeHtml(app.company_name || "Nhà tuyển dụng")}</strong> &bull; 
                            📅 Ngày nộp: ${formatDate(app.created_at)} &bull; 
                            ⏰ Ca mong muốn: <strong>${getShiftLabel(app.preferred_shift)}</strong>
                        </div>

                        ${app.cover_letter ? `
                            <div style="background:#f8fafc;border-left:3px solid var(--border);padding:0.6rem 0.85rem;font-size:0.85rem;color:var(--text);margin-bottom:0.75rem;border-radius:0 var(--radius) var(--radius) 0;line-height:1.5;">
                                💬 <em>"${escapeHtml(app.cover_letter)}"</em>
                            </div>
                        ` : ''}

                        ${app.cv_url_snapshot ? `
                            <div style="font-size:0.8rem;color:var(--text-muted);">
                                📎 CV đính kèm lúc nộp: <a href="${escapeHtml(app.cv_url_snapshot)}" target="_blank" style="text-decoration:underline;">Xem liên kết CV</a>
                            </div>
                        ` : ''}
                    </div>

                    <div style="display:flex;flex-direction:column;align-items:flex-end;gap:0.75rem;">
                        <a href="/viec-lam/${encodeURIComponent(app.job_id)}" class="btn btn-outline btn-sm">
                            Xem tin việc làm &rarr;
                        </a>
                        ${canWithdraw ? `
                            <button onclick="handleWithdraw('${escapeHtml(app.id)}')" class="btn btn-sm" style="background:#fff;border:1px solid var(--danger);color:var(--danger);font-size:0.8rem;">
                                ↩️ Rút đơn ứng tuyển
                            </button>
                        ` : ''}
                    </div>
                </div>
            `;
        }).join("");

        // Render Pagination
        const totalPages = meta.total_pages || 1;
        if (totalPages > 1) {
            let pagHtml = "";
            for (let i = 1; i <= totalPages; i++) {
                pagHtml += `<button onclick="loadApplications(${i})" class="btn btn-sm ${i === currentAppPage ? 'btn-primary' : 'btn-outline'}" style="min-width:36px;">${i}</button>`;
            }
            paginationEl.innerHTML = pagHtml;
        }

    } else {
        errorEl.style.display = "block";
        document.getElementById("apps-err-msg").innerText = (res && res.message) ? res.message : "Đã có lỗi xảy ra.";
    }
}

async function handleWithdraw(applicationId) {
    if (!confirm("Bạn có chắc chắn muốn rút đơn ứng tuyển này không?\nHành động này không thể hoàn tác.")) {
        return;
    }

    const res = await apiRequest(`/applications/${encodeURIComponent(applicationId)}/withdraw`, {
        method: "POST",
        requireAuth: true
    });

    if (res && res.success) {
        showToast("Rút đơn ứng tuyển thành công!", "success");
        loadApplications(currentAppPage);
    } else {
        showToast((res && res.message) ? res.message : "Không thể rút đơn ứng tuyển.", "error");
    }
}
</script>
