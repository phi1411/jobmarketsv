<div class="auth-wrapper">
    <div class="auth-card" style="max-width:520px;">
        <h1 class="auth-title">Đăng Ký Tài Khoản</h1>
        <p class="auth-subtitle">Tham gia nền tảng kết nối việc làm part-time hàng đầu</p>

        <!-- Role Selector Tabs -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.5rem;margin-bottom:1.25rem;background:var(--bg);padding:0.35rem;border-radius:var(--radius-sm);border:1px solid var(--border);">
            <button type="button" id="tab-student" class="btn btn-sm btn-primary" onclick="setRegisterRole('student')">
                🎓 Sinh viên tìm việc
            </button>
            <button type="button" id="tab-company" class="btn btn-sm btn-outline" onclick="setRegisterRole('company')">
                🏢 Nhà tuyển dụng
            </button>
        </div>

        <!-- Google Register Button (GOOGLE-AUTH-P1-01) -->
        <div style="margin-bottom:1.25rem;">
            <a href="/auth/google/start?role=student" id="btn-google-register" class="btn btn-google" aria-label="Đăng ký bằng Google với vai trò sinh viên">
                <svg class="btn-google-icon" viewBox="0 0 24 24" width="18" height="18" aria-hidden="true">
                    <path fill="#4285F4" d="M23.745 12.27c0-.7-.06-1.4-.19-2.07H12v4.51h6.6c-.29 1.52-1.14 2.82-2.4 3.68v3.05h3.88c2.27-2.09 3.665-5.17 3.665-9.17z"/>
                    <path fill="#34A853" d="M12 24c3.24 0 5.95-1.08 7.93-2.91l-3.88-3.05c-1.08.72-2.45 1.16-4.05 1.16-3.12 0-5.77-2.1-6.72-4.93H1.25v3.15C3.26 21.36 7.33 24 12 24z"/>
                    <path fill="#FBBC05" d="M5.28 14.27c-.25-.72-.38-1.49-.38-2.27s.13-1.55.38-2.27V6.58H1.25C.45 8.18 0 9.97 0 12s.45 3.82 1.25 5.42l4.03-3.15z"/>
                    <path fill="#EA4335" d="M12 4.75c1.77 0 3.35.61 4.6 1.8l3.42-3.42C17.95 1.19 15.24 0 12 0 7.33 0 3.26 2.64 1.25 6.58l4.03 3.15c.95-2.83 3.6-4.98 6.72-4.98z"/>
                </svg>
                <span id="btn-google-register-text">Tiếp tục với Google</span>
            </a>
        </div>

        <div class="auth-divider">
            <span>hoặc đăng ký bằng email</span>
        </div>

        <!-- Error Alert -->
        <div id="register-error" style="display:none;background:var(--danger-light);color:var(--danger);padding:0.75rem 1rem;border-radius:var(--radius-sm);margin-bottom:1.25rem;font-size:0.9rem;border:1px solid #fca5a5;"></div>

        <form id="register-form">
            <input type="hidden" id="register-role" name="role" value="student">

            <div class="form-group" style="margin-bottom:1.25rem;">
                <label id="label-name" class="form-label" for="register-name">Họ và tên sinh viên</label>
                <input type="text" id="register-name" name="name" class="form-control" required placeholder="Nguyễn Văn A" minlength="2">
            </div>

            <div class="form-group" style="margin-bottom:1.25rem;">
                <label class="form-label" for="register-email">Địa chỉ Email</label>
                <input type="email" id="register-email" name="email" class="form-control" required placeholder="name@example.com">
            </div>

            <div class="form-group" style="margin-bottom:1.25rem;">
                <label class="form-label" for="register-password">Mật khẩu</label>
                <input type="password" id="register-password" name="password" class="form-control" required placeholder="Tối thiểu 6 ký tự" minlength="6">
            </div>

            <div class="form-group" style="margin-bottom:1.5rem;">
                <label class="form-label" for="register-password-confirm">Xác nhận mật khẩu</label>
                <input type="password" id="register-password-confirm" name="password_confirmation" class="form-control" required placeholder="Nhập lại mật khẩu">
            </div>

            <button type="submit" id="btn-submit-register" class="btn btn-primary btn-lg" style="width:100%;">
                Tạo Tài Khoản
            </button>
        </form>

        <div style="text-align:center;margin-top:1.5rem;font-size:0.9rem;">
            Đã có tài khoản? <a href="/login" style="font-weight:600;">Đăng nhập ngay</a>
        </div>
    </div>
</div>

<script>
function setRegisterRole(role) {
    // Only accept 'student' or 'company' - fail closed, no admin option
    if (role !== "student" && role !== "company") {
        role = "student";
    }

    document.getElementById("register-role").value = role;
    const tabStudent = document.getElementById("tab-student");
    const tabCompany = document.getElementById("tab-company");
    const labelName = document.getElementById("label-name");
    const inputName = document.getElementById("register-name");
    const btnGoogle = document.getElementById("btn-google-register");

    const urlParams = new URLSearchParams(window.location.search);
    const redirect = urlParams.get("redirect");
    const redirectParam = (redirect && redirect.startsWith("/") && !redirect.startsWith("//") && !redirect.includes("://") && !redirect.includes("%2f") && !redirect.includes("%2F"))
        ? "&redirect=" + encodeURIComponent(redirect)
        : "";

    if (role === "student") {
        tabStudent.className = "btn btn-sm btn-primary";
        tabCompany.className = "btn btn-sm btn-outline";
        labelName.innerText = "Họ và tên sinh viên";
        inputName.placeholder = "Nguyễn Văn A";
        if (btnGoogle) {
            btnGoogle.href = "/auth/google/start?role=student" + redirectParam;
            btnGoogle.setAttribute("aria-label", "Đăng ký bằng Google với vai trò sinh viên");
        }
    } else {
        tabCompany.className = "btn btn-sm btn-primary";
        tabStudent.className = "btn btn-sm btn-outline";
        labelName.innerText = "Tên công ty / Chuỗi cửa hàng";
        inputName.placeholder = "Highlands Coffee, The Coffee House...";
        if (btnGoogle) {
            btnGoogle.href = "/auth/google/start?role=company" + redirectParam;
            btnGoogle.setAttribute("aria-label", "Đăng ký bằng Google với vai trò nhà tuyển dụng");
        }
    }
}

document.addEventListener("DOMContentLoaded", () => {
    const registerForm = document.getElementById("register-form");
    const errorBox = document.getElementById("register-error");
    const submitBtn = document.getElementById("btn-submit-register");
    const googleBtn = document.getElementById("btn-google-register");

    // Display OAuth error safely from query string (anti-XSS via textContent)
    const urlParams = new URLSearchParams(window.location.search);
    const isOAuthError = urlParams.get("oauth_error") === "1";
    const oauthError = urlParams.get("error");

    // Guarded: only clear session when oauth_error=1 is present
    if (isOAuthError) {
        if (typeof TokenStorage !== "undefined" && typeof TokenStorage.clear === "function") {
            TokenStorage.clear();
        } else {
            localStorage.removeItem("jobmarket_token");
            localStorage.removeItem("jobmarket_user");
        }
    }

    if (oauthError) {
        errorBox.textContent = oauthError;
        errorBox.style.display = "block";

        // Clean up OAuth error parameters from URL after rendering
        if (isOAuthError && window.history && window.history.replaceState) {
            urlParams.delete("error");
            urlParams.delete("oauth_error");
            const newQuery = urlParams.toString();
            const newUrl = window.location.pathname + (newQuery ? "?" + newQuery : "");
            window.history.replaceState(null, "", newUrl);
        }
    }

    // Initialize Google register button with default or current role
    const currentRole = document.getElementById("register-role").value || "student";
    setRegisterRole(currentRole);

    if (googleBtn) {
        googleBtn.addEventListener("click", () => {
            googleBtn.classList.add("disabled");
            googleBtn.style.pointerEvents = "none";
            const btnText = document.getElementById("btn-google-register-text");
            if (btnText) {
                btnText.textContent = "Đang chuyển hướng tới Google...";
            }
        });
    }

    registerForm.addEventListener("submit", async (e) => {
        e.preventDefault();
        errorBox.style.display = "none";

        const name = document.getElementById("register-name").value.trim();
        const email = document.getElementById("register-email").value.trim();
        const password = document.getElementById("register-password").value;
        const passwordConfirm = document.getElementById("register-password-confirm").value;
        const role = document.getElementById("register-role").value;

        if (password !== passwordConfirm) {
            errorBox.innerText = "Mật khẩu xác nhận không khớp.";
            errorBox.style.display = "block";
            return;
        }

        submitBtn.disabled = true;
        submitBtn.innerText = "Đang đăng ký...";

        // Call REST API POST /register
        const res = await apiRequest("/register", {
            method: "POST",
            body: { name, email, password, role }
        });

        submitBtn.disabled = false;
        submitBtn.innerText = "Tạo Tài Khoản";

        if (res && res.success) {
            showToast("Đăng ký tài khoản thành công! Đang chuyển hướng đăng nhập...", "success");
            setTimeout(() => {
                window.location.href = "/login";
            }, 1000);
        } else {
            let errMsg = res && res.message ? res.message : "Đăng ký không thành công.";
            if (res && res.errors) {
                const detailedErrors = Object.values(res.errors).flat().map(escapeHtml).join("<br>");
                errorBox.innerHTML = detailedErrors;
            } else {
                errorBox.innerText = errMsg;
            }
            errorBox.style.display = "block";
        }
    });
});
</script>
