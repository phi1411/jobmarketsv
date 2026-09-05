<?php include __DIR__ . "/nav.php"; ?>

<div class="container" style="margin-bottom:3rem;max-width:850px;">
    <!-- Loading State -->
    <div id="comp-prof-loading" style="display:block;">
        <div class="job-card skeleton" style="height:120px;margin-bottom:1.5rem;"></div>
        <div class="job-card skeleton" style="height:350px;"></div>
    </div>

    <!-- Main Container -->
    <div id="comp-prof-content" style="display:none;">
        <!-- Verification Status Banner -->
        <div id="banner-verification" style="border-radius:var(--radius);padding:1.25rem 1.5rem;margin-bottom:1.5rem;display:flex;align-items:flex-start;gap:1rem;border:1px solid transparent;">
            <div id="verify-icon" style="font-size:2rem;line-height:1;"></div>
            <div>
                <h3 id="verify-title" style="font-size:1.1rem;font-weight:700;margin-bottom:0.25rem;"></h3>
                <p id="verify-desc" style="font-size:0.88rem;margin:0;line-height:1.5;"></p>
                <div id="verify-reject-box" style="display:none;margin-top:0.75rem;padding:0.6rem 0.85rem;background:#fee2e2;border-radius:var(--radius);font-size:0.85rem;color:#991b1b;">
                    <strong>Lý do từ chối:</strong> <span id="verify-reject-reason"></span>
                </div>
            </div>
        </div>

        <!-- Form Edit Company Profile -->
        <div style="background:#fff;border-radius:var(--radius);border:1px solid var(--border);padding:2rem;">
            <div style="margin-bottom:1.5rem;border-bottom:1px solid var(--border);padding-bottom:1rem;">
                <h2 style="font-size:1.25rem;font-weight:700;color:var(--dark);margin-bottom:0.25rem;">
                    🏢 Thông Tin Hồ Sơ Doanh Nghiệp
                </h2>
                <p style="color:var(--text-muted);font-size:0.88rem;margin:0;">
                    Thông tin công ty sẽ xuất hiện công khai trên các tin tuyển dụng và trang chi tiết của doanh nghiệp.
                </p>
            </div>

            <div id="prof-alert-box" style="display:none;margin-bottom:1.25rem;" class="toast"></div>

            <form id="form-company-profile" onsubmit="handleSaveCompanyProfile(event)">
                <!-- Row 1: Company Name -->
                <div class="form-group" style="margin-bottom:1.25rem;">
                    <label class="form-label" for="cp-name">Tên doanh nghiệp / Đơn vị tuyển dụng <span style="color:var(--danger)">*</span></label>
                    <input type="text" id="cp-name" class="form-control" placeholder="Ví dụ: Công ty Cổ phần Highlands Coffee Việt Nam" required minlength="2">
                </div>

                <!-- Row 2: Contact Person & Phone -->
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;margin-bottom:1.25rem;">
                    <div class="form-group">
                        <label class="form-label" for="cp-contact-person">Người liên hệ / Bộ phận nhân sự</label>
                        <input type="text" id="cp-contact-person" class="form-control" placeholder="Nguyễn Văn A (Phòng Tuyển Dụng)">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="cp-contact-phone">Số điện thoại liên hệ</label>
                        <input type="tel" id="cp-contact-phone" class="form-control" placeholder="02431234567 hoặc 0988776655">
                    </div>
                </div>

                <!-- Row 3: City & District -->
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;margin-bottom:1.25rem;">
                    <div class="form-group">
                        <label class="form-label" for="cp-city">Tỉnh / Thành phố</label>
                        <input type="text" id="cp-city" class="form-control" placeholder="Hà Nội, TP. Hồ Chí Minh...">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="cp-district">Quận / Huyện</label>
                        <input type="text" id="cp-district" class="form-control" placeholder="Cầu Giấy, Đống Đa, Quận 1...">
                    </div>
                </div>

                <!-- Row 4: Address -->
                <div class="form-group" style="margin-bottom:1.25rem;">
                    <label class="form-label" for="cp-address">Địa chỉ trụ sở / văn phòng</label>
                    <input type="text" id="cp-address" class="form-control" placeholder="Số 123 Đường Xuân Thủy, Phường Dịch Vọng Hậu">
                </div>

                <!-- Row 5: Website -->
                <div class="form-group" style="margin-bottom:1.25rem;">
                    <label class="form-label" for="cp-website">Website chính thức</label>
                    <input type="url" id="cp-website" class="form-control" placeholder="https://highlandscoffee.com.vn">
                </div>

                <!-- Row 6: Description -->
                <div class="form-group" style="margin-bottom:1.5rem;">
                    <label class="form-label" for="cp-desc">Giới thiệu ngắn về doanh nghiệp</label>
                    <textarea id="cp-desc" rows="5" class="form-control" placeholder="Mô tả văn hóa công ty, quy mô, định hướng tuyển dụng sinh viên part-time..."></textarea>
                </div>

                <!-- Submit Button -->
                <div style="display:flex;justify-content:flex-end;gap:1rem;border-top:1px solid var(--border);padding-top:1.25rem;">
                    <button type="submit" id="btn-save-comp" class="btn btn-primary">
                        💾 Lưu Thay Đổi Hồ Sơ
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {
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

    loadCompanyProfile();
});

async function loadCompanyProfile() {
    const loadingEl = document.getElementById("comp-prof-loading");
    const contentEl = document.getElementById("comp-prof-content");

    loadingEl.style.display = "block";
    contentEl.style.display = "none";

    const res = await apiRequest("/company/profile", { requireAuth: true });
    loadingEl.style.display = "none";

    if (res && res.success && res.data) {
        contentEl.style.display = "block";
        const c = res.data;

        // Fill form
        document.getElementById("cp-name").value = c.name || "";
        document.getElementById("cp-contact-person").value = c.contact_person || "";
        document.getElementById("cp-contact-phone").value = c.contact_phone || "";
        document.getElementById("cp-city").value = c.city || "";
        document.getElementById("cp-district").value = c.district || "";
        document.getElementById("cp-address").value = c.address || "";
        document.getElementById("cp-website").value = c.website || "";
        document.getElementById("cp-desc").value = c.description || "";

        // Render verification banner
        renderVerificationStatus(c.verification_status, c.rejection_reason);
    } else {
        showToast((res && res.message) ? res.message : "Không thể tải hồ sơ công ty.", "error");
    }
}

function renderVerificationStatus(status, rejectionReason) {
    const banner = document.getElementById("banner-verification");
    const icon = document.getElementById("verify-icon");
    const title = document.getElementById("verify-title");
    const desc = document.getElementById("verify-desc");
    const rejectBox = document.getElementById("verify-reject-box");
    const rejectReasonSpan = document.getElementById("verify-reject-reason");

    if (status === "verified") {
        banner.style.background = "#f0fdf4";
        banner.style.borderColor = "#bbf7d0";
        icon.innerHTML = "✅";
        title.style.color = "#166534";
        title.innerText = "Doanh nghiệp đã được xác thực chính thức";
        desc.style.color = "#15803d";
        desc.innerText = "Hồ sơ công ty của bạn đã được kiểm duyệt hợp lệ bởi Quản trị viên. Bạn có toàn quyền đăng và công khai các tin tuyển dụng ngay lập tức.";
        rejectBox.style.display = "none";
    } else if (status === "rejected") {
        banner.style.background = "#fef2f2";
        banner.style.borderColor = "#fecaca";
        icon.innerHTML = "❌";
        title.style.color = "#991b1b";
        title.innerText = "Hồ sơ công ty bị từ chối xác thực";
        desc.style.color = "#b91c1c";
        desc.innerText = "Hồ sơ của bạn chưa đủ điều kiện hoặc thiếu thông tin minh bạch. Vui lòng cập nhật lại thông tin theo lý do bên dưới để được xét duyệt lại.";
        if (rejectionReason) {
            rejectBox.style.display = "block";
            rejectReasonSpan.innerText = rejectionReason;
        } else {
            rejectBox.style.display = "none";
        }
    } else {
        // Pending
        banner.style.background = "#fffbeb";
        banner.style.borderColor = "#fde68a";
        icon.innerHTML = "⏳";
        title.style.color = "#92400e";
        title.innerText = "Hồ sơ đang chờ Quản trị viên xét duyệt xác thực";
        desc.style.color = "#b45309";
        desc.innerText = "Hồ sơ công ty đang trong quá trình đối soát thông tin. Bạn hiện chỉ có thể lưu nháp tin việc làm (Draft) hoặc gửi tin chờ duyệt (Pending Approval).";
        rejectBox.style.display = "none";
    }
}

async function handleSaveCompanyProfile(e) {
    e.preventDefault();
    const btn = document.getElementById("btn-save-comp");
    const alertBox = document.getElementById("prof-alert-box");
    alertBox.style.display = "none";

    btn.disabled = true;
    btn.innerText = "Đang lưu...";

    const payload = {
        name: document.getElementById("cp-name").value.trim(),
        contact_person: document.getElementById("cp-contact-person").value.trim() || null,
        contact_phone: document.getElementById("cp-contact-phone").value.trim() || null,
        city: document.getElementById("cp-city").value.trim() || null,
        district: document.getElementById("cp-district").value.trim() || null,
        address: document.getElementById("cp-address").value.trim() || null,
        website: document.getElementById("cp-website").value.trim() || null,
        description: document.getElementById("cp-desc").value.trim() || null
    };

    const res = await apiRequest("/company/profile", {
        method: "PUT",
        body: payload,
        requireAuth: true
    });

    btn.disabled = false;
    btn.innerText = "💾 Lưu Thay Đổi Hồ Sơ";

    if (res && res.success) {
        showToast("Cập nhật hồ sơ công ty thành công!", "success");
        // Update stored user name if changed
        const user = TokenStorage.getUser();
        if (user && user.name !== payload.name) {
            user.name = payload.name;
            TokenStorage.setUser(user);
        }
    } else {
        let msg = (res && res.message) ? res.message : "Cập nhật thất bại.";
        if (res && res.errors) {
            msg += " " + Object.values(res.errors).flat().map(escapeHtml).join(" ");
        }
        alertBox.className = "toast toast-error";
        alertBox.innerText = msg;
        alertBox.style.display = "block";
    }
}
</script>
