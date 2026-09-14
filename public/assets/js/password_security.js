(function () {
    "use strict";

    const state = { status: null, challengeId: null };

    function ensureModal() {
        let modal = document.getElementById("password-security-modal");
        if (modal) return modal;
        modal = document.createElement("div");
        modal.id = "password-security-modal";
        modal.className = "password-security-modal";
        modal.setAttribute("role", "dialog");
        modal.setAttribute("aria-modal", "true");
        modal.setAttribute("aria-labelledby", "password-security-title");
        modal.innerHTML = `
            <div class="password-security-card">
                <div class="password-security-header">
                    <div>
                        <div class="password-security-kicker">Bảo mật tài khoản</div>
                        <h2 id="password-security-title">Đổi mật khẩu</h2>
                    </div>
                    <button type="button" class="password-security-close" aria-label="Đóng" onclick="closePasswordSecurityModal()">&times;</button>
                </div>
                <div id="password-security-content" class="password-security-content"></div>
            </div>`;
        modal.addEventListener("click", (event) => {
            if (event.target === modal) window.closePasswordSecurityModal();
        });
        document.body.appendChild(modal);
        return modal;
    }

    function setContent(html) {
        const content = document.getElementById("password-security-content");
        if (content) content.innerHTML = html;
    }

    function errorText(res, fallback) {
        if (res && res.errors && typeof res.errors === "object") {
            for (const value of Object.values(res.errors)) {
                if (Array.isArray(value) && value[0]) return value[0];
            }
        }
        return (res && res.message) || fallback;
    }

    function showInlineError(message) {
        const box = document.getElementById("password-security-error");
        if (!box) return;
        box.textContent = message;
        box.style.display = "block";
    }

    function renderCurrentPasswordStep() {
        const masked = state.status ? state.status.masked_email : "email của bạn";
        setContent(`
            <div class="password-security-steps"><span class="active">1</span><i></i><span>2</span><i></i><span>3</span></div>
            <p class="password-security-description">Nhập mật khẩu hiện tại. Sau khi xác thực, hệ thống sẽ gửi mã gồm 6 chữ số đến <strong>${escapeHtml(masked)}</strong>.</p>
            <div id="password-security-error" class="password-security-error"></div>
            <form onsubmit="requestPasswordChangeCode(event)">
                <label class="form-label" for="password-current">Mật khẩu hiện tại</label>
                <input id="password-current" class="form-control" type="password" autocomplete="current-password" required>
                <div class="password-security-actions">
                    <button type="button" class="btn btn-outline btn-sm" onclick="closePasswordSecurityModal()">Hủy</button>
                    <button id="password-request-code-btn" type="submit" class="btn btn-primary btn-sm">Xác thực và gửi mã</button>
                </div>
            </form>`);
        document.getElementById("password-current")?.focus();
    }

    function renderCodeStep(data) {
        const devCode = data && data.development_code
            ? `<div class="password-security-dev">Môi trường local – mã thử nghiệm: <strong>${escapeHtml(data.development_code)}</strong></div>`
            : "";
        setContent(`
            <div class="password-security-steps"><span class="done">✓</span><i class="done"></i><span class="active">2</span><i></i><span>3</span></div>
            <p class="password-security-description">Mã xác nhận đã được gửi tới <strong>${escapeHtml(data.masked_email || "email của bạn")}</strong>. Mã có hiệu lực trong 10 phút.</p>
            ${devCode}
            <div id="password-security-error" class="password-security-error"></div>
            <form onsubmit="verifyPasswordChangeCode(event)">
                <label class="form-label" for="password-email-code">Mã xác nhận</label>
                <input id="password-email-code" class="form-control password-code-input" type="text" inputmode="numeric" autocomplete="one-time-code" maxlength="6" pattern="[0-9]{6}" placeholder="000000" required>
                <button type="button" class="password-security-link" onclick="renderPasswordCurrentStep()">Gửi lại mã bằng mật khẩu hiện tại</button>
                <div class="password-security-actions">
                    <button type="button" class="btn btn-outline btn-sm" onclick="closePasswordSecurityModal()">Hủy</button>
                    <button id="password-verify-code-btn" type="submit" class="btn btn-primary btn-sm">Xác nhận mã</button>
                </div>
            </form>`);
        document.getElementById("password-email-code")?.focus();
    }

    function renderNewPasswordStep(firstPassword) {
        const title = firstPassword ? "Đặt mật khẩu lần đầu" : "Tạo mật khẩu mới";
        const description = firstPassword
            ? "Bạn đang đăng nhập bằng Google và chưa có mật khẩu JobMarketSV. Hãy đặt mật khẩu để có thể đăng nhập bằng email khi cần."
            : "Email đã được xác nhận. Nhập mật khẩu mới hai lần để hoàn tất.";
        setContent(`
            <div class="password-security-steps"><span class="done">✓</span><i class="done"></i><span class="done">✓</span><i class="done"></i><span class="active">3</span></div>
            <h3 class="password-security-step-title">${title}</h3>
            <p class="password-security-description">${description}</p>
            <div id="password-security-error" class="password-security-error"></div>
            <form onsubmit="submitPasswordUpdate(event)">
                <div class="form-group">
                    <label class="form-label" for="password-new">Mật khẩu mới</label>
                    <input id="password-new" class="form-control" type="password" autocomplete="new-password" minlength="8" maxlength="72" required>
                    <small class="form-help">Từ 8–72 ký tự, có ít nhất một chữ cái và một chữ số.</small>
                </div>
                <div class="form-group">
                    <label class="form-label" for="password-confirmation">Nhập lại mật khẩu mới</label>
                    <input id="password-confirmation" class="form-control" type="password" autocomplete="new-password" minlength="8" maxlength="72" required>
                </div>
                <div class="password-security-actions">
                    <button type="button" class="btn btn-outline btn-sm" onclick="closePasswordSecurityModal()">Hủy</button>
                    <button id="password-update-btn" type="submit" class="btn btn-primary btn-sm">${firstPassword ? "Đặt mật khẩu" : "Đổi mật khẩu"}</button>
                </div>
            </form>`);
        document.getElementById("password-new")?.focus();
    }

    window.openPasswordSecurityModal = async function () {
        const modal = ensureModal();
        modal.classList.add("open");
        document.body.classList.add("password-security-open");
        setContent('<div class="password-security-loading"><span class="autocomplete-spinner"></span>Đang kiểm tra tài khoản...</div>');
        const dropdown = document.getElementById("nav-user-profile-dropdown");
        if (dropdown) dropdown.classList.remove("open");

        const res = await apiRequest("/profile/password/status", { requireAuth: true });
        if (!res || !res.success || !res.data) {
            setContent(`<div class="password-security-error" style="display:block">${escapeHtml(errorText(res, "Không thể kiểm tra tài khoản."))}</div>`);
            return;
        }
        state.status = res.data;
        state.challengeId = null;
        if (res.data.mode === "set_first_password") renderNewPasswordStep(true);
        else renderCurrentPasswordStep();
    };

    window.closePasswordSecurityModal = function () {
        const modal = document.getElementById("password-security-modal");
        if (modal) modal.classList.remove("open");
        document.body.classList.remove("password-security-open");
        state.challengeId = null;
    };

    window.renderPasswordCurrentStep = renderCurrentPasswordStep;

    window.requestPasswordChangeCode = async function (event) {
        event.preventDefault();
        const button = document.getElementById("password-request-code-btn");
        if (button) { button.disabled = true; button.textContent = "Đang gửi..."; }
        const res = await apiRequest("/profile/password/request-code", {
            method: "POST",
            requireAuth: true,
            body: { current_password: document.getElementById("password-current").value }
        });
        if (res && res.success && res.data) {
            state.challengeId = res.data.challenge_id;
            renderCodeStep(res.data);
            return;
        }
        showInlineError(errorText(res, "Không thể gửi mã xác nhận."));
        if (button) { button.disabled = false; button.textContent = "Xác thực và gửi mã"; }
    };

    window.verifyPasswordChangeCode = async function (event) {
        event.preventDefault();
        const button = document.getElementById("password-verify-code-btn");
        if (button) { button.disabled = true; button.textContent = "Đang xác nhận..."; }
        const res = await apiRequest("/profile/password/verify-code", {
            method: "POST",
            requireAuth: true,
            body: { challenge_id: state.challengeId, code: document.getElementById("password-email-code").value.trim() }
        });
        if (res && res.success) {
            renderNewPasswordStep(false);
            return;
        }
        showInlineError(errorText(res, "Mã xác nhận không hợp lệ."));
        if (button) { button.disabled = false; button.textContent = "Xác nhận mã"; }
    };

    window.submitPasswordUpdate = async function (event) {
        event.preventDefault();
        const password = document.getElementById("password-new").value;
        const confirmation = document.getElementById("password-confirmation").value;
        if (password !== confirmation) {
            showInlineError("Mật khẩu nhập lại không khớp.");
            return;
        }
        const button = document.getElementById("password-update-btn");
        if (button) { button.disabled = true; button.textContent = "Đang cập nhật..."; }
        const res = await apiRequest("/profile/password", {
            method: "PUT",
            requireAuth: true,
            body: { password, password_confirmation: confirmation, challenge_id: state.challengeId }
        });
        if (res && res.success) {
            setContent('<div class="password-security-success"><div>✓</div><h3>Mật khẩu đã được cập nhật</h3><p>Bạn sẽ được chuyển tới trang đăng nhập để sử dụng mật khẩu mới.</p></div>');
            TokenStorage.clear();
            setTimeout(() => { window.location.href = "/login?password_updated=1"; }, 1400);
            return;
        }
        showInlineError(errorText(res, "Không thể cập nhật mật khẩu."));
        if (button) { button.disabled = false; button.textContent = state.status && state.status.mode === "set_first_password" ? "Đặt mật khẩu" : "Đổi mật khẩu"; }
    };

    document.addEventListener("keydown", (event) => {
        if (event.key === "Escape" && document.getElementById("password-security-modal")?.classList.contains("open")) {
            window.closePasswordSecurityModal();
        }
    });
})();
