<?php include __DIR__ . "/nav.php"; ?>

<div class="container" style="margin-bottom:3rem;">
    <!-- Filter Bar -->
    <div style="background:#fff;border-radius:var(--radius);border:1px solid var(--border);padding:1.25rem 1.5rem;margin-bottom:1.5rem;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;">
        <div style="display:flex;align-items:center;gap:1rem;flex-wrap:wrap;">
            <!-- Filter Role -->
            <div style="display:flex;align-items:center;gap:0.5rem;">
                <label for="filter-user-role" style="font-size:0.88rem;font-weight:600;color:var(--dark);">Vai trò:</label>
                <select id="filter-user-role" class="form-control" style="width:auto;padding:0.4rem 0.8rem;font-size:0.88rem;" onchange="loadAdminUsers(1)">
                    <option value="">Tất cả vai trò</option>
                    <option value="student">🎓 Sinh viên (Student)</option>
                    <option value="company">🏢 Nhà tuyển dụng (Company)</option>
                    <option value="admin">🛡️ Quản trị viên (Admin)</option>
                </select>
            </div>

            <!-- Filter Status -->
            <div style="display:flex;align-items:center;gap:0.5rem;">
                <label for="filter-user-status" style="font-size:0.88rem;font-weight:600;color:var(--dark);">Trạng thái:</label>
                <select id="filter-user-status" class="form-control" style="width:auto;padding:0.4rem 0.8rem;font-size:0.88rem;" onchange="loadAdminUsers(1)">
                    <option value="">Tất cả trạng thái</option>
                    <option value="active">🟢 Đang hoạt động (Active)</option>
                    <option value="suspended">🟡 Tạm khóa (Suspended)</option>
                    <option value="banned">🔴 Bị cấm (Banned)</option>
                </select>
            </div>

            <!-- Search Keyword -->
            <div style="display:flex;align-items:center;gap:0.5rem;">
                <input type="text" id="filter-user-keyword" class="form-control" placeholder="Tên hoặc email..." style="padding:0.4rem 0.8rem;font-size:0.88rem;width:220px;" onkeydown="if(event.key==='Enter') loadAdminUsers(1)">
                <button onclick="loadAdminUsers(1)" class="btn btn-outline btn-sm">Tìm</button>
            </div>
        </div>

        <div id="users-total-text" style="font-size:0.88rem;color:var(--text-muted);font-weight:600;">
            Đang tải dữ liệu...
        </div>
    </div>

    <!-- Loading Skeleton -->
    <div id="admin-users-loading" style="display:block;">
        <div class="job-card skeleton" style="height:80px;margin-bottom:0.75rem;"></div>
        <div class="job-card skeleton" style="height:80px;margin-bottom:0.75rem;"></div>
        <div class="job-card skeleton" style="height:80px;margin-bottom:0.75rem;"></div>
        <div class="job-card skeleton" style="height:80px;"></div>
    </div>

    <!-- Error State -->
    <div id="admin-users-error" style="display:none;background:#fff;border-radius:var(--radius);padding:3rem 1.5rem;text-align:center;border:1px solid var(--danger);margin-bottom:2rem;">
        <div style="font-size:3rem;margin-bottom:1rem;">⚠️</div>
        <h3 style="font-weight:700;color:var(--dark);margin-bottom:0.5rem;">Không Thể Tải Danh Sách Người Dùng</h3>
        <p id="admin-users-err-msg" style="color:var(--text-muted);margin-bottom:1.5rem;">Đã có lỗi xảy ra khi truy vấn dữ liệu từ API.</p>
        <button onclick="loadAdminUsers(1)" class="btn btn-primary btn-sm">Thử Lại</button>
    </div>

    <!-- Users Table Container -->
    <div id="admin-users-table-card" style="display:none;background:#fff;border-radius:var(--radius);border:1px solid var(--border);overflow:hidden;">
        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;text-align:left;font-size:0.88rem;">
                <thead>
                    <tr style="background:#f8fafc;border-bottom:1px solid var(--border);color:var(--dark);font-weight:700;">
                        <th style="padding:1rem 1.25rem;">Họ Và Tên</th>
                        <th style="padding:1rem 1.25rem;">Email</th>
                        <th style="padding:1rem 1.25rem;">Vai Trò</th>
                        <th style="padding:1rem 1.25rem;">Trạng Thái</th>
                        <th style="padding:1rem 1.25rem;">Ngày Tạo</th>
                        <th style="padding:1rem 1.25rem;text-align:right;">Thao Tác</th>
                    </tr>
                </thead>
                <tbody id="admin-users-tbody"></tbody>
            </table>
        </div>
    </div>

    <!-- Empty State -->
    <div id="admin-users-empty" style="display:none;background:#fff;border-radius:var(--radius);border:1px solid var(--border);padding:3.5rem 1.5rem;text-align:center;">
        <div style="font-size:3rem;margin-bottom:1rem;">👥</div>
        <h3 style="font-size:1.25rem;font-weight:700;color:var(--dark);margin-bottom:0.5rem;">Không Tìm Thấy Người Dùng Nào</h3>
        <p style="color:var(--text-muted);max-width:450px;margin:0 auto 1.5rem;">
            Không có tài khoản nào phù hợp với bộ lọc tìm kiếm hiện tại.
        </p>
        <button onclick="document.getElementById('filter-user-keyword').value='';document.getElementById('filter-user-role').value='';document.getElementById('filter-user-status').value='';loadAdminUsers(1);" class="btn btn-outline btn-sm">
            Xóa bộ lọc
        </button>
    </div>

    <!-- Pagination -->
    <div id="admin-users-pagination" style="display:flex;justify-content:center;gap:0.5rem;margin-top:2rem;"></div>
</div>

<!-- Modal: Update User Status -->
<div id="user-status-modal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(15,23,42,0.6);z-index:9999;align-items:center;justify-content:center;padding:1rem;">
    <div style="background:#fff;border-radius:var(--radius);max-width:460px;width:100%;padding:2rem;box-shadow:var(--shadow);">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.25rem;border-bottom:1px solid var(--border);padding-bottom:0.75rem;">
            <h3 style="font-size:1.15rem;font-weight:700;color:var(--dark);margin:0;">
                ⚙️ Cập Nhật Trạng Thái Tài Khoản
            </h3>
            <button type="button" onclick="closeUserStatusModal()" style="background:none;border:none;font-size:1.5rem;cursor:pointer;color:var(--text-muted);">&times;</button>
        </div>

        <div id="modal-user-summary" style="font-size:0.88rem;color:var(--text);margin-bottom:1.25rem;background:#f8fafc;padding:0.75rem 1rem;border-radius:var(--radius);line-height:1.5;"></div>

        <form onsubmit="handleSaveUserStatus(event)">
            <input type="hidden" id="modal-user-id">

            <div class="form-group" style="margin-bottom:1.5rem;">
                <label class="form-label" for="modal-user-status-select">Chọn trạng thái mới <span style="color:var(--danger)">*</span></label>
                <select id="modal-user-status-select" class="form-control" required style="font-weight:600;">
                    <option value="active">🟢 Đang hoạt động (Active)</option>
                    <option value="suspended">🟡 Tạm khóa / Đình chỉ (Suspended)</option>
                    <option value="banned">🔴 Cấm tài khoản vĩnh viễn (Banned)</option>
                </select>
                <small style="color:var(--text-muted);font-size:0.78rem;margin-top:0.35rem;display:block;">
                    Lưu ý: Không thể vô hiệu hóa tài khoản Quản trị viên (Admin) đang hoạt động duy nhất của hệ thống.
                </small>
            </div>

            <div style="display:flex;justify-content:flex-end;gap:0.75rem;">
                <button type="button" onclick="closeUserStatusModal()" class="btn btn-outline btn-sm">Hủy</button>
                <button type="submit" id="btn-save-user-status" class="btn btn-primary btn-sm">
                    💾 Cập Nhật Trạng Thái
                </button>
            </div>
        </form>
    </div>
</div>

<script>
let currentUserPage = 1;
let currentUsersList = [];

document.addEventListener("DOMContentLoaded", () => {
    // Auth UX Guard
    if (!TokenStorage.isLoggedIn()) {
        showToast("Vui lòng đăng nhập với tài khoản Quản trị viên.", "error");
        window.location.href = `/login?login_required=1&redirect=${encodeURIComponent(window.location.pathname)}`;
        return;
    }
    const user = TokenStorage.getUser();
    if (!user || user.role !== "admin") {
        showToast("Chỉ Quản trị viên (Admin) mới có quyền truy cập khu vực này.", "error");
        setTimeout(() => {
            window.location.href = user && user.role === "company" ? "/company/dashboard" : (user && user.role === "student" ? "/student/dashboard" : "/");
        }, 800);
        return;
    }

    loadAdminUsers(1);
});

function getUserRoleBadge(role) {
    if (role === "admin") {
        return `<span class="badge" style="background:#fee2e2;color:#991b1b;font-weight:700;font-size:0.78rem;">🛡️ Quản trị</span>`;
    } else if (role === "company") {
        return `<span class="badge badge-salary" style="font-weight:700;font-size:0.78rem;">🏢 Doanh nghiệp</span>`;
    } else {
        return `<span class="badge badge-shift" style="font-weight:700;font-size:0.78rem;">🎓 Sinh viên</span>`;
    }
}

function getUserStatusBadge(status) {
    if (status === "active") {
        return `<span class="badge" style="background:#dcfce7;color:#166534;font-weight:700;font-size:0.78rem;">🟢 Hoạt động</span>`;
    } else if (status === "suspended") {
        return `<span class="badge" style="background:#fef3c7;color:#92400e;font-weight:700;font-size:0.78rem;">🟡 Tạm khóa</span>`;
    } else {
        return `<span class="badge" style="background:#fee2e2;color:#7f1d1d;font-weight:700;font-size:0.78rem;">🔴 Bị cấm</span>`;
    }
}

async function loadAdminUsers(page = 1) {
    currentUserPage = page;
    const loadingEl = document.getElementById("admin-users-loading");
    const errorEl = document.getElementById("admin-users-error");
    const tableCard = document.getElementById("admin-users-table-card");
    const tbody = document.getElementById("admin-users-tbody");
    const emptyEl = document.getElementById("admin-users-empty");
    const totalText = document.getElementById("users-total-text");
    const pagEl = document.getElementById("admin-users-pagination");

    loadingEl.style.display = "block";
    errorEl.style.display = "none";
    tableCard.style.display = "none";
    tbody.innerHTML = "";
    emptyEl.style.display = "none";
    pagEl.innerHTML = "";

    const role = document.getElementById("filter-user-role").value;
    const status = document.getElementById("filter-user-status").value;
    const keyword = document.getElementById("filter-user-keyword").value.trim();

    const params = new URLSearchParams({ page: currentUserPage, per_page: 10 });
    if (role) params.append("role", role);
    if (status) params.append("status", status);
    if (keyword) params.append("keyword", keyword);

    const res = await apiRequest(`/admin/users?${params.toString()}`, { requireAuth: true });
    loadingEl.style.display = "none";

    if (res && res.success && Array.isArray(res.data)) {
        currentUsersList = res.data;
        const meta = res.meta || {};
        const total = meta.total !== undefined ? meta.total : currentUsersList.length;

        totalText.innerText = `Tìm thấy ${total} người dùng`;

        if (currentUsersList.length === 0) {
            emptyEl.style.display = "block";
            return;
        }

        tableCard.style.display = "block";
        tbody.innerHTML = currentUsersList.map(u => `
            <tr style="border-bottom:1px solid var(--border);transition:background 0.15s ease;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='transparent'">
                <td style="padding:1rem 1.25rem;font-weight:600;color:var(--dark);">
                    ${escapeHtml(u.name || "N/A")}
                </td>
                <td style="padding:1rem 1.25rem;color:var(--text);">
                    ${escapeHtml(u.email || "")}
                </td>
                <td style="padding:1rem 1.25rem;">
                    ${getUserRoleBadge(u.role)}
                </td>
                <td style="padding:1rem 1.25rem;">
                    ${getUserStatusBadge(u.status)}
                </td>
                <td style="padding:1rem 1.25rem;color:var(--text-muted);font-size:0.82rem;">
                    ${formatDate(u.created_at)}
                </td>
                <td style="padding:1rem 1.25rem;text-align:right;">
                    <button onclick="openUserStatusModal('${escapeHtml(u.id)}')" class="btn btn-outline btn-sm" style="font-size:0.8rem;padding:0.25rem 0.65rem;">
                        ⚙️ Đổi Trạng Thái
                    </button>
                </td>
            </tr>
        `).join("");

        // Render Pagination
        const totalPages = meta.total_pages || 1;
        if (totalPages > 1) {
            let pagHtml = "";
            for (let i = 1; i <= totalPages; i++) {
                pagHtml += `<button onclick="loadAdminUsers(${i})" class="btn btn-sm ${i === currentUserPage ? 'btn-primary' : 'btn-outline'}" style="min-width:36px;">${i}</button>`;
            }
            pagEl.innerHTML = pagHtml;
        }

    } else {
        errorEl.style.display = "block";
        document.getElementById("admin-users-err-msg").innerText = (res && res.message) ? res.message : "Đã có lỗi xảy ra.";
    }
}

function openUserStatusModal(userId) {
    const user = currentUsersList.find(u => u.id === userId);
    if (!user) return;

    document.getElementById("modal-user-id").value = user.id;
    document.getElementById("modal-user-summary").innerHTML = `
        <strong>Tài khoản:</strong> ${escapeHtml(user.name)} (${escapeHtml(user.email)})<br>
        <strong>Vai trò:</strong> ${escapeHtml(user.role)} &bull; 
        <strong>Trạng thái hiện tại:</strong> ${escapeHtml(user.status)}
    `;

    document.getElementById("modal-user-status-select").value = user.status || "active";
    document.getElementById("user-status-modal").style.display = "flex";
}

function closeUserStatusModal() {
    document.getElementById("user-status-modal").style.display = "none";
}

async function handleSaveUserStatus(e) {
    e.preventDefault();
    const btn = document.getElementById("btn-save-user-status");
    const userId = document.getElementById("modal-user-id").value;
    const newStatus = document.getElementById("modal-user-status-select").value;

    btn.disabled = true;
    btn.innerText = "Đang lưu...";

    const res = await apiRequest(`/admin/users/${encodeURIComponent(userId)}/status`, {
        method: "PATCH",
        body: { status: newStatus },
        requireAuth: true
    });

    btn.disabled = false;
    btn.innerText = "💾 Cập Nhật Trạng Thái";

    if (res && res.success) {
        closeUserStatusModal();
        showToast("Cập nhật trạng thái người dùng thành công!", "success");
        loadAdminUsers(currentUserPage);
    } else {
        let msg = (res && res.message) ? res.message : "Cập nhật thất bại.";
        if (res && res.errors) {
            msg += " " + Object.values(res.errors).flat().map(escapeHtml).join(" ");
        }
        showToast(msg, "error");
    }
}
</script>
