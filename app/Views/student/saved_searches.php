<?php include __DIR__ . "/nav.php"; ?>

<div class="container" style="margin-bottom:3rem;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;flex-wrap:wrap;gap:1rem;">
        <div>
            <h2 style="font-size:1.25rem;font-weight:700;color:var(--dark);margin:0;">
                🔍 Bộ Lọc Tìm Kiếm Đã Lưu
            </h2>
            <span id="ss-count-text" style="font-size:0.88rem;color:var(--text-muted);">Đang tải danh sách...</span>
        </div>
        <button onclick="openCreateModal()" class="btn btn-primary btn-sm">
            ➕ Tạo Bộ Lọc Mới
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
    <div id="ss-empty" style="display:none;background:#fff;border-radius:var(--radius);border:1px solid var(--border);padding:3.5rem 1.5rem;text-align:center;">
        <div style="font-size:3rem;margin-bottom:1rem;">🔍</div>
        <h3 style="font-size:1.25rem;font-weight:700;color:var(--dark);margin-bottom:0.5rem;">Chưa Có Bộ Lọc Nào Được Lưu</h3>
        <p style="color:var(--text-muted);max-width:480px;margin:0 auto 1.5rem;">
            Lưu tiêu chí tìm kiếm (ca làm, quận/huyện, mức lương) giúp bạn nhanh chóng tra cứu các tin việc làm mới chỉ bằng 1 cú nhấp chuột.
        </p>
        <button onclick="openCreateModal()" class="btn btn-primary">➕ Tạo Bộ Lọc Tìm Kiếm Đầu Tiên</button>
    </div>
</div>

<!-- Modal Create Saved Search -->
<div id="ss-modal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(15,23,42,0.6);z-index:9999;align-items:center;justify-content:center;padding:1rem;">
    <div style="background:#fff;border-radius:var(--radius);max-width:520px;width:100%;padding:2rem;box-shadow:var(--shadow);max-height:90vh;overflow-y:auto;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.25rem;">
            <h3 style="font-size:1.2rem;font-weight:700;color:var(--dark);margin:0;">➕ Lưu Bộ Lọc Tìm Kiếm</h3>
            <button type="button" onclick="closeCreateModal()" style="background:none;border:none;font-size:1.5rem;cursor:pointer;color:var(--text-muted);">&times;</button>
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

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1rem;">
                <div class="form-group">
                    <label class="form-label">Ngành nghề</label>
                    <select id="ss-category" class="form-control">
                        <option value="">Tất cả ngành</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Khu vực / Quận</label>
                    <select id="ss-location" class="form-control">
                        <option value="">Tất cả địa điểm</option>
                    </select>
                </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1.5rem;">
                <div class="form-group">
                    <label class="form-label">Ca làm việc</label>
                    <select id="ss-shift" class="form-control">
                        <option value="">Tất cả các ca</option>
                        <option value="morning">🌅 Ca Sáng</option>
                        <option value="afternoon">☀️ Ca Chiều</option>
                        <option value="evening">🌙 Ca Tối</option>
                        <option value="weekend">📅 Cuối tuần</option>
                        <option value="flexible">🔄 Linh hoạt</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Mức lương tối thiểu (VND/h)</label>
                    <input type="number" id="ss-salary-min" class="form-control" placeholder="25000" step="5000" min="0">
                </div>
            </div>

            <div style="display:flex;justify-content:flex-end;gap:0.75rem;">
                <button type="button" onclick="closeCreateModal()" class="btn btn-outline btn-sm">Hủy</button>
                <button type="submit" id="btn-submit-ss" class="btn btn-primary btn-sm">
                    💾 Lưu Bộ Lọc
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", async () => {
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

    await Promise.all([loadCategories(), loadLocations()]);
    loadSavedSearches();
});

async function loadCategories() {
    const res = await apiRequest("/categories");
    if (res && res.success && Array.isArray(res.data)) {
        const sel = document.getElementById("ss-category");
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
        const sel = document.getElementById("ss-location");
        res.data.forEach(l => {
            const opt = document.createElement("option");
            opt.value = l.id;
            opt.textContent = l.name;
            sel.appendChild(opt);
        });
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

    const res = await apiRequest("/saved-searches", { requireAuth: true });
    loadingEl.style.display = "none";

    if (res && res.success && Array.isArray(res.data)) {
        const searches = res.data;
        countText.innerText = `Đang lưu ${searches.length} bộ lọc tìm kiếm`;

        if (searches.length === 0) {
            emptyEl.style.display = "block";
            return;
        }

        container.innerHTML = searches.map(item => {
            const params = new URLSearchParams();
            if (item.keyword) params.append("keyword", item.keyword);
            if (item.category_id) params.append("category_id", item.category_id);
            if (item.location_id) params.append("location_id", item.location_id);
            if (item.shift_type) params.append("shift_type", item.shift_type);
            if (item.salary_min) params.append("salary_min", item.salary_min);

            const searchUrl = `/viec-lam?${params.toString()}`;

            return `
                <div class="job-card" style="padding:1.25rem 1.5rem;margin:0;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;">
                    <div style="flex:1;min-width:260px;">
                        <h3 style="font-size:1.1rem;font-weight:700;color:var(--dark);margin-bottom:0.4rem;">
                            ${escapeHtml(item.name)}
                        </h3>
                        <div style="display:flex;flex-wrap:wrap;gap:0.4rem;align-items:center;">
                            ${item.keyword ? `<span class="badge" style="background:#f1f5f9;color:var(--text);">🔍 "${escapeHtml(item.keyword)}"</span>` : ''}
                            ${item.shift_type ? `<span class="badge badge-shift">⏰ ${getShiftLabel(item.shift_type)}</span>` : ''}
                            ${item.salary_min ? `<span class="badge badge-salary">💰 &ge; ${formatCurrency(item.salary_min)}/h</span>` : ''}
                            <span style="font-size:0.75rem;color:var(--text-muted);margin-left:0.5rem;">Tạo ngày: ${formatDate(item.created_at)}</span>
                        </div>
                    </div>

                    <div style="display:flex;gap:0.75rem;align-items:center;">
                        <a href="${searchUrl}" class="btn btn-primary btn-sm">
                            🔍 Chạy Tìm Kiếm
                        </a>
                        <button onclick="handleDeleteSavedSearch('${escapeHtml(item.id)}')" class="btn btn-sm" style="background:#fff;border:1px solid #cbd5e1;color:var(--danger);" title="Xóa bộ lọc">
                            🗑️
                        </button>
                    </div>
                </div>
            `;
        }).join("");
    } else {
        emptyEl.style.display = "block";
        countText.innerText = "Không thể tải bộ lọc đã lưu.";
    }
}

function openCreateModal() {
    document.getElementById("ss-name").value = "";
    document.getElementById("ss-keyword").value = "";
    document.getElementById("ss-category").value = "";
    document.getElementById("ss-location").value = "";
    document.getElementById("ss-shift").value = "";
    document.getElementById("ss-salary-min").value = "";
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
        salary_min: document.getElementById("ss-salary-min").value ? parseInt(document.getElementById("ss-salary-min").value) : null
    };

    const res = await apiRequest("/saved-searches", {
        method: "POST",
        body: payload,
        requireAuth: true
    });

    btn.disabled = false;
    btn.innerText = "💾 Lưu Bộ Lọc";

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
</script>
