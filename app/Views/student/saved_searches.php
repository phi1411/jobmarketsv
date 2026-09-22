<?php include __DIR__ . "/nav.php"; ?>

<div class="container" style="margin-bottom:3rem;">
    <div class="surface-card" style="margin-bottom:1.5rem;padding:1.25rem 1.5rem;background:linear-gradient(135deg,#ecfdf5 0%,#ffffff 70%);border-color:var(--primary-border);">
        <div style="display:flex;gap:1rem;align-items:flex-start;">
            <div style="width:42px;height:42px;border-radius:12px;background:var(--primary);color:#fff;display:grid;place-items:center;flex:0 0 auto;">
                <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.7 21a2 2 0 0 1-3.4 0"></path></svg>
            </div>
            <div>
                <h3 style="margin:0 0 .35rem;font-size:1rem;color:var(--dark);">Thông báo việc làm phù hợp tự động</h3>
                <p style="margin:0;color:var(--text-muted);font-size:.9rem;line-height:1.55;">Khi nhà tuyển dụng đăng tin mới đạt ngưỡng phù hợp, JobMarketSV sẽ báo ngay trong ứng dụng và gửi tới email tài khoản của bạn.</p>
            </div>
        </div>
    </div>
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;flex-wrap:wrap;gap:1rem;">
        <div>
            <h2 style="font-size:1.25rem;font-weight:700;color:var(--dark);margin:0;">
                Bộ Lọc Tìm Kiếm Đã Lưu
            </h2>
            <span id="ss-count-text" style="font-size:0.88rem;color:var(--text-muted);">Đang tải danh sách...</span>
        </div>
        <button onclick="openCreateModal()" class="btn btn-primary btn-sm">
            Tạo Bộ Lọc Mới
        </button>
    </div>

    <!-- Loading State -->
    <div id="ss-loading" style="text-align:center;padding:2rem 0;">
        <div class="job-card skeleton" style="height:110px;margin-bottom:1rem;"></div>
        <div class="job-card skeleton" style="height:110px;"></div>
    </div>

    <!-- Container -->
    <div id="ss-container" style="display:flex;flex-direction:column;gap:1rem;"></div>

    <!-- Empty State -->
    <div id="ss-empty" class="surface-card empty-state" style="display:none;">
        <div class="empty-state-icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
        </div>
        <h3 class="empty-state-title">Chưa Có Bộ Lọc Nào Được Lưu</h3>
        <p class="empty-state-text">
            Lưu tiêu chí tìm kiếm (ca làm, quận/huyện, mức lương) giúp bạn nhanh chóng tra cứu các tin việc làm mới chỉ bằng 1 cú nhấp chuột.
        </p>
        <div class="empty-state-action">
            <button onclick="openCreateModal()" class="btn btn-primary">Tạo Bộ Lọc Tìm Kiếm Đầu Tiên</button>
        </div>
    </div>
</div>

<!-- Modal Create Saved Search -->
<div id="ss-modal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(15,23,42,0.6);z-index:9999;align-items:center;justify-content:center;padding:1rem;">
    <div class="modal-card" style="background:var(--surface);border-radius:var(--radius);max-width:520px;width:100%;padding:2rem;box-shadow:var(--shadow);max-height:90vh;overflow-y:auto;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.25rem;padding-bottom:0.75rem;border-bottom:1px solid var(--border);">
            <h3 style="font-size:1.2rem;font-weight:700;color:var(--dark);margin:0;">Lưu Bộ Lọc Tìm Kiếm</h3>
            <button type="button" onclick="closeCreateModal()" class="modal-close-btn" aria-label="Đóng hộp thoại">&times;</button>
        </div>

        <div id="ss-error-box" style="display:none;margin-bottom:1rem;" class="toast toast-error"></div>

        <form id="ss-form" onsubmit="handleCreateSavedSearch(event)">
            <div class="form-group" style="margin-bottom:1rem;">
                <label class="form-label">Tên bộ lọc gợi nhớ <span style="color:var(--danger)">*</span></label>
                <input type="text" id="ss-name" class="form-control" placeholder="Ví dụ: Việc Cầu Giấy ca tối, Thu ngân lương > 30k..." required>
            </div>

            <div class="form-group" style="margin-bottom:1rem;">
                <label class="form-label">Từ khóa công việc</label>
                <input type="text" id="ss-keyword" class="form-control" placeholder="Pha chế, phục vụ, gia sư, thu ngân...">
            </div>

            <div class="form-grid-2" style="margin-bottom:1rem;">
                <div class="form-group">
                    <label class="form-label">Ngành nghề</label>
                    <select id="ss-category" class="form-control" data-searchable="true" data-allow-custom="true" data-placeholder-search="Tìm hoặc gõ ngành nghề...">
                        <option value="">Tất cả ngành</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="ss-btn-open-location-picker">Khu vực / Địa điểm</label>
                    <button type="button" id="ss-btn-open-location-picker" class="location-picker-trigger" aria-haspopup="dialog" title="Mở bộ chọn khu vực làm việc" style="width:100%;height:42px;background:#fff;border:1px solid var(--border);border-radius:var(--radius-sm);padding:0 0.875rem;display:flex;align-items:center;justify-content:space-between;cursor:pointer;box-sizing:border-box;">
                        <span class="loc-trigger-content" style="display:flex;align-items:center;gap:0.5rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                            <svg class="loc-trigger-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color:var(--text-muted);flex-shrink:0;">
                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                <circle cx="12" cy="10" r="3"></circle>
                            </svg>
                            <span id="ss-location-picker-label" class="location-picker-label" style="font-size:0.9rem;color:var(--dark);">Tất cả địa điểm</span>
                        </span>
                        <span style="display:flex;align-items:center;gap:0.4rem;">
                            <span id="ss-location-picker-badge" class="location-picker-badge" style="display:none;background:var(--primary);color:#fff;border-radius:10px;padding:2px 7px;font-size:0.75rem;font-weight:600;">0</span>
                            <svg class="loc-chevron-down" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color:var(--text-muted);">
                                <path d="m6 9 6 6 6-6"></path>
                            </svg>
                        </span>
                    </button>
                    <input type="hidden" id="ss-location" value="">
                </div>
            </div>

            <div class="form-grid-2" style="margin-bottom:1.5rem;">
                <div class="form-group">
                    <label class="form-label">Ca làm việc</label>
                    <select id="ss-shift" class="form-control">
                        <option value="">Tất cả các ca</option>
                        <option value="morning">Ca Sáng (08:00 - 12:00)</option>
                        <option value="afternoon">Ca Chiều (13:00 - 17:00)</option>
                        <option value="evening">Ca Tối (18:00 - 22:00)</option>
                        <option value="weekend">Cuối tuần</option>
                        <option value="flexible">Linh hoạt</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Mức lương tối thiểu (VND/h)</label>
                    <input type="number" id="ss-salary-min" class="form-control" placeholder="25000" step="5000" min="0">
                </div>
            </div>

            <div class="form-grid-2" style="margin-bottom:1rem;">
                <div class="form-group">
                    <label class="form-label">Mức phù hợp tối thiểu</label>
                    <select id="ss-min-score" class="form-control">
                        <option value="50">Từ 50% — nhiều gợi ý</option>
                        <option value="65" selected>Từ 65% — cân bằng</option>
                        <option value="80">Từ 80% — rất sát nhu cầu</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Tần suất email</label>
                    <select id="ss-frequency" class="form-control">
                        <option value="instant" selected>Gửi ngay khi có tin mới</option>
                        <option value="daily">Tổng hợp hàng ngày</option>
                        <option value="weekly">Tổng hợp hàng tuần</option>
                    </select>
                </div>
            </div>

            <label style="display:flex;align-items:center;gap:.65rem;margin-bottom:1.25rem;cursor:pointer;color:var(--dark);font-size:.9rem;">
                <input type="checkbox" id="ss-email-enabled" checked style="width:18px;height:18px;accent-color:var(--primary);">
                Gửi thêm thông báo tới email tài khoản
            </label>

            <div style="display:flex;justify-content:flex-end;gap:0.75rem;padding-top:1rem;border-top:1px solid var(--border);">
                <button type="button" onclick="closeCreateModal()" class="btn btn-outline btn-sm">Hủy</button>
                <button type="submit" id="btn-submit-ss" class="btn btn-primary btn-sm">
                    Lưu Bộ Lọc
                </button>
            </div>
        </form>
    </div>
</div>

<script>
let ssLocationPicker = null;

async function initStudentSavedSearchesPage() {
    if (!TokenStorage.isLoggedIn()) {
        showToast("Vui lòng đăng nhập để xem tìm kiếm đã lưu.", "error");
        window.location.href = `/login?login_required=1&redirect=${encodeURIComponent(window.location.pathname)}`;
        return;
    }
    const user = TokenStorage.getUser();
    if (!user || (user.role !== "student" && user.role !== "developer")) {
        showToast("Chỉ tài khoản Sinh viên mới có quyền xem trang này.", "error");
        setTimeout(() => { window.location.href = "/viec-lam"; }, 1000);
        return;
    }

    if (typeof LargeLocationPicker === 'function') {
        ssLocationPicker = new LargeLocationPicker({
            mode: 'multi',
            maxSelect: 20,
            title: 'Chọn địa điểm làm việc',
            trigger: '#ss-btn-open-location-picker',
            labelElement: '#ss-location-picker-label',
            badgeElement: '#ss-location-picker-badge',
            hiddenInput: '#ss-location'
        });
    }

    await loadCategories();
    loadSavedSearches();
}

if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initStudentSavedSearchesPage);
} else {
    initStudentSavedSearchesPage();
}

async function loadCategories() {
    const res = await apiRequest("/categories");
    if (res && res.success && Array.isArray(res.data)) {
        const sel = document.getElementById("ss-category");
        const currentVal = sel.value;
        res.data.forEach(c => {
            if (!Array.from(sel.options).some(o => o.value === c.id)) {
                const opt = document.createElement("option");
                opt.value = c.id;
                opt.textContent = c.name;
                sel.appendChild(opt);
            }
        });
        if (currentVal) {
            sel.value = currentVal;
        }
        window.refreshCustomSelect?.(sel);
    }
}

async function loadSavedSearches() {
    const loadingEl = document.getElementById("ss-loading");
    const container = document.getElementById("ss-container");
    const emptyEl = document.getElementById("ss-empty");
    const countText = document.getElementById("ss-count-text");

    loadingEl.style.display = "block";
    container.innerHTML = "";
    emptyEl.style.display = "none";

    try {
        const res = await apiRequest("/saved-searches", { requireAuth: true });

        if (res && res.success && Array.isArray(res.data)) {
            const searches = res.data;
            if (countText) countText.innerText = `Đang lưu ${searches.length} bộ lọc tìm kiếm`;

            if (searches.length === 0) {
                if (emptyEl) emptyEl.style.display = "block";
                return;
            }

            container.innerHTML = searches.map(item => {
                const params = new URLSearchParams();
                if (item.keyword) params.append("keyword", item.keyword);
                if (item.category_id) params.append("category_id", item.category_id);
                if (item.location_id) {
                    if (item.location_id.includes(",")) {
                        params.append("location_ids", item.location_id);
                    } else {
                        params.append("location_id", item.location_id);
                    }
                }
                if (item.shift_type) params.append("shift_type", item.shift_type);
                if (item.salary_min) params.append("salary_min", item.salary_min);

                const searchUrl = `/viec-lam?${params.toString()}`;

                let locBadge = "";
                if (item.location_id) {
                    let locLabel = item.location_name || "";
                    if (!locLabel) {
                        const ids = item.location_id.split(",").map(s => s.trim()).filter(Boolean);
                        if (ids.length > 1) {
                            locLabel = `${ids.length} khu vực`;
                        } else if (ids.length === 1 && typeof LargeLocationPicker !== "undefined" && LargeLocationPicker.locationMap) {
                            const found = LargeLocationPicker.locationMap.get(ids[0]);
                            locLabel = found ? (found.area_name || found.name) : ids[0];
                        } else {
                            locLabel = item.location_id;
                        }
                    }
                    locBadge = `<span class="badge" style="background:#f1f5f9;color:var(--text);display:inline-flex;align-items:center;gap:3px;"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:-1px;"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>${escapeHtml(locLabel)}</span>`;
                }

                return `
                    <div class="data-card" style="margin:0;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1.25rem;">
                        <div style="flex:1;min-width:260px;">
                            <h3 style="font-size:1.1rem;font-weight:700;color:var(--dark);margin-bottom:0.4rem;">
                                ${escapeHtml(item.name)}
                            </h3>
                            <div style="display:flex;flex-wrap:wrap;gap:0.4rem;align-items:center;">
                                ${item.keyword ? `<span class="badge" style="background:#f1f5f9;color:var(--text);">"${escapeHtml(item.keyword)}"</span>` : ''}
                                ${item.category_id ? `<span class="badge" style="background:#eff6ff;color:#1d4ed8;display:inline-flex;align-items:center;gap:3px;"><i class="ri-briefcase-line"></i> ${escapeHtml(item.category_name || item.category_id)}</span>` : ''}
                                ${locBadge}
                                ${item.shift_type ? `<span class="badge badge-shift">${getShiftLabel(item.shift_type)}</span>` : ''}
                                ${item.salary_min ? `<span class="badge badge-salary">&ge; ${formatCurrency(item.salary_min)}/h</span>` : ''}
                                <span class="badge" style="background:${item.notification_enabled ? '#ecfdf5' : '#f1f5f9'};color:${item.notification_enabled ? '#047857' : '#64748b'};">
                                    ${item.notification_enabled ? `Đang báo từ ${parseInt(item.minimum_match_score || 65)}%` : 'Đã tắt thông báo'}
                                </span>
                                ${item.notification_enabled && item.email_enabled ? `<span class="badge" style="background:var(--primary-light);color:var(--primary-text);border:1px solid var(--primary-border);">Email: ${getFrequencyLabel(item.frequency)}</span>` : ''}
                                <span style="font-size:0.75rem;color:var(--text-muted);margin-left:0.5rem;">Tạo ngày: ${formatDate(item.created_at)}</span>
                            </div>
                        </div>

                        <div class="row-actions">
                            <button onclick="handleToggleNotifications('${escapeHtml(item.id)}', ${item.notification_enabled ? 'false' : 'true'})" class="btn btn-outline btn-sm row-action-btn">
                                ${item.notification_enabled ? 'Tắt thông báo' : 'Bật thông báo'}
                            </button>
                            <a href="${searchUrl}" class="btn btn-primary btn-sm row-action-btn">
                                Chạy Tìm Kiếm
                            </a>
                            <button onclick="handleDeleteSavedSearch('${escapeHtml(item.id)}')" class="btn btn-sm row-action-btn" style="background:#fff;border:1px solid var(--border);color:var(--danger);" title="Xóa bộ lọc" aria-label="Xóa bộ lọc">
                                Xóa
                            </button>
                        </div>
                    </div>
                `;
            }).join("");
        } else {
            if (emptyEl) emptyEl.style.display = "block";
            if (countText) countText.innerText = "Không thể tải bộ lọc đã lưu.";
        }
    } catch (err) {
        console.error("Error loading saved searches:", err);
        if (emptyEl) emptyEl.style.display = "block";
        if (countText) countText.innerText = "Lỗi kết nối máy chủ.";
    } finally {
        if (loadingEl) loadingEl.style.display = "none";
    }
}

function openCreateModal() {
    document.getElementById("ss-name").value = "";
    document.getElementById("ss-keyword").value = "";
    document.getElementById("ss-category").value = "";
    window.refreshCustomSelect?.(document.getElementById("ss-category"));
    document.getElementById("ss-location").value = "";
    if (ssLocationPicker) {
        ssLocationPicker.setSelected([], true);
    }
    document.getElementById("ss-shift").value = "";
    document.getElementById("ss-salary-min").value = "";
    document.getElementById("ss-min-score").value = "65";
    document.getElementById("ss-frequency").value = "instant";
    document.getElementById("ss-email-enabled").checked = true;
    document.getElementById("ss-error-box").style.display = "none";
    document.getElementById("ss-modal").style.display = "flex";
}

function closeCreateModal() {
    document.getElementById("ss-modal").style.display = "none";
}

async function handleCreateSavedSearch(e) {
    e.preventDefault();
    const btn = document.getElementById("btn-submit-ss");
    const errBox = document.getElementById("ss-error-box");
    errBox.style.display = "none";

    btn.disabled = true;
    btn.innerText = "Đang lưu...";

    const payload = {
        name: document.getElementById("ss-name").value.trim(),
        keyword: document.getElementById("ss-keyword").value.trim() || null,
        category_id: document.getElementById("ss-category").value || null,
        location_id: document.getElementById("ss-location").value || null,
        shift_type: document.getElementById("ss-shift").value || null,
        salary_min: document.getElementById("ss-salary-min").value ? parseInt(document.getElementById("ss-salary-min").value) : null,
        notification_enabled: true,
        email_enabled: document.getElementById("ss-email-enabled").checked,
        minimum_match_score: parseInt(document.getElementById("ss-min-score").value),
        frequency: document.getElementById("ss-frequency").value
    };

    const res = await apiRequest("/saved-searches", {
        method: "POST",
        body: payload,
        requireAuth: true
    });

    btn.disabled = false;
    btn.innerText = "Lưu Bộ Lọc";

    if (res && res.success) {
        closeCreateModal();
        showToast("Lưu bộ lọc tìm kiếm thành công!", "success");
        loadSavedSearches();
    } else {
        let msg = (res && res.message) ? res.message : "Không thể lưu bộ lọc.";
        if (res && res.errors) {
            msg += " " + Object.values(res.errors).flat().map(escapeHtml).join(" ");
        }
        errBox.innerText = msg;
        errBox.style.display = "block";
    }
}

async function handleDeleteSavedSearch(id) {
    if (!confirm("Bạn có chắc muốn xóa bộ lọc tìm kiếm này?")) {
        return;
    }

    const res = await apiRequest(`/saved-searches/${encodeURIComponent(id)}`, {
        method: "DELETE",
        requireAuth: true
    });

    if (res && res.success) {
        showToast("Đã xóa bộ lọc tìm kiếm.", "info");
        loadSavedSearches();
    } else {
        showToast((res && res.message) ? res.message : "Xóa thất bại.", "error");
    }
}

function getFrequencyLabel(value) {
    return ({ instant: 'ngay', daily: 'hàng ngày', weekly: 'hàng tuần' })[value] || 'ngay';
}

async function handleToggleNotifications(id, enabled) {
    const res = await apiRequest(`/saved-searches/${encodeURIComponent(id)}`, {
        method: "PATCH",
        body: { notification_enabled: enabled },
        requireAuth: true
    });

    if (res && res.success) {
        showToast(enabled ? "Đã bật thông báo việc làm phù hợp." : "Đã tắt thông báo cho bộ lọc.", "success");
        loadSavedSearches();
    } else {
        showToast((res && res.message) ? res.message : "Không thể cập nhật thông báo.", "error");
    }
}
</script>
