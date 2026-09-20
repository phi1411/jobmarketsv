<?php include __DIR__ . "/nav.php"; ?>

<div class="container" style="margin-bottom:3rem;">
    <!-- Filter & Action Bar -->
    <div style="background:#fff;border-radius:var(--radius);border:1px solid var(--border);padding:1.25rem 1.5rem;margin-bottom:1.5rem;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;">
        <div style="display:flex;align-items:center;gap:1rem;flex-wrap:wrap;">
            <div style="display:flex;align-items:center;gap:0.5rem;">
                <label for="filter-job-status" style="font-size:0.88rem;font-weight:600;color:var(--dark);">Trạng thái:</label>
                <select id="filter-job-status" class="form-control" style="width:auto;padding:0.4rem 0.8rem;font-size:0.88rem;" onchange="loadCompanyJobs(1)">
                    <option value="">Tất cả trạng thái</option>
                    <option value="published">🟢 Đang tuyển (Published)</option>
                    <option value="draft">📝 Bản nháp (Draft)</option>
                    <option value="pending_approval">⏳ Chờ duyệt (Pending Approval)</option>
                    <option value="hidden">Tạm ẩn (Hidden)</option>
                    <option value="closed">🔒 Đã đóng tuyển (Closed)</option>
                    <option value="expired">⌛ Đã hết hạn (Expired)</option>
                    <option value="rejected">❌ Bị từ chối (Rejected)</option>
                </select>
            </div>

            <div style="display:flex;align-items:center;gap:0.5rem;">
                <input type="text" id="filter-job-keyword" class="form-control" placeholder="Tìm theo tiêu đề..." style="padding:0.4rem 0.8rem;font-size:0.88rem;width:200px;" onkeydown="if(event.key==='Enter') loadCompanyJobs(1)">
                <button onclick="loadCompanyJobs(1)" class="btn btn-outline btn-sm">Tìm</button>
            </div>
        </div>

        <div style="display:flex;align-items:center;gap:1rem;">
            <span id="job-total-text" style="font-size:0.88rem;color:var(--text-muted);font-weight:600;">
                Đang tải...
            </span>
            <a href="/company/jobs/create" class="btn btn-primary btn-sm">
                Tạo Tin Tuyển Dụng
            </a>
        </div>
    </div>

    <!-- Loading Skeleton -->
    <div id="company-jobs-loading" style="display:block;">
        <div class="job-card skeleton" style="height:130px;margin-bottom:1rem;"></div>
        <div class="job-card skeleton" style="height:130px;margin-bottom:1rem;"></div>
        <div class="job-card skeleton" style="height:130px;"></div>
    </div>

    <!-- Error State -->
    <div id="company-jobs-error" class="state-error" style="display:none;">
        <div class="state-error-icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
        </div>
        <h3 class="state-error-title">Không Thể Tải Danh Sách Tin Tuyển Dụng</h3>
        <p id="company-jobs-err-msg" class="state-error-desc">Đã xảy ra lỗi khi lấy danh sách việc làm nội bộ.</p>
        <button onclick="loadCompanyJobs(1)" class="btn btn-primary btn-sm">Thử Lại</button>
    </div>

    <!-- Jobs Container -->
    <div id="company-jobs-container" style="display:flex;flex-direction:column;gap:1rem;"></div>

    <!-- Empty State -->
    <div id="company-jobs-empty" class="surface-card empty-state" style="display:none;">
        <div class="empty-state-icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
        </div>
        <h3 class="empty-state-title">Chưa Có Tin Tuyển Dụng Nào</h3>
        <p class="empty-state-text">
            Bạn chưa tạo tin tuyển dụng nào phù hợp với bộ lọc hiện tại. Hãy tạo tin việc làm part-time để tiếp cận các ứng viên sinh viên!
        </p>
        <div class="empty-state-action">
            <a href="/company/jobs/create" class="btn btn-primary">Đăng Tin Ngay</a>
        </div>
    </div>

    <!-- Pagination -->
    <div id="company-jobs-pagination" style="display:flex;justify-content:center;gap:0.5rem;margin-top:2rem;"></div>
</div>

<script>
let currentJobPage = 1;

function initCompanyJobsPage() {
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

    loadCompanyJobs(1);
}

if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initCompanyJobsPage);
} else {
    initCompanyJobsPage();
}

function renderJobStatusBadge(status) {
    if (typeof getJobStatusBadge === "function") {
        return getJobStatusBadge(status);
    }
    const map = {
        "published": { label: "Đang hiển thị", modifier: "status-badge--success" },
        "draft": { label: "Bản nháp", modifier: "status-badge--neutral" },
        "pending_approval": { label: "Chờ duyệt", modifier: "status-badge--warning" },
        "hidden": { label: "Tạm ẩn", modifier: "status-badge--neutral" },
        "closed": { label: "Đã đóng", modifier: "status-badge--neutral" },
        "expired": { label: "Hết hạn", modifier: "status-badge--neutral" },
        "rejected": { label: "Bị từ chối", modifier: "status-badge--danger" }
    };
    const s = map[status] || { label: status || "Không rõ", modifier: "status-badge--neutral" };
    return `<span class="status-badge ${s.modifier}"><span class="status-dot"></span>${escapeHtml(s.label)}</span>`;
}

async function loadCompanyJobs(page = 1) {
    currentJobPage = page;
    const loadingEl = document.getElementById("company-jobs-loading");
    const errorEl = document.getElementById("company-jobs-error");
    const container = document.getElementById("company-jobs-container");
    const emptyEl = document.getElementById("company-jobs-empty");
    const totalText = document.getElementById("job-total-text");
    const pagEl = document.getElementById("company-jobs-pagination");

    if (loadingEl) loadingEl.style.display = "block";
    if (errorEl) errorEl.style.display = "none";
    if (container) container.innerHTML = "";
    if (emptyEl) emptyEl.style.display = "none";
    if (pagEl) pagEl.innerHTML = "";

    try {
        const statusEl = document.getElementById("filter-job-status");
        const keywordEl = document.getElementById("filter-job-keyword");
        const status = statusEl ? statusEl.value : "";
        const keyword = keywordEl ? keywordEl.value.trim() : "";

        const params = new URLSearchParams({ page: currentJobPage, per_page: 10 });
        if (status) params.append("status", status);
        if (keyword) params.append("keyword", keyword);

        const res = await apiRequest(`/company/jobs?${params.toString()}`, { requireAuth: true });

        if (res && res.success && Array.isArray(res.data)) {
            const jobs = res.data;
            const meta = res.meta || {};
            const total = meta.total !== undefined ? meta.total : jobs.length;

            if (totalText) totalText.innerText = `Tìm thấy ${total} tin tuyển dụng`;

            if (jobs.length === 0) {
                if (emptyEl) emptyEl.style.display = "block";
                return;
            }

            container.innerHTML = jobs.map(job => {
                const isPublished = job.status === "published";
                const shiftLabel = typeof getShiftLabel === "function" ? getShiftLabel(job.shift_type) : (job.shift_type || "Linh hoạt");
                const currText = typeof formatCurrency === "function" ? `${formatCurrency(job.salary_min)} - ${formatCurrency(job.salary_max)}` : "Thoả thuận";
                const dateText = typeof formatDate === "function" ? formatDate(job.application_deadline) : (job.application_deadline || "Chưa đặt");
                return `
                    <div class="data-card" style="margin:0;display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:1.25rem;">
                        <div style="flex:1;min-width:280px;">
                            <div style="display:flex;align-items:center;gap:0.75rem;margin-bottom:0.5rem;flex-wrap:wrap;">
                                <h3 style="font-size:1.15rem;font-weight:700;margin:0;color:var(--dark);">
                                    <a href="/company/jobs/${encodeURIComponent(job.id)}/edit">
                                        ${escapeHtml(job.title)}
                                    </a>
                                </h3>
                                ${renderJobStatusBadge(job.status)}
                            </div>

                            <div style="color:var(--text-muted);font-size:0.88rem;margin-bottom:0.75rem;">
                                <i class="ri-map-pin-2-line"></i> ${escapeHtml(job.location_name || job.city || "Hà Nội")} &bull;
                                Ca: <strong>${shiftLabel}</strong> &bull;
                                Lương: <strong>${currText}</strong> &bull;
                                Hạn: ${dateText || "Chưa đặt"}
                            </div>

                            <div style="display:flex;gap:0.5rem;flex-wrap:wrap;align-items:center;">
                                <a href="/company/applications?job_id=${encodeURIComponent(job.id)}" class="badge badge-primary" style="text-decoration:none;font-size:0.8rem;padding:0.35rem 0.65rem;">
                                    Xem ứng viên nộp vào tin này &rarr;
                                </a>
                                ${isPublished ? `
                                    <a href="/viec-lam/${encodeURIComponent(job.id)}" target="_blank" class="badge" style="background:#f1f5f9;color:var(--text);text-decoration:none;font-size:0.8rem;padding:0.35rem 0.65rem;">
                                        Xem trang public
                                    </a>
                                ` : ''}
                            </div>
                        </div>

                        <!-- Action buttons -->
                        <div class="row-actions">
                            <a href="/company/jobs/${encodeURIComponent(job.id)}/edit" class="btn btn-outline btn-sm row-action-btn">
                                Sửa tin
                            </a>
                            ${isPublished ? `
                                <button onclick="handleCloseJob('${escapeHtml(job.id)}')" class="btn btn-sm row-action-btn" style="background:#fff;border:1px solid var(--border);color:var(--warning-text);" title="Đóng tin tuyển dụng này" aria-label="Đóng tin tuyển dụng này">
                                    Đóng tuyển
                                </button>
                            ` : ''}
                            <button onclick="handleDeleteJob('${escapeHtml(job.id)}')" class="btn btn-sm row-action-btn" style="background:#fff;border:1px solid var(--border);color:var(--danger);" title="Xóa tin tuyển dụng này" aria-label="Xóa tin tuyển dụng này">
                                Xóa
                            </button>
                        </div>
                    </div>
                `;
            }).join("");

            // Render Pagination
            const totalPages = meta.total_pages || 1;
            if (totalPages > 1 && pagEl) {
                let pagHtml = "";
                for (let i = 1; i <= totalPages; i++) {
                    pagHtml += `<button onclick="loadCompanyJobs(${i})" class="btn btn-sm ${i === currentJobPage ? 'btn-primary' : 'btn-outline'}" style="min-width:36px;">${i}</button>`;
                }
                pagEl.innerHTML = pagHtml;
            }
        } else {
            if (errorEl) errorEl.style.display = "block";
            const msgEl = document.getElementById("company-jobs-err-msg");
            if (msgEl) msgEl.innerText = (res && res.message) ? res.message : "Đã có lỗi xảy ra.";
        }
    } catch (err) {
        console.error("Error loading company jobs:", err);
        if (errorEl) errorEl.style.display = "block";
        const msgEl = document.getElementById("company-jobs-err-msg");
        if (msgEl) msgEl.innerText = "Lỗi kết nối máy chủ hoặc tải dữ liệu.";
    } finally {
        if (loadingEl) loadingEl.style.display = "none";
    }
}

async function handleCloseJob(id) {
    if (!confirm("Bạn có chắc chắn muốn đóng tin tuyển dụng này?\nTin đóng sẽ không còn hiển thị công khai cho sinh viên tìm kiếm.")) {
        return;
    }

    const res = await apiRequest(`/jobs/${encodeURIComponent(id)}/close`, {
        method: "POST",
        requireAuth: true
    });

    if (res && res.success) {
        showToast("Đã đóng tin tuyển dụng.", "info");
        loadCompanyJobs(currentJobPage);
    } else {
        showToast((res && res.message) ? res.message : "Thao tác thất bại.", "error");
    }
}

async function handleDeleteJob(id) {
    if (!confirm("CẢNH BÁO: Bạn có chắc chắn muốn xóa vĩnh viễn tin tuyển dụng này?\nHành động này không thể hoàn tác.")) {
        return;
    }

    const res = await apiRequest(`/jobs/${encodeURIComponent(id)}`, {
        method: "DELETE",
        requireAuth: true
    });

    if (res && res.success) {
        showToast("Đã xóa tin tuyển dụng thành công.", "success");
        loadCompanyJobs(currentJobPage);
    } else {
        showToast((res && res.message) ? res.message : "Xóa tin thất bại.", "error");
    }
}
</script>
