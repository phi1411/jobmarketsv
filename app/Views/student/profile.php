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
        <div class="surface-card" style="padding:1.5rem;margin-bottom:1.5rem;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1.5rem;">
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
                <a href="#schedule-section" class="btn btn-outline btn-sm">Cập nhật lịch rảnh</a>
            </div>
        </div>

        <!-- Profile Edit Form -->
        <form id="profile-form" class="form-card">
            <div id="profile-alert" style="display:none;margin-bottom:1.5rem;" class="toast toast-error"></div>

            <div class="form-section">
                <h3 class="form-section-title">
                    1. Thông Tin Cá Nhân Cơ Bản
                </h3>

                <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(260px, 1fr));gap:1.25rem;">
                    <div class="form-group">
                        <label class="form-label">Họ và Tên <span style="color:var(--danger)">*</span></label>
                        <input type="text" id="prof-fullname" class="form-control" placeholder="Nguyễn Văn A" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email Tài Khoản</label>
                        <input type="email" id="prof-email" class="form-control" disabled style="background:#f1f5f9;cursor:not-allowed;">
                        <small class="form-help">Email dùng để nhận thông báo và không thể thay đổi tại đây.</small>
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
            </div>

            <div class="form-section">
                <h3 class="form-section-title">
                    2. Học Vấn & Giới Thiệu
                </h3>

                <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(260px, 1fr));gap:1.25rem;margin-bottom:1.25rem;">
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

                <div class="form-group">
                    <label class="form-label">Giới Thiệu Bản Thân (Bio)</label>
                    <textarea id="prof-bio" class="form-control" rows="3" placeholder="Chia sẻ đôi nét về bạn, mục tiêu công việc part-time hoặc tính cách nổi bật..."></textarea>
                </div>
            </div>

            <div class="form-section">
                <h3 class="form-section-title">
                    3. Kỹ Năng & Hồ Sơ CV
                </h3>

                <div class="form-group" style="margin-bottom:1.25rem;">
                    <label class="form-label">Kỹ Năng Nổi Bật (Chọn các kỹ năng bạn có)</label>
                    <div id="prof-skills-container" style="display:flex;flex-wrap:wrap;gap:0.5rem;padding:0.75rem;background:#f8fafc;border-radius:var(--radius);border:1px solid var(--border);">
                        <!-- Dynamic skills -->
                    </div>
                </div>

                <!-- Active CV Upload Section (CV-P0-01) -->
                <div class="form-group" style="margin-top:1.5rem;">
                    <label class="form-label" style="font-weight:700;display:flex;align-items:center;justify-content:space-between;">
                        <span>Hồ Sơ CV Cá Nhân (Tệp PDF)</span>
                        <span style="font-weight:normal;font-size:0.8rem;color:var(--text-muted);">Tối đa 1 CV hoạt động, dung lượng &le; 5 MB</span>
                    </label>

                    <!-- CV Alert Box -->
                    <div id="cv-feedback-alert" style="display:none;margin-bottom:1rem;padding:0.75rem 1rem;border-radius:var(--radius);font-size:0.9rem;"></div>

                    <!-- Hidden File Input -->
                    <input type="file" id="cv-file-input" accept=".pdf,application/pdf" style="display:none;">

                    <!-- State 1: Loading State -->
                    <div id="cv-state-loading" style="display:none;padding:1.5rem;background:#f8fafc;border:1px solid var(--border);border-radius:var(--radius);text-align:center;">
                        <span style="color:var(--text-muted);font-size:0.9rem;">Đang tải trạng thái CV...</span>
                    </div>

                    <!-- State 2: Uploading State -->
                    <div id="cv-state-uploading" style="display:none;padding:2rem 1.5rem;background:#f8fafc;border:2px dashed var(--primary);border-radius:var(--radius);text-align:center;">
                        <div style="font-size:1.5rem;margin-bottom:0.5rem;display:inline-block;">⏳</div>
                        <div style="font-weight:600;color:var(--primary);margin-bottom:0.25rem;">Đang tải lên và xử lý tệp PDF an toàn...</div>
                        <small style="color:var(--text-muted);">Vui lòng chờ trong giây lát, hệ thống đang kiểm tra định dạng và lưu trữ bảo mật.</small>
                    </div>

                    <!-- State 3: Empty State (No CV uploaded) -->
                    <div id="cv-state-empty" style="display:none;padding:2rem 1.5rem;background:#f8fafc;border:2px dashed #cbd5e1;border-radius:var(--radius);text-align:center;cursor:pointer;transition:border-color 0.2s;"
                         onclick="document.getElementById('cv-file-input').click();">
                        <div style="width:52px;height:52px;margin:0 auto 0.75rem;border-radius:50%;background:#e0e7ff;display:flex;align-items:center;justify-content:center;color:var(--primary);">
                            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                <polyline points="17 8 12 3 7 8"></polyline>
                                <line x1="12" y1="3" x2="12" y2="15"></line>
                            </svg>
                        </div>
                        <div style="font-weight:700;color:var(--dark);font-size:1.05rem;margin-bottom:0.35rem;">Chưa có CV trong hồ sơ</div>
                        <p style="font-size:0.875rem;color:var(--text-muted);margin:0 auto 0.75rem;max-width:480px;line-height:1.5;">
                            Tải lên tệp CV (định dạng PDF) để hoàn thiện hồ sơ và sẵn sàng nộp đơn ứng tuyển việc làm.
                        </p>
                        <div style="display:inline-flex;align-items:center;gap:0.5rem;font-size:0.8rem;color:var(--text-muted);margin-bottom:1rem;">
                            <span>📄 Định dạng: <strong>PDF (.pdf)</strong></span>
                            <span>&bull;</span>
                            <span>Dung lượng tối đa: <strong>5 MB</strong></span>
                        </div>
                        <div>
                            <button type="button" class="btn btn-primary btn-sm" onclick="event.stopPropagation(); document.getElementById('cv-file-input').click();">
                                📤 Chọn tệp PDF từ máy tính
                            </button>
                        </div>
                    </div>

                    <!-- State 4: Active CV State (File uploaded) -->
                    <div id="cv-state-active" style="display:none;padding:1.25rem;background:#ffffff;border:1px solid #10b981;border-left:5px solid #10b981;border-radius:var(--radius);box-shadow:0 1px 3px rgba(0,0,0,0.05);">
                        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;">
                            <div style="display:flex;align-items:center;gap:1rem;min-width:240px;">
                                <div style="width:48px;height:56px;background:#fee2e2;border:1px solid #fca5a5;border-radius:6px;display:flex;flex-direction:column;align-items:center;justify-content:center;color:#ef4444;flex-shrink:0;">
                                    <span style="font-size:1.2rem;line-height:1;">📄</span>
                                    <span style="font-weight:800;font-size:0.7rem;margin-top:2px;">PDF</span>
                                </div>
                                <div>
                                    <div id="cv-active-name" style="font-weight:700;color:var(--dark);word-break:break-all;font-size:1rem;">ten_file_cv.pdf</div>
                                    <div style="display:flex;flex-wrap:wrap;gap:0.75rem;margin-top:0.25rem;font-size:0.82rem;color:var(--text-muted);">
                                        <span id="cv-active-size">0 KB</span>
                                        <span>&bull;</span>
                                        <span id="cv-active-date">Đã tải lên: 01/01/2026</span>
                                    </div>
                                    <div style="margin-top:0.4rem;">
                                        <span style="display:inline-flex;align-items:center;gap:0.25rem;padding:0.2rem 0.6rem;background:#d1fae5;color:#065f46;border-radius:4px;font-size:0.78rem;font-weight:600;">
                                            ✓ CV đang hoạt động (Sẵn sàng ứng tuyển)
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div style="display:flex;align-items:center;gap:0.5rem;flex-wrap:wrap;">
                                <button type="button" id="btn-replace-cv" class="btn btn-outline btn-sm" onclick="document.getElementById('cv-file-input').click();">
                                    Thay thế CV
                                </button>
                                <button type="button" id="btn-delete-cv" class="btn btn-outline btn-sm" style="color:var(--danger);border-color:var(--danger);" onclick="handleDeleteCv();">
                                    Xóa CV
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-section" id="schedule-section">
                <h3 class="form-section-title">
                    4. Lịch Rảnh Trong Tuần (Availability Schedule)
                </h3>
                <p class="form-section-desc">
                    Đánh dấu các ca bạn có thể đi làm part-time để hệ thống ưu tiên gợi ý việc làm khớp lịch học:
                </p>

                <div class="table-responsive" style="margin-bottom:1rem;">
                    <table class="data-table" style="text-align:center;">
                        <thead>
                            <tr>
                                <th style="text-align:left;">Ca làm việc</th>
                                <th style="text-align:center;">Thứ 2</th>
                                <th style="text-align:center;">Thứ 3</th>
                                <th style="text-align:center;">Thứ 4</th>
                                <th style="text-align:center;">Thứ 5</th>
                                <th style="text-align:center;">Thứ 6</th>
                                <th style="text-align:center;">Thứ 7</th>
                                <th style="text-align:center;">Chủ Nhật</th>
                            </tr>
                        </thead>
                        <tbody id="schedule-matrix-body">
                            <!-- Rendered by JS -->
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="form-actions">
                <a href="/student/dashboard" class="btn btn-outline">Hủy Bỏ</a>
                <button type="submit" id="btn-save-profile" class="btn btn-primary btn-lg">
                    Lưu Hồ Sơ Sinh Viên
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
    initCvHandlers();
    await Promise.all([loadLocations(), loadSkills()]);
    await loadProfile();

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
        renderCvState(prof.active_cv);

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
        availability_schedule: scheduleObj
    };

    const res = await apiRequest("/student/profile", {
        method: "PUT",
        body: payload,
        requireAuth: true
    });

    btn.disabled = false;
    btn.innerText = "Lưu Hồ Sơ Sinh Viên";

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

// ----------------------------------------------------
// CV Management Functions (CV-P0-01)
// ----------------------------------------------------

function initCvHandlers() {
    const fileInput = document.getElementById("cv-file-input");
    if (fileInput) {
        fileInput.addEventListener("change", (e) => {
            if (e.target.files && e.target.files[0]) {
                handleCvUpload(e.target.files[0]);
            }
        });
    }

    const dropZone = document.getElementById("cv-state-empty");
    if (dropZone) {
        dropZone.addEventListener("dragover", (e) => {
            e.preventDefault();
            dropZone.style.borderColor = "var(--primary)";
            dropZone.style.background = "#eef2ff";
        });
        dropZone.addEventListener("dragleave", (e) => {
            e.preventDefault();
            dropZone.style.borderColor = "#cbd5e1";
            dropZone.style.background = "#f8fafc";
        });
        dropZone.addEventListener("drop", (e) => {
            e.preventDefault();
            dropZone.style.borderColor = "#cbd5e1";
            dropZone.style.background = "#f8fafc";
            if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files[0]) {
                handleCvUpload(e.dataTransfer.files[0]);
            }
        });
    }
}

function formatBytes(bytes) {
    if (!bytes || bytes <= 0) return "0 Bytes";
    const k = 1024;
    const sizes = ["Bytes", "KB", "MB", "GB"];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + " " + sizes[i];
}

function formatDate(dateStr) {
    if (!dateStr) return "";
    const d = new Date(dateStr);
    if (isNaN(d.getTime())) return dateStr;
    const pad = (n) => String(n).padStart(2, "0");
    return `${pad(d.getDate())}/${pad(d.getMonth() + 1)}/${d.getFullYear()} ${pad(d.getHours())}:${pad(d.getMinutes())}`;
}

function renderCvState(activeCv) {
    const emptyEl = document.getElementById("cv-state-empty");
    const activeEl = document.getElementById("cv-state-active");
    const loadingEl = document.getElementById("cv-state-loading");
    const uploadingEl = document.getElementById("cv-state-uploading");

    if (loadingEl) loadingEl.style.display = "none";
    if (uploadingEl) uploadingEl.style.display = "none";

    if (activeCv && activeCv.file_name) {
        if (emptyEl) emptyEl.style.display = "none";
        if (activeEl) {
            activeEl.style.display = "block";
            document.getElementById("cv-active-name").textContent = activeCv.file_name;
            const sizeStr = typeof formatBytes === "function" ? formatBytes(activeCv.file_size) : (activeCv.file_size + " B");
            document.getElementById("cv-active-size").textContent = sizeStr;
            const dateStr = typeof formatDateTime === "function" ? formatDateTime(activeCv.uploaded_at) : formatDate(activeCv.uploaded_at);
            document.getElementById("cv-active-date").textContent = "Đã tải lên: " + (dateStr || "Gần đây");
        }
    } else {
        if (emptyEl) emptyEl.style.display = "block";
        if (activeEl) activeEl.style.display = "none";
    }
}

function showCvFeedback(message, type = "success") {
    const el = document.getElementById("cv-feedback-alert");
    if (!el) return;
    el.style.display = "block";
    if (type === "success") {
        el.style.background = "#dcfce7";
        el.style.border = "1px solid #86efac";
        el.style.color = "#166534";
    } else {
        el.style.background = "#fee2e2";
        el.style.border = "1px solid #fca5a5";
        el.style.color = "#991b1b";
    }
    el.innerHTML = escapeHtml(message);
    setTimeout(() => {
        el.style.display = "none";
    }, 6000);
}

async function handleCvUpload(file) {
    if (!file) return;

    // Client-side quick validation
    if (!file.name.toLowerCase().endsWith(".pdf") && file.type !== "application/pdf") {
        showCvFeedback("Định dạng tệp không hợp lệ. Hệ thống chỉ chấp nhận tệp PDF (.pdf).", "error");
        return;
    }
    const maxSize = 5 * 1024 * 1024;
    if (file.size > maxSize) {
        showCvFeedback("Dung lượng tệp CV không được vượt quá 5 MB.", "error");
        return;
    }

    const emptyEl = document.getElementById("cv-state-empty");
    const activeEl = document.getElementById("cv-state-active");
    const uploadingEl = document.getElementById("cv-state-uploading");

    if (emptyEl) emptyEl.style.display = "none";
    if (activeEl) activeEl.style.display = "none";
    if (uploadingEl) uploadingEl.style.display = "block";

    const formData = new FormData();
    formData.append("cv_file", file);

    try {
        const res = await apiRequest("/student/cv", {
            method: "POST",
            body: formData,
            requireAuth: true
        });

        if (uploadingEl) uploadingEl.style.display = "none";

        if (res && res.success && res.data) {
            showCvFeedback("Tải lên và lưu trữ CV thành công!", "success");
            showToast("Tải lên CV thành công!", "success");
            renderCvState(res.data);

            // Update completion percentage
            const profRes = await apiRequest("/student/profile", { requireAuth: true });
            if (profRes && profRes.success && profRes.data) {
                const pct = profRes.data.profile_completion_percent || 0;
                document.getElementById("profile-percent-badge").innerText = `${pct}%`;
                document.getElementById("profile-progress-bar").style.width = `${pct}%`;
            }
        } else {
            let errMsg = (res && res.message) ? res.message : "Tải lên CV thất bại.";
            if (res && res.errors && res.errors.cv_file) {
                errMsg = res.errors.cv_file.join(" ");
            }
            showCvFeedback(errMsg, "error");
            showToast(errMsg, "error");

            // Revert to current server state
            const currentRes = await apiRequest("/student/cv", { requireAuth: true });
            renderCvState(currentRes && currentRes.data ? currentRes.data : null);
        }
    } catch (err) {
        if (uploadingEl) uploadingEl.style.display = "none";
        showCvFeedback("Lỗi kết nối khi tải lên CV. Vui lòng thử lại.", "error");
        showToast("Lỗi kết nối khi tải lên CV.", "error");
        const currentRes = await apiRequest("/student/cv", { requireAuth: true });
        renderCvState(currentRes && currentRes.data ? currentRes.data : null);
    }

    // Reset input
    const fileInput = document.getElementById("cv-file-input");
    if (fileInput) fileInput.value = "";
}

async function handleDeleteCv() {
    if (!confirm("Bạn có chắc chắn muốn xóa tệp CV hiện tại khỏi hồ sơ không?")) {
        return;
    }

    const btnDelete = document.getElementById("btn-delete-cv");
    const btnReplace = document.getElementById("btn-replace-cv");
    const origText = btnDelete ? btnDelete.innerText : "Xóa CV";

    if (btnDelete) {
        btnDelete.disabled = true;
        btnDelete.innerText = "Đang xóa...";
    }
    if (btnReplace) btnReplace.disabled = true;

    try {
        const res = await apiRequest("/student/cv", {
            method: "DELETE",
            requireAuth: true
        });

        if (res && res.success) {
            showCvFeedback("Đã xóa CV khỏi hồ sơ.", "success");
            showToast("Đã xóa CV thành công.", "success");
            renderCvState(null);

            // Update completion percentage
            const profRes = await apiRequest("/student/profile", { requireAuth: true });
            if (profRes && profRes.success && profRes.data) {
                const pct = profRes.data.profile_completion_percent || 0;
                document.getElementById("profile-percent-badge").innerText = `${pct}%`;
                document.getElementById("profile-progress-bar").style.width = `${pct}%`;
            }
        } else {
            const errMsg = (res && res.message) ? res.message : "Xóa CV thất bại.";
            showCvFeedback(errMsg, "error");
            showToast(errMsg, "error");
        }
    } catch (err) {
        showCvFeedback("Lỗi kết nối máy chủ khi xóa CV.", "error");
        showToast("Lỗi kết nối máy chủ khi xóa CV.", "error");
    } finally {
        if (btnDelete) {
            btnDelete.disabled = false;
            btnDelete.innerText = origText;
        }
        if (btnReplace) btnReplace.disabled = false;
    }
}
</script>
