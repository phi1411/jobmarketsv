<?php include __DIR__ . "/nav.php"; ?>

<style>
    .employer-filter-card { padding: 1.25rem 1.5rem; margin-bottom: 1.5rem; }
    .employer-filter-header { display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;flex-wrap:wrap;margin-bottom:1rem; }
    .employer-filter-header h2 { margin:0 0 .3rem;color:var(--dark);font-size:1.2rem; }
    .employer-filter-header p { margin:0;color:var(--text-muted);font-size:.82rem;line-height:1.5; }
    .employer-filter-grid { display:grid;grid-template-columns:repeat(4,minmax(180px,1fr));gap:.85rem;align-items:end; }
    .employer-filter-field label { display:block;margin-bottom:.35rem;font-size:.76rem;font-weight:700;color:var(--dark); }
    .employer-filter-field .form-control { width:100%;padding:.58rem .7rem;font-size:.82rem; }
    .skill-filter-wrap { position:relative; }
    .skill-filter-trigger { width:100%;justify-content:space-between;background:#fff;color:var(--dark);font-weight:500;border-color:var(--border); }
    .skill-filter-panel { position:absolute;z-index:30;top:calc(100% + 6px);left:0;width:min(330px,90vw);padding:.75rem;background:#fff;border:1px solid var(--border);border-radius:10px;box-shadow:var(--shadow); }
    .skill-filter-options { max-height:210px;overflow:auto;display:flex;flex-direction:column;gap:.25rem; }
    .skill-filter-option { display:flex;align-items:center;gap:.55rem;padding:.45rem .5rem;border-radius:7px;font-size:.8rem;color:var(--text);cursor:pointer; }
    .skill-filter-option:hover { background:#f8fafc; }
    .skill-filter-option input { width:16px;height:16px;accent-color:var(--primary); }
    .employer-filter-actions { display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap;margin-top:1rem;padding-top:1rem;border-top:1px solid var(--border); }
    .active-filter-summary { display:flex;flex-wrap:wrap;gap:.35rem;margin-top:.75rem; }
    .active-filter-chip { padding:.3rem .55rem;border-radius:999px;background:var(--primary-light);color:var(--primary-text);border:1px solid var(--primary-border);font-size:.7rem;font-weight:700; }
    .app-match-badge { display:inline-flex;align-items:center;gap:.35rem;padding:.32rem .58rem;border-radius:999px;font-size:.72rem;font-weight:800; }
    .app-match-badge.available { background:#dcfce7;color:#166534; }
    .app-match-badge.pending { background:#f1f5f9;color:#64748b; }
    .app-skill-list { display:flex;flex-wrap:wrap;gap:.35rem;margin-top:.2rem; }
    .app-skill-chip { padding:.23rem .48rem;border-radius:999px;background:#eef2ff;color:#4338ca;font-size:.69rem;font-weight:700; }
    @media (max-width:1050px) { .employer-filter-grid { grid-template-columns:repeat(2,minmax(180px,1fr)); } }
    @media (max-width:620px) { .employer-filter-grid { grid-template-columns:1fr; } .employer-filter-card { padding:1rem; } }
</style>

<div class="container" style="margin-bottom:3rem;">
    <section class="surface-card employer-filter-card" aria-labelledby="employer-filter-title">
        <div class="employer-filter-header">
            <div>
                <h2 id="employer-filter-title">Bộ Lọc Ứng Viên</h2>
                <p>Thu hẹp danh sách theo hồ sơ và mức phù hợp. Bộ lọc không tự động chấp nhận hoặc loại ứng viên.</p>
            </div>
            <div id="apps-total-text" style="font-size:.86rem;color:var(--text-muted);font-weight:700;">Đang tải dữ liệu...</div>
        </div>

        <div class="employer-filter-grid">
            <div class="employer-filter-field">
                <label for="filter-app-job">Tin tuyển dụng</label>
                <select id="filter-app-job" class="form-control">
                    <option value="">Tất cả tin việc làm</option>
                </select>
            </div>

            <div class="employer-filter-field">
                <label for="filter-app-status">Trạng thái</label>
                <select id="filter-app-status" class="form-control">
                    <option value="">Tất cả trạng thái</option>
                    <option value="pending">⏳ Chưa phản hồi</option>
                    <option value="interview">📅 Mời phỏng vấn</option>
                    <option value="accepted">🎉 Trúng tuyển</option>
                    <option value="rejected">❌ Từ chối</option>
                    <option value="withdrawn">↩️ Đã rút</option>
                </select>
            </div>

            <div class="employer-filter-field">
                <label for="filter-app-university">Trường</label>
                <input id="filter-app-university" class="form-control" maxlength="100" placeholder="VD: Đại học Thương Mại">
            </div>

            <div class="employer-filter-field">
                <label for="filter-app-major">Ngành học</label>
                <input id="filter-app-major" class="form-control" maxlength="100" placeholder="VD: Quản trị kinh doanh">
            </div>

            <div class="employer-filter-field">
                <label for="filter-app-shift">Ca mong muốn</label>
                <select id="filter-app-shift" class="form-control">
                    <option value="">Tất cả ca</option>
                    <option value="morning">Ca sáng</option>
                    <option value="afternoon">Ca chiều</option>
                    <option value="evening">Ca tối</option>
                    <option value="night">Ca đêm</option>
                    <option value="weekend">Cuối tuần</option>
                    <option value="flexible">Linh hoạt</option>
                </select>
            </div>

            <div class="employer-filter-field skill-filter-wrap">
                <label>Kỹ năng <span style="font-weight:500;color:var(--text-muted)">(chọn nhiều)</span></label>
                <button type="button" id="filter-skill-trigger" class="btn btn-outline skill-filter-trigger" aria-expanded="false" onclick="toggleSkillFilterPanel()">
                    <span id="filter-skill-label">Tất cả kỹ năng</span><span aria-hidden="true">⌄</span>
                </button>
                <div id="filter-skill-panel" class="skill-filter-panel" hidden>
                    <div id="filter-skill-options" class="skill-filter-options"><span style="font-size:.78rem;color:var(--text-muted)">Đang tải kỹ năng...</span></div>
                    <div style="display:flex;justify-content:flex-end;gap:.5rem;margin-top:.65rem;padding-top:.65rem;border-top:1px solid var(--border)">
                        <button type="button" class="btn btn-outline btn-sm" onclick="clearSkillFilters()">Bỏ chọn</button>
                        <button type="button" class="btn btn-primary btn-sm" onclick="closeSkillFilterPanel()">Xong</button>
                    </div>
                </div>
            </div>

            <div class="employer-filter-field">
                <label for="filter-app-match">Mức độ phù hợp</label>
                <select id="filter-app-match" class="form-control">
                    <option value="">Tất cả hồ sơ</option>
                    <option value="50">Từ 50%</option>
                    <option value="65">Từ 65%</option>
                    <option value="80">Từ 80%</option>
                    <option value="unscored">Chưa có điểm đánh giá</option>
                </select>
            </div>

            <div class="employer-filter-field">
                <label for="filter-app-sort">Sắp xếp</label>
                <select id="filter-app-sort" class="form-control">
                    <option value="newest">Nộp gần nhất</option>
                    <option value="oldest">Nộp lâu nhất</option>
                    <option value="match_desc">Phù hợp cao nhất</option>
                    <option value="match_asc">Phù hợp thấp nhất</option>
                </select>
            </div>
        </div>

        <div id="active-filter-summary" class="active-filter-summary" aria-live="polite"></div>
        <div class="employer-filter-actions">
            <span style="font-size:.72rem;color:var(--text-muted)">Chọn nhiều kỹ năng sẽ tìm ứng viên có đủ tất cả kỹ năng đã chọn.</span>
            <div style="display:flex;gap:.55rem">
                <button type="button" class="btn btn-outline btn-sm" onclick="resetCompanyApplicationFilters()">Đặt Lại</button>
                <button type="button" class="btn btn-primary btn-sm" onclick="applyCompanyApplicationFilters()">Áp Dụng Bộ Lọc</button>
            </div>
        </div>
    </section>

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

<!-- Modal: Send Application Decision -->
<div id="status-modal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(15,23,42,0.6);z-index:9999;align-items:center;justify-content:center;padding:1rem;">
    <div class="modal-card" style="background:var(--surface);border-radius:var(--radius);max-width:540px;width:100%;padding:2rem;box-shadow:var(--shadow);">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.25rem;border-bottom:1px solid var(--border);padding-bottom:0.75rem;">
            <h3 style="font-size:1.15rem;font-weight:700;color:var(--dark);margin:0;">
                Gửi Kết Quả Cho Sinh Viên
            </h3>
            <button type="button" onclick="closeStatusModal()" class="modal-close-btn" aria-label="Đóng hộp thoại">&times;</button>
        </div>

        <div id="modal-app-info" style="font-size:0.88rem;color:var(--text);margin-bottom:1.25rem;background:var(--bg);border:1px solid var(--border);padding:0.75rem 1rem;border-radius:var(--radius);line-height:1.5;"></div>

        <form onsubmit="handleSaveApplicationStatus(event)">
            <input type="hidden" id="modal-app-id">

            <div class="form-group" style="margin-bottom:1.25rem;">
                <label class="form-label" for="modal-status-select">Trạng thái xét duyệt <span style="color:var(--danger)">*</span></label>
                <select id="modal-status-select" class="form-control" required style="font-weight:600;" onchange="updateDecisionMessageHint()">
                    <option value="interview">Mời phỏng vấn (Interview)</option>
                    <option value="accepted">Nhận việc / Trúng tuyển (Accepted)</option>
                    <option value="rejected">Từ chối ứng viên (Rejected)</option>
                </select>
            </div>

            <div class="form-group" style="margin-bottom:1.5rem;">
                <label class="form-label" for="modal-employer-note">
                    Nội dung gửi sinh viên <span style="color:var(--danger)">*</span>
                </label>
                <textarea id="modal-employer-note" rows="5" maxlength="1000" required class="form-control" placeholder="Ví dụ: Mời bạn phỏng vấn lúc 09:00 ngày 15/09 tại... Vui lòng mang theo CV bản in."></textarea>
                <small class="form-help">Nội dung này sẽ hiện trong thông báo ứng dụng và được gửi tới email của sinh viên.</small>
            </div>

            <div style="display:flex;justify-content:flex-end;gap:0.75rem;padding-top:1rem;border-top:1px solid var(--border);">
                <button type="button" onclick="closeStatusModal()" class="btn btn-outline btn-sm">Hủy</button>
                <button type="submit" id="btn-save-app-status" class="btn btn-primary btn-sm">
                    Gửi Kết Quả & Thông Báo
                </button>
            </div>
        </form>
    </div>
</div>

<script>
let currentAppPage = 1;
let currentAppsData = [];
let companySkillCatalog = [];
let selectedCompanySkillIds = new Set();

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

    const urlParams = new URLSearchParams(window.location.search);
    const preJobId = urlParams.get("job_id");
    document.getElementById("filter-app-status").value = urlParams.get("status") || "";
    document.getElementById("filter-app-university").value = urlParams.get("university") || "";
    document.getElementById("filter-app-major").value = urlParams.get("major") || "";
    document.getElementById("filter-app-shift").value = urlParams.get("preferred_shift") || "";
    document.getElementById("filter-app-match").value = urlParams.get("match_filter") || "";
    document.getElementById("filter-app-sort").value = urlParams.get("sort_by") || "newest";

    await Promise.all([
        loadCompanyJobsFilter(preJobId),
        loadCompanySkillsFilter(urlParams.get("skill_ids") || "")
    ]);
    loadCompanyApplications(1);

    document.addEventListener("click", event => {
        const wrap = document.querySelector(".skill-filter-wrap");
        if (wrap && !wrap.contains(event.target)) closeSkillFilterPanel();
    });
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

async function loadCompanySkillsFilter(selectedIds) {
    selectedCompanySkillIds = new Set(String(selectedIds || "").split(",").map(value => value.trim()).filter(Boolean));
    const res = await apiRequest("/skills", { requireAuth: true });
    companySkillCatalog = res && res.success && Array.isArray(res.data) ? res.data : [];
    const options = document.getElementById("filter-skill-options");

    if (companySkillCatalog.length === 0) {
        options.innerHTML = `<span style="font-size:.78rem;color:var(--text-muted)">Chưa có danh mục kỹ năng.</span>`;
        updateSkillFilterLabel();
        return;
    }

    options.innerHTML = companySkillCatalog.map(skill => `
        <label class="skill-filter-option">
            <input type="checkbox" value="${escapeHtml(skill.id)}" ${selectedCompanySkillIds.has(String(skill.id)) ? "checked" : ""} onchange="handleCompanySkillSelection(this)">
            <span>${escapeHtml(skill.name)}</span>
        </label>
    `).join("");
    updateSkillFilterLabel();
}

function handleCompanySkillSelection(input) {
    if (input.checked) selectedCompanySkillIds.add(input.value);
    else selectedCompanySkillIds.delete(input.value);
    updateSkillFilterLabel();
}

function updateSkillFilterLabel() {
    const count = selectedCompanySkillIds.size;
    document.getElementById("filter-skill-label").textContent = count ? `Đã chọn ${count} kỹ năng` : "Tất cả kỹ năng";
}

function toggleSkillFilterPanel() {
    const panel = document.getElementById("filter-skill-panel");
    const trigger = document.getElementById("filter-skill-trigger");
    panel.hidden = !panel.hidden;
    trigger.setAttribute("aria-expanded", panel.hidden ? "false" : "true");
}

function closeSkillFilterPanel() {
    document.getElementById("filter-skill-panel").hidden = true;
    document.getElementById("filter-skill-trigger").setAttribute("aria-expanded", "false");
}

function clearSkillFilters() {
    selectedCompanySkillIds.clear();
    document.querySelectorAll("#filter-skill-options input[type=checkbox]").forEach(input => { input.checked = false; });
    updateSkillFilterLabel();
}

function applyCompanyApplicationFilters() {
    closeSkillFilterPanel();
    loadCompanyApplications(1);
}

function resetCompanyApplicationFilters() {
    ["filter-app-job", "filter-app-status", "filter-app-shift", "filter-app-match"].forEach(id => {
        document.getElementById(id).value = "";
    });
    document.getElementById("filter-app-university").value = "";
    document.getElementById("filter-app-major").value = "";
    document.getElementById("filter-app-sort").value = "newest";
    clearSkillFilters();
    closeSkillFilterPanel();
    loadCompanyApplications(1);
}

function renderAppStatusBadge(status) {
    if (typeof getAppStatusBadge === "function") {
        return getAppStatusBadge(status);
    }
    const map = {
        "pending":     { label: "Chờ xem xét", modifier: "status-badge--warning" },
        "viewed":      { label: "Đã xem", modifier: "status-badge--info" },
        "shortlisted": { label: "Phù hợp", modifier: "status-badge--info" },
        "interview":   { label: "Mời phỏng vấn", modifier: "status-badge--info" },
        "accepted":    { label: "Trúng tuyển", modifier: "status-badge--success" },
        "rejected":    { label: "Chưa phù hợp", modifier: "status-badge--danger" },
        "withdrawn":   { label: "Đã rút đơn", modifier: "status-badge--neutral" }
    };
    const s = map[status] || { label: status || "Không rõ", modifier: "status-badge--neutral" };
    return `<span class="status-badge ${s.modifier}"><span class="status-dot"></span>${escapeHtml(s.label)}</span>`;
}

function renderEmployerMatchBadge(analysis) {
    const data = analysis || {};
    if (data.status === "available" && data.score !== null && data.score !== undefined) {
        return `<span class="app-match-badge available" title="Độ phủ dữ liệu ${escapeHtml(String(data.coverage_percent || 0))}%">✦ Phù hợp ${escapeHtml(String(data.score))}%</span>`;
    }

    const labels = {
        not_consented: "Chưa có sự đồng ý đánh giá",
        not_evaluated: "Chưa đánh giá",
        processing: "Đang đánh giá",
        insufficient_data: "Chưa đủ dữ liệu",
        revoked: "Đã thu hồi đồng ý",
        unavailable: "Chưa có điểm"
    };
    return `<span class="app-match-badge pending">${escapeHtml(labels[data.status] || "Chưa đánh giá")}</span>`;
}

function renderActiveCompanyFilters(values) {
    const chips = [];
    const selectedJob = document.getElementById("filter-app-job").selectedOptions[0];
    const selectedStatus = document.getElementById("filter-app-status").selectedOptions[0];
    const selectedShift = document.getElementById("filter-app-shift").selectedOptions[0];
    const selectedMatch = document.getElementById("filter-app-match").selectedOptions[0];

    if (values.job_id) chips.push(`Tin: ${selectedJob ? selectedJob.textContent : values.job_id}`);
    if (values.status) chips.push(`Trạng thái: ${selectedStatus ? selectedStatus.textContent : values.status}`);
    if (values.university) chips.push(`Trường: ${values.university}`);
    if (values.major) chips.push(`Ngành: ${values.major}`);
    if (values.preferred_shift) chips.push(`Ca: ${selectedShift ? selectedShift.textContent : values.preferred_shift}`);
    if (selectedCompanySkillIds.size) {
        const names = companySkillCatalog.filter(skill => selectedCompanySkillIds.has(String(skill.id))).map(skill => skill.name);
        chips.push(`Kỹ năng: ${names.join(", ") || selectedCompanySkillIds.size + " đã chọn"}`);
    }
    if (values.match_filter) chips.push(`Điểm: ${selectedMatch ? selectedMatch.textContent : values.match_filter}`);

    document.getElementById("active-filter-summary").innerHTML = chips.map(text => `<span class="active-filter-chip">${escapeHtml(text)}</span>`).join("");
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
        const params = new URLSearchParams({ page: currentAppPage, per_page: 10 });
        const filterValues = {
            job_id: document.getElementById("filter-app-job").value,
            status: document.getElementById("filter-app-status").value,
            university: document.getElementById("filter-app-university").value.trim(),
            major: document.getElementById("filter-app-major").value.trim(),
            preferred_shift: document.getElementById("filter-app-shift").value,
            skill_ids: Array.from(selectedCompanySkillIds).join(","),
            match_filter: document.getElementById("filter-app-match").value,
            sort_by: document.getElementById("filter-app-sort").value
        };
        Object.entries(filterValues).forEach(([key, value]) => {
            if (value && !(key === "sort_by" && value === "newest")) params.append(key, value);
        });

        const visibleParams = new URLSearchParams(params);
        visibleParams.delete("page");
        visibleParams.delete("per_page");
        const newUrl = visibleParams.toString() ? `${window.location.pathname}?${visibleParams}` : window.location.pathname;
        window.history.replaceState({}, "", newUrl);
        renderActiveCompanyFilters(filterValues);

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
                    const skills = Array.isArray(app.student_skills) ? app.student_skills : [];
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
                                    ${renderEmployerMatchBadge(app.match_analysis)}
                                </div>
                                <div style="font-size:0.88rem;color:var(--text-muted);">
                                    Ứng tuyển vào: <a href="/viec-lam/${encodeURIComponent(app.job_id)}" target="_blank" style="color:var(--primary);font-weight:600;">${escapeHtml(app.job_title || "Vị trí việc làm")}</a> &bull;
                                    Ngày nộp: ${dateText}
                                </div>
                            </div>

                            <div class="row-actions">
                                ${app.has_cv_snapshot ? `
                                    <button onclick="viewApplicationCv('${escapeHtml(app.id)}', this)" class="btn btn-outline btn-sm row-action-btn" style="display:inline-flex;align-items:center;gap:0.35rem;">
                                        📄 Xem CV
                                    </button>
                                ` : ''}
                                <button onclick="openStatusModal('${escapeHtml(app.id)}')" class="btn btn-primary btn-sm row-action-btn">
                                    Gửi Kết Quả
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
                            <div style="min-width:0;overflow-wrap:anywhere;"><strong>CV:</strong> ${app.has_cv_snapshot ? `<button onclick="viewApplicationCv('${escapeHtml(app.id)}', this)" style="background:none;border:none;padding:0;color:var(--primary);text-decoration:underline;cursor:pointer;font-size:inherit;display:inline-flex;align-items:center;gap:0.25rem;">📄 Xem CV (${escapeHtml(app.cv_file_name || 'PDF')})</button>` : '<span style="color:var(--text-muted)">Không có CV lưu trữ</span>'}</div>
                            <div style="grid-column:1/-1;min-width:0;"><strong>Kỹ năng:</strong> ${skills.length ? `<span class="app-skill-list">${skills.map(skill => `<span class="app-skill-chip">${escapeHtml(skill)}</span>`).join("")}</span>` : '<span style="color:var(--text-muted)">Chưa cập nhật</span>'}</div>
                        </div>

                        <!-- Cover Letter -->
                        ${app.cover_letter ? `
                            <div style="font-size:0.85rem;color:var(--text);margin-bottom:0.75rem;padding:0.6rem 0.85rem;background:#fff;border:1px dashed var(--border);border-radius:var(--radius);line-height:1.5;">
                                <strong>Lời nhắn / Thư giới thiệu:</strong><br>
                                <em>"${escapeHtml(app.cover_letter)}"</em>
                            </div>
                        ` : ''}

                        ${app.student_message ? `
                            <div style="font-size:0.82rem;color:#0f172a;background:#eff6ff;border-left:3px solid var(--primary);padding:0.5rem 0.75rem;border-radius:0 var(--radius) var(--radius) 0;">
                                <strong>Nội dung đã gửi sinh viên:</strong> ${escapeHtml(app.student_message)}
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

    // Chỉ có ba quyết định được phép gửi tới sinh viên.
    const sel = document.getElementById("modal-status-select");
    sel.value = ["interview", "accepted", "rejected"].includes(app.status) ? app.status : "interview";

    document.getElementById("modal-employer-note").value = app.student_message || "";
    updateDecisionMessageHint();
    document.getElementById("status-modal").style.display = "flex";
}

function updateDecisionMessageHint() {
    const status = document.getElementById("modal-status-select").value;
    const field = document.getElementById("modal-employer-note");
    const hints = {
        interview: "Ví dụ: Mời bạn phỏng vấn lúc 09:00 ngày 15/09 tại... Vui lòng mang theo CV bản in.",
        accepted: "Ví dụ: Chúc mừng bạn đã được nhận. Vui lòng có mặt lúc 08:00 ngày 20/09 để hoàn tất thủ tục...",
        rejected: "Ví dụ: Cảm ơn bạn đã ứng tuyển. Hiện kinh nghiệm của bạn chưa phù hợp với yêu cầu vị trí..."
    };
    field.placeholder = hints[status] || "Nhập nội dung gửi tới sinh viên...";
}

function closeStatusModal() {
    document.getElementById("status-modal").style.display = "none";
}

async function handleSaveApplicationStatus(e) {
    e.preventDefault();
    const btn = document.getElementById("btn-save-app-status");
    const appId = document.getElementById("modal-app-id").value;
    const newStatus = document.getElementById("modal-status-select").value;
    const studentMessage = document.getElementById("modal-employer-note").value.trim();
    if (!studentMessage) {
        showToast("Vui lòng nhập nội dung gửi tới sinh viên.", "error");
        return;
    }

    btn.disabled = true;
    btn.innerText = "Đang lưu...";

    const res = await apiRequest(`/applications/${encodeURIComponent(appId)}/status`, {
        method: "PATCH",
        body: {
            status: newStatus,
            student_message: studentMessage
        },
        requireAuth: true
    });

    btn.disabled = false;
    btn.innerText = "Gửi Kết Quả & Thông Báo";

    if (res && res.success) {
        closeStatusModal();
        const emailStatus = res.data?.delivery?.email_status;
        showToast(emailStatus === "sent" ? "Đã cập nhật và gửi thông báo qua app + email!" : "Đã cập nhật và gửi thông báo trong app. Email chưa gửi được.", emailStatus === "sent" ? "success" : "warning");
        loadCompanyApplications(currentAppPage);
    } else {
        showToast((res && res.message) ? res.message : "Cập nhật thất bại.", "error");
    }
}
</script>
