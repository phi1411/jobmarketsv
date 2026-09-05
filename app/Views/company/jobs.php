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
                    <option value="hidden">👁️ Tạm ẩn (Hidden)</option>
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
                ➕ Tạo Tin Tuyển Dụng
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
    <div id="company-jobs-error" style="display:none;background:#fff;border-radius:var(--radius);padding:3rem 1.5rem;text-align:center;border:1px solid var(--danger);margin-bottom:2rem;">
        <div style="font-size:3rem;margin-bottom:1rem;">⚠️</div>
        <h3 style="font-weight:700;color:var(--dark);margin-bottom:0.5rem;">Không Thể Tải Danh Sách Tin Tuyển Dụng</h3>
        <p id="company-jobs-err-msg" style="color:var(--text-muted);margin-bottom:1.5rem;">Đã xảy ra lỗi khi lấy danh sách việc làm nội bộ.</p>
        <button onclick="loadCompanyJobs(1)" class="btn btn-primary btn-sm">Thử Lại</button>
    </div>

    <!-- Jobs Container -->
    <div id="company-jobs-container" style="display:flex;flex-direction:column;gap:1rem;"></div>

    <!-- Empty State -->
    <div id="company-jobs-empty" style="display:none;background:#fff;border-radius:var(--radius);border:1px solid var(--border);padding:3.5rem 1.5rem;text-align:center;">
        <div style="font-size:3rem;margin-bottom:1rem;">📋</div>
        <h3 style="font-size:1.25rem;font-weight:700;color:var(--dark);margin-bottom:0.5rem;">Chưa Có Tin Tuyển Dụng Nào</h3>
        <p style="color:var(--text-muted);max-width:450px;margin:0 auto 1.5rem;">
            Bạn chưa tạo tin tuyển dụng nào phù hợp với bộ lọc hiện tại. Hãy tạo tin việc làm part-time để tiếp cận các ứng viên sinh viên!
        </p>
        <a href="/company/jobs/create" class="btn btn-primary">➕ Đăng Tin Ngay</a>
    </div>

    <!-- Pagination -->
    <div id="company-jobs-pagination" style="display:flex;justify-content:center;gap:0.5rem;margin-top:2rem;"></div>
</div>

<script>
let currentJobPage = 1;

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

    loadCompanyJobs(1);
});

function getJobStatusBadge(status) {
    const map = {
        "published":        { label: "🟢 Đang tuyển", bg: "#dcfce7", color: "#166534" },
        "draft":            { label: "📝 Bản nháp", bg: "#f1f5f9", color: "#475569" },
        "pending_approval": { label: "⏳ Chờ duyệt", bg: "#fef3c7", color: "#92400e" },
        "hidden":           { label: "👁️ Tạm ẩn", bg: "#e2e8f0", color: "#334155" },
        "closed":           { label: "🔒 Đã đóng", bg: "#fee2e2", color: "#991b1b" },
        "expired":          { label: "⌛ Hết hạn", bg: "#fef2f2", color: "#b91c1c" },
        "rejected":         { label: "❌ Bị từ chối", bg: "#fee2e2", color: "#7f1d1d" }
    };
    const s = map[status] || { label: status, bg: "#f1f5f9", color: "#475569" };
    return `<span class="badge" style="background:${s.bg};color:${s.color};font-size:0.8rem;padding:0.25rem 0.65rem;border-radius:20px;font-weight:700;">${escapeHtml(s.label)}</span>`;
}

async function loadCompanyJobs(page = 1) {
    currentJobPage = page;
    const loadingEl = document.getElementById("company-jobs-loading");
    const errorEl = document.getElementById("company-jobs-error");
    const container = document.getElementById("company-jobs-container");
    const emptyEl = document.getElementById("company-jobs-empty");
    const totalText = document.getElementById("job-total-text");
    const pagEl = document.getElementById("company-jobs-pagination");

    loadingEl.style.display = "block";
    errorEl.style.display = "none";
    container.innerHTML = "";
    emptyEl.style.display = "none";
    pagEl.innerHTML = "";

    const status = document.getElementById("filter-job-status").value;
    const keyword = document.getElementById("filter-job-keyword").value.trim();

    const params = new URLSearchParams({ page: currentJobPage, per_page: 10 });
    if (status) params.append("status", status);
    if (keyword) params.append("keyword", keyword);

    const res = await apiRequest(`/company/jobs?${params.toString()}`, { requireAuth: true });
    loadingEl.style.display = "none";

    if (res && res.success && Array.isArray(res.data)) {
        const jobs = res.data;
        const meta = res.meta || {};
        const total = meta.total !== undefined ? meta.total : jobs.length;

        totalText.innerText = `Tìm thấy ${total} tin tuyển dụng`;

        if (jobs.length === 0) {
            emptyEl.style.display = "block";
            return;
        }

        container.innerHTML = jobs.map(job => {
            const isPublished = job.status === "published";
            return `
                <div class="job-card" style="padding:1.5rem;margin:0;display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:1.25rem;">
                    <div style="flex:1;min-width:280px;">
                        <div style="display:flex;align-items:center;gap:0.75rem;margin-bottom:0.5rem;flex-wrap:wrap;">
                            <h3 style="font-size:1.15rem;font-weight:700;margin:0;color:var(--dark);">
                                <a href="/company/jobs/${encodeURIComponent(job.id)}/edit">
                                    ${escapeHtml(job.title)}
                                </a>
                            </h3>
                            ${getJobStatusBadge(job.status)}
                        </div>

                        <div style="color:var(--text-muted);font-size:0.88rem;margin-bottom:0.75rem;">
                            📍 ${escapeHtml(job.location_name || job.city || "Hà Nội")} &bull; 
                            ⏰ Ca: <strong>${getShiftLabel(job.shift_type)}</strong> &bull; 
                            💰 Lương: <strong>${formatCurrency(job.salary_min)} - ${formatCurrency(job.salary_max)}</strong> &bull; 
                            📅 Hạn: ${formatDate(job.application_deadline) || "Chưa đặt"}
                        </div>

                        <div style="display:flex;gap:0.5rem;flex-wrap:wrap;align-items:center;">
                            <a href="/company/applications?job_id=${encodeURIComponent(job.id)}" class="badge badge-primary" style="text-decoration:none;font-size:0.8rem;padding:0.25rem 0.6rem;">
                                👥 Xem ứng viên nộp vào tin này &rarr;
                            </a>
                            ${isPublished ? `
                                <a href="/viec-lam/${encodeURIComponent(job.id)}" target="_blank" class="badge" style="background:#f1f5f9;color:var(--text);text-decoration:none;font-size:0.8rem;padding:0.25rem 0.6rem;">
                                    🔗 Xem trang public
                                </a>
                            ` : ''}
                        </div>
                    </div>

                    <!-- Action buttons -->
                    <div style="display:flex;gap:0.5rem;align-items:center;flex-wrap:wrap;">
                        <a href="/company/jobs/${encodeURIComponent(job.id)}/edit" class="btn btn-outline btn-sm" style="font-size:0.82rem;">
                            ✏️ Sửa tin
                        </a>
                        ${isPublished ? `
                            <button onclick="handleCloseJob('${escapeHtml(job.id)}')" class="btn btn-sm" style="background:#fff;border:1px solid #cbd5e1;color:#b45309;font-size:0.82rem;" title="Đóng tin tuyển dụng này">
                                🔒 Đóng tuyển
                            </button>
                        ` : ''}
                        <button onclick="handleDeleteJob('${escapeHtml(job.id)}')" class="btn btn-sm" style="background:#fff;border:1px solid var(--danger);color:var(--danger);font-size:0.82rem;" title="Xóa tin tuyển dụng này">
                            🗑️ Xóa
                        </button>
                    </div>
                </div>
            `;
        }).join("");

        // Render Pagination
        const totalPages = meta.total_pages || 1;
        if (totalPages > 1) {
            let pagHtml = "";
            for (let i = 1; i <= totalPages; i++) {
                pagHtml += `<button onclick="loadCompanyJobs(${i})" class="btn btn-sm ${i === currentJobPage ? 'btn-primary' : 'btn-outline'}" style="min-width:36px;">${i}</button>`;
            }
            pagEl.innerHTML = pagHtml;
        }

    } else {
        errorEl.style.display = "block";
        document.getElementById("company-jobs-err-msg").innerText = (res && res.message) ? res.message : "Đã có lỗi xảy ra.";
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
