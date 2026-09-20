<?php include __DIR__ . "/nav.php"; ?>

<div class="container" style="margin-bottom:3rem;">
    <!-- Filter Bar -->
    <div style="background:var(--surface);border-radius:var(--radius);border:1px solid var(--border);padding:1.25rem 1.5rem;margin-bottom:1.5rem;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;">
        <div style="display:flex;align-items:center;gap:1rem;flex-wrap:wrap;">
            <!-- Filter Verification Status -->
            <div style="display:flex;align-items:center;gap:0.5rem;">
                <label for="filter-comp-verify" style="font-size:0.88rem;font-weight:600;color:var(--dark);">Xác thực:</label>
                <select id="filter-comp-verify" class="form-control" style="width:auto;padding:0.4rem 0.8rem;font-size:0.88rem;" onchange="loadAdminCompanies(1)">
                    <option value="">Tất cả trạng thái</option>
                    <option value="pending">⏳ Chờ duyệt (Pending)</option>
                    <option value="verified">🟢 Đã xác thực (Verified)</option>
                    <option value="rejected">🔴 Bị từ chối (Rejected)</option>
                </select>
            </div>

            <!-- Search Keyword -->
            <div style="display:flex;align-items:center;gap:0.5rem;">
                <input type="text" id="filter-comp-keyword" class="form-control" placeholder="Tên công ty hoặc email..." style="padding:0.4rem 0.8rem;font-size:0.88rem;width:230px;" onkeydown="if(event.key==='Enter') loadAdminCompanies(1)">
                <button onclick="loadAdminCompanies(1)" class="btn btn-outline btn-sm">Tìm</button>
            </div>
        </div>

        <div id="comps-total-text" style="font-size:0.88rem;color:var(--text-muted);font-weight:600;">
            Đang tải dữ liệu...
        </div>
    </div>

    <!-- Loading Skeleton -->
    <div id="admin-comps-loading" style="display:block;">
        <div class="job-card skeleton" style="height:140px;margin-bottom:1rem;"></div>
        <div class="job-card skeleton" style="height:140px;margin-bottom:1rem;"></div>
        <div class="job-card skeleton" style="height:140px;"></div>
    </div>

    <!-- Error State -->
    <div id="admin-comps-error" style="display:none;background:var(--surface);border-radius:var(--radius);padding:3rem 1.5rem;text-align:center;border:1px solid var(--danger);margin-bottom:2rem;">
        <div style="font-size:3rem;margin-bottom:1rem;color:var(--danger);"><i class="ri-alert-line"></i></div>
        <h3 style="font-weight:700;color:var(--dark);margin-bottom:0.5rem;">Không Thể Tải Danh Sách Doanh Nghiệp</h3>
        <p id="admin-comps-err-msg" style="color:var(--text-muted);margin-bottom:1.5rem;">Đã có lỗi xảy ra khi truy vấn dữ liệu.</p>
        <button onclick="loadAdminCompanies(1)" class="btn btn-primary btn-sm">Thử Lại</button>
    </div>

    <!-- Companies List Container -->
    <div id="admin-comps-container" style="display:flex;flex-direction:column;gap:1.25rem;"></div>

    <!-- Empty State -->
    <div id="admin-comps-empty" style="display:none;background:var(--surface);border-radius:var(--radius);border:1px solid var(--border);padding:3.5rem 1.5rem;text-align:center;">
        <div style="font-size:3rem;margin-bottom:1rem;color:var(--text-muted);"><i class="ri-building-line"></i></div>
        <h3 style="font-size:1.25rem;font-weight:700;color:var(--dark);margin-bottom:0.5rem;">Không Tìm Thấy Doanh Nghiệp Nào</h3>
        <p style="color:var(--text-muted);max-width:450px;margin:0 auto 1.5rem;">
            Không có doanh nghiệp nào phù hợp với điều kiện tìm kiếm.
        </p>
    </div>

    <!-- Pagination -->
    <div id="admin-comps-pagination" style="display:flex;justify-content:center;gap:0.5rem;margin-top:2rem;"></div>
</div>

<!-- Modal: Verify / Reject Company -->
<div id="verify-modal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(15,23,42,0.6);z-index:9999;align-items:center;justify-content:center;padding:1rem;">
    <div class="modal-card" style="background:var(--surface);border-radius:var(--radius);max-width:520px;width:100%;padding:2rem;box-shadow:var(--shadow);">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.25rem;border-bottom:1px solid var(--border);padding-bottom:0.75rem;">
            <h3 style="font-size:1.15rem;font-weight:700;color:var(--dark);margin:0;">
                <i class="ri-shield-check-line" style="color:var(--primary);"></i> Kiểm Duyệt Hồ Sơ Doanh Nghiệp
            </h3>
            <button type="button" onclick="closeVerifyModal()" style="background:none;border:none;font-size:1.5rem;cursor:pointer;color:var(--text-muted);">&times;</button>
        </div>

        <div id="modal-company-info" style="font-size:0.88rem;color:var(--text);margin-bottom:1.25rem;background:var(--bg);padding:0.75rem 1rem;border-radius:var(--radius);line-height:1.5;"></div>

        <form onsubmit="handleSaveCompanyVerification(event)">
            <input type="hidden" id="modal-comp-id">

            <div class="form-group" style="margin-bottom:1.25rem;">
                <label class="form-label" for="modal-verify-status">Quyết định kiểm duyệt <span style="color:var(--danger)">*</span></label>
                <select id="modal-verify-status" class="form-control" required style="font-weight:600;" onchange="toggleRejectionReasonInput()">
                    <option value="verified">🟢 Phê duyệt xác thực (Verified - Được đăng tin công khai)</option>
                    <option value="rejected">🔴 Từ chối xác thực (Rejected - Yêu cầu sửa đổi)</option>
                    <option value="pending">🟡 Đặt lại chờ duyệt (Pending)</option>
                </select>
            </div>

            <div id="rejection-reason-group" class="form-group" style="display:none;margin-bottom:1.5rem;">
                <label class="form-label" for="modal-rejection-reason">
                    Lý do từ chối xác thực <span style="color:var(--danger)">* (Bắt buộc)</span>
                </label>
                <textarea id="modal-rejection-reason" rows="3" class="form-control" placeholder="Nêu rõ lý do từ chối (Ví dụ: Thiếu giấy phép kinh doanh, thông tin liên hệ không chính xác...) để gửi thông báo cho công ty"></textarea>
                <small style="color:#b91c1c;font-size:0.78rem;">Quy tắc: Lý do từ chối là bắt buộc khi chọn trạng thái 'Từ chối'.</small>
            </div>

            <div style="display:flex;justify-content:flex-end;gap:0.75rem;">
                <button type="button" onclick="closeVerifyModal()" class="btn btn-outline btn-sm">Hủy</button>
                <button type="submit" id="btn-save-verification" class="btn btn-primary btn-sm">
                    💾 Lưu Quyết Định
                </button>
            </div>
        </form>
    </div>
</div>

<script>
let currentCompPage = 1;
let currentCompsList = [];

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

    // Check query param for pre-selected status
    const urlParams = new URLSearchParams(window.location.search);
    const preStatus = urlParams.get("verification_status");
    if (preStatus) {
        document.getElementById("filter-comp-verify").value = preStatus;
    }

    loadAdminCompanies(1);
});

function getVerificationBadge(status) {
    if (status === "verified") {
        return `<span class="badge" style="background:#dcfce7;color:#166534;font-weight:700;font-size:0.8rem;padding:0.25rem 0.65rem;border-radius:20px;">🟢 Đã xác thực</span>`;
    } else if (status === "rejected") {
        return `<span class="badge" style="background:#fee2e2;color:#991b1b;font-weight:700;font-size:0.8rem;padding:0.25rem 0.65rem;border-radius:20px;">🔴 Bị từ chối</span>`;
    } else {
        return `<span class="badge" style="background:#fef3c7;color:#92400e;font-weight:700;font-size:0.8rem;padding:0.25rem 0.65rem;border-radius:20px;">⏳ Chờ duyệt</span>`;
    }
}

async function loadAdminCompanies(page = 1) {
    currentCompPage = page;
    const loadingEl = document.getElementById("admin-comps-loading");
    const errorEl = document.getElementById("admin-comps-error");
    const container = document.getElementById("admin-comps-container");
    const emptyEl = document.getElementById("admin-comps-empty");
    const totalText = document.getElementById("comps-total-text");
    const pagEl = document.getElementById("admin-comps-pagination");

    loadingEl.style.display = "block";
    errorEl.style.display = "none";
    container.innerHTML = "";
    emptyEl.style.display = "none";
    pagEl.innerHTML = "";

    const verifyStatus = document.getElementById("filter-comp-verify").value;
    const keyword = document.getElementById("filter-comp-keyword").value.trim();

    const params = new URLSearchParams({ page: currentCompPage, per_page: 10 });
    if (verifyStatus) params.append("verification_status", verifyStatus);
    if (keyword) params.append("keyword", keyword);

    const res = await apiRequest(`/admin/companies?${params.toString()}`, { requireAuth: true });
    loadingEl.style.display = "none";

    if (res && res.success && Array.isArray(res.data)) {
        currentCompsList = res.data;
        const meta = res.meta || {};
        const total = meta.total !== undefined ? meta.total : currentCompsList.length;

        totalText.innerText = `Tìm thấy ${total} doanh nghiệp`;

        if (currentCompsList.length === 0) {
            emptyEl.style.display = "block";
            return;
        }

        container.innerHTML = currentCompsList.map(c => `
            <div class="job-card" style="padding:1.5rem;margin:0;border-left:4px solid ${c.verification_status === 'verified' ? '#10b981' : (c.verification_status === 'rejected' ? '#ef4444' : '#f59e0b')};">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:1rem;margin-bottom:0.75rem;">
                    <div>
                        <div style="display:flex;align-items:center;gap:0.75rem;flex-wrap:wrap;margin-bottom:0.25rem;">
                            <h3 style="font-size:1.15rem;font-weight:800;color:var(--dark);margin:0;">
                                ${escapeHtml(c.name)}
                            </h3>
                            ${getVerificationBadge(c.verification_status)}
                        </div>
                        <div style="font-size:0.85rem;color:var(--text-muted);">
                            👤 Người liên hệ: <strong>${escapeHtml(c.contact_person || "Chưa cập nhật")}</strong> &bull; 
                            📞 SĐT: <strong>${escapeHtml(c.contact_phone || "Chưa cập nhật")}</strong> &bull; 
                            ✉️ Email tài khoản: <strong>${escapeHtml(c.user_email || "")}</strong>
                        </div>
                    </div>

                    <div>
                        <button onclick="openVerifyModal('${escapeHtml(c.id)}')" class="btn btn-primary btn-sm" style="font-size:0.82rem;">
                            ⚙️ Kiểm Duyệt Xác Thực
                        </button>
                    </div>
                </div>

                <div style="font-size:0.85rem;color:var(--text);margin-bottom:0.5rem;">
                    📍 <strong>Địa chỉ:</strong> ${escapeHtml([c.address, c.district, c.city].filter(Boolean).join(", ") || "Chưa cung cấp")}
                    ${c.website ? ` &bull; 🌐 <strong>Website:</strong> <a href="${escapeHtml(c.website)}" target="_blank" style="color:var(--primary);">${escapeHtml(c.website)}</a>` : ''}
                </div>

                ${c.description ? `
                    <div style="font-size:0.85rem;color:var(--text-muted);margin-bottom:0.5rem;line-height:1.4;">
                        ${escapeHtml(c.description)}
                    </div>
                ` : ''}

                ${c.verification_status === 'rejected' && c.rejection_reason ? `
                    <div style="font-size:0.82rem;color:#991b1b;background:#fee2e2;border-radius:var(--radius);padding:0.5rem 0.75rem;margin-top:0.5rem;">
                        <strong>Lý do từ chối:</strong> ${escapeHtml(c.rejection_reason)}
                    </div>
                ` : ''}
            </div>
        `).join("");

        // Render Pagination
        const totalPages = meta.total_pages || 1;
        if (totalPages > 1) {
            let pagHtml = "";
            for (let i = 1; i <= totalPages; i++) {
                pagHtml += `<button onclick="loadAdminCompanies(${i})" class="btn btn-sm ${i === currentCompPage ? 'btn-primary' : 'btn-outline'}" style="min-width:36px;">${i}</button>`;
            }
            pagEl.innerHTML = pagHtml;
        }

    } else {
        errorEl.style.display = "block";
        document.getElementById("admin-comps-err-msg").innerText = (res && res.message) ? res.message : "Đã có lỗi xảy ra.";
    }
}

function openVerifyModal(compId) {
    const comp = currentCompsList.find(c => c.id === compId);
    if (!comp) return;

    document.getElementById("modal-comp-id").value = comp.id;
    document.getElementById("modal-company-info").innerHTML = `
        <strong>Doanh nghiệp:</strong> ${escapeHtml(comp.name)}<br>
        <strong>Người liên hệ:</strong> ${escapeHtml(comp.contact_person || "Chưa có")} &bull; <strong>SĐT:</strong> ${escapeHtml(comp.contact_phone || "Chưa có")}<br>
        <strong>Trạng thái hiện tại:</strong> ${escapeHtml(comp.verification_status)}
    `;

    document.getElementById("modal-verify-status").value = comp.verification_status || "pending";
    document.getElementById("modal-rejection-reason").value = comp.rejection_reason || "";
    toggleRejectionReasonInput();

    document.getElementById("verify-modal").style.display = "flex";
}

function closeVerifyModal() {
    document.getElementById("verify-modal").style.display = "none";
}

function toggleRejectionReasonInput() {
    const status = document.getElementById("modal-verify-status").value;
    const reasonGroup = document.getElementById("rejection-reason-group");
    if (status === "rejected") {
        reasonGroup.style.display = "block";
        document.getElementById("modal-rejection-reason").setAttribute("required", "required");
    } else {
        reasonGroup.style.display = "none";
        document.getElementById("modal-rejection-reason").removeAttribute("required");
    }
}

async function handleSaveCompanyVerification(e) {
    e.preventDefault();
    const btn = document.getElementById("btn-save-verification");
    const compId = document.getElementById("modal-comp-id").value;
    const newStatus = document.getElementById("modal-verify-status").value;
    const reason = document.getElementById("modal-rejection-reason").value.trim();

    if (newStatus === "rejected" && !reason) {
        showToast("Lý do từ chối là bắt buộc khi từ chối xác thực công ty.", "error");
        return;
    }

    btn.disabled = true;
    btn.innerText = "Đang lưu...";

    const res = await apiRequest(`/admin/companies/${encodeURIComponent(compId)}/verification`, {
        method: "PATCH",
        body: {
            verification_status: newStatus,
            rejection_reason: newStatus === "rejected" ? reason : null
        },
        requireAuth: true
    });

    btn.disabled = false;
    btn.innerText = "💾 Lưu Quyết Định";

    if (res && res.success) {
        closeVerifyModal();
        showToast("Cập nhật trạng thái xác thực doanh nghiệp thành công!", "success");
        loadAdminCompanies(currentCompPage);
    } else {
        let msg = (res && res.message) ? res.message : "Cập nhật thất bại.";
        if (res && res.errors) {
            msg += " " + Object.values(res.errors).flat().map(escapeHtml).join(" ");
        }
        showToast(msg, "error");
    }
}
</script>
