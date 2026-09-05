<div id="job-detail-loading" class="container" style="padding:4rem 1.25rem;">
    <div class="job-card skeleton" style="height:350px;margin-bottom:2rem;"></div>
    <div class="job-card skeleton" style="height:250px;"></div>
</div>

<div id="job-detail-wrapper" style="display:none;">
    <!-- Detail Header -->
    <section class="detail-header">
        <div class="container">
            <div style="margin-bottom:1.5rem;">
                <a href="/viec-lam" class="btn btn-outline btn-sm">&larr; Quay lại danh sách việc làm</a>
            </div>

            <div class="detail-top">
                <div style="display:flex;gap:1.5rem;align-items:flex-start;">
                    <div id="job-company-logo" class="company-logo" style="width:72px;height:72px;font-size:1.75rem;">J</div>
                    <div>
                        <h1 id="job-title" style="font-size:1.85rem;font-weight:800;color:var(--dark);margin-bottom:0.5rem;line-height:1.25;">Đang tải...</h1>
                        <div id="job-company-name" style="font-size:1.1rem;color:var(--text-muted);margin-bottom:1rem;">Công ty</div>
                        <div id="job-badges-header" class="job-badges"></div>
                    </div>
                </div>

                <div style="display:flex;gap:0.75rem;align-items:center;flex-shrink:0;">
                    <button id="btn-favorite" class="btn btn-outline" onclick="handleToggleFavorite()">
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path></svg>
                        Lưu tin
                    </button>
                    <button id="btn-apply-top" class="btn btn-primary btn-lg" onclick="handleApplyClick()">
                        Ứng Tuyển Ngay &rarr;
                    </button>
                </div>
            </div>
        </div>
    </section>

    <!-- Detail Body -->
    <div class="container">
        <div class="detail-content">
            <!-- Left Column: Details -->
            <div>
                <div class="detail-card">
                    <h2 class="detail-card-title">Mô Tả Công Việc</h2>
                    <div id="job-description" style="white-space:pre-line;color:var(--text);line-height:1.7;"></div>
                </div>

                <div class="detail-card">
                    <h2 class="detail-card-title">Yêu Cầu Ứng Viên</h2>
                    <div id="job-requirements" style="white-space:pre-line;color:var(--text);line-height:1.7;"></div>
                </div>

                <div class="detail-card">
                    <h2 class="detail-card-title">Quyền Lợi Được Hưởng</h2>
                    <div id="job-benefits" style="white-space:pre-line;color:var(--text);line-height:1.7;"></div>
                </div>
            </div>

            <!-- Right Column: Meta & Employer Info -->
            <div>
                <div class="detail-card">
                    <h3 class="detail-card-title">Thông Tin Việc Làm</h3>
                    <ul style="list-style:none;display:flex;flex-direction:column;gap:1rem;font-size:0.95rem;">
                        <li style="display:flex;justify-content:space-between;">
                            <span style="color:var(--text-muted);">Mức lương:</span>
                            <strong id="meta-salary" style="color:var(--secondary);">Thoả thuận</strong>
                        </li>
                        <li style="display:flex;justify-content:space-between;">
                            <span style="color:var(--text-muted);">Ca làm việc:</span>
                            <span id="meta-shift" style="font-weight:600;">Linh hoạt</span>
                        </li>
                        <li style="display:flex;justify-content:space-between;">
                            <span style="color:var(--text-muted);">Hình thức:</span>
                            <span id="meta-work-type" style="font-weight:600;">Part-time</span>
                        </li>
                        <li style="display:flex;justify-content:space-between;">
                            <span style="color:var(--text-muted);">Địa điểm:</span>
                            <span id="meta-location" style="font-weight:600;text-align:right;">Hà Nội</span>
                        </li>
                        <li style="display:flex;justify-content:space-between;">
                            <span style="color:var(--text-muted);">Hạn nộp hồ sơ:</span>
                            <span id="meta-deadline" style="font-weight:600;color:var(--danger);">Còn tuyển</span>
                        </li>
                    </ul>

                    <div style="margin-top:1.5rem;padding-top:1.25rem;border-top:1px solid var(--border);">
                        <button class="btn btn-primary" style="width:100%;" onclick="handleApplyClick()">
                            Ứng Tuyển Vị Trí Này
                        </button>
                    </div>
                </div>

                <div class="detail-card">
                    <h3 class="detail-card-title">Về Nhà Tuyển Dụng</h3>
                    <div id="company-info-box">
                        <div id="comp-sidebar-name" style="font-weight:700;font-size:1.05rem;margin-bottom:0.5rem;color:var(--dark);">Công ty</div>
                        <p id="comp-sidebar-desc" style="font-size:0.88rem;color:var(--text-muted);line-height:1.5;">Doanh nghiệp đối tác tuyển dụng sinh viên part-time trên hệ thống.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Apply Job Modal -->
<div id="apply-modal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(15,23,42,0.6);z-index:9999;align-items:center;justify-content:center;padding:1rem;">
    <div style="background:#fff;border-radius:var(--radius);max-width:550px;width:100%;padding:2rem;box-shadow:var(--shadow);position:relative;max-height:90vh;overflow-y:auto;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.25rem;padding-bottom:0.75rem;border-bottom:1px solid var(--border);">
            <h3 style="font-size:1.2rem;font-weight:700;color:var(--dark);margin:0;">Ứng Tuyển Việc Làm</h3>
            <button type="button" onclick="closeApplyModal()" class="modal-close-btn" aria-label="Đóng hộp thoại">&times;</button>
        </div>

        <div id="apply-job-info" style="background:#f8fafc;border-radius:var(--radius-sm);padding:0.75rem 1rem;margin-bottom:1.25rem;border:1px solid var(--border);">
            <div id="apply-modal-job-title" style="font-weight:700;color:var(--dark);font-size:0.95rem;"></div>
            <div id="apply-modal-company" style="font-size:0.85rem;color:var(--text-muted);"></div>
        </div>

        <div id="apply-error-box" style="display:none;margin-bottom:1rem;" class="toast toast-error"></div>

        <form id="apply-form" onsubmit="submitApplication(event)">
            <div class="form-group" style="margin-bottom:1rem;">
                <label class="form-label">Ca làm việc mong muốn <span style="color:var(--danger)">*</span></label>
                <select id="apply-shift" class="form-control" required>
                    <option value="morning">Ca Sáng (08:00 - 12:00)</option>
                    <option value="afternoon">Ca Chiều (13:00 - 17:00)</option>
                    <option value="evening">Ca Tối (18:00 - 22:00)</option>
                    <option value="weekend">Cuối Tuần (Thứ 7 & CN)</option>
                    <option value="flexible">Linh hoạt theo lịch học</option>
                </select>
            </div>

            <div class="form-group" style="margin-bottom:1.5rem;">
                <label class="form-label">Thư giới thiệu / Lời nhắn tới nhà tuyển dụng</label>
                <textarea id="apply-cover-letter" class="form-control" rows="4" placeholder="Giới thiệu nhanh về bạn, kinh nghiệm làm thêm (nếu có) và mong muốn khi làm việc..."></textarea>
                <small class="form-help">Hồ sơ năng lực và liên kết CV đã lưu trong hồ sơ của bạn sẽ được gửi kèm tự động.</small>
            </div>

            <div style="display:flex;justify-content:flex-end;gap:0.75rem;padding-top:1rem;border-top:1px solid var(--border);">
                <button type="button" onclick="closeApplyModal()" class="btn btn-outline btn-sm">Hủy</button>
                <button type="submit" id="btn-submit-apply" class="btn btn-primary btn-sm">
                    Gửi Đơn Ứng Tuyển
                </button>
            </div>
        </form>
    </div>
</div>

<script>
const currentJobId = <?= json_encode($jobId ?? "") ?>;
let currentJobData = null;

document.addEventListener("DOMContentLoaded", async () => {
    if (!currentJobId) {
        showToast("Không tìm thấy mã việc làm.", "error");
        return;
    }

    // Call REST API GET /jobs/{id}
    const res = await apiRequest(`/jobs/${encodeURIComponent(currentJobId)}`);

    document.getElementById("job-detail-loading").style.display = "none";

    if (res && res.success && res.data) {
        const job = res.data;
        currentJobData = job;
        document.getElementById("job-detail-wrapper").style.display = "block";

        // Update Title & Meta
        document.title = `${job.title} | JobMarket SV`;
        document.getElementById("job-title").innerText = job.title;
        document.getElementById("job-company-name").innerText = job.company_name || "Nhà tuyển dụng";
        document.getElementById("job-company-logo").innerText = (job.company_name ? job.company_name.substring(0, 1) : "J");

        // Badges
        document.getElementById("job-badges-header").innerHTML = `
            <span class="badge badge-salary" style="font-size:0.85rem;padding:0.35rem 0.75rem;">💰 ${formatCurrency(job.salary_min)} - ${formatCurrency(job.salary_max)}</span>
            <span class="badge badge-shift" style="font-size:0.85rem;padding:0.35rem 0.75rem;">⏰ ${getShiftLabel(job.shift_type)}</span>
            <span class="badge badge-location" style="font-size:0.85rem;padding:0.35rem 0.75rem;">📍 ${escapeHtml(job.location_name || job.city || "Hà Nội")}</span>
        `;

        // Content
        document.getElementById("job-description").innerText = job.description || "Chưa có mô tả chi tiết.";
        document.getElementById("job-requirements").innerText = job.requirements || "Không yêu cầu kinh nghiệm đặc biệt.";
        document.getElementById("job-benefits").innerText = job.benefits || "Hưởng mức lương theo giờ và các chế độ phụ cấp.";

        // Sidebar Meta
        document.getElementById("meta-salary").innerText = `${formatCurrency(job.salary_min)} - ${formatCurrency(job.salary_max)}`;
        document.getElementById("meta-shift").innerText = getShiftLabel(job.shift_type);
        document.getElementById("meta-work-type").innerText = getWorkTypeLabel(job.work_type);
        document.getElementById("meta-location").innerText = `${job.district ? job.district + ', ' : ''}${job.city || 'Hà Nội'}`;
        document.getElementById("meta-deadline").innerText = formatDate(job.application_deadline) || "Còn tuyển";

        // Company Sidebar
        document.getElementById("comp-sidebar-name").innerText = job.company_name || "Nhà tuyển dụng";
        if (job.company_description) {
            document.getElementById("comp-sidebar-desc").innerText = job.company_description;
        }
    } else {
        document.getElementById("job-detail-loading").innerHTML = `
            <div class="empty-state" style="background:#fff;border-radius:var(--radius);border:1px solid var(--border);margin-top:2rem;">
                <div class="empty-icon">❌</div>
                <h2>Việc làm không tồn tại hoặc đã đóng</h2>
                <p>Tin tuyển dụng này có thể đã hết hạn nộp hoặc đã được nhà tuyển dụng tạm đóng.</p>
                <a href="/viec-lam" class="btn btn-primary" style="margin-top:1.5rem;">Xem các việc làm khác</a>
            </div>
        `;
        document.getElementById("job-detail-loading").style.display = "block";
    }
});

function handleApplyClick() {
    handleApplyJob();
}

function handleApplyJob() {
    const user = TokenStorage.getUser();

    if (!user) {
        showToast("Vui lòng đăng nhập với tài khoản Sinh viên để nộp đơn ứng tuyển.", "error");
        setTimeout(() => {
            window.location.href = `/login?login_required=1&redirect=${encodeURIComponent(window.location.pathname)}`;
        }, 600);
        return;
    }

    if (user.role !== "student" && user.role !== "developer") {
        showToast("Chỉ tài khoản Sinh viên mới có quyền nộp đơn ứng tuyển.", "error");
        return;
    }

    openApplyModal();
}

function openApplyModal() {
    const modal = document.getElementById("apply-modal");
    if (!modal) return;
    document.getElementById("apply-modal-job-title").innerText = currentJobData ? currentJobData.title : "";
    document.getElementById("apply-modal-company").innerText = currentJobData ? (currentJobData.company_name || "Nhà tuyển dụng") : "";
    if (currentJobData && currentJobData.shift_type) {
        const sel = document.getElementById("apply-shift");
        if (sel.querySelector(`option[value="${currentJobData.shift_type}"]`)) {
            sel.value = currentJobData.shift_type;
        }
    }
    document.getElementById("apply-error-box").style.display = "none";
    modal.style.display = "flex";
}

function closeApplyModal() {
    const modal = document.getElementById("apply-modal");
    if (modal) modal.style.display = "none";
}

async function submitApplication(e) {
    e.preventDefault();
    const btn = document.getElementById("btn-submit-apply");
    const errBox = document.getElementById("apply-error-box");
    errBox.style.display = "none";

    btn.disabled = true;
    btn.innerText = "Đang gửi đơn...";

    const shift = document.getElementById("apply-shift").value;
    const coverLetter = document.getElementById("apply-cover-letter").value.trim();

    const res = await apiRequest(`/jobs/${encodeURIComponent(currentJobId)}/applications`, {
        method: "POST",
        body: {
            preferred_shift: shift,
            cover_letter: coverLetter
        },
        requireAuth: true
    });

    btn.disabled = false;
    btn.innerText = "Gửi Đơn Ứng Tuyển";

    if (res && res.success) {
        closeApplyModal();
        showToast("Ứng tuyển thành công! Nhà tuyển dụng sẽ xem xét hồ sơ của bạn.", "success");
        // Update apply buttons
        const btnTop = document.getElementById("btn-apply-top");
        const btnBottom = document.getElementById("btn-apply-bottom");
        if (btnTop) {
            btnTop.innerText = "✅ Đã Ứng Tuyển";
            btnTop.disabled = true;
            btnTop.classList.remove("btn-primary");
            btnTop.classList.add("btn-secondary");
        }
        if (btnBottom) {
            btnBottom.innerText = "✅ Đã Ứng Tuyển";
            btnBottom.disabled = true;
            btnBottom.classList.remove("btn-primary");
            btnBottom.classList.add("btn-secondary");
        }
    } else {
        let msg = (res && res.message) ? res.message : "Ứng tuyển không thành công.";
        if (res && res.errors) {
            msg += " " + Object.values(res.errors).flat().map(escapeHtml).join(" ");
        }
        errBox.innerText = msg;
        errBox.style.display = "block";
    }
}

async function handleToggleFavorite() {
    const user = TokenStorage.getUser();

    if (!user) {
        showToast("Vui lòng đăng nhập để lưu việc làm yêu thích.", "error");
        setTimeout(() => {
            window.location.href = `/login?redirect=${encodeURIComponent(window.location.pathname)}`;
        }, 800);
        return;
    }

    if (user.role !== "student") {
        showToast("Chỉ tài khoản Sinh viên mới có tính năng lưu việc làm yêu thích.", "error");
        return;
    }

    // Call REST API POST /favorites/jobs/{id}
    const res = await apiRequest(`/favorites/jobs/${encodeURIComponent(currentJobId)}`, {
        method: "POST"
    });

    if (res && res.success) {
        showToast(res.message || "Đã lưu việc làm vào danh sách yêu thích!", "success");
        const btn = document.getElementById("btn-favorite");
        btn.classList.remove("btn-outline");
        btn.classList.add("btn-secondary");
        btn.innerHTML = `❤️ Đã lưu`;
    } else {
        showToast(res && res.message ? res.message : "Không thể lưu việc làm.", "error");
    }
}
</script>
