<?php include __DIR__ . "/nav.php"; ?>

<div class="container" style="margin-bottom:3rem;">
    <!-- Filter Bar -->
    <div style="background:#fff;border-radius:var(--radius);border:1px solid var(--border);padding:1.25rem 1.5rem;margin-bottom:1.5rem;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;">
        <div style="display:flex;align-items:center;gap:1rem;flex-wrap:wrap;">
            <div style="display:flex;align-items:center;gap:0.5rem;">
                <label for="filter-app-job" style="font-size:0.88rem;font-weight:600;color:var(--dark);">Tin tuyển dụng:</label>
                <select id="filter-app-job" class="form-control" style="width:auto;padding:0.4rem 0.8rem;font-size:0.88rem;max-width:250px;" onchange="loadCompanyApplications(1)">
                    <option value="">Tất cả tin việc làm</option>
                </select>
            </div>

            <div style="display:flex;align-items:center;gap:0.5rem;">
                <label for="filter-app-status" style="font-size:0.88rem;font-weight:600;color:var(--dark);">Trạng thái:</label>
                <select id="filter-app-status" class="form-control" style="width:auto;padding:0.4rem 0.8rem;font-size:0.88rem;" onchange="loadCompanyApplications(1)">
                    <option value="">Tất cả trạng thái</option>
                    <option value="pending">⏳ Chờ duyệt (Pending)</option>
                    <option value="viewed">👀 Đã xem (Viewed)</option>
                    <option value="shortlisted">🌟 Phù hợp / Mời phỏng vấn</option>
                    <option value="accepted">🎉 Trúng tuyển (Accepted)</option>
                    <option value="rejected">❌ Từ chối (Rejected)</option>
                    <option value="withdrawn">↩️ Đã rút (Withdrawn)</option>
                </select>
            </div>
        </div>

        <div id="apps-total-text" style="font-size:0.88rem;color:var(--text-muted);font-weight:600;">
            Đang tải dữ liệu...
        </div>
    </div>

    <!-- Loading Skeleton -->
    <div id="comp-apps-loading" style="display:block;">
        <div class="job-card skeleton" style="height:150px;margin-bottom:1rem;"></div>
        <div class="job-card skeleton" style="height:150px;margin-bottom:1rem;"></div>
        <div class="job-card skeleton" style="height:150px;"></div>
    </div>

    <!-- Error State -->
    <div id="comp-apps-error" class="state-error" style="display:none;">
        <div class="state-error-icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
        </div>
        <h3 class="state-error-title">Không Thể Tải Hồ Sơ Ứng Tuyển</h3>
        <p id="comp-apps-err-msg" class="state-error-desc">Đã có lỗi xảy ra khi truy vấn dữ liệu hồ sơ.</p>
        <button onclick="loadCompanyApplications(1)" class="btn btn-primary btn-sm">Thử Lại</button>
    </div>

    <!-- Applications Container -->
    <div id="comp-apps-container" style="display:flex;flex-direction:column;gap:1.25rem;"></div>

    <!-- Empty State -->
    <div id="comp-apps-empty" class="surface-card empty-state" style="display:none;">
        <div class="empty-state-icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
        </div>
        <h3 class="empty-state-title">Chưa Có Hồ Sơ Ứng Tuyển Nào</h3>
        <p class="empty-state-text">
            Chưa có sinh viên nào nộp đơn vào các tin việc làm phù hợp với bộ lọc này.
        </p>
        <div class="empty-state-action">
            <a href="/company/jobs" class="btn btn-primary btn-sm">Quản Lý Tin Tuyển Dụng</a>
        </div>
    </div>

    <!-- Pagination -->
    <div id="comp-apps-pagination" style="display:flex;justify-content:center;gap:0.5rem;margin-top:2rem;"></div>
</div>

<!-- Modal: Update Application Status & Employer Note -->
<div id="status-modal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(15,23,42,0.6);z-index:9999;align-items:center;justify-content:center;padding:1rem;">
    <div style="background:#fff;border-radius:var(--radius);max-width:540px;width:100%;padding:2rem;box-shadow:var(--shadow);">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.25rem;border-bottom:1px solid var(--border);padding-bottom:0.75rem;">
            <h3 style="font-size:1.15rem;font-weight:700;color:var(--dark);margin:0;">
                Cập Nhật Trạng Thái & Ghi Chú
            </h3>
            <button type="button" onclick="closeStatusModal()" class="modal-close-btn" aria-label="Đóng hộp thoại">&times;</button>
        </div>

        <div id="modal-app-info" style="font-size:0.88rem;color:var(--text);margin-bottom:1.25rem;background:#f8fafc;padding:0.75rem 1rem;border-radius:var(--radius);line-height:1.5;"></div>

        <form onsubmit="handleSaveApplicationStatus(event)">
            <input type="hidden" id="modal-app-id">

            <div class="form-group" style="margin-bottom:1.25rem;">
                <label class="form-label" for="modal-status-select">Trạng thái xét duyệt <span style="color:var(--danger)">*</span></label>
                <select id="modal-status-select" class="form-control" required style="font-weight:600;">
                    <option value="viewed">Đã xem hồ sơ (Viewed)</option>
                    <option value="shortlisted">Phù hợp / Mời phỏng vấn (Shortlisted)</option>
                    <option value="accepted">Nhận việc / Trúng tuyển (Accepted)</option>
                    <option value="rejected">Từ chối ứng viên (Rejected)</option>
                </select>
            </div>

            <div class="form-group" style="margin-bottom:1.5rem;">
                <label class="form-label" for="modal-employer-note">
                    Ghi chú nội bộ (Employer Note)
                </label>
                <textarea id="modal-employer-note" rows="4" class="form-control" placeholder="Ghi chú đánh giá ứng viên, lịch hẹn phỏng vấn, lý do loại... (Chỉ nhà tuyển dụng nhìn thấy, tuyệt đối không lộ cho sinh viên)"></textarea>
                <small class="form-help">Bảo mật: Ghi chú này chỉ lưu hành nội bộ trong doanh nghiệp của bạn.</small>
            </div>

            <div style="display:flex;justify-content:flex-end;gap:0.75rem;padding-top:1rem;border-top:1px solid var(--border);">
                <button type="button" onclick="closeStatusModal()" class="btn btn-outline btn-sm">Hủy</button>
                <button type="submit" id="btn-save-app-status" class="btn btn-primary btn-sm">
                    Cập Nhật Trạng Thái
                </button>
            </div>
        </form>
    </div>
</div>

<script>
let currentAppPage = 1;
let currentAppsData = [];

async function initCompanyApplicationsPage() {
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

    // Check query param for pre-selected job_id
    const urlParams = new URLSearchParams(window.location.search);
    const preJobId = urlParams.get("job_id");

    await loadCompanyJobsFilter(preJobId);
    loadCompanyApplications(1);
}

if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initCompanyApplicationsPage);
} else {
    initCompanyApplicationsPage();
}

async function loadCompanyJobsFilter(selectedJobId) {
    const res = await apiRequest("/company/jobs?per_page=100", { requireAuth: true });
    if (res && res.success && Array.isArray(res.data)) {
        const sel = document.getElementById("filter-app-job");
        res.data.forEach(job => {
            const opt = document.createElement("option");
            opt.value = job.id;
            opt.textContent = `${job.title} (${job.status})`;
            if (selectedJobId && job.id === selectedJobId) {
                opt.selected = true;
            }
            sel.appendChild(opt);
        });
    }
}

function renderAppStatusBadge(status) {
    if (typeof getAppStatusBadge === "function") {
        return getAppStatusBadge(status);
    }
    const map = {
        "pending":     { label: "Chờ xem xét", modifier: "status-badge--warning" },
        "viewed":      { label: "Đã xem", modifier: "status-badge--info" },
        "shortlisted": { label: "Phù hợp", modifier: "status-badge--info" },
        "accepted":    { label: "Trúng tuyển", modifier: "status-badge--success" },
        "rejected":    { label: "Chưa phù hợp", modifier: "status-badge--danger" },
        "withdrawn":   { label: "Đã rút đơn", modifier: "status-badge--neutral" }
    };
    const s = map[status] || { label: status || "Không rõ", modifier: "status-badge--neutral" };
    return `<span class="status-badge ${s.modifier}"><span class="status-dot"></span>${escapeHtml(s.label)}</span>`;
}

async function loadCompanyApplications(page = 1) {
    currentAppPage = page;
    const loadingEl = document.getElementById("comp-apps-loading");
    const errorEl = document.getElementById("comp-apps-error");
    const container = document.getElementById("comp-apps-container");
    const emptyEl = document.getElementById("comp-apps-empty");
    const totalText = document.getElementById("apps-total-text");
    const pagEl = document.getElementById("comp-apps-pagination");

    loadingEl.style.display = "block";
    errorEl.style.display = "none";
    container.innerHTML = "";
    emptyEl.style.display = "none";
    pagEl.innerHTML = "";

    try {
        const jobId = document.getElementById("filter-app-job").value;
        const status = document.getElementById("filter-app-status").value;

        const params = new URLSearchParams({ page: currentAppPage, per_page: 10 });
        if (jobId) params.append("job_id", jobId);
        if (status) params.append("status", status);

        const res = await apiRequest(`/company/applications?${params.toString()}`, { requireAuth: true });

        if (res && res.success && Array.isArray(res.data)) {
            currentAppsData = res.data;
            const meta = res.meta || {};
            const total = meta.total !== undefined ? meta.total : currentAppsData.length;

            totalText.innerText = `Tìm thấy ${total} hồ sơ ứng tuyển`;

            if (currentAppsData.length === 0) {
                emptyEl.style.display = "block";
                return;
            }

            try {
                container.innerHTML = currentAppsData.map(app => {
                    const shiftLabel = typeof getShiftLabel === "function" ? getShiftLabel(app.preferred_shift) : (app.preferred_shift || "Linh hoạt");
                    const dateText = typeof formatDate === "function" ? formatDate(app.applied_at) : (app.applied_at || "");
                    return `
                    <div class="data-card" style="margin:0;border-left:4px solid var(--primary);">
                        <!-- Card Header -->
                        <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:1rem;margin-bottom:0.75rem;">
                            <div>
                                <div style="display:flex;align-items:center;gap:0.75rem;flex-wrap:wrap;margin-bottom:0.25rem;">
                                    <h3 style="font-size:1.15rem;font-weight:800;color:var(--dark);margin:0;">
                                        ${escapeHtml(app.student_name || "Ứng viên")}
                                    </h3>
                                    ${renderAppStatusBadge(app.status)}
                                </div>
                                <div style="font-size:0.88rem;color:var(--text-muted);">
                                    Ứng tuyển vào: <a href="/viec-lam/${encodeURIComponent(app.job_id)}" target="_blank" style="color:var(--primary);font-weight:600;">${escapeHtml(app.job_title || "Vị trí việc làm")}</a> &bull;
                                    Ngày nộp: ${dateText}
                                </div>
                            </div>

                            <div class="row-actions">
                                ${app.has_cv_snapshot ? `
                                    <button onclick="viewApplicationCv('${escapeHtml(app.id)}')" class="btn btn-outline btn-sm row-action-btn" style="display:inline-flex;align-items:center;gap:0.35rem;">
                                        📄 Xem CV
                                    </button>
                                ` : ''}
                                <button onclick="openStatusModal('${escapeHtml(app.id)}')" class="btn btn-primary btn-sm row-action-btn">
                                    Cập Nhật Trạng Thái
                                </button>
                            </div>
                        </div>

                        <!-- Applicant Academic & Contact Info -->
                        <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(220px, 1fr));gap:0.75rem;padding:0.85rem;background:#f8fafc;border-radius:var(--radius);font-size:0.85rem;margin-bottom:0.85rem;">
                            <div style="min-width:0;overflow-wrap:anywhere;"><strong>Trường:</strong> ${escapeHtml(app.student_university || "Chưa cập nhật")}</div>
                            <div style="min-width:0;overflow-wrap:anywhere;"><strong>Ngành:</strong> ${escapeHtml(app.student_major || "Chưa cập nhật")}</div>
                            <div style="min-width:0;overflow-wrap:anywhere;"><strong>SĐT:</strong> ${escapeHtml(app.student_phone || "Chưa cập nhật")}</div>
                            <div style="min-width:0;word-break:break-all;overflow-wrap:anywhere;"><strong>Email:</strong> ${escapeHtml(app.student_email || "Chưa cập nhật")}</div>
                            <div style="min-width:0;overflow-wrap:anywhere;"><strong>Ca mong muốn:</strong> <strong>${shiftLabel}</strong></div>
                            <div style="min-width:0;overflow-wrap:anywhere;"><strong>CV:</strong> ${app.has_cv_snapshot ? `<button onclick="viewApplicationCv('${escapeHtml(app.id)}')" style="background:none;border:none;padding:0;color:var(--primary);text-decoration:underline;cursor:pointer;font-size:inherit;">📄 Xem CV (${escapeHtml(app.cv_file_name || 'PDF')})</button>` : (app.cv_url_snapshot ? `<a href="${escapeHtml(app.cv_url_snapshot)}" target="_blank" style="color:var(--primary);text-decoration:underline;">Xem liên kết CV</a>` : '<span style="color:var(--text-muted)">Không có</span>')}</div>
                        </div>

                        <!-- Cover Letter -->
                        ${app.cover_letter ? `
                            <div style="font-size:0.85rem;color:var(--text);margin-bottom:0.75rem;padding:0.6rem 0.85rem;background:#fff;border:1px dashed var(--border);border-radius:var(--radius);line-height:1.5;">
                                <strong>Lời nhắn / Thư giới thiệu:</strong><br>
                                <em>"${escapeHtml(app.cover_letter)}"</em>
                            </div>
                        ` : ''}

                        <!-- Employer Internal Note -->
                        ${app.employer_note ? `
                            <div style="font-size:0.82rem;color:#0f172a;background:#eff6ff;border-left:3px solid var(--primary);padding:0.5rem 0.75rem;border-radius:0 var(--radius) var(--radius) 0;">
                                <strong>Ghi chú nội bộ:</strong> ${escapeHtml(app.employer_note)}
                            </div>
                        ` : ''}
                    </div>
                    `;
                }).join("");
            } catch (renderErr) {
                console.error("Error rendering company applications:", renderErr);
                container.innerHTML = `<div class="state-error" style="display:block;"><p class="state-error-desc">Lỗi hiển thị danh sách hồ sơ ứng tuyển.</p></div>`;
            }

            // Render Pagination
            const totalPages = meta.total_pages || 1;
            if (totalPages > 1) {
                let pagHtml = "";
                for (let i = 1; i <= totalPages; i++) {
                    pagHtml += `<button onclick="loadCompanyApplications(${i})" class="btn btn-sm ${i === currentAppPage ? 'btn-primary' : 'btn-outline'}" style="min-width:36px;">${i}</button>`;
                }
                pagEl.innerHTML = pagHtml;
            }

        } else {
            errorEl.style.display = "block";
            document.getElementById("comp-apps-err-msg").innerText = (res && res.message) ? res.message : "Đã có lỗi xảy ra.";
        }
    } catch (err) {
        console.error("Error loading company applications:", err);
        errorEl.style.display = "block";
        document.getElementById("comp-apps-err-msg").innerText = "Lỗi kết nối máy chủ hoặc tải dữ liệu.";
    } finally {
        if (loadingEl) loadingEl.style.display = "none";
    }
}

function openStatusModal(appId) {
    const app = currentAppsData.find(a => a.id === appId);
    if (!app) return;

    document.getElementById("modal-app-id").value = app.id;
    document.getElementById("modal-app-info").innerHTML = `
        <strong>Ứng viên:</strong> ${escapeHtml(app.student_name)}<br>
        <strong>Vị trí:</strong> ${escapeHtml(app.job_title)}<br>
        <strong>Trạng thái hiện tại:</strong> ${escapeHtml(app.status)}
    `;

    // Set dropdown to current status (or default to viewed if pending)
    const sel = document.getElementById("modal-status-select");
    sel.value = ["viewed", "shortlisted", "accepted", "rejected"].includes(app.status) ? app.status : "viewed";

    document.getElementById("modal-employer-note").value = app.employer_note || "";
    document.getElementById("status-modal").style.display = "flex";
}

function closeStatusModal() {
    document.getElementById("status-modal").style.display = "none";
}

async function handleSaveApplicationStatus(e) {
    e.preventDefault();
    const btn = document.getElementById("btn-save-app-status");
    const appId = document.getElementById("modal-app-id").value;
    const newStatus = document.getElementById("modal-status-select").value;
    const employerNote = document.getElementById("modal-employer-note").value.trim();

    btn.disabled = true;
    btn.innerText = "Đang lưu...";

    const res = await apiRequest(`/applications/${encodeURIComponent(appId)}/status`, {
        method: "PATCH",
        body: {
            status: newStatus,
            employer_note: employerNote || null
        },
        requireAuth: true
    });

    btn.disabled = false;
    btn.innerText = "Cập Nhật Trạng Thái";

    if (res && res.success) {
        closeStatusModal();
        showToast("Cập nhật trạng thái đơn ứng tuyển thành công!", "success");
        loadCompanyApplications(currentAppPage);
    } else {
        showToast((res && res.message) ? res.message : "Cập nhật thất bại.", "error");
    }
}
</script>
