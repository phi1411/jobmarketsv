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
                <?= $isEditMode ? "Chỉnh Sửa Tin Tuyển Dụng" : "Đăng Tin Tuyển Dụng Mới" ?>
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
    <div id="job-form-container" class="form-card" style="display:<?= $isEditMode ? 'none' : 'block' ?>;">
        <form id="form-job" onsubmit="handleSubmitJob(event)">
            <!-- 1. Tiêu đề việc làm -->
            <div class="form-group" style="margin-bottom:1.25rem;">
                <label class="form-label" for="job-title">Tiêu đề việc làm <span style="color:var(--danger)">*</span></label>
                <input type="text" id="job-title" class="form-control" placeholder="Ví dụ: Nhân viên phục vụ & Phụ quầy Part-time (Ca sáng/tối)" required minlength="5">
                <small class="form-help">Tối thiểu 5 ký tự. Nên ghi rõ chức danh và ca làm việc để thu hút sinh viên.</small>
            </div>

            <!-- 2. Ngành nghề & Địa điểm -->
            <div class="form-grid-2" style="margin-bottom:1.25rem;">
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
            <div class="form-grid-2" style="margin-bottom:1.25rem;">
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
            <div class="fieldset-card">
                <label class="fieldset-card-title">Chế độ Tiền lương</label>
                <div class="form-grid-3">
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
            <div class="form-grid-2" style="margin-bottom:1.25rem;">
                <div class="form-group">
                    <label class="form-label" for="job-shift-type">Ca làm việc chính</label>
                    <select id="job-shift-type" class="form-control">
                        <option value="">Không yêu cầu ca cố định</option>
                        <option value="morning">Ca Sáng (08:00 - 12:00)</option>
                        <option value="afternoon">Ca Chiều (13:00 - 17:00)</option>
                        <option value="evening">Ca Tối (18:00 - 22:00)</option>
                        <option value="night">Ca Đêm</option>
                        <option value="rotating">Xoay ca</option>
                        <option value="weekend">Cuối tuần</option>
                        <option value="flexible">Linh hoạt theo lịch học</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="job-working-schedule">Lịch làm việc chi tiết</label>
                    <input type="text" id="job-working-schedule" class="form-control" placeholder="Ví dụ: Đăng ký tối thiểu 4 buổi/tuần, 4-6h/buổi">
                </div>
            </div>

            <!-- 6. Độ tuổi yêu cầu -->
            <div class="fieldset-card">
                <label class="fieldset-card-title">Độ tuổi ứng viên</label>
                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label" for="job-minimum-age">Tuổi tối thiểu</label>
                        <input type="number" id="job-minimum-age" class="form-control" placeholder="Để trống nếu không yêu cầu" min="15" max="80">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="job-maximum-age">Tuổi tối đa</label>
                        <input type="number" id="job-maximum-age" class="form-control" placeholder="Để trống nếu không yêu cầu" min="15" max="80">
                    </div>
                </div>
                <small class="form-help">Bỏ trống cả hai ô thì mọi sinh viên đều đạt tiêu chí độ tuổi.</small>
            </div>

            <!-- 7. Số lượng tuyển & Hạn nộp -->
            <div class="form-grid-2" style="margin-bottom:1.25rem;">
                <div class="form-group">
                    <label class="form-label" for="job-quantity">Số lượng cần tuyển</label>
                    <input type="number" id="job-quantity" class="form-control" placeholder="Ví dụ: 5" min="1">
                </div>
                <div class="form-group">
                    <label class="form-label" for="job-deadline">Hạn nộp hồ sơ <span style="color:var(--danger)">*</span></label>
                    <input type="date" id="job-deadline" class="form-control" required>
                    <small class="form-help">Ngày hết hạn phải ở tương lai.</small>
                </div>
            </div>

            <!-- 8. Kỹ năng yêu cầu (Skills) -->
            <div class="form-group" style="margin-bottom:1.25rem;">
                <label class="form-label">Kỹ năng / Phẩm chất mong muốn</label>
                <div id="job-skills-container" style="display:flex;flex-wrap:wrap;gap:0.5rem;padding:0.75rem;background:#f8fafc;border:1px solid var(--border);border-radius:var(--radius);max-height:150px;overflow-y:auto;">
                    <!-- Checkboxes rendered dynamically -->
                </div>
                <label class="form-label" for="job-custom-skills" style="margin-top:.75rem;">Kỹ năng khác</label>
                <input type="text" id="job-custom-skills" class="form-control" maxlength="1000" placeholder="Ví dụ: Chụp hình sản phẩm, quản lý fanpage (ngăn cách bằng dấu phẩy)">
                <small class="form-help">Có thể vừa tích kỹ năng có sẵn, vừa nhập kỹ năng riêng. Mỗi kỹ năng cách nhau bằng dấu phẩy.</small>
            </div>

            <!-- 9. Mô tả công việc -->
            <div class="form-group" style="margin-bottom:1.25rem;">
                <label class="form-label" for="job-desc">Mô tả công việc <span style="color:var(--danger)">*</span></label>
                <textarea id="job-desc" rows="5" class="form-control" placeholder="Chi tiết các nhiệm vụ hàng ngày sinh viên sẽ thực hiện..." required minlength="10"></textarea>
                <small class="form-help">Tối thiểu 10 ký tự.</small>
            </div>

            <!-- 10. Yêu cầu ứng viên -->
            <div class="form-group" style="margin-bottom:1.25rem;">
                <label class="form-label" for="job-req">Yêu cầu ứng viên</label>
                <textarea id="job-req" rows="4" class="form-control" placeholder="Sinh viên năm 1-4, chăm chỉ, đúng giờ, giao tiếp tốt, không yêu cầu kinh nghiệm..."></textarea>
            </div>

            <!-- 11. Quyền lợi được hưởng -->
            <div class="form-group" style="margin-bottom:1.25rem;">
                <label class="form-label" for="job-benefits">Quyền lợi & Đãi ngộ</label>
                <textarea id="job-benefits" rows="4" class="form-control" placeholder="Hỗ trợ gửi xe, phụ cấp ăn trưa/tối, thưởng theo năng suất, linh hoạt đổi ca thi cử..."></textarea>
            </div>

            <!-- Section: Địa điểm làm việc cụ thể (Work Locations) -->
            <div class="job-locations-section" id="section-job-locations">
                <div class="job-locations-header">
                    <div>
                        <h3 class="job-locations-title">
                            <span>📍 Địa Điểm Làm Việc Cụ Thể</span>
                            <span id="locs-count-badge" class="job-locations-counter">0 / 20</span>
                        </h3>
                        <small style="color:var(--text-muted);font-size:0.83rem;">
                            Thêm các chi nhánh, cơ sở hoặc điểm làm việc cụ thể của tin tuyển dụng này để sinh viên dễ dàng tìm thấy việc làm gần mình.
                        </small>
                    </div>
                    <button type="button" id="btn-open-add-location" class="btn btn-outline btn-sm" onclick="openAddLocationModal()" style="display:inline-flex;align-items:center;gap:0.35rem;">
                        <span>➕</span> <span>Thêm địa điểm</span>
                    </button>
                </div>

                <div id="job-locations-list" class="job-locations-grid">
                    <!-- Cards rendered dynamically -->
                </div>

                <div id="job-locations-empty" style="display:none;padding:1.5rem;text-align:center;background:#f8fafc;border:1px dashed #cbd5e1;border-radius:var(--radius-sm);">
                    <div style="font-size:1.5rem;margin-bottom:0.35rem;">📍</div>
                    <div style="font-weight:600;color:var(--dark);font-size:0.92rem;">Chưa có địa điểm làm việc cụ thể</div>
                    <p style="font-size:0.82rem;color:var(--text-muted);margin:0.25rem auto 0.75rem;max-width:420px;">
                        Nhấn nút <strong>"Thêm địa điểm"</strong> ở trên để thêm cơ sở làm việc qua bản đồ Goong. Tin có địa điểm chính xác sẽ được ưu tiên hiển thị cho sinh viên ở gần.
                    </p>
                </div>
            </div>

            <!-- 12. Trạng thái xuất bản -->
            <div class="fieldset-card" style="margin-bottom:0;">
                <label class="form-label" for="job-status" style="font-weight:700;">Trạng thái xuất bản tin</label>
                <select id="job-status" class="form-control" style="font-weight:600;">
                    <option value="published">Công khai tuyển dụng ngay (Published)</option>
                    <option value="draft">Lưu bản nháp (Draft)</option>
                    <option value="pending_approval">Gửi chờ xét duyệt (Pending Approval)</option>
                    <?php if ($isEditMode): ?>
                    <option value="closed">Đóng tuyển dụng (Closed)</option>
                    <?php endif; ?>
                </select>
                <small id="status-warning" style="display:none;color:var(--warning-text);margin-top:0.4rem;font-weight:600;">
                    Lưu ý: Doanh nghiệp của bạn chưa được xác minh (verified) nên chưa thể công khai trực tiếp. Vui lòng chọn "Lưu bản nháp" hoặc "Gửi chờ xét duyệt".
                </small>
            </div>

            <!-- Submit Buttons -->
            <div class="form-actions">
                <a href="/company/jobs" class="btn btn-outline">Hủy Bỏ</a>
                <button type="submit" id="btn-submit-job" class="btn btn-primary">
                    <?= $isEditMode ? "Cập Nhật Tin Tuyển Dụng" : "Đăng Tin Tuyển Dụng" ?>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Thêm / Sửa Địa Điểm Làm Việc -->
<div id="modal-location-form" style="display:none;position:fixed;inset:0;background:rgba(15,23,42,.6);z-index:10000;align-items:center;justify-content:center;padding:1rem;" role="dialog" aria-modal="true" aria-labelledby="modal-loc-title">
    <div style="background:#fff;border-radius:var(--radius);max-width:560px;width:100%;padding:1.5rem;box-shadow:var(--shadow);position:relative;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.25rem;padding-bottom:0.75rem;border-bottom:1px solid var(--border);">
            <h3 id="modal-loc-title" style="font-size:1.15rem;font-weight:700;color:var(--dark);margin:0;">Thêm Địa Điểm Làm Việc</h3>
            <button type="button" onclick="closeLocationModal()" class="modal-close-btn" aria-label="Đóng">&times;</button>
        </div>

        <form id="form-location-modal" onsubmit="handleSaveLocation(event)">
            <input type="hidden" id="loc-edit-id" value="">

            <div class="form-group" style="margin-bottom:1rem;">
                <label class="form-label" for="loc-branch-name">Tên chi nhánh / cơ sở (Tùy chọn)</label>
                <input type="text" id="loc-branch-name" class="form-control" placeholder="Ví dụ: Chi nhánh Nguyễn Huệ, Cửa hàng số 2..." maxlength="150">
            </div>

            <div class="form-group" style="margin-bottom:1rem;">
                <div id="loc-autocomplete-container"></div>
                <small class="form-help">Tìm kiếm theo tên đường, phường/xã, quận/huyện để hệ thống tự động xác thực tọa độ.</small>
            </div>

            <div class="form-group" style="margin-bottom:1.25rem;">
                <label style="display:inline-flex;align-items:center;gap:0.5rem;cursor:pointer;font-size:0.9rem;font-weight:600;color:var(--dark);">
                    <input type="checkbox" id="loc-is-primary" style="width:17px;height:17px;">
                    <span>Đặt làm địa điểm chính (Hiển thị nổi bật trên tin tuyển dụng)</span>
                </label>
            </div>

            <div style="display:flex;justify-content:flex-end;gap:0.75rem;">
                <button type="button" class="btn btn-outline btn-sm" onclick="closeLocationModal()">Hủy</button>
                <button type="submit" id="btn-save-loc" class="btn btn-primary btn-sm">Lưu Địa Điểm</button>
            </div>
        </form>
    </div>
</div>

<script>
const IS_EDIT_MODE = <?= $isEditMode ? "true" : "false" ?>;
const EDIT_JOB_ID = "<?= htmlspecialchars($editingJobId, ENT_QUOTES, 'UTF-8') ?>";
let companyVerificationStatus = "pending";
let jobLocations = [];
let locAutocompleteInstance = null;

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

    locAutocompleteInstance = new AddressAutocomplete("#loc-autocomplete-container", {
        id: "job-work-address",
        label: "Địa chỉ chính xác",
        placeholder: "Nhập và chọn một địa chỉ từ gợi ý Goong...",
        required: true
    });
    renderJobLocations();

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

/* ==========================================================================
   LOCATION MANAGEMENT (CRUD & MULTI-BRANCH)
   ========================================================================== */
async function loadJobLocations(jobId) {
    const res = await apiRequest(`/jobs/${encodeURIComponent(jobId)}/locations`);
    if (res && res.success && Array.isArray(res.data)) {
        jobLocations = res.data;
    } else {
        jobLocations = [];
    }
    renderJobLocations();
}

function renderJobLocations() {
    const container = document.getElementById("job-locations-list");
    const emptyEl = document.getElementById("job-locations-empty");
    const badgeEl = document.getElementById("locs-count-badge");
    const addBtn = document.getElementById("btn-open-add-location");

    badgeEl.innerText = `${jobLocations.length} / 20`;
    if (addBtn) {
        addBtn.disabled = jobLocations.length >= 20;
        if (jobLocations.length >= 20) {
            addBtn.title = "Đã đạt số lượng tối đa 20 địa điểm.";
        } else {
            addBtn.title = "";
        }
    }

    if (jobLocations.length === 0) {
        container.innerHTML = "";
        emptyEl.style.display = "block";
        return;
    }

    emptyEl.style.display = "none";
    container.innerHTML = jobLocations.map((loc, idx) => {
        const locId = loc.id || loc.temp_id || `loc-${idx}`;
        const isPrimary = !!loc.is_primary;
        const isVerified = loc.geocode_status === "verified" || loc.provider === "goong";
        const branchName = loc.branch_name ? escapeHtml(loc.branch_name) : `Cơ sở ${idx + 1}`;
        const addressText = escapeHtml(loc.address_text || "");

        const parts = [loc.commune, loc.province].filter(Boolean);
        const subAddress = parts.length > 0 ? parts.join(", ") : "";
        const legacyDistrict = loc.district_text_legacy ? escapeHtml(loc.district_text_legacy) : "";

        return `
            <div class="location-card ${isPrimary ? 'is-primary' : ''}">
                <div class="location-card-info">
                    <div class="location-card-title">
                        <span>${branchName}</span>
                        ${isPrimary ? '<span class="badge-loc badge-loc-primary">★ Địa điểm chính</span>' : ''}
                        ${isVerified ? '<span class="badge-loc badge-loc-verified">✓ Đã xác thực</span>' : '<span class="badge-loc badge-loc-manual">✎ Nhập thủ công</span>'}
                    </div>
                    <div class="location-card-address">${addressText}</div>
                    <div class="location-card-meta">
                        ${subAddress ? `<span style="font-size:0.8rem;color:var(--text-muted);">📍 ${escapeHtml(subAddress)}</span>` : ''}
                        ${legacyDistrict ? `<span class="badge-loc" style="background:#e2e8f0;color:#475569;">${legacyDistrict}</span>` : ''}
                    </div>
                </div>
                <div class="location-card-actions">
                    ${!isPrimary ? `<button type="button" class="btn-set-primary" onclick="handleSetPrimaryLocation('${escapeHtml(locId)}')">Đặt làm chính</button>` : ''}
                    <button type="button" class="btn-loc-action" onclick="openEditLocationModal('${escapeHtml(locId)}')">Sửa</button>
                    <button type="button" class="btn-loc-action btn-danger-action" onclick="handleDeleteLocation('${escapeHtml(locId)}')">Xóa</button>
                </div>
            </div>
        `;
    }).join("");
}

function openAddLocationModal() {
    if (jobLocations.length >= 20) {
        showToast("Mỗi tin được có tối đa 20 địa điểm làm việc.", "warning");
        return;
    }
    document.getElementById("modal-loc-title").innerText = "Thêm Địa Điểm Làm Việc";
    document.getElementById("loc-edit-id").value = "";
    document.getElementById("loc-branch-name").value = "";
    document.getElementById("loc-is-primary").checked = jobLocations.length === 0;
    locAutocompleteInstance.clear();

    document.getElementById("modal-location-form").style.display = "flex";
}

function openEditLocationModal(id) {
    const loc = jobLocations.find(l => (l.id || l.temp_id) === id);
    if (!loc) return;

    document.getElementById("modal-loc-title").innerText = "Chỉnh Sửa Địa Điểm Làm Việc";
    document.getElementById("loc-edit-id").value = id;
    document.getElementById("loc-branch-name").value = loc.branch_name || "";
    document.getElementById("loc-is-primary").checked = !!loc.is_primary;
    const providerPlaceId = loc.provider_place_id || loc.place_id || null;
    const draftSelectedPlace = loc.temp_id && providerPlaceId ? {
        ...loc,
        place_id: providerPlaceId,
        description: loc.address_text || ""
    } : null;
    locAutocompleteInstance.setValue(loc.address_text || "", draftSelectedPlace);

    document.getElementById("modal-location-form").style.display = "flex";
}

function closeLocationModal() {
    document.getElementById("modal-location-form").style.display = "none";
}

async function handleSaveLocation(e) {
    e.preventDefault();
    const editId = document.getElementById("loc-edit-id").value.trim();
    const branchName = document.getElementById("loc-branch-name").value.trim();
    const isPrimary = document.getElementById("loc-is-primary").checked;
    const selected = locAutocompleteInstance.getSelected();
    const rawAddress = locAutocompleteInstance.getValue();
    const currentLocation = editId ? jobLocations.find(l => (l.id || l.temp_id) === editId) : null;
    const isUnchangedExistingAddress = !!currentLocation && rawAddress === (currentLocation.address_text || "");

    if (!selected && !isUnchangedExistingAddress) {
        showToast("Vui lòng chọn một địa chỉ trong danh sách gợi ý Goong để xác thực tọa độ.", "warning");
        return;
    }

    const saveBtn = document.getElementById("btn-save-loc");
    saveBtn.disabled = true;
    saveBtn.innerText = "Đang lưu...";

    if (IS_EDIT_MODE && EDIT_JOB_ID) {
        try {
            if (editId) {
                const payload = {
                    branch_name: branchName || null,
                    is_primary: isPrimary
                };
                if (selected) {
                    payload.place_id = selected.place_id;
                    payload.session_token = selected.session_token;
                }

                const res = await apiRequest(`/company/jobs/${encodeURIComponent(EDIT_JOB_ID)}/locations/${encodeURIComponent(editId)}`, {
                    method: "PATCH",
                    body: payload,
                    requireAuth: true
                });

                if (res && res.success) {
                    showToast("Đã cập nhật địa điểm làm việc.", "success");
                    closeLocationModal();
                    await loadJobLocations(EDIT_JOB_ID);
                } else {
                    showToast((res && res.message) ? res.message : "Cập nhật địa điểm thất bại.", "error");
                }
            } else {
                const payload = {
                    branch_name: branchName || null,
                    is_primary: isPrimary
                };
                if (selected) {
                    payload.place_id = selected.place_id;
                    payload.session_token = selected.session_token;
                }

                const res = await apiRequest(`/company/jobs/${encodeURIComponent(EDIT_JOB_ID)}/locations`, {
                    method: "POST",
                    body: payload,
                    requireAuth: true
                });

                if (res && res.success) {
                    showToast("Đã thêm địa điểm làm việc.", "success");
                    closeLocationModal();
                    await loadJobLocations(EDIT_JOB_ID);
                } else {
                    showToast((res && res.message) ? res.message : "Thêm địa điểm thất bại.", "error");
                }
            }
        } catch (err) {
            console.error("Save location error:", err);
            showToast("Lỗi khi lưu địa điểm.", "error");
        }
    } else {
        if (editId) {
            const idx = jobLocations.findIndex(l => (l.id || l.temp_id) === editId);
            if (idx !== -1) {
                if (isPrimary) {
                    jobLocations.forEach(l => l.is_primary = false);
                }
                jobLocations[idx] = {
                    ...jobLocations[idx],
                    branch_name: branchName || null,
                    is_primary: isPrimary,
                    address_text: selected ? selected.description : jobLocations[idx].address_text,
                    place_id: selected ? selected.place_id : (jobLocations[idx].place_id || jobLocations[idx].provider_place_id || null),
                    session_token: selected ? selected.session_token : (jobLocations[idx].session_token || null),
                    commune: selected ? selected.commune : jobLocations[idx].commune,
                    province: selected ? selected.province : jobLocations[idx].province,
                    district_text_legacy: selected ? selected.district_text_legacy : jobLocations[idx].district_text_legacy,
                    geocode_status: selected ? "verified" : jobLocations[idx].geocode_status
                };
            }
        } else {
            if (isPrimary) {
                jobLocations.forEach(l => l.is_primary = false);
            }
            jobLocations.push({
                temp_id: "draft-" + Date.now() + "-" + Math.random().toString(36).substring(2, 6),
                branch_name: branchName || null,
                is_primary: isPrimary || jobLocations.length === 0,
                address_text: selected.description,
                place_id: selected.place_id,
                session_token: selected.session_token,
                commune: selected.commune || null,
                province: selected.province || null,
                district_text_legacy: selected.district_text_legacy || null,
                geocode_status: "verified"
            });
        }
        closeLocationModal();
        renderJobLocations();
        showToast("Đã lưu địa điểm vào danh sách chờ.", "success");
    }

    saveBtn.disabled = false;
    saveBtn.innerText = "Lưu Địa Điểm";
}

async function handleDeleteLocation(id) {
    if (!confirm("Bạn có chắc chắn muốn xóa địa điểm làm việc này khỏi tin tuyển dụng?")) {
        return;
    }

    if (IS_EDIT_MODE && EDIT_JOB_ID) {
        try {
            const res = await apiRequest(`/company/jobs/${encodeURIComponent(EDIT_JOB_ID)}/locations/${encodeURIComponent(id)}`, {
                method: "DELETE",
                requireAuth: true
            });
            if (res && res.success) {
                showToast("Đã xóa địa điểm làm việc.", "info");
                await loadJobLocations(EDIT_JOB_ID);
            } else {
                showToast((res && res.message) ? res.message : "Xóa địa điểm thất bại.", "error");
            }
        } catch (err) {
            console.error("Delete location error:", err);
            showToast("Lỗi khi xóa địa điểm.", "error");
        }
    } else {
        const deletedWasPrimary = jobLocations.find(l => (l.id || l.temp_id) === id)?.is_primary;
        jobLocations = jobLocations.filter(l => (l.id || l.temp_id) !== id);
        if (deletedWasPrimary && jobLocations.length > 0) {
            jobLocations[0].is_primary = true;
        }
        renderJobLocations();
        showToast("Đã xóa địa điểm khỏi danh sách.", "info");
    }
}

async function handleSetPrimaryLocation(id) {
    if (IS_EDIT_MODE && EDIT_JOB_ID) {
        try {
            const res = await apiRequest(`/company/jobs/${encodeURIComponent(EDIT_JOB_ID)}/locations/${encodeURIComponent(id)}`, {
                method: "PATCH",
                body: { is_primary: true },
                requireAuth: true
            });
            if (res && res.success) {
                showToast("Đã đặt làm địa điểm chính.", "success");
                await loadJobLocations(EDIT_JOB_ID);
            } else {
                showToast((res && res.message) ? res.message : "Không thể đặt địa điểm chính.", "error");
            }
        } catch (err) {
            console.error("Set primary error:", err);
            showToast("Lỗi thao tác.", "error");
        }
    } else {
        jobLocations.forEach(l => {
            l.is_primary = (l.id || l.temp_id) === id;
        });
        renderJobLocations();
        showToast("Đã đặt làm địa điểm chính.", "success");
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
        document.getElementById("job-shift-type").value = j.shift_type || "";
        document.getElementById("job-minimum-age").value = j.minimum_age ?? "";
        document.getElementById("job-maximum-age").value = j.maximum_age ?? "";
        document.getElementById("job-working-schedule").value = j.working_schedule || "";
        document.getElementById("job-quantity").value = j.quantity || "";
        document.getElementById("job-deadline").value = j.application_deadline ? j.application_deadline.substring(0, 10) : "";
        document.getElementById("job-desc").value = j.description || "";
        document.getElementById("job-req").value = j.requirements || "";
        document.getElementById("job-benefits").value = j.benefits || "";
        document.getElementById("job-status").value = j.status || "published";
        await loadJobLocations(id);

        // Pre-check skills if available
        let skillIds = [];
        if (Array.isArray(j.skills)) {
            skillIds = j.skills.map(s => typeof s === "object" && s !== null ? s.id : s);
        } else if (Array.isArray(j.required_skills)) {
            skillIds = j.required_skills.map(s => typeof s === "object" && s !== null ? s.id : s);
        } else if (typeof j.required_skills === "string") {
            try {
                const parsed = JSON.parse(j.required_skills);
                if (Array.isArray(parsed)) {
                    skillIds = parsed;
                }
            } catch (e) {
                skillIds = j.required_skills.split(",").map(s => s.trim()).filter(Boolean);
            }
        }
        const knownSkillIds = new Set(Array.from(document.querySelectorAll("input[name='skill_id']")).map(cb => cb.value));
        const customSkills = [];
        document.querySelectorAll("input[name='skill_id']").forEach(cb => {
            if (skillIds.map(String).includes(cb.value)) cb.checked = true;
        });
        skillIds.forEach(skill => {
            const value = String(skill || "").trim();
            if (value && !knownSkillIds.has(value)) customSkills.push(value);
        });
        document.getElementById("job-custom-skills").value = customSkills.join(", ");
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
    document.getElementById("job-custom-skills").value
        .split(/[,;\n]+/)
        .map(skill => skill.trim())
        .filter(Boolean)
        .forEach(skill => selectedSkills.push(skill));
    const uniqueSkills = Array.from(new Map(selectedSkills.map(skill => [skill.toLocaleLowerCase("vi"), skill])).values());

    const statusVal = document.getElementById("job-status").value;
    const workMode = document.getElementById("job-work-mode").value;

    if (["onsite", "hybrid"].includes(workMode) && jobLocations.length === 0) {
        alertBox.className = "toast toast-error";
        alertBox.innerText = "Vui lòng thêm ít nhất một địa điểm làm việc đã chọn từ gợi ý Goong.";
        alertBox.style.display = "block";
        document.getElementById("section-job-locations").scrollIntoView({ behavior: "smooth", block: "center" });
        return;
    }

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
        work_mode: workMode,
        salary_type: document.getElementById("job-salary-type").value,
        salary_min: document.getElementById("job-salary-min").value ? parseInt(document.getElementById("job-salary-min").value) : null,
        salary_max: document.getElementById("job-salary-max").value ? parseInt(document.getElementById("job-salary-max").value) : null,
        shift_type: document.getElementById("job-shift-type").value || null,
        working_schedule: document.getElementById("job-working-schedule").value.trim() || null,
        minimum_age: document.getElementById("job-minimum-age").value ? parseInt(document.getElementById("job-minimum-age").value, 10) : null,
        maximum_age: document.getElementById("job-maximum-age").value ? parseInt(document.getElementById("job-maximum-age").value, 10) : null,
        quantity: document.getElementById("job-quantity").value ? parseInt(document.getElementById("job-quantity").value) : 1,
        application_deadline: document.getElementById("job-deadline").value || null,
        description: document.getElementById("job-desc").value.trim(),
        requirements: document.getElementById("job-req").value.trim() || null,
        benefits: document.getElementById("job-benefits").value.trim() || null,
        status: statusVal,
        required_skills: uniqueSkills
    };

    btn.disabled = true;
    btn.innerText = "Đang lưu...";

    const url = IS_EDIT_MODE ? `/jobs/${encodeURIComponent(EDIT_JOB_ID)}` : "/jobs";
    const method = IS_EDIT_MODE ? "PUT" : "POST";

    const res = await apiRequest(url, {
        method: method,
        // Tin mới luôn được tạo ở trạng thái nháp trước. Chỉ công khai/chờ duyệt
        // sau khi toàn bộ địa điểm đã được lưu thành công.
        body: IS_EDIT_MODE ? payload : { ...payload, status: "draft" },
        requireAuth: true
    });

    if (res && res.success) {
        if (!IS_EDIT_MODE) {
            const createdJobId = res.data && res.data.id ? String(res.data.id) : "";
            if (!createdJobId) {
                btn.disabled = false;
                btn.innerText = "Đăng Tin Tuyển Dụng";
                alertBox.className = "toast toast-error";
                alertBox.innerText = "Tin đã được tạo nhưng máy chủ không trả về mã tin để lưu địa điểm. Vui lòng mở danh sách tin và bổ sung lại địa điểm.";
                alertBox.style.display = "block";
                return;
            }

            btn.innerText = "Đang lưu địa điểm...";
            const locationErrors = await persistDraftJobLocations(createdJobId);
            if (locationErrors.length > 0) {
                btn.disabled = false;
                btn.innerText = "Đăng Tin Tuyển Dụng";
                alertBox.className = "toast toast-error";
                alertBox.innerText = `Tin đã được lưu an toàn ở trạng thái nháp, nhưng ${locationErrors.length} địa điểm chưa lưu được. Hãy mở tin vừa tạo để bổ sung lại.`;
                alertBox.style.display = "block";
                setTimeout(() => {
                    window.location.href = `/company/jobs/${encodeURIComponent(createdJobId)}/edit?location_error=1`;
                }, 1400);
                return;
            }

            if (statusVal !== "draft") {
                btn.innerText = "Đang hoàn tất tin...";
                const finalizeResult = await apiRequest(`/jobs/${encodeURIComponent(createdJobId)}`, {
                    method: "PUT",
                    body: payload,
                    requireAuth: true
                });
                if (!finalizeResult || !finalizeResult.success) {
                    btn.disabled = false;
                    btn.innerText = "Đăng Tin Tuyển Dụng";
                    alertBox.className = "toast toast-error";
                    alertBox.innerText = "Tin và địa điểm đã được lưu ở trạng thái nháp, nhưng chưa thể chuyển sang trạng thái bạn chọn. Bạn có thể hoàn tất từ trang quản lý tin.";
                    alertBox.style.display = "block";
                    setTimeout(() => {
                        window.location.href = `/company/jobs/${encodeURIComponent(createdJobId)}/edit?finalize_error=1`;
                    }, 1400);
                    return;
                }
            }
        }

        btn.disabled = false;
        btn.innerText = IS_EDIT_MODE ? "Cập Nhật Tin Tuyển Dụng" : "Đăng Tin Tuyển Dụng";
        showToast(IS_EDIT_MODE ? "Cập nhật tin tuyển dụng thành công!" : "Đăng tin tuyển dụng thành công!", "success");
        setTimeout(() => {
            window.location.href = "/company/jobs";
        }, 600);
    } else {
        btn.disabled = false;
        btn.innerText = IS_EDIT_MODE ? "Cập Nhật Tin Tuyển Dụng" : "Đăng Tin Tuyển Dụng";
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

async function persistDraftJobLocations(jobId) {
    const errors = [];
    for (const location of jobLocations) {
        const payload = {
            place_id: location.place_id || location.provider_place_id,
            session_token: location.session_token || undefined,
            branch_name: location.branch_name || null,
            is_primary: !!location.is_primary
        };
        const result = await apiRequest(`/company/jobs/${encodeURIComponent(jobId)}/locations`, {
            method: "POST",
            body: payload,
            requireAuth: true
        });
        if (!result || !result.success) {
            errors.push(result && result.message ? result.message : "Không thể lưu địa điểm.");
        }
    }
    return errors;
}
</script>
