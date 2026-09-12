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
        background: linear-gradient(145deg, #2563eb, #16a34a); box-shadow: 0 8px 20px rgba(37, 99, 235, .18);
    }
    .match-score span { display: block; font-size: 1.08rem; }
    .match-score small { font-size: .58rem; font-weight: 700; opacity: .92; }
    .match-label {
        display: inline-flex; align-items: center; width: fit-content; padding: .3rem .65rem;
        border-radius: 999px; background: #dcfce7; color: #166534; font-size: .76rem; font-weight: 800;
    }
    .match-label.good { background: #dbeafe; color: #1d4ed8; }
    .match-label.review { background: #fef3c7; color: #92400e; }
    .job-meta { display: flex; flex-wrap: wrap; gap: .45rem; }
    .match-reasons { margin: 0; padding: .8rem .9rem; list-style: none; border-radius: 10px; background: #f8fafc; }
    .match-reasons li { position: relative; padding-left: 1.25rem; color: var(--text); font-size: .82rem; line-height: 1.5; }
    .match-reasons li + li { margin-top: .35rem; }
    .match-reasons li::before { content: "✓"; position: absolute; left: 0; color: #16a34a; font-weight: 900; }
    .match-consideration { color: #92400e; background: #fffbeb; border-radius: 8px; padding: .55rem .7rem; font-size: .78rem; line-height: 1.45; }
    .coverage-row { display: flex; justify-content: space-between; align-items: center; gap: .75rem; color: var(--text-muted); font-size: .73rem; }
    .coverage-track { height: 5px; flex: 1; background: #e2e8f0; border-radius: 999px; overflow: hidden; }
    .coverage-fill { height: 100%; border-radius: inherit; background: #60a5fa; }
    .recommendation-actions { margin-top: auto; padding-top: .1rem; display: flex; align-items: center; gap: .65rem; }
    .recommendation-actions .btn { flex: 1; }
    .recommendation-note { color: var(--text-muted); font-size: .76rem; }
    @media (max-width: 640px) {
        .recommendation-hero { flex-direction: column; }
        .recommendation-grid { grid-template-columns: 1fr; }
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
            <div style="width:38px;height:38px;border-radius:10px;background:#dbeafe;color:#2563eb;display:grid;place-items:center;flex:0 0 auto;">
                <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.7 21a2 2 0 0 1-3.4 0"/></svg>
            </div>
            <div>
                <h3 id="personal-alert-title" style="margin:0 0 .25rem;color:var(--dark);font-size:.98rem;">Thông Báo Cá Nhân Hóa</h3>
                <p style="margin:0;color:var(--text-muted);font-size:.8rem;line-height:1.5;">Khi có tin mới đủ dữ liệu và đạt mức phù hợp, hệ thống báo trong ứng dụng và có thể gửi tới email tài khoản.</p>
            </div>
        </div>
        <div style="display:flex;align-items:center;gap:.75rem;flex-wrap:wrap;">
            <label style="display:flex;align-items:center;gap:.4rem;font-size:.8rem;color:var(--dark);cursor:pointer;">
                <input type="checkbox" id="personal-alert-enabled" style="width:17px;height:17px;accent-color:#2563eb;"> Bật thông báo
            </label>
            <label style="display:flex;align-items:center;gap:.4rem;font-size:.8rem;color:var(--dark);cursor:pointer;">
                <input type="checkbox" id="personal-alert-email" style="width:17px;height:17px;accent-color:#2563eb;"> Gửi email
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

        grid.innerHTML = jobs.map(renderRecommendationCard).join("");
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
            <span aria-hidden="true">${ready ? "✓" : "!"}</span>${escapeHtml(label)}
        </span>
    `).join("");
}

function renderRecommendationCard(job) {
    const classification = getRecommendationClassification(job.classification);
    const score = Math.max(0, Math.min(100, parseInt(job.match_score || 0, 10)));
    const coverage = Math.max(0, Math.min(100, parseInt(job.coverage_percent || 0, 10)));
    const reasons = Array.isArray(job.reasons) ? job.reasons.slice(0, 2) : [];
    const location = [job.district, job.city].filter(Boolean).join(", ") || job.location || "Chưa cập nhật khu vực";
    const salary = formatRecommendationSalary(job);

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
                <span class="badge" style="background:#f1f5f9;color:var(--text);">📍 ${escapeHtml(location)}</span>
                <span class="badge badge-shift">${escapeHtml(getShiftLabel(job.shift_type))}</span>
                <span class="badge badge-salary">${escapeHtml(salary)}</span>
            </div>

            ${reasons.length > 0 ? `<ul class="match-reasons" aria-label="Lý do phù hợp">${reasons.map(reason => `<li>${escapeHtml(reason)}</li>`).join("")}</ul>` : ""}
            ${job.consideration ? `<div class="match-consideration"><strong>Cần cân nhắc:</strong> ${escapeHtml(job.consideration)}</div>` : ""}

            <div class="coverage-row" title="Tỷ lệ dữ liệu hồ sơ đủ để đối chiếu với tin tuyển dụng">
                <span>Dữ liệu đối chiếu ${coverage}%</span>
                <div class="coverage-track"><div class="coverage-fill" style="width:${coverage}%"></div></div>
            </div>

            <div class="recommendation-actions">
                <a href="/viec-lam/${encodeURIComponent(job.id)}" class="btn btn-primary btn-sm">Xem Việc Làm &rarr;</a>
                ${job.is_favorite ? `<span class="badge" style="background:#fef3c7;color:#92400e;white-space:nowrap;">★ Đã lưu</span>` : ""}
            </div>
        </article>
    `;
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
