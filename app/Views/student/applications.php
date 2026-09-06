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
    <div id="apps-error" class="state-error" style="display:none;">
        <div class="state-error-icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
        </div>
        <h3 class="state-error-title">Không Thể Tải Đơn Ứng Tuyển</h3>
        <p id="apps-err-msg" class="state-error-desc">Đã xảy ra lỗi khi kết nối.</p>
        <button onclick="loadApplications()" class="btn btn-primary btn-sm">Thử Lại</button>
    </div>

    <!-- Applications Container -->
    <div id="apps-container" style="display:flex;flex-direction:column;gap:1rem;">
        <!-- Dynamic Cards -->
    </div>

    <!-- Empty State -->
    <div id="apps-empty" class="surface-card empty-state" style="display:none;">
        <div class="empty-state-icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>
        </div>
        <h3 class="empty-state-title">Chưa Có Đơn Ứng Tuyển Nào</h3>
        <p class="empty-state-text">
            Bạn chưa gửi đơn ứng tuyển vào công việc part-time nào. Hãy khám phá ngay các công việc phù hợp với lịch học của bạn!
        </p>
        <div class="empty-state-action">
            <a href="/viec-lam" class="btn btn-primary">Khám Phá Việc Làm Ngay</a>
        </div>
    </div>

    <!-- Pagination -->
    <div id="apps-pagination" style="display:flex;justify-content:center;gap:0.5rem;margin-top:2rem;"></div>
</div>

<script>
let currentAppPage = 1;

function initStudentApplicationsPage() {
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

    loadApplications(1);
}

if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initStudentApplicationsPage);
} else {
    initStudentApplicationsPage();
}

function getStatusBadge(status) {
    return (typeof getAppStatusBadge === "function") ? getAppStatusBadge(status) : `<span class="badge">${escapeHtml(status)}</span>`;
}

async function loadApplications(page = 1) {
    currentAppPage = page;
    const loadingEl = document.getElementById("apps-loading");
    const errorEl = document.getElementById("apps-error");
    const container = document.getElementById("apps-container");
    const emptyEl = document.getElementById("apps-empty");
    const countText = document.getElementById("apps-count-text");
    const paginationEl = document.getElementById("apps-pagination");

    if (loadingEl) loadingEl.style.display = "block";
    if (errorEl) errorEl.style.display = "none";
    if (container) container.innerHTML = "";
    if (emptyEl) emptyEl.style.display = "none";
    if (paginationEl) paginationEl.innerHTML = "";

    try {
        const statusFilter = document.getElementById("filter-app-status").value;
        const params = new URLSearchParams({ page: currentAppPage, per_page: 10 });
        if (statusFilter) params.append("status", statusFilter);

        const res = await apiRequest(`/student/applications?${params.toString()}`, { requireAuth: true });

        if (res && res.success && Array.isArray(res.data)) {
            const apps = res.data;
            const meta = res.meta || {};
            const total = meta.total || apps.length;

            if (countText) countText.innerText = `Tìm thấy ${total} đơn ứng tuyển`;

            if (apps.length === 0) {
                if (emptyEl) emptyEl.style.display = "block";
                return;
            }

        container.innerHTML = apps.map(app => {
            const canWithdraw = ["pending", "viewed", "reviewed"].includes(app.status);
            return `
                <div class="data-card" style="margin:0;display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:1.25rem;">
                    <div style="flex:1;min-width:280px;">
                        <div style="display:flex;align-items:center;gap:0.75rem;margin-bottom:0.5rem;flex-wrap:wrap;">
                            <h3 style="font-size:1.15rem;font-weight:700;margin:0;color:var(--dark);">
                                <a href="/viec-lam/${encodeURIComponent(app.job_id)}">${escapeHtml(app.job_title || "Vị trí việc làm")}</a>
                            </h3>
                            ${getStatusBadge(app.status)}
                        </div>

                        <div style="color:var(--text-muted);font-size:0.88rem;margin-bottom:0.75rem;">
                            <strong>${escapeHtml(app.company_name || "Nhà tuyển dụng")}</strong> &bull;
                            Ngày nộp: ${formatDate(app.created_at)} &bull;
                            Ca mong muốn: <strong>${getShiftLabel(app.preferred_shift)}</strong>
                        </div>

                        ${app.cover_letter ? `
                            <div style="background:#f8fafc;border-left:3px solid var(--border);padding:0.6rem 0.85rem;font-size:0.85rem;color:var(--text);margin-bottom:0.75rem;border-radius:0 var(--radius) var(--radius) 0;line-height:1.5;">
                                <em>"${escapeHtml(app.cover_letter)}"</em>
                            </div>
                        ` : ''}

                        ${app.has_cv_snapshot ? `
                            <div style="font-size:0.82rem;color:var(--text);margin-top:0.35rem;display:flex;align-items:center;gap:0.4rem;flex-wrap:wrap;">
                                <span>📄 CV đã nộp:</span>
                                <strong style="color:var(--dark);word-break:break-all;">${escapeHtml(app.cv_file_name || 'Bản sao PDF')}</strong>
                                ${app.cv_file_size ? `<span style="color:var(--text-muted);font-size:0.78rem;">(${typeof formatBytes === 'function' ? formatBytes(app.cv_file_size) : app.cv_file_size + ' B'})</span>` : ''}
                            </div>
                        ` : (app.cv_url_snapshot ? `
                            <div style="font-size:0.8rem;color:var(--text-muted);margin-top:0.35rem;">
                                CV đính kèm lúc nộp: <a href="${escapeHtml(app.cv_url_snapshot)}" target="_blank" rel="noopener noreferrer" style="text-decoration:underline;">Xem liên kết CV</a>
                            </div>
                        ` : `
                            <div style="font-size:0.8rem;color:var(--text-muted);margin-top:0.35rem;">
                                Không có tệp CV đính kèm
                            </div>
                        `)}
                    </div>

                    <div class="row-actions student-app-actions">
                        ${app.has_cv_snapshot ? `
                            <button onclick="viewApplicationCv('${escapeHtml(app.id)}', this)" class="btn btn-outline btn-sm row-action-btn" style="display:inline-flex;align-items:center;gap:0.35rem;">
                                📄 Xem CV đã nộp
                            </button>
                        ` : ''}
                        <a href="/viec-lam/${encodeURIComponent(app.job_id)}" class="btn btn-outline btn-sm row-action-btn">
                            Xem việc làm &rarr;
                        </a>
                        ${canWithdraw ? `
                            <button onclick="handleWithdraw('${escapeHtml(app.id)}')" class="btn btn-sm row-action-btn" style="background:#fff;border:1px solid var(--border);color:var(--danger);">
                                Rút đơn ứng tuyển
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
            if (errorEl) errorEl.style.display = "block";
            const msgEl = document.getElementById("apps-err-msg");
            if (msgEl) msgEl.innerText = (res && res.message) ? res.message : "Đã có lỗi xảy ra.";
        }
    } catch (err) {
        console.error("Error loading student applications:", err);
        if (errorEl) errorEl.style.display = "block";
        const msgEl = document.getElementById("apps-err-msg");
        if (msgEl) msgEl.innerText = "Lỗi kết nối máy chủ hoặc tải dữ liệu.";
    } finally {
        if (loadingEl) loadingEl.style.display = "none";
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
