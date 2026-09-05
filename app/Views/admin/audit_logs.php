<?php include __DIR__ . "/nav.php"; ?>

<div class="container" style="margin-bottom:3rem;">
    <!-- Filter Bar -->
    <div style="background:#fff;border-radius:var(--radius);border:1px solid var(--border);padding:1.25rem 1.5rem;margin-bottom:1.5rem;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;">
        <div style="display:flex;align-items:center;gap:1rem;flex-wrap:wrap;">
            <!-- Filter Action -->
            <div style="display:flex;align-items:center;gap:0.5rem;">
                <label for="filter-audit-action" style="font-size:0.88rem;font-weight:600;color:var(--dark);">Hành động:</label>
                <select id="filter-audit-action" class="form-control" style="width:auto;padding:0.4rem 0.8rem;font-size:0.88rem;" onchange="loadAdminAuditLogs(1)">
                    <option value="">Tất cả hành động</option>
                    <option value="moderate_job">Kiểm duyệt việc làm (moderate_job)</option>
                    <option value="verify_company">Xác thực doanh nghiệp (verify_company)</option>
                    <option value="update_user_status">Đổi trạng thái tài khoản (update_user_status)</option>
                </select>
            </div>

            <!-- Filter Target Type -->
            <div style="display:flex;align-items:center;gap:0.5rem;">
                <label for="filter-audit-target" style="font-size:0.88rem;font-weight:600;color:var(--dark);">Đối tượng:</label>
                <select id="filter-audit-target" class="form-control" style="width:auto;padding:0.4rem 0.8rem;font-size:0.88rem;" onchange="loadAdminAuditLogs(1)">
                    <option value="">Tất cả đối tượng</option>
                    <option value="job">Việc làm (job)</option>
                    <option value="company">Doanh nghiệp (company)</option>
                    <option value="user">Người dùng (user)</option>
                </select>
            </div>
        </div>

        <div id="audit-total-text" style="font-size:0.88rem;color:var(--text-muted);font-weight:600;">
            Đang tải dữ liệu...
        </div>
    </div>

    <!-- Loading Skeleton -->
    <div id="admin-audit-loading" style="display:block;">
        <div class="job-card skeleton" style="height:70px;margin-bottom:0.75rem;"></div>
        <div class="job-card skeleton" style="height:70px;margin-bottom:0.75rem;"></div>
        <div class="job-card skeleton" style="height:70px;margin-bottom:0.75rem;"></div>
        <div class="job-card skeleton" style="height:70px;"></div>
    </div>

    <!-- Error State -->
    <div id="admin-audit-error" style="display:none;background:#fff;border-radius:var(--radius);padding:3rem 1.5rem;text-align:center;border:1px solid var(--danger);margin-bottom:2rem;">
        <div style="font-size:3rem;margin-bottom:1rem;">⚠️</div>
        <h3 style="font-weight:700;color:var(--dark);margin-bottom:0.5rem;">Không Thể Tải Nhật Ký Quản Trị</h3>
        <p id="admin-audit-err-msg" style="color:var(--text-muted);margin-bottom:1.5rem;">Đã có lỗi xảy ra khi truy vấn dữ liệu audit logs.</p>
        <button onclick="loadAdminAuditLogs(1)" class="btn btn-primary btn-sm">Thử Lại</button>
    </div>

    <!-- Audit Logs Table -->
    <div id="admin-audit-table-card" style="display:none;background:#fff;border-radius:var(--radius);border:1px solid var(--border);overflow:hidden;">
        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;text-align:left;font-size:0.88rem;">
                <thead>
                    <tr style="background:#f8fafc;border-bottom:1px solid var(--border);color:var(--dark);font-weight:700;">
                        <th style="padding:1rem 1.25rem;">Thời Gian</th>
                        <th style="padding:1rem 1.25rem;">Quản Trị Viên</th>
                        <th style="padding:1rem 1.25rem;">Hành Động</th>
                        <th style="padding:1rem 1.25rem;">Đối Tượng (Mã)</th>
                        <th style="padding:1rem 1.25rem;">Chi Tiết Thay Đổi (Metadata)</th>
                    </tr>
                </thead>
                <tbody id="admin-audit-tbody"></tbody>
            </table>
        </div>
    </div>

    <!-- Empty State -->
    <div id="admin-audit-empty" style="display:none;background:#fff;border-radius:var(--radius);border:1px solid var(--border);padding:3.5rem 1.5rem;text-align:center;">
        <div style="font-size:3rem;margin-bottom:1rem;">📜</div>
        <h3 style="font-size:1.25rem;font-weight:700;color:var(--dark);margin-bottom:0.5rem;">Không Có Nhật Ký Thao Tác Nào</h3>
        <p style="color:var(--text-muted);max-width:450px;margin:0 auto;">
            Chưa có thao tác quản trị nào được ghi nhận với bộ lọc này.
        </p>
    </div>

    <!-- Pagination -->
    <div id="admin-audit-pagination" style="display:flex;justify-content:center;gap:0.5rem;margin-top:2rem;"></div>
</div>

<script>
let currentAuditPage = 1;

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

    loadAdminAuditLogs(1);
});

function getActionBadge(action) {
    if (action === "moderate_job") {
        return `<span class="badge" style="background:#eff6ff;color:#1e40af;font-weight:700;font-size:0.78rem;">Kiểm duyệt việc làm</span>`;
    } else if (action === "verify_company") {
        return `<span class="badge" style="background:#fef3c7;color:#92400e;font-weight:700;font-size:0.78rem;">Xác thực công ty</span>`;
    } else if (action === "update_user_status") {
        return `<span class="badge" style="background:#fee2e2;color:#991b1b;font-weight:700;font-size:0.78rem;">Đổi trạng thái User</span>`;
    } else {
        return `<span class="badge" style="background:#f1f5f9;color:#475569;font-weight:700;font-size:0.78rem;">${escapeHtml(action)}</span>`;
    }
}

async function loadAdminAuditLogs(page = 1) {
    currentAuditPage = page;
    const loadingEl = document.getElementById("admin-audit-loading");
    const errorEl = document.getElementById("admin-audit-error");
    const tableCard = document.getElementById("admin-audit-table-card");
    const tbody = document.getElementById("admin-audit-tbody");
    const emptyEl = document.getElementById("admin-audit-empty");
    const totalText = document.getElementById("audit-total-text");
    const pagEl = document.getElementById("admin-audit-pagination");

    loadingEl.style.display = "block";
    errorEl.style.display = "none";
    tableCard.style.display = "none";
    tbody.innerHTML = "";
    emptyEl.style.display = "none";
    pagEl.innerHTML = "";

    const action = document.getElementById("filter-audit-action").value;
    const targetType = document.getElementById("filter-audit-target").value;

    const params = new URLSearchParams({ page: currentAuditPage, per_page: 15 });
    if (action) params.append("action", action);
    if (targetType) params.append("target_type", targetType);

    const res = await apiRequest(`/admin/audit-logs?${params.toString()}`, { requireAuth: true });
    loadingEl.style.display = "none";

    if (res && res.success && Array.isArray(res.data)) {
        const logs = res.data;
        const meta = res.meta || {};
        const total = meta.total !== undefined ? meta.total : logs.length;

        totalText.innerText = `Tìm thấy ${total} nhật ký thao tác`;

        if (logs.length === 0) {
            emptyEl.style.display = "block";
            return;
        }

        tableCard.style.display = "block";
        tbody.innerHTML = logs.map(l => {
            let metadataStr = "";
            if (l.metadata) {
                if (typeof l.metadata === "object") {
                    metadataStr = JSON.stringify(l.metadata);
                } else {
                    metadataStr = String(l.metadata);
                }
            }

            return `
                <tr style="border-bottom:1px solid var(--border);transition:background 0.15s ease;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='transparent'">
                    <td style="padding:0.85rem 1.25rem;color:var(--text-muted);white-space:nowrap;font-size:0.82rem;">
                        🕒 ${formatDate(l.created_at)}
                    </td>
                    <td style="padding:0.85rem 1.25rem;font-weight:600;color:var(--dark);">
                        ${escapeHtml(l.admin_name || l.admin_id || "Admin")}
                    </td>
                    <td style="padding:0.85rem 1.25rem;">
                        ${getActionBadge(l.action)}
                    </td>
                    <td style="padding:0.85rem 1.25rem;color:var(--text);">
                        <strong>${escapeHtml(l.target_type)}</strong>: <code>${escapeHtml(l.target_id)}</code>
                    </td>
                    <td style="padding:0.85rem 1.25rem;max-width:320px;">
                        <code style="display:block;font-size:0.78rem;background:#f1f5f9;padding:0.35rem 0.55rem;border-radius:4px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="${escapeHtml(metadataStr)}">
                            ${escapeHtml(metadataStr)}
                        </code>
                    </td>
                </tr>
            `;
        }).join("");

        // Render Pagination
        const totalPages = meta.total_pages || 1;
        if (totalPages > 1) {
            let pagHtml = "";
            for (let i = 1; i <= totalPages; i++) {
                pagHtml += `<button onclick="loadAdminAuditLogs(${i})" class="btn btn-sm ${i === currentAuditPage ? 'btn-primary' : 'btn-outline'}" style="min-width:36px;">${i}</button>`;
            }
            pagEl.innerHTML = pagHtml;
        }

    } else {
        errorEl.style.display = "block";
        document.getElementById("admin-audit-err-msg").innerText = (res && res.message) ? res.message : "Đã có lỗi xảy ra.";
    }
}
</script>
