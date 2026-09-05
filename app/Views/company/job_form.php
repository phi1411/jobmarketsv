<?php include __DIR__ . "/nav.php"; ?>

<?php
$isEditMode = !empty($isEdit);
$editingJobId = $jobId ?? "";
?>

<div class="container" style="margin-bottom:3rem;max-width:900px;">
    <!-- Breadcrumb / Header -->
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;flex-wrap:wrap;gap:1rem;">
        <div>
            <a href="/company/jobs" style="color:var(--primary);text-decoration:none;font-size:0.88rem;font-weight:600;">
                &larr; Quay lại danh sách tin
            </a>
            <h2 style="font-size:1.4rem;font-weight:800;color:var(--dark);margin-top:0.35rem;margin-bottom:0;">
                <?= $isEditMode ? "✏️ Chỉnh Sửa Tin Tuyển Dụng" : "➕ Đăng Tin Tuyển Dụng Mới" ?>
            </h2>
        </div>
        <div id="company-verify-notice" style="display:none;"></div>
    </div>

    <!-- Loading Skeleton for Edit -->
    <div id="job-form-loading" style="display:<?= $isEditMode ? 'block' : 'none' ?>;">
        <div class="job-card skeleton" style="height:450px;"></div>
    </div>

    <!-- Alert Box -->
    <div id="job-form-alert" style="display:none;margin-bottom:1.5rem;" class="toast"></div>

    <!-- Form Container -->
    <div id="job-form-container" style="background:#fff;border-radius:var(--radius);border:1px solid var(--border);padding:2rem;display:<?= $isEditMode ? 'none' : 'block' ?>;">
        <form id="form-job" onsubmit="handleSubmitJob(event)">
            <!-- 1. Tiêu đề việc làm -->
            <div class="form-group" style="margin-bottom:1.5rem;">
                <label class="form-label" for="job-title">Tiêu đề việc làm <span style="color:var(--danger)">*</span></label>
                <input type="text" id="job-title" class="form-control" placeholder="Ví dụ: Nhân viên phục vụ & Phụ quầy Part-time (Ca sáng/tối)" required minlength="5">
                <small style="color:var(--text-muted);font-size:0.8rem;">Tối thiểu 5 ký tự. Nên ghi rõ chức danh và ca làm việc để thu hút sinh viên.</small>
            </div>

            <!-- 2. Ngành nghề & Địa điểm -->
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;margin-bottom:1.5rem;">
                <div class="form-group">
                    <label class="form-label" for="job-category">Ngành nghề / Lĩnh vực <span style="color:var(--danger)">*</span></label>
                    <select id="job-category" class="form-control" required>
                        <option value="">-- Chọn ngành nghề --</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="job-location">Khu vực / Quận làm việc <span style="color:var(--danger)">*</span></label>
                    <select id="job-location" class="form-control" required>
                        <option value="">-- Chọn địa điểm --</option>
                    </select>
                </div>
            </div>

            <!-- 3. Hình thức làm việc & Chế độ làm việc -->
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;margin-bottom:1.5rem;">
                <div class="form-group">
                    <label class="form-label" for="job-work-type">Hình thức tuyển dụng</label>
                    <select id="job-work-type" class="form-control">
                        <option value="part_time">Bán thời gian (Part-time)</option>
                        <option value="internship">Thực tập sinh (Internship)</option>
                        <option value="freelance">Làm tự do / Theo dự án (Freelance)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="job-work-mode">Chế độ làm việc</label>
                    <select id="job-work-mode" class="form-control">
                        <option value="onsite">Trực tiếp tại cơ sở (On-site)</option>
                        <option value="hybrid">Kết hợp linh hoạt (Hybrid)</option>
                        <option value="remote">Làm việc từ xa (Remote)</option>
                    </select>
                </div>
            </div>

            <!-- 4. Lương: Loại lương, Min, Max -->
            <div style="background:#f8fafc;border:1px solid var(--border);border-radius:var(--radius);padding:1.25rem;margin-bottom:1.5rem;">
                <label style="font-weight:700;color:var(--dark);display:block;margin-bottom:0.75rem;">💰 Chế độ Tiền lương</label>
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:1rem;">
                    <div class="form-group">
                        <label class="form-label" for="job-salary-type">Loại hình trả lương</label>
                        <select id="job-salary-type" class="form-control">
                            <option value="hourly">Theo giờ (Hourly - VND/h)</option>
                            <option value="monthly">Theo tháng (Monthly - VND/tháng)</option>
                            <option value="daily">Theo ngày / ca (Daily)</option>
                            <option value="negotiable">Thỏa thuận (Negotiable)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="job-salary-min">Lương tối thiểu (VND)</label>
                        <input type="number" id="job-salary-min" class="form-control" placeholder="25000" min="0" step="1000">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="job-salary-max">Lương tối đa (VND)</label>
                        <input type="number" id="job-salary-max" class="form-control" placeholder="35000" min="0" step="1000">
                    </div>
                </div>
            </div>

            <!-- 5. Ca làm việc & Lịch làm chi tiết -->
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;margin-bottom:1.5rem;">
                <div class="form-group">
                    <label class="form-label" for="job-shift-type">Ca làm việc chính</label>
                    <select id="job-shift-type" class="form-control">
                        <option value="morning">🌅 Ca Sáng</option>
                        <option value="afternoon">☀️ Ca Chiều</option>
                        <option value="evening">🌙 Ca Tối</option>
                        <option value="night">🌃 Ca Đêm</option>
                        <option value="rotating">🔄 Xoay ca</option>
                        <option value="weekend">📅 Cuối tuần</option>
                        <option value="flexible">⚡ Linh hoạt theo lịch học</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="job-working-schedule">Lịch làm việc chi tiết</label>
                    <input type="text" id="job-working-schedule" class="form-control" placeholder="Ví dụ: Đăng ký tối thiểu 4 buổi/tuần, 4-6h/buổi">
                </div>
            </div>

            <!-- 6. Số lượng tuyển & Hạn nộp -->
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;margin-bottom:1.5rem;">
                <div class="form-group">
                    <label class="form-label" for="job-quantity">Số lượng cần tuyển</label>
                    <input type="number" id="job-quantity" class="form-control" placeholder="Ví dụ: 5" min="1">
                </div>
                <div class="form-group">
                    <label class="form-label" for="job-deadline">Hạn nộp hồ sơ <span style="color:var(--danger)">*</span></label>
                    <input type="date" id="job-deadline" class="form-control" required>
                    <small style="color:var(--text-muted);font-size:0.8rem;">Ngày hết hạn phải ở tương lai.</small>
                </div>
            </div>

            <!-- 7. Kỹ năng yêu cầu (Skills) -->
            <div class="form-group" style="margin-bottom:1.5rem;">
                <label class="form-label">Kỹ năng / Phẩm chất mong muốn</label>
                <div id="job-skills-container" style="display:flex;flex-wrap:wrap;gap:0.5rem;padding:0.75rem;background:#f8fafc;border:1px solid var(--border);border-radius:var(--radius);max-height:150px;overflow-y:auto;">
                    <!-- Checkboxes rendered dynamically -->
                </div>
            </div>

            <!-- 8. Mô tả công việc -->
            <div class="form-group" style="margin-bottom:1.5rem;">
                <label class="form-label" for="job-desc">Mô tả công việc <span style="color:var(--danger)">*</span></label>
                <textarea id="job-desc" rows="5" class="form-control" placeholder="Chi tiết các nhiệm vụ hàng ngày sinh viên sẽ thực hiện..." required minlength="10"></textarea>
                <small style="color:var(--text-muted);font-size:0.8rem;">Tối thiểu 10 ký tự.</small>
            </div>

            <!-- 9. Yêu cầu ứng viên -->
            <div class="form-group" style="margin-bottom:1.5rem;">
                <label class="form-label" for="job-req">Yêu cầu ứng viên</label>
                <textarea id="job-req" rows="4" class="form-control" placeholder="Sinh viên năm 1-4, chăm chỉ, đúng giờ, giao tiếp tốt, không yêu cầu kinh nghiệm..."></textarea>
            </div>

            <!-- 10. Quyền lợi được hưởng -->
            <div class="form-group" style="margin-bottom:1.5rem;">
                <label class="form-label" for="job-benefits">Quyền lợi & Đãi ngộ</label>
                <textarea id="job-benefits" rows="4" class="form-control" placeholder="Hỗ trợ gửi xe, phụ cấp ăn trưa/tối, thưởng theo năng suất, linh hoạt đổi ca thi cử..."></textarea>
            </div>

            <!-- 11. Trạng thái xuất bản -->
            <div style="background:#f8fafc;border:1px solid var(--border);border-radius:var(--radius);padding:1.25rem;margin-bottom:2rem;">
                <label class="form-label" for="job-status" style="font-weight:700;">Trạng thái xuất bản tin</label>
                <select id="job-status" class="form-control" style="font-weight:600;">
                    <option value="published">🟢 Công khai tuyển dụng ngay (Published)</option>
                    <option value="draft">📝 Lưu bản nháp (Draft)</option>
                    <option value="pending_approval">⏳ Gửi chờ xét duyệt (Pending Approval)</option>
                    <?php if ($isEditMode): ?>
                    <option value="closed">🔒 Đóng tuyển dụng (Closed)</option>
                    <?php endif; ?>
                </select>
                <small id="status-warning" style="display:none;color:#b45309;margin-top:0.4rem;font-weight:600;">
                    ⚠️ Lưu ý: Doanh nghiệp của bạn chưa được xác minh (verified) nên chưa thể công khai trực tiếp. Vui lòng chọn "Lưu bản nháp" hoặc "Gửi chờ xét duyệt".
                </small>
            </div>

            <!-- Submit Buttons -->
            <div style="display:flex;justify-content:flex-end;gap:1rem;border-top:1px solid var(--border);padding-top:1.5rem;">
                <a href="/company/jobs" class="btn btn-outline">Hủy Bỏ</a>
                <button type="submit" id="btn-submit-job" class="btn btn-primary">
                    <?= $isEditMode ? "💾 Cập Nhật Tin Tuyển Dụng" : "🚀 Đăng Tin Tuyển Dụng" ?>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
const IS_EDIT_MODE = <?= $isEditMode ? "true" : "false" ?>;
const EDIT_JOB_ID = "<?= htmlspecialchars($editingJobId, ENT_QUOTES, 'UTF-8') ?>";
let companyVerificationStatus = "pending";

document.addEventListener("DOMContentLoaded", async () => {
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

    // Set default deadline to +30 days
    if (!IS_EDIT_MODE) {
        const d = new Date();
        d.setDate(d.getDate() + 30);
        document.getElementById("job-deadline").value = d.toISOString().split('T')[0];
    }

    await Promise.all([loadCategories(), loadLocations(), loadSkills(), checkCompanyVerification()]);

    if (IS_EDIT_MODE && EDIT_JOB_ID) {
        loadJobForEditing(EDIT_JOB_ID);
    }
});

async function checkCompanyVerification() {
    const res = await apiRequest("/company/profile", { requireAuth: true });
    if (res && res.success && res.data) {
        companyVerificationStatus = res.data.verification_status || "pending";
        const warningEl = document.getElementById("status-warning");
        const statusSel = document.getElementById("job-status");

        if (companyVerificationStatus !== "verified") {
            warningEl.style.display = "block";
            // Default to draft or pending if unverified
            if (!IS_EDIT_MODE) {
                statusSel.value = "draft";
            }
        }
    }
}

async function loadCategories() {
    const res = await apiRequest("/categories");
    if (res && res.success && Array.isArray(res.data)) {
        const sel = document.getElementById("job-category");
        res.data.forEach(c => {
            const opt = document.createElement("option");
            opt.value = c.id;
            opt.textContent = c.name;
            sel.appendChild(opt);
        });
    }
}

async function loadLocations() {
    const res = await apiRequest("/locations");
    if (res && res.success && Array.isArray(res.data)) {
        const sel = document.getElementById("job-location");
        res.data.forEach(l => {
            const opt = document.createElement("option");
            opt.value = l.id;
            opt.textContent = l.name;
            sel.appendChild(opt);
        });
    }
}

async function loadSkills() {
    const res = await apiRequest("/skills");
    if (res && res.success && Array.isArray(res.data)) {
        const container = document.getElementById("job-skills-container");
        container.innerHTML = res.data.map(s => `
            <label style="display:inline-flex;align-items:center;gap:0.35rem;padding:0.3rem 0.6rem;background:#fff;border:1px solid var(--border);border-radius:var(--radius);font-size:0.85rem;cursor:pointer;">
                <input type="checkbox" name="skill_id" value="${escapeHtml(s.id)}">
                ${escapeHtml(s.name)}
            </label>
        `).join("");
    }
}

async function loadJobForEditing(id) {
    const loadingEl = document.getElementById("job-form-loading");
    const container = document.getElementById("job-form-container");

    loadingEl.style.display = "block";
    container.style.display = "none";

    const res = await apiRequest(`/jobs/${encodeURIComponent(id)}`, { requireAuth: true });
    loadingEl.style.display = "none";

    if (res && res.success && res.data) {
        container.style.display = "block";
        const j = res.data;

        document.getElementById("job-title").value = j.title || "";
        document.getElementById("job-category").value = j.category_id || "";
        document.getElementById("job-location").value = j.location_id || "";
        document.getElementById("job-work-type").value = j.work_type || "part_time";
        document.getElementById("job-work-mode").value = j.work_mode || "onsite";
        document.getElementById("job-salary-type").value = j.salary_type || "hourly";
        document.getElementById("job-salary-min").value = j.salary_min || "";
        document.getElementById("job-salary-max").value = j.salary_max || "";
        document.getElementById("job-shift-type").value = j.shift_type || "morning";
        document.getElementById("job-working-schedule").value = j.working_schedule || "";
        document.getElementById("job-quantity").value = j.quantity || "";
        document.getElementById("job-deadline").value = j.application_deadline ? j.application_deadline.substring(0, 10) : "";
        document.getElementById("job-desc").value = j.description || "";
        document.getElementById("job-req").value = j.requirements || "";
        document.getElementById("job-benefits").value = j.benefits || "";
        document.getElementById("job-status").value = j.status || "published";

        // Pre-check skills if available
        if (Array.isArray(j.skills)) {
            const skillIds = j.skills.map(s => typeof s === "object" ? s.id : s);
            document.querySelectorAll("input[name='skill_id']").forEach(cb => {
                if (skillIds.includes(cb.value)) cb.checked = true;
            });
        }
    } else {
        showToast((res && res.message) ? res.message : "Không thể tải thông tin tin việc làm.", "error");
        setTimeout(() => { window.location.href = "/company/jobs"; }, 1500);
    }
}

async function handleSubmitJob(e) {
    e.preventDefault();
    const btn = document.getElementById("btn-submit-job");
    const alertBox = document.getElementById("job-form-alert");
    alertBox.style.display = "none";

    // Collect selected skills
    const selectedSkills = [];
    document.querySelectorAll("input[name='skill_id']:checked").forEach(cb => {
        selectedSkills.push(cb.value);
    });

    const statusVal = document.getElementById("job-status").value;

    // UX Pre-check for unverified company trying to publish
    if (statusVal === "published" && companyVerificationStatus !== "verified") {
        alertBox.className = "toast toast-error";
        alertBox.innerText = "Công ty của bạn chưa được xác minh (verified) nên chưa thể công khai tin tuyển dụng. Vui lòng chọn 'Lưu bản nháp' (Draft) hoặc 'Chờ duyệt' (Pending Approval).";
        alertBox.style.display = "block";
        window.scrollTo({ top: 0, behavior: 'smooth' });
        return;
    }

    const payload = {
        title: document.getElementById("job-title").value.trim(),
        category_id: document.getElementById("job-category").value || null,
        location_id: document.getElementById("job-location").value || null,
        work_type: document.getElementById("job-work-type").value,
        work_mode: document.getElementById("job-work-mode").value,
        salary_type: document.getElementById("job-salary-type").value,
        salary_min: document.getElementById("job-salary-min").value ? parseInt(document.getElementById("job-salary-min").value) : null,
        salary_max: document.getElementById("job-salary-max").value ? parseInt(document.getElementById("job-salary-max").value) : null,
        shift_type: document.getElementById("job-shift-type").value,
        working_schedule: document.getElementById("job-working-schedule").value.trim() || null,
        quantity: document.getElementById("job-quantity").value ? parseInt(document.getElementById("job-quantity").value) : 1,
        application_deadline: document.getElementById("job-deadline").value || null,
        description: document.getElementById("job-desc").value.trim(),
        requirements: document.getElementById("job-req").value.trim() || null,
        benefits: document.getElementById("job-benefits").value.trim() || null,
        status: statusVal,
        required_skills: selectedSkills
    };

    btn.disabled = true;
    btn.innerText = "Đang lưu...";

    const url = IS_EDIT_MODE ? `/jobs/${encodeURIComponent(EDIT_JOB_ID)}` : "/jobs";
    const method = IS_EDIT_MODE ? "PUT" : "POST";

    const res = await apiRequest(url, {
        method: method,
        body: payload,
        requireAuth: true
    });

    btn.disabled = false;
    btn.innerText = IS_EDIT_MODE ? "💾 Cập Nhật Tin Tuyển Dụng" : "🚀 Đăng Tin Tuyển Dụng";

    if (res && res.success) {
        showToast(IS_EDIT_MODE ? "Cập nhật tin tuyển dụng thành công!" : "Đăng tin tuyển dụng thành công!", "success");
        setTimeout(() => {
            window.location.href = "/company/jobs";
        }, 600);
    } else {
        let msg = (res && res.message) ? res.message : "Thao tác thất bại.";
        if (res && res.errors) {
            msg += "\n" + Object.values(res.errors).flat().map(escapeHtml).join(" | ");
        }
        alertBox.className = "toast toast-error";
        alertBox.innerText = msg;
        alertBox.style.display = "block";
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
}
</script>
