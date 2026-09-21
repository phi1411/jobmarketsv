<?php include __DIR__ . "/nav.php"; ?>

<style>
    .recommendation-hero {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 1.5rem;
        margin-bottom: 1.25rem;
        padding: 1.5rem;
        background: linear-gradient(135deg, #eff6ff 0%, var(--surface, #fff) 62%, #ecfdf5 100%);
        border: 1px solid #bfdbfe;
        border-radius: var(--radius);
    }
    .recommendation-hero h2 { margin: 0 0 .45rem; color: var(--dark); font-size: 1.35rem; }
    .recommendation-hero p { margin: 0; color: var(--text-muted); line-height: 1.6; max-width: 720px; }
    .profile-readiness { display: flex; flex-wrap: wrap; gap: .5rem; margin-top: 1rem; }
    .readiness-chip {
        display: inline-flex; align-items: center; gap: .4rem; padding: .4rem .7rem;
        border-radius: 999px; font-size: .78rem; font-weight: 700;
        background: #f1f5f9; color: #64748b;
    }
    .readiness-chip.ready { background: #dcfce7; color: #166534; }
    .recommendation-toolbar {
        display: flex; justify-content: space-between; align-items: center; gap: 1rem;
        margin-bottom: 1rem; flex-wrap: wrap;
    }
    .recommendation-grid {
        display: grid; grid-template-columns: repeat(auto-fit, minmax(330px, 1fr)); gap: 1rem;
    }
    .recommendation-card {
        padding: 1.25rem; display: flex; flex-direction: column; gap: .9rem;
        min-height: 100%; transition: transform .18s ease, box-shadow .18s ease;
    }
    .recommendation-card:hover { transform: translateY(-2px); box-shadow: var(--shadow); }
    .recommendation-card-top { display: flex; justify-content: space-between; gap: 1rem; align-items: flex-start; }
    .recommendation-card h3 { margin: 0 0 .35rem; font-size: 1.08rem; line-height: 1.4; }
    .recommendation-card h3 a { color: var(--dark); }
    .recommendation-company { color: var(--text-muted); font-size: .86rem; }
    .match-score {
        width: 66px; height: 66px; flex: 0 0 66px; border-radius: 50%; display: grid;
        place-items: center; text-align: center; color: #fff; font-weight: 800; line-height: 1.05;
        background: linear-gradient(145deg, var(--primary), var(--secondary)); box-shadow: 0 8px 20px rgba(5, 150, 105, .25);
    }
    .match-score span { display: block; font-size: 1.08rem; }
    .match-score small { font-size: .58rem; font-weight: 700; opacity: .92; }
    .match-label {
        display: inline-flex; align-items: center; width: fit-content; padding: .3rem .65rem;
        border-radius: 999px; background: #dcfce7; color: #166534; font-size: .76rem; font-weight: 800;
    }
    .match-label.good { background: var(--primary-light); color: var(--primary-text); }
    .match-label.review { background: #fef3c7; color: #92400e; }
    .job-meta { display: flex; flex-wrap: wrap; gap: .45rem; }
    .match-reasons { margin: 0; padding: .8rem .9rem; list-style: none; border-radius: 10px; background: #f8fafc; }
    .match-reasons li { position: relative; padding-left: 1.25rem; color: var(--text); font-size: .82rem; line-height: 1.5; }
    .match-reasons li + li { margin-top: .35rem; }
    .match-reasons li::before { content: "✓"; position: absolute; left: 0; color: #16a34a; font-weight: 900; }
    .match-consideration { color: #92400e; background: #fffbeb; border-radius: 8px; padding: .55rem .7rem; font-size: .78rem; line-height: 1.45; }
    .coverage-row { display: flex; justify-content: space-between; align-items: center; gap: .75rem; color: var(--text-muted); font-size: .73rem; }
    .coverage-track { height: 5px; flex: 1; background: #e2e8f0; border-radius: 999px; overflow: hidden; }
    .coverage-fill { height: 100%; border-radius: inherit; background: var(--primary); }
    .match-explanation-toggle {
        width: 100%; display: flex; align-items: center; justify-content: space-between; gap: .75rem;
        padding: .7rem .8rem; border: 1px solid var(--primary-border); border-radius: 10px; background: var(--primary-light);
        color: var(--primary-text); font: inherit; font-size: .82rem; font-weight: 800; cursor: pointer;
    }
    .match-explanation-toggle:hover { background: #d1fae5; }
    .match-explanation-toggle .toggle-icon { transition: transform .18s ease; }
    .match-explanation-toggle[aria-expanded="true"] .toggle-icon { transform: rotate(180deg); }
    .match-explanation[hidden] { display: none; }
    .match-explanation {
        padding: .9rem; border: 1px solid var(--primary-border); border-radius: 12px; background: #fff;
        display: flex; flex-direction: column; gap: .9rem;
    }
    .match-explanation-title { margin: 0; color: var(--dark); font-size: .9rem; }
    .criterion-list { display: flex; flex-direction: column; gap: .72rem; }
    .criterion-head { display: flex; align-items: center; justify-content: space-between; gap: .75rem; }
    .criterion-name { color: var(--dark); font-size: .79rem; font-weight: 800; }
    .criterion-weight { color: var(--text-muted); font-size: .68rem; font-weight: 600; }
    .criterion-result { font-size: .73rem; font-weight: 800; white-space: nowrap; }
    .criterion-result.matched { color: #15803d; }
    .criterion-result.partial { color: #b45309; }
    .criterion-result.needs_improvement { color: #dc2626; }
    .criterion-result.missing_data, .criterion-result.not_applicable { color: #64748b; }
    .criterion-track { height: 7px; margin-top: .35rem; background: #e2e8f0; border-radius: 999px; overflow: hidden; }
    .criterion-fill { height: 100%; border-radius: inherit; background: #ef4444; }
    .criterion-fill.matched { background: #22c55e; }
    .criterion-fill.partial { background: #f59e0b; }
    .criterion-fill.missing_data, .criterion-fill.not_applicable { background: #94a3b8; }
    .criterion-evidence { margin-top: .3rem; color: var(--text-muted); font-size: .72rem; line-height: 1.4; }
    .skill-breakdown { display: grid; grid-template-columns: 1fr 1fr; gap: .55rem; margin-top: .45rem; }
    .skill-breakdown-box { padding: .55rem .65rem; border-radius: 8px; font-size: .7rem; line-height: 1.45; }
    .skill-breakdown-box.matched { background: #f0fdf4; color: #166534; }
    .skill-breakdown-box.missing { background: #fff7ed; color: #9a3412; }
    .improvement-box { padding: .8rem; border-radius: 10px; background: #fffbeb; border: 1px solid #fde68a; }
    .improvement-box h5 { margin: 0 0 .55rem; color: #92400e; font-size: .8rem; }
    .improvement-list { margin: 0; padding-left: 1.1rem; color: #78350f; font-size: .73rem; line-height: 1.5; }
    .improvement-list li + li { margin-top: .4rem; }
    .score-note { margin: 0; padding-top: .7rem; border-top: 1px solid #e2e8f0; color: var(--text-muted); font-size: .68rem; line-height: 1.45; }
    .recommendation-actions { margin-top: auto; padding-top: .1rem; display: flex; align-items: center; gap: .65rem; }
    .recommendation-actions .btn { flex: 1; }
    .recommendation-note { color: var(--text-muted); font-size: .76rem; }
    @media (max-width: 640px) {
        .recommendation-hero { flex-direction: column; }
        .recommendation-grid { grid-template-columns: 1fr; }
        .skill-breakdown { grid-template-columns: 1fr; }
    }
    [data-theme="dark"] .recommendation-hero {
        background: linear-gradient(135deg, rgba(30, 41, 59, 0.7) 0%, rgba(15, 23, 42, 0.9) 100%);
        border-color: var(--border);
    }
    [data-theme="dark"] .recommendation-hero p { color: var(--text-muted); }
    [data-theme="dark"] .match-explanation {
        background: var(--surface);
        border-color: var(--border);
    }
    [data-theme="dark"] .match-explanation-toggle {
        background: var(--surface-hover);
        border-color: var(--border);
        color: var(--primary-text);
    }
    [data-theme="dark"] .match-reasons {
        background: var(--bg);
    }
    [data-theme="dark"] .match-consideration {
        background: var(--warning-light);
        color: var(--warning-text);
    }
    [data-theme="dark"] .improvement-box {
        background: var(--warning-light);
        border-color: var(--warning-border);
    }
    [data-theme="dark"] .improvement-box h5 { color: var(--warning-text); }
    [data-theme="dark"] .improvement-list { color: var(--text); }
    [data-theme="dark"] .skill-breakdown-box.missing {
        background: var(--danger-light);
        color: var(--danger-text);
    }
    [data-theme="dark"] .skill-breakdown-box.matched {
        background: var(--success-light);
        color: var(--success-text);
    }
    [data-theme="dark"] .score-note {
        border-top-color: var(--border);
    }
</style>

<div class="container" style="margin-bottom:3rem;">
    <section class="recommendation-hero" aria-labelledby="recommendation-title">
        <div>
            <div class="portal-eyebrow" style="margin-bottom:.65rem;">Cá Nhân Hóa Tự Động</div>
            <h2 id="recommendation-title">Việc Làm Dành Riêng Cho Bạn</h2>
            <p>JobMarketSV tự đối chiếu hồ sơ, kỹ năng, kinh nghiệm, khu vực và lịch rảnh của bạn với các tin đang tuyển. Bạn không cần tạo bộ lọc thủ công.</p>
            <div id="profile-readiness" class="profile-readiness" aria-live="polite"></div>
        </div>
        <a href="/student/profile" class="btn btn-outline btn-sm">Cập Nhật Hồ Sơ</a>
    </section>

    <section class="surface-card" style="padding:1rem 1.25rem;margin-bottom:1.25rem;display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;" aria-labelledby="personal-alert-title">
        <div style="display:flex;align-items:flex-start;gap:.8rem;flex:1;min-width:280px;">
            <div style="width:38px;height:38px;border-radius:10px;background:var(--primary-light);color:var(--primary);display:grid;place-items:center;flex:0 0 auto;">
                <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.7 21a2 2 0 0 1-3.4 0"/></svg>
            </div>
            <div>
                <h3 id="personal-alert-title" style="margin:0 0 .25rem;color:var(--dark);font-size:.98rem;">Thông Báo Cá Nhân Hóa</h3>
                <p style="margin:0;color:var(--text-muted);font-size:.8rem;line-height:1.5;">Khi có tin mới đủ dữ liệu và đạt mức phù hợp, hệ thống báo trong ứng dụng và có thể gửi tới email tài khoản.</p>
            </div>
        </div>
        <div style="display:flex;align-items:center;gap:.75rem;flex-wrap:wrap;">
            <label style="display:flex;align-items:center;gap:.4rem;font-size:.8rem;color:var(--dark);cursor:pointer;">
                <input type="checkbox" id="personal-alert-enabled" style="width:17px;height:17px;accent-color:var(--primary);"> Bật thông báo
            </label>
            <label style="display:flex;align-items:center;gap:.4rem;font-size:.8rem;color:var(--dark);cursor:pointer;">
                <input type="checkbox" id="personal-alert-email" style="width:17px;height:17px;accent-color:var(--primary);"> Gửi email
            </label>
            <select id="personal-alert-score" class="form-control" style="width:auto;padding:.5rem .7rem;font-size:.8rem;" aria-label="Ngưỡng thông báo cá nhân hóa">
                <option value="50">Từ 50%</option>
                <option value="65" selected>Từ 65%</option>
                <option value="80">Từ 80%</option>
                <option value="90">Từ 90%</option>
            </select>
            <button type="button" id="personal-alert-save" class="btn btn-primary btn-sm" onclick="savePersonalAlertSettings()">Lưu Cài Đặt</button>
        </div>
    </section>

    <div class="recommendation-toolbar">
        <div>
            <h3 style="margin:0;color:var(--dark);font-size:1.08rem;">Gợi ý phù hợp nhất</h3>
            <span id="recommendation-count" class="recommendation-note">Đang phân tích hồ sơ...</span>
        </div>
        <label style="display:flex;align-items:center;gap:.55rem;color:var(--text-muted);font-size:.84rem;">
            Mức phù hợp
            <select id="recommendation-score-filter" class="form-control" style="width:auto;min-width:165px;padding:.55rem .8rem;" aria-label="Lọc theo mức phù hợp">
                <option value="0">Tất cả gợi ý</option>
                <option value="65" selected>Từ 65% trở lên</option>
                <option value="80">Từ 80% trở lên</option>
            </select>
        </label>
    </div>

    <div id="recommendation-loading" class="recommendation-grid" aria-label="Đang tải gợi ý">
        <div class="surface-card skeleton" style="height:330px;"></div>
        <div class="surface-card skeleton" style="height:330px;"></div>
        <div class="surface-card skeleton" style="height:330px;"></div>
    </div>

    <div id="recommendation-grid" class="recommendation-grid"></div>

    <div id="recommendation-empty" class="surface-card empty-state" style="display:none;">
        <div class="empty-state-icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m12 3-1.9 4.6L5 9.5l4 3.3L8.5 18l3.5-2.3 3.5 2.3-.5-5.2 4-3.3-5.1-1.9z"/><path d="M19 3v4M21 5h-4"/></svg>
        </div>
        <h3 id="recommendation-empty-title" class="empty-state-title">Chưa Có Việc Phù Hợp</h3>
        <p id="recommendation-empty-text" class="empty-state-text">Hãy bổ sung kỹ năng và lịch rảnh để hệ thống tìm được công việc sát với bạn hơn.</p>
        <div class="empty-state-action"><a href="/student/profile" class="btn btn-primary">Hoàn Thiện Hồ Sơ</a></div>
    </div>
</div>

<script>
function initStudentRecommendationsPage() {
    if (!TokenStorage.isLoggedIn()) {
        showToast("Vui lòng đăng nhập để xem việc làm dành cho bạn.", "error");
        window.location.href = `/login?login_required=1&redirect=${encodeURIComponent(window.location.pathname)}`;
        return;
    }

    const user = TokenStorage.getUser();
    if (!user || (user.role !== "student" && user.role !== "developer")) {
        showToast("Chỉ tài khoản Sinh viên mới có quyền xem trang này.", "error");
        setTimeout(() => { window.location.href = "/viec-lam"; }, 1000);
        return;
    }

    document.getElementById("recommendation-score-filter").addEventListener("change", loadRecommendations);
    document.getElementById("personal-alert-enabled").addEventListener("change", syncPersonalAlertControls);
    loadRecommendations();
    loadPersonalAlertSettings();
}

async function loadPersonalAlertSettings() {
    const res = await apiRequest("/student/recommendation-alert-settings", { requireAuth: true });
    if (!res || !res.success || !res.data) return;
    document.getElementById("personal-alert-enabled").checked = !!res.data.enabled;
    document.getElementById("personal-alert-email").checked = !!res.data.email_enabled;
    document.getElementById("personal-alert-score").value = String(res.data.minimum_score || 65);
    syncPersonalAlertControls();
}

function syncPersonalAlertControls() {
    const enabled = document.getElementById("personal-alert-enabled").checked;
    const email = document.getElementById("personal-alert-email");
    const score = document.getElementById("personal-alert-score");
    email.disabled = !enabled;
    score.disabled = !enabled;
    if (!enabled) email.checked = false;
}

async function savePersonalAlertSettings() {
    const button = document.getElementById("personal-alert-save");
    const enabled = document.getElementById("personal-alert-enabled").checked;
    button.disabled = true;
    button.textContent = "Đang lưu...";
    const res = await apiRequest("/student/recommendation-alert-settings", {
        method: "PATCH",
        requireAuth: true,
        body: {
            enabled,
            email_enabled: enabled && document.getElementById("personal-alert-email").checked,
            minimum_score: parseInt(document.getElementById("personal-alert-score").value || "65", 10)
        }
    });
    button.disabled = false;
    button.textContent = "Lưu Cài Đặt";
    if (res && res.success) {
        showToast(enabled ? "Đã bật thông báo việc làm cá nhân hóa." : "Đã tắt thông báo việc làm cá nhân hóa.", "success");
        loadPersonalAlertSettings();
    } else {
        showToast((res && res.message) || "Không thể lưu cài đặt thông báo.", "error");
    }
}

if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initStudentRecommendationsPage);
} else {
    initStudentRecommendationsPage();
}

async function loadRecommendations() {
    const loading = document.getElementById("recommendation-loading");
    const grid = document.getElementById("recommendation-grid");
    const empty = document.getElementById("recommendation-empty");
    const count = document.getElementById("recommendation-count");
    const minimumScore = parseInt(document.getElementById("recommendation-score-filter").value || "0", 10);

    loading.style.display = "grid";
    grid.innerHTML = "";
    empty.style.display = "none";
    count.textContent = "Đang phân tích hồ sơ...";

    try {
        const res = await apiRequest(`/student/job-recommendations?limit=24&minimum_score=${minimumScore}`, { requireAuth: true });
        if (!res || !res.success || !res.data) {
            throw new Error((res && res.message) || "Không thể tải gợi ý việc làm.");
        }

        const data = res.data;
        const jobs = Array.isArray(data.items) ? data.items : [];
        renderProfileReadiness(data.profile || {});
        count.textContent = jobs.length > 0
            ? `Tìm thấy ${data.total} việc phù hợp · Đã ẩn ${data.excluded_applied || 0} việc bạn đã ứng tuyển`
            : "Chưa tìm thấy việc đạt mức phù hợp đã chọn";

        if (jobs.length === 0) {
            document.getElementById("recommendation-empty-title").textContent = minimumScore > 0 ? "Chưa Có Việc Đạt Mức Này" : "Chưa Có Việc Phù Hợp";
            document.getElementById("recommendation-empty-text").textContent = minimumScore > 0
                ? "Hãy chọn mức phù hợp thấp hơn hoặc cập nhật thêm kỹ năng và lịch rảnh trong hồ sơ."
                : "Hãy bổ sung kỹ năng và lịch rảnh để hệ thống tìm được công việc sát với bạn hơn.";
            empty.style.display = "block";
            return;
        }

        grid.innerHTML = jobs.map((job, index) => renderRecommendationCard(job, index)).join("");
    } catch (error) {
        console.error("Error loading job recommendations:", error);
        count.textContent = "Không thể tải gợi ý lúc này";
        document.getElementById("recommendation-empty-title").textContent = "Không Thể Tải Gợi Ý";
        document.getElementById("recommendation-empty-text").textContent = error.message || "Vui lòng thử lại sau.";
        empty.style.display = "block";
    } finally {
        loading.style.display = "none";
    }
}

function renderProfileReadiness(profile) {
    const completion = Math.max(0, Math.min(100, parseInt(profile.completion_percent || 0, 10)));
    const chips = [
        [true, `Hồ sơ ${completion}%`],
        [!!profile.has_skills, profile.has_skills ? "Đã có kỹ năng" : "Thiếu kỹ năng"],
        [!!profile.has_schedule, profile.has_schedule ? "Đã có lịch rảnh" : "Thiếu lịch rảnh"],
        [!!profile.has_location, profile.has_location ? "Đã có khu vực" : "Thiếu khu vực"]
    ];

    document.getElementById("profile-readiness").innerHTML = chips.map(([ready, label]) => `
        <span class="readiness-chip ${ready ? "ready" : ""}">
            <span aria-hidden="true">${ready ? '<i class="ri-check-line"></i>' : '!'}</span>${escapeHtml(label)}
        </span>
    `).join("");
}

function renderRecommendationCard(job, index) {
    const classification = getRecommendationClassification(job.classification);
    const score = Math.max(0, Math.min(100, parseInt(job.match_score || 0, 10)));
    const coverage = Math.max(0, Math.min(100, parseInt(job.coverage_percent || 0, 10)));
    const reasons = Array.isArray(job.reasons) ? job.reasons.slice(0, 2) : [];
    const location = [job.district, job.city].filter(Boolean).join(", ") || job.location || "Chưa cập nhật khu vực";
    const salary = formatRecommendationSalary(job);
    const explanationId = `match-explanation-${index}`;

    return `
        <article class="surface-card recommendation-card">
            <div class="recommendation-card-top">
                <div>
                    <span class="match-label ${classification.className}">${classification.label}</span>
                    <h3 style="margin-top:.65rem;"><a href="/viec-lam/${encodeURIComponent(job.id)}">${escapeHtml(job.title)}</a></h3>
                    <div class="recommendation-company">${escapeHtml(job.company_name || "Nhà tuyển dụng")}</div>
                </div>
                <div class="match-score" title="Điểm phù hợp tổng hợp từ hồ sơ và yêu cầu công việc">
                    <div><span>${score}%</span><small>PHÙ HỢP</small></div>
                </div>
            </div>

            <div class="job-meta">
                ${job.category_name ? `<span class="badge" style="background:#f1f5f9;color:var(--text);">${escapeHtml(job.category_name)}</span>` : ""}
                <span class="badge" style="background:#f1f5f9;color:var(--text);"><i class="ri-map-pin-line"></i> ${escapeHtml(location)}</span>
                <span class="badge badge-shift">${escapeHtml(getShiftLabel(job.shift_type))}</span>
                <span class="badge badge-salary">${escapeHtml(salary)}</span>
            </div>

            ${reasons.length > 0 ? `<ul class="match-reasons" aria-label="Lý do phù hợp">${reasons.map(reason => `<li>${escapeHtml(reason)}</li>`).join("")}</ul>` : ""}
            ${job.consideration ? `<div class="match-consideration"><strong>Cần cân nhắc:</strong> ${escapeHtml(job.consideration)}</div>` : ""}

            <div class="coverage-row" title="Tỷ lệ dữ liệu hồ sơ đủ để đối chiếu với tin tuyển dụng">
                <span>Dữ liệu đối chiếu ${coverage}%</span>
                <div class="coverage-track"><div class="coverage-fill" style="width:${coverage}%"></div></div>
            </div>

            <button type="button" class="match-explanation-toggle" aria-expanded="false" aria-controls="${explanationId}" onclick="toggleMatchExplanation('${explanationId}', this)">
                <span style="display:inline-flex;align-items:center;gap:0.35rem;"><i class="ri-sparkling-fill" style="color:var(--primary);"></i> Vì sao phù hợp ${score}%?</span>
                <span class="toggle-icon" aria-hidden="true">⌄</span>
            </button>
            <div id="${explanationId}" class="match-explanation" hidden>
                ${renderMatchExplanation(job)}
            </div>

            <div class="recommendation-actions">
                <a href="/viec-lam/${encodeURIComponent(job.id)}" class="btn btn-primary btn-sm">Xem Việc Làm &rarr;</a>
                ${job.is_favorite ? `<span class="badge" style="background:#fef3c7;color:#92400e;white-space:nowrap;"><i class="ri-star-fill"></i> Đã lưu</span>` : ""}
            </div>
        </article>
    `;
}

function renderMatchExplanation(job) {
    const criteria = Array.isArray(job.criteria) ? job.criteria : [];
    const suggestions = Array.isArray(job.improvement_suggestions) ? job.improvement_suggestions : [];

    const criterionHtml = criteria.map(criterion => {
        const status = criterion.status || "missing_data";
        const score = criterion.score === null || criterion.score === undefined
            ? null
            : Math.max(0, Math.min(100, parseInt(criterion.score, 10) || 0));
        const statusText = getCriterionStatusText(status, score);
        const matchedItems = Array.isArray(criterion.matched_items) ? criterion.matched_items : [];
        const missingItems = Array.isArray(criterion.missing_items) ? criterion.missing_items : [];
        const skillBreakdown = criterion.key === "skills" && (matchedItems.length || missingItems.length) ? `
            <div class="skill-breakdown">
                ${matchedItems.length ? `<div class="skill-breakdown-box matched"><strong>Đã đáp ứng:</strong><br>${matchedItems.map(escapeHtml).join(", ")}</div>` : ""}
                ${missingItems.length ? `<div class="skill-breakdown-box missing"><strong>Còn thiếu:</strong><br>${missingItems.map(escapeHtml).join(", ")}</div>` : ""}
            </div>
        ` : "";

        return `
            <div class="criterion-item">
                <div class="criterion-head">
                    <div>
                        <span class="criterion-name">${escapeHtml(criterion.label || criterion.key || "Tiêu chí")}</span>
                        <span class="criterion-weight"> · Trọng số ${parseInt(criterion.weight || 0, 10)}%</span>
                    </div>
                    <span class="criterion-result ${status}">${escapeHtml(statusText)}</span>
                </div>
                <div class="criterion-track"><div class="criterion-fill ${status}" style="width:${score === null ? 0 : score}%"></div></div>
                ${criterion.evidence ? `<div class="criterion-evidence">${escapeHtml(criterion.evidence)}</div>` : ""}
                ${skillBreakdown}
            </div>
        `;
    }).join("");

    const suggestionsHtml = suggestions.length ? `
        <div class="improvement-box">
            <h5><i class="ri-lightbulb-line"></i> Nên cải thiện trước</h5>
            <ol class="improvement-list">
                ${suggestions.map(item => `<li><strong>${escapeHtml(item.label || "Hồ sơ")}:</strong> ${escapeHtml(item.text || "")}</li>`).join("")}
            </ol>
        </div>
    ` : `<div class="skill-breakdown-box matched"><strong>Hồ sơ đang đáp ứng tốt các tiêu chí có thể đối chiếu của công việc này.</strong></div>`;

    return `
        <h4 class="match-explanation-title">Chi tiết từng tiêu chí</h4>
        <div class="criterion-list">${criterionHtml}</div>
        ${suggestionsHtml}
        <p class="score-note"><strong>Cách tính:</strong> hệ thống chỉ đối chiếu 5 tiêu chí: độ tuổi 10%, kinh nghiệm 15%, kỹ năng 30%, học vấn 15% và lịch làm việc 30%. Tiêu chí nhà tuyển dụng để trống được tính là đạt 100%. Đây không phải xác suất được tuyển.</p>
    `;
}

function getCriterionStatusText(status, score) {
    if (status === "not_applicable") return "Không yêu cầu";
    if (status === "missing_data") return "Thiếu dữ liệu";
    if (status === "matched") return `Đạt tốt · ${score}%`;
    if (status === "partial") return `Khớp một phần · ${score}%`;
    return `Cần cải thiện · ${score}%`;
}

function toggleMatchExplanation(id, button) {
    const panel = document.getElementById(id);
    if (!panel) return;
    const willOpen = panel.hidden;
    panel.hidden = !willOpen;
    button.setAttribute("aria-expanded", willOpen ? "true" : "false");
}

function getRecommendationClassification(value) {
    const map = {
        HIGH_MATCH: { label: "Rất phù hợp", className: "" },
        GOOD_MATCH: { label: "Phù hợp tốt", className: "good" },
        REVIEW_NEEDED: { label: "Nên xem thêm", className: "review" },
        INSUFFICIENT_DATA: { label: "Tin còn thiếu dữ liệu", className: "review" }
    };
    return map[value] || map.REVIEW_NEEDED;
}

function formatRecommendationSalary(job) {
    if (job.salary_type === "negotiable" || (!job.salary_min && !job.salary_max)) return "Thỏa thuận";
    if (job.salary_min && job.salary_max) return `${formatCurrency(job.salary_min)} - ${formatCurrency(job.salary_max)}`;
    if (job.salary_min) return `Từ ${formatCurrency(job.salary_min)}`;
    return `Đến ${formatCurrency(job.salary_max)}`;
}
</script>
