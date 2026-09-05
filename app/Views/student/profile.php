<?php include __DIR__ . "/nav.php"; ?>

<div class="container" style="margin-bottom:3rem;">
    <!-- Loading State -->
    <div id="profile-loading" style="text-align:center;padding:3rem 0;">
        <div class="job-card skeleton" style="height:150px;margin-bottom:1rem;"></div>
        <div class="job-card skeleton" style="height:400px;"></div>
    </div>

    <!-- Main Profile Content -->
    <div id="profile-content" style="display:none;">
        <!-- Completion Progress Card -->
        <div style="background:#fff;border-radius:var(--radius);border:1px solid var(--border);padding:1.5rem;margin-bottom:2rem;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1.5rem;">
            <div style="flex:1;min-width:260px;">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.5rem;">
                    <span style="font-weight:700;color:var(--dark);font-size:1rem;">Mức Độ Hoàn Thiện Hồ Sơ</span>
                    <span id="profile-percent-badge" style="font-weight:800;color:var(--primary);font-size:1.1rem;">0%</span>
                </div>
                <div style="background:#e2e8f0;height:10px;border-radius:5px;overflow:hidden;">
                    <div id="profile-progress-bar" style="background:var(--primary);height:100%;width:0%;transition:width 0.4s ease;"></div>
                </div>
                <p style="font-size:0.8rem;color:var(--text-muted);margin:0.5rem 0 0;">
                    Hồ sơ trên 80% sẽ tăng 3 lần cơ hội được nhà tuyển dụng xem và phản hồi nhanh.
                </p>
            </div>
            <div>
                <a href="#schedule-section" class="btn btn-outline btn-sm">⏰ Cập nhật lịch rảnh</a>
            </div>
        </div>

        <!-- Profile Edit Form -->
        <form id="profile-form" style="background:#fff;border-radius:var(--radius);border:1px solid var(--border);padding:2rem;">
            <div id="profile-alert" style="display:none;margin-bottom:1.5rem;" class="toast toast-error"></div>

            <h3 style="font-size:1.15rem;font-weight:700;color:var(--dark);margin-bottom:1.25rem;padding-bottom:0.5rem;border-bottom:1px solid var(--border);">
                1. Thông Tin Cá Nhân Cơ Bản
            </h3>

            <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(260px, 1fr));gap:1.25rem;margin-bottom:1.5rem;">
                <div class="form-group">
                    <label class="form-label">Họ và Tên <span style="color:var(--danger)">*</span></label>
                    <input type="text" id="prof-fullname" class="form-control" placeholder="Nguyễn Văn A" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Email Tài Khoản</label>
                    <input type="email" id="prof-email" class="form-control" disabled style="background:#f1f5f9;cursor:not-allowed;">
                    <small style="font-size:0.75rem;color:var(--text-muted);">Email dùng để nhận thông báo và không thể thay đổi tại đây.</small>
                </div>
                <div class="form-group">
                    <label class="form-label">Số Điện Thoại <span style="color:var(--danger)">*</span></label>
                    <input type="tel" id="prof-phone" class="form-control" placeholder="0988776655" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Ngày Sinh</label>
                    <input type="date" id="prof-dob" class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label">Giới Tính</label>
                    <select id="prof-gender" class="form-control">
                        <option value="">-- Chọn giới tính --</option>
                        <option value="male">Nam</option>
                        <option value="female">Nữ</option>
                        <option value="other">Khác</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Khu Vực Sinh Sống / Trọ</label>
                    <select id="prof-location" class="form-control">
                        <option value="">-- Chọn Quận / Huyện --</option>
                        <!-- Dynamic locations -->
                    </select>
                </div>
            </div>

            <h3 style="font-size:1.15rem;font-weight:700;color:var(--dark);margin-bottom:1.25rem;padding-bottom:0.5rem;border-bottom:1px solid var(--border);">
                2. Học Vấn & Giới Thiệu
            </h3>

            <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(260px, 1fr));gap:1.25rem;margin-bottom:1.5rem;">
                <div class="form-group">
                    <label class="form-label">Trường Đại Học / Cao Đẳng</label>
                    <input type="text" id="prof-university" class="form-control" placeholder="ĐH Quốc Gia, ĐH Bách Khoa, ĐH Ngoại Thương...">
                </div>
                <div class="form-group">
                    <label class="form-label">Chuyên Ngành</label>
                    <input type="text" id="prof-major" class="form-control" placeholder="Công nghệ thông tin, Marketing, Ngôn ngữ Anh...">
                </div>
                <div class="form-group">
                    <label class="form-label">Sinh Viên Năm</label>
                    <select id="prof-academic-year" class="form-control">
                        <option value="">-- Chọn năm học --</option>
                        <option value="1">Năm nhất (Năm 1)</option>
                        <option value="2">Năm 2</option>
                        <option value="3">Năm 3</option>
                        <option value="4">Năm 4</option>
                        <option value="5">Năm 5+</option>
                    </select>
                </div>
            </div>

            <div class="form-group" style="margin-bottom:1.5rem;">
                <label class="form-label">Giới Thiệu Bản Thân (Bio)</label>
                <textarea id="prof-bio" class="form-control" rows="3" placeholder="Chia sẻ đôi nét về bạn, mục tiêu công việc part-time hoặc tính cách nổi bật..."></textarea>
            </div>

            <h3 style="font-size:1.15rem;font-weight:700;color:var(--dark);margin-bottom:1.25rem;padding-bottom:0.5rem;border-bottom:1px solid var(--border);">
                3. Kỹ Năng & Hồ Sơ CV
            </h3>

            <div class="form-group" style="margin-bottom:1.5rem;">
                <label class="form-label">Kỹ Năng Nổi Bật (Chọn các kỹ năng bạn có)</label>
                <div id="prof-skills-container" style="display:flex;flex-wrap:wrap;gap:0.5rem;padding:0.75rem;background:#f8fafc;border-radius:var(--radius);border:1px solid var(--border);">
                    <!-- Dynamic skills -->
                </div>
            </div>

            <div class="form-group" style="margin-bottom:1.5rem;">
                <label class="form-label">Đường Dẫn CV Trực Tuyến (Google Drive, Canva, TopCV...)</label>
                <div style="display:flex;gap:0.75rem;">
                    <input type="url" id="prof-cv-url" class="form-control" placeholder="https://drive.google.com/file/d/.../view">
                    <a id="btn-view-cv" href="#" target="_blank" class="btn btn-outline btn-sm" style="display:none;white-space:nowrap;align-items:center;">
                        🔗 Xem CV
                    </a>
                </div>
                <small style="font-size:0.75rem;color:var(--text-muted);">
                    Hệ thống lưu link CV đã công khai quyền xem để nhà tuyển dụng dễ dàng mở trực tiếp.
                </small>
            </div>

            <h3 id="schedule-section" style="font-size:1.15rem;font-weight:700;color:var(--dark);margin-bottom:1.25rem;padding-bottom:0.5rem;border-bottom:1px solid var(--border);">
                4. Lịch Rảnh Trong Tuần (Availability Schedule)
            </h3>
            <p style="font-size:0.85rem;color:var(--text-muted);margin-bottom:1rem;">
                Đánh dấu các ca bạn có thể đi làm part-time để hệ thống ưu tiên gợi ý việc làm khớp lịch học:
            </p>

            <div style="overflow-x:auto;margin-bottom:2rem;">
                <table style="width:100%;border-collapse:collapse;text-align:center;font-size:0.88rem;">
                    <thead>
                        <tr style="background:#f1f5f9;border-bottom:2px solid var(--border);">
                            <th style="padding:0.75rem;text-align:left;">Ca làm việc</th>
                            <th style="padding:0.75rem;">Thứ 2</th>
                            <th style="padding:0.75rem;">Thứ 3</th>
                            <th style="padding:0.75rem;">Thứ 4</th>
                            <th style="padding:0.75rem;">Thứ 5</th>
                            <th style="padding:0.75rem;">Thứ 6</th>
                            <th style="padding:0.75rem;">Thứ 7</th>
                            <th style="padding:0.75rem;">Chủ Nhật</th>
                        </tr>
                    </thead>
                    <tbody id="schedule-matrix-body">
                        <!-- Rendered by JS -->
                    </tbody>
                </table>
            </div>

            <div style="display:flex;justify-content:flex-end;gap:1rem;border-top:1px solid var(--border);padding-top:1.5rem;">
                <a href="/student/dashboard" class="btn btn-outline">Hủy Bỏ</a>
                <button type="submit" id="btn-save-profile" class="btn btn-primary btn-lg">
                    💾 Lưu Hồ Sơ Sinh Viên
                </button>
            </div>
        </form>
    </div>
</div>

<script>
const DAYS = [
    { key: "monday", label: "T2" },
    { key: "tuesday", label: "T3" },
    { key: "wednesday", label: "T4" },
    { key: "thursday", label: "T5" },
    { key: "friday", label: "T6" },
    { key: "saturday", label: "T7" },
    { key: "sunday", label: "CN" }
];

const SHIFTS = [
    { key: "morning", label: "🌅 Ca Sáng (08:00 - 12:00)" },
    { key: "afternoon", label: "☀️ Ca Chiều (13:00 - 17:00)" },
    { key: "evening", label: "🌙 Ca Tối (18:00 - 22:00)" }
];

document.addEventListener("DOMContentLoaded", async () => {
    // 1. Auth Guard
    if (!TokenStorage.isLoggedIn()) {
        showToast("Vui lòng đăng nhập để xem hồ sơ.", "error");
        window.location.href = `/login?login_required=1&redirect=${encodeURIComponent(window.location.pathname)}`;
        return;
    }
    const user = TokenStorage.getUser();
    if (!user || (user.role !== "student" && user.role !== "developer")) {
        showToast("Chỉ tài khoản Sinh viên mới có quyền truy cập hồ sơ sinh viên.", "error");
        setTimeout(() => { window.location.href = "/viec-lam"; }, 1000);
        return;
    }

    renderScheduleTable();
    await Promise.all([loadLocations(), loadSkills()]);
    await loadProfile();

    // CV Preview watcher
    const cvInput = document.getElementById("prof-cv-url");
    const cvBtn = document.getElementById("btn-view-cv");
    cvInput.addEventListener("input", () => {
        const val = cvInput.value.trim();
        if (val.startsWith("http")) {
            cvBtn.href = val;
            cvBtn.style.display = "inline-flex";
        } else {
            cvBtn.style.display = "none";
        }
    });

    // Form Submit
    document.getElementById("profile-form").addEventListener("submit", handleSaveProfile);
});

function renderScheduleTable() {
    const tbody = document.getElementById("schedule-matrix-body");
    tbody.innerHTML = SHIFTS.map(shift => `
        <tr style="border-bottom:1px solid var(--border);">
            <td style="padding:0.75rem;text-align:left;font-weight:600;color:var(--dark);">${escapeHtml(shift.label)}</td>
            ${DAYS.map(day => `
                <td style="padding:0.75rem;">
                    <input type="checkbox" class="schedule-check" data-day="${escapeHtml(day.key)}" data-shift="${escapeHtml(shift.key)}" style="width:18px;height:18px;cursor:pointer;">
                </td>
            `).join("")}
        </tr>
    `).join("");
}

async function loadLocations() {
    const res = await apiRequest("/locations");
    const sel = document.getElementById("prof-location");
    if (res && res.success && Array.isArray(res.data)) {
        res.data.forEach(loc => {
            const opt = document.createElement("option");
            opt.value = loc.id;
            opt.textContent = `${loc.name} (${loc.city || 'Hà Nội'})`;
            sel.appendChild(opt);
        });
    }
}

async function loadSkills() {
    const res = await apiRequest("/skills");
    const container = document.getElementById("prof-skills-container");
    if (res && res.success && Array.isArray(res.data)) {
        container.innerHTML = res.data.map(sk => `
            <label style="display:inline-flex;align-items:center;gap:0.4rem;padding:0.35rem 0.65rem;background:#fff;border:1px solid var(--border);border-radius:var(--radius);cursor:pointer;font-size:0.85rem;">
                <input type="checkbox" class="skill-checkbox" value="${escapeHtml(sk.id)}" style="cursor:pointer;">
                <span>${escapeHtml(sk.name)}</span>
            </label>
        `).join("");
    }
}

async function loadProfile() {
    const loadingEl = document.getElementById("profile-loading");
    const contentEl = document.getElementById("profile-content");

    const res = await apiRequest("/student/profile", { requireAuth: true });
    loadingEl.style.display = "none";

    if (res && res.success && res.data) {
        contentEl.style.display = "block";
        const prof = res.data;

        // Progress
        const percent = prof.profile_completion_percent || 0;
        document.getElementById("profile-percent-badge").innerText = `${percent}%`;
        document.getElementById("profile-progress-bar").style.width = `${percent}%`;

        // Fields
        document.getElementById("prof-fullname").value = prof.full_name || "";
        document.getElementById("prof-email").value = prof.email || "";
        document.getElementById("prof-phone").value = prof.phone || "";
        document.getElementById("prof-dob").value = prof.date_of_birth || "";
        document.getElementById("prof-gender").value = prof.gender || "";
        document.getElementById("prof-university").value = prof.university || "";
        document.getElementById("prof-major").value = prof.major || "";
        document.getElementById("prof-academic-year").value = prof.academic_year || "";
        document.getElementById("prof-bio").value = prof.bio || "";
        document.getElementById("prof-location").value = prof.location_id || "";

        // CV
        if (prof.cv_url) {
            document.getElementById("prof-cv-url").value = prof.cv_url;
            const cvBtn = document.getElementById("btn-view-cv");
            cvBtn.href = prof.cv_url;
            cvBtn.style.display = "inline-flex";
        }

        // Skills
        const selectedSkills = Array.isArray(prof.skill_ids) ? prof.skill_ids : [];
        document.querySelectorAll(".skill-checkbox").forEach(cb => {
            cb.checked = selectedSkills.includes(cb.value);
        });

        // Schedule Matrix
        const sched = prof.available_schedule || prof.availability_schedule || {};
        if (typeof sched === "object" && sched !== null) {
            document.querySelectorAll(".schedule-check").forEach(cb => {
                const day = cb.getAttribute("data-day");
                const shift = cb.getAttribute("data-shift");
                if (sched[day] && Array.isArray(sched[day])) {
                    cb.checked = sched[day].includes(shift);
                }
            });
        }
    } else {
        showToast((res && res.message) ? res.message : "Không thể tải hồ sơ sinh viên.", "error");
    }
}

async function handleSaveProfile(e) {
    e.preventDefault();
    const btn = document.getElementById("btn-save-profile");
    const alertBox = document.getElementById("profile-alert");
    alertBox.style.display = "none";

    btn.disabled = true;
    btn.innerText = "Đang lưu hồ sơ...";

    // Collect skills
    const selectedSkillIds = [];
    document.querySelectorAll(".skill-checkbox:checked").forEach(cb => {
        selectedSkillIds.push(cb.value);
    });

    // Collect schedule matrix
    const scheduleObj = {};
    DAYS.forEach(day => {
        scheduleObj[day.key] = [];
    });
    document.querySelectorAll(".schedule-check:checked").forEach(cb => {
        const day = cb.getAttribute("data-day");
        const shift = cb.getAttribute("data-shift");
        if (scheduleObj[day]) {
            scheduleObj[day].push(shift);
        }
    });

    // Construct Payload (EXCLUDE SENSITIVE: role, user_id, profile_completion_percent)
    const payload = {
        full_name: document.getElementById("prof-fullname").value.trim(),
        phone: document.getElementById("prof-phone").value.trim(),
        date_of_birth: document.getElementById("prof-dob").value || null,
        gender: document.getElementById("prof-gender").value || null,
        university: document.getElementById("prof-university").value.trim(),
        major: document.getElementById("prof-major").value.trim(),
        academic_year: document.getElementById("prof-academic-year").value ? parseInt(document.getElementById("prof-academic-year").value) : null,
        bio: document.getElementById("prof-bio").value.trim(),
        location_id: document.getElementById("prof-location").value || null,
        skill_ids: selectedSkillIds,
        availability_schedule: scheduleObj,
        cv_url: document.getElementById("prof-cv-url").value.trim() || null
    };

    const res = await apiRequest("/student/profile", {
        method: "PUT",
        body: payload,
        requireAuth: true
    });

    btn.disabled = false;
    btn.innerText = "💾 Lưu Hồ Sơ Sinh Viên";

    if (res && res.success) {
        showToast("Lưu hồ sơ sinh viên thành công!", "success");
        if (res.data && res.data.profile_completion_percent !== undefined) {
            const pct = res.data.profile_completion_percent;
            document.getElementById("profile-percent-badge").innerText = `${pct}%`;
            document.getElementById("profile-progress-bar").style.width = `${pct}%`;
        }
        window.scrollTo({ top: 0, behavior: "smooth" });
    } else {
        let errMsg = (res && res.message) ? res.message : "Cập nhật hồ sơ thất bại.";
        if (res && res.errors) {
            const list = Object.values(res.errors).flat().map(escapeHtml).join("<br>&bull; ");
            errMsg += `<br>&bull; ${list}`;
        }
        alertBox.innerHTML = errMsg;
        alertBox.style.display = "block";
        window.scrollTo({ top: 0, behavior: "smooth" });
    }
}
</script>
