<?php include __DIR__ . "/nav.php"; ?>

<div class="container" style="margin-bottom:3rem;">
    <!-- Filter Bar -->
    <div style="background:#fff;border-radius:var(--radius);border:1px solid var(--border);padding:1.25rem 1.5rem;margin-bottom:1.5rem;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;">
        <div style="display:flex;align-items:center;gap:1rem;flex-wrap:wrap;">
            <!-- Filter Moderation Status -->
            <div style="display:flex;align-items:center;gap:0.5rem;">
                <label for="filter-job-status" style="font-size:0.88rem;font-weight:600;color:var(--dark);">Trạng thái:</label>
                <select id="filter-job-status" class="form-control" style="width:auto;padding:0.4rem 0.8rem;font-size:0.88rem;" onchange="loadAdminJobs(1)">
                    <option value="">Tất cả trạng thái</option>
                    <option value="pending_approval">⏳ Chờ duyệt (Pending Approval)</option>
                    <option value="published">🟢 Đang tuyển (Published)</option>
                    <option value="hidden">👁️ Tạm ẩn (Hidden)</option>
                    <option value="rejected">🔴 Bị từ chối (Rejected)</option>
                    <option value="closed">🔒 Đã đóng (Closed)</option>
                    <option value="draft">📝 Bản nháp (Draft)</option>
                    <option value="expired">⌛ Đã hết hạn (Expired)</option>
                </select>
            </div>

            <!-- Search Keyword -->
            <div style="display:flex;align-items:center;gap:0.5rem;">
                <input type="text" id="filter-job-keyword" class="form-control" placeholder="Tiêu đề việc làm..." style="padding:0.4rem 0.8rem;font-size:0.88rem;width:220px;" onkeydown="if(event.key==='Enter') loadAdminJobs(1)">
                <button onclick="loadAdminJobs(1)" class="btn btn-outline btn-sm">Tìm</button>
            </div>
        </div>

        <div id="jobs-total-text" style="font-size:0.88rem;color:var(--text-muted);font-weight:600;">
            Đang tải dữ liệu...
        </div>
    </div>

    <!-- Loading Skeleton -->
    <div id="admin-jobs-loading" style="display:block;">
        <div class="job-card skeleton" style="height:140px;margin-bottom:1rem;"></div>
        <div class="job-card skeleton" style="height:140px;margin-bottom:1rem;"></div>
        <div class="job-card skeleton" style="height:140px;"></div>
    </div>

    <!-- Error State -->
    <div id="admin-jobs-error" style="display:none;background:#fff;border-radius:var(--radius);padding:3rem 1.5rem;text-align:center;border:1px solid var(--danger);margin-bottom:2rem;">
        <div style="font-size:3rem;margin-bottom:1rem;">⚠️</div>
        <h3 style="font-weight:700;color:var(--dark);margin-bottom:0.5rem;">Không Thể Tải Danh Sách Tin Tuyển Dụng</h3>
        <p id="admin-jobs-err-msg" style="color:var(--text-muted);margin-bottom:1.5rem;">Đã có lỗi xảy ra khi truy vấn dữ liệu việc làm.</p>
        <button onclick="loadAdminJobs(1)" class="btn btn-primary btn-sm">Thử Lại</button>
    </div>

    <!-- Jobs List Container -->
    <div id="admin-jobs-container" style="display:flex;flex-direction:column;gap:1.25rem;"></div>

    <!-- Empty State -->
    <div id="admin-jobs-empty" style="display:none;background:#fff;border-radius:var(--radius);border:1px solid var(--border);padding:3.5rem 1.5rem;text-align:center;">
        <div style="font-size:3rem;margin-bottom:1rem;">💼</div>
        <h3 style="font-size:1.25rem;font-weight:700;color:var(--dark);margin-bottom:0.5rem;">Không Tìm Thấy Tin Tuyển Dụng Nào</h3>
        <p style="color:var(--text-muted);max-width:450px;margin:0 auto 1.5rem;">
            Không có tin việc làm nào phù hợp với bộ lọc tìm kiếm hiện tại.
        </p>
    </div>

    <!-- Pagination -->
    <div id="admin-jobs-pagination" style="display:flex;justify-content:center;gap:0.5rem;margin-top:2rem;"></div>
</div>

<!-- Modal: Moderate Job -->
<div id="mod-job-modal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(15,23,42,0.6);z-index:9999;align-items:center;justify-content:center;padding:1rem;">
    <div style="background:#fff;border-radius:var(--radius);max-width:540px;width:100%;padding:2rem;box-shadow:var(--shadow);">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.25rem;border-bottom:1px solid var(--border);padding-bottom:0.75rem;">
            <h3 style="font-size:1.15rem;font-weight:700;color:var(--dark);margin:0;">
                💼 Kiểm Duyệt Tin Tuyển Dụng
            </h3>
            <button type="button" onclick="closeModJobModal()" style="background:none;border:none;font-size:1.5rem;cursor:pointer;color:var(--text-muted);">&times;</button>
        </div>

        <div id="modal-job-info" style="font-size:0.88rem;color:var(--text);margin-bottom:1.25rem;background:#f8fafc;padding:0.75rem 1rem;border-radius:var(--radius);line-height:1.5;"></div>

        <form onsubmit="handleSaveJobModeration(event)">
            <input type="hidden" id="modal-mod-job-id">

            <div class="form-group" style="margin-bottom:1.25rem;">
                <label class="form-label" for="modal-job-status-select">Hành động kiểm duyệt <span style="color:var(--danger)">*</span></label>
                <select id="modal-job-status-select" class="form-control" required style="font-weight:600;" onchange="toggleJobRejectionReason()">
                    <option value="published">🟢 Phê duyệt công khai (Published - Hiển thị trên sàn)</option>
                    <option value="hidden">👁️ Tạm ẩn tin (Hidden - Rút khỏi trang tìm kiếm)</option>
                    <option value="rejected">🔴 Từ chối tin (Rejected - Không đạt tiêu chuẩn)</option>
                    <option value="closed">🔒 Đóng tin tuyển dụng (Closed)</option>
                    <option value="draft">📝 Trả về bản nháp (Draft)</option>
                </select>
            </div>

            <div id="job-rejection-group" class="form-group" style="display:none;margin-bottom:1.5rem;">
                <label class="form-label" for="modal-job-rejection-reason">
                    Lý do từ chối tin <span style="color:var(--danger)">* (Bắt buộc)</span>
                </label>
                <textarea id="modal-job-rejection-reason" rows="3" class="form-control" placeholder="Nêu rõ lý do tin bị từ chối (Ví dụ: Mức lương không minh bạch, nội dung phản cảm, thông tin liên hệ không chính xác...) để công ty khắc phục"></textarea>
                <small style="color:#b91c1c;font-size:0.78rem;">Quy tắc: Bắt buộc nhập lý do khi từ chối tin tuyển dụng.</small>
            </div>

            <div style="display:flex;justify-content:flex-end;gap:0.75rem;">
                <button type="button" onclick="closeModJobModal()" class="btn btn-outline btn-sm">Hủy</button>
                <button type="submit" id="btn-save-job-mod" class="btn btn-primary btn-sm">
                    💾 Lưu Quyết Định
                </button>
            </div>
        </form>
    </div>
</div>

<script>
let currentJobPage = 1;
let currentJobsList = [];

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

    // Check query param for pre-selected status
    const urlParams = new URLSearchParams(window.location.search);
    const preStatus = urlParams.get("status");
    if (preStatus) {
        document.getElementById("filter-job-status").value = preStatus;
    }

    loadAdminJobs(1);
});

function getJobModBadge(status) {
    const map = {
        "published":        { label: "🟢 Đang tuyển", bg: "#dcfce7", color: "#166534" },
        "pending_approval": { label: "⏳ Chờ duyệt", bg: "#fef3c7", color: "#92400e" },
        "hidden":           { label: "👁️ Tạm ẩn", bg: "#e2e8f0", color: "#334155" },
        "rejected":         { label: "🔴 Bị từ chối", bg: "#fee2e2", color: "#991b1b" },
        "closed":           { label: "🔒 Đã đóng", bg: "#f1f5f9", color: "#475569" },
        "draft":            { label: "📝 Bản nháp", bg: "#f8fafc", color: "#64748b" },
        "expired":          { label: "⌛ Đã hết hạn", bg: "#fef2f2", color: "#b91c1c" }
    };
    const s = map[status] || { label: status, bg: "#f1f5f9", color: "#475569" };
    return `<span class="badge" style="background:${s.bg};color:${s.color};font-weight:700;font-size:0.8rem;padding:0.25rem 0.65rem;border-radius:20px;">${escapeHtml(s.label)}</span>`;
}

async function loadAdminJobs(page = 1) {
    currentJobPage = page;
    const loadingEl = document.getElementById("admin-jobs-loading");
    const errorEl = document.getElementById("admin-jobs-error");
    const container = document.getElementById("admin-jobs-container");
    const emptyEl = document.getElementById("admin-jobs-empty");
    const totalText = document.getElementById("jobs-total-text");
    const pagEl = document.getElementById("admin-jobs-pagination");

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

    const res = await apiRequest(`/admin/jobs?${params.toString()}`, { requireAuth: true });
    loadingEl.style.display = "none";

    if (res && res.success && Array.isArray(res.data)) {
        currentJobsList = res.data;
        const meta = res.meta || {};
        const total = meta.total !== undefined ? meta.total : currentJobsList.length;

        totalText.innerText = `Tìm thấy ${total} tin việc làm`;

        if (currentJobsList.length === 0) {
            emptyEl.style.display = "block";
            return;
        }

        container.innerHTML = currentJobsList.map(j => {
            const isPub = j.status === "published";
            return `
                <div class="job-card" style="padding:1.5rem;margin:0;border-left:4px solid ${isPub ? '#10b981' : (j.status === 'rejected' ? '#ef4444' : (j.status === 'hidden' ? '#64748b' : '#f59e0b'))};">
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:1rem;margin-bottom:0.75rem;">
                        <div>
                            <div style="display:flex;align-items:center;gap:0.75rem;flex-wrap:wrap;margin-bottom:0.25rem;">
                                <h3 style="font-size:1.15rem;font-weight:800;color:var(--dark);margin:0;">
                                    ${escapeHtml(j.title)}
                                </h3>
                                ${getJobModBadge(j.status)}
                            </div>
                            <div style="font-size:0.85rem;color:var(--text-muted);">
                                🏢 Doanh nghiệp: <strong>${escapeHtml(j.company_name || "Doanh nghiệp")}</strong> &bull; 
                                📍 Khu vực: <strong>${escapeHtml(j.location_name || j.city || "Hà Nội")}</strong> &bull; 
                                ⏰ Ca: <strong>${getShiftLabel(j.shift_type)}</strong> &bull; 
                                💰 Lương: <strong>${formatCurrency(j.salary_min)} - ${formatCurrency(j.salary_max)}</strong>
                            </div>
                        </div>

                        <div style="display:flex;gap:0.5rem;align-items:center;">
                            ${isPub ? `
                                <a href="/viec-lam/${encodeURIComponent(j.id)}" target="_blank" class="btn btn-outline btn-sm" style="font-size:0.8rem;">
                                    🔗 Xem Public
                                </a>
                            ` : ''}
                            <button onclick="openModJobModal('${escapeHtml(j.id)}')" class="btn btn-primary btn-sm" style="font-size:0.82rem;">
                                ⚙️ Kiểm Duyệt Tin
                            </button>
                        </div>
                    </div>

                    <div style="font-size:0.85rem;color:var(--text);margin-bottom:0.5rem;line-height:1.4;">
                        ${escapeHtml(j.description ? j.description.substring(0, 180) + '...' : '')}
                    </div>

                    ${j.status === 'rejected' && j.rejection_reason ? `
                        <div style="font-size:0.82rem;color:#991b1b;background:#fee2e2;border-radius:var(--radius);padding:0.5rem 0.75rem;margin-top:0.5rem;">
                            <strong>Lý do từ chối:</strong> ${escapeHtml(j.rejection_reason)}
                        </div>
                    ` : ''}
                </div>
            `;
        }).join("");

        // Render Pagination
        const totalPages = meta.total_pages || 1;
        if (totalPages > 1) {
            let pagHtml = "";
            for (let i = 1; i <= totalPages; i++) {
                pagHtml += `<button onclick="loadAdminJobs(${i})" class="btn btn-sm ${i === currentJobPage ? 'btn-primary' : 'btn-outline'}" style="min-width:36px;">${i}</button>`;
            }
            pagEl.innerHTML = pagHtml;
        }

    } else {
        errorEl.style.display = "block";
        document.getElementById("admin-jobs-err-msg").innerText = (res && res.message) ? res.message : "Đã có lỗi xảy ra.";
    }
}

function openModJobModal(jobId) {
    const job = currentJobsList.find(j => j.id === jobId);
    if (!job) return;

    document.getElementById("modal-mod-job-id").value = job.id;
    document.getElementById("modal-job-info").innerHTML = `
        <strong>Tiêu đề:</strong> ${escapeHtml(job.title)}<br>
        <strong>Công ty:</strong> ${escapeHtml(job.company_name || "N/A")} &bull; 
        <strong>Trạng thái hiện tại:</strong> ${escapeHtml(job.status)}
    `;

    document.getElementById("modal-job-status-select").value = job.status || "published";
    document.getElementById("modal-job-rejection-reason").value = job.rejection_reason || "";
    toggleJobRejectionReason();

    document.getElementById("mod-job-modal").style.display = "flex";
}

function closeModJobModal() {
    document.getElementById("mod-job-modal").style.display = "none";
}

function toggleJobRejectionReason() {
    const status = document.getElementById("modal-job-status-select").value;
    const reasonGroup = document.getElementById("job-rejection-group");
    if (status === "rejected") {
        reasonGroup.style.display = "block";
        document.getElementById("modal-job-rejection-reason").setAttribute("required", "required");
    } else {
        reasonGroup.style.display = "none";
        document.getElementById("modal-job-rejection-reason").removeAttribute("required");
    }
}

async function handleSaveJobModeration(e) {
    e.preventDefault();
    const btn = document.getElementById("btn-save-job-mod");
    const jobId = document.getElementById("modal-mod-job-id").value;
    const newStatus = document.getElementById("modal-job-status-select").value;
    const reason = document.getElementById("modal-job-rejection-reason").value.trim();

    if (newStatus === "rejected" && !reason) {
        showToast("Lý do từ chối là bắt buộc khi từ chối tin tuyển dụng.", "error");
        return;
    }

    btn.disabled = true;
    btn.innerText = "Đang lưu...";

    const res = await apiRequest(`/admin/jobs/${encodeURIComponent(jobId)}/moderation`, {
        method: "PATCH",
        body: {
            status: newStatus,
            rejection_reason: newStatus === "rejected" ? reason : null
        },
        requireAuth: true
    });

    btn.disabled = false;
    btn.innerText = "💾 Lưu Quyết Định";

    if (res && res.success) {
        closeModJobModal();
        showToast("Kiểm duyệt tin tuyển dụng thành công!", "success");
        loadAdminJobs(currentJobPage);
    } else {
        let msg = (res && res.message) ? res.message : "Cập nhật thất bại.";
        if (res && res.errors) {
            msg += " " + Object.values(res.errors).flat().map(escapeHtml).join(" ");
        }
        showToast(msg, "error");
    }
}
</script>
