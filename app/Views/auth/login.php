<div class="auth-wrapper">
    <div class="auth-card">
        <h1 class="auth-title">Đăng Nhập</h1>
        <p class="auth-subtitle">Truy cập tài khoản tìm việc hoặc tuyển dụng của bạn</p>

        <!-- Error Alert -->
        <div id="login-error" style="display:none;background:var(--danger-light);color:var(--danger);padding:0.75rem 1rem;border-radius:var(--radius-sm);margin-bottom:1.25rem;font-size:0.9rem;border:1px solid #fca5a5;"></div>

        <form id="login-form">
            <div class="form-group" style="margin-bottom:1.25rem;">
                <label class="form-label" for="login-email">Email đăng nhập</label>
                <input type="email" id="login-email" name="email" class="form-control" required placeholder="name@example.com" autocomplete="email">
            </div>

            <div class="form-group" style="margin-bottom:1.5rem;">
                <div style="display:flex;justify-content:space-between;align-items:center;">
                    <label class="form-label" for="login-password">Mật khẩu</label>
                </div>
                <input type="password" id="login-password" name="password" class="form-control" required placeholder="••••••••" autocomplete="current-password">
            </div>

            <button type="submit" id="btn-submit-login" class="btn btn-primary btn-lg" style="width:100%;">
                Đăng Nhập
            </button>
        </form>

        <div class="auth-divider">
            <span>hoặc</span>
        </div>

        <a href="/auth/google/start" id="btn-google-login" class="btn btn-google" aria-label="Tiếp tục với Google">
            <svg class="btn-google-icon" viewBox="0 0 24 24" width="18" height="18" aria-hidden="true">
                <path fill="#4285F4" d="M23.745 12.27c0-.7-.06-1.4-.19-2.07H12v4.51h6.6c-.29 1.52-1.14 2.82-2.4 3.68v3.05h3.88c2.27-2.09 3.665-5.17 3.665-9.17z"/>
                <path fill="#34A853" d="M12 24c3.24 0 5.95-1.08 7.93-2.91l-3.88-3.05c-1.08.72-2.45 1.16-4.05 1.16-3.12 0-5.77-2.1-6.72-4.93H1.25v3.15C3.26 21.36 7.33 24 12 24z"/>
                <path fill="#FBBC05" d="M5.28 14.27c-.25-.72-.38-1.49-.38-2.27s.13-1.55.38-2.27V6.58H1.25C.45 8.18 0 9.97 0 12s.45 3.82 1.25 5.42l4.03-3.15z"/>
                <path fill="#EA4335" d="M12 4.75c1.77 0 3.35.61 4.6 1.8l3.42-3.42C17.95 1.19 15.24 0 12 0 7.33 0 3.26 2.64 1.25 6.58l4.03 3.15c.95-2.83 3.6-4.98 6.72-4.98z"/>
            </svg>
            <span id="btn-google-login-text">Tiếp tục với Google</span>
        </a>

        <div style="text-align:center;margin-top:1.5rem;font-size:0.9rem;">
            Chưa có tài khoản? <a href="/register" style="font-weight:600;">Đăng ký ngay</a>
        </div>

        <?php if (!empty($isDev)): ?>
        <!-- Quick Demo Email Fillers (Development / Local QA ONLY - No Passwords) -->
        <div class="demo-accounts">
            <div class="demo-accounts-title">⚡ Gợi ý email mẫu phát triển (Nhập mật khẩu kiểm thử thủ công):</div>
            <div class="demo-btn-group">
                <button type="button" class="btn btn-outline btn-sm" onclick="fillEmail('sinhvien1@jobmarket.vn')">
                    🎓 Sinh viên
                </button>
                <button type="button" class="btn btn-outline btn-sm" onclick="fillEmail('highlands@jobmarket.vn')">
                    ☕ Doanh nghiệp
                </button>
                <button type="button" class="btn btn-outline btn-sm" onclick="fillEmail('admin@jobmarket.vn')">
                    🛡️ Quản trị viên
                </button>
            </div>
            <small style="color:var(--text-muted);display:block;margin-top:0.4rem;">* Lưu ý: Mật khẩu không được lưu trong mã nguồn. Vui lòng tự nhập mật khẩu kiểm thử của bạn.</small>
        </div>
        <script>
        function fillEmail(email) {
            document.getElementById("login-email").value = email;
            document.getElementById("login-password").focus();
        }
        </script>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const loginForm = document.getElementById("login-form");
    const errorBox = document.getElementById("login-error");
    const submitBtn = document.getElementById("btn-submit-login");
    const googleBtn = document.getElementById("btn-google-login");

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

    // Configure Google Sign-In button with safe internal redirect if present
    if (googleBtn) {
        const redirect = urlParams.get("redirect");
        if (redirect && redirect.startsWith("/") && !redirect.startsWith("//") && !redirect.includes("://") && !redirect.includes("%2f") && !redirect.includes("%2F")) {
            googleBtn.href = "/auth/google/start?redirect=" + encodeURIComponent(redirect);
        }

        googleBtn.addEventListener("click", () => {
            googleBtn.classList.add("disabled");
            googleBtn.style.pointerEvents = "none";
            const btnText = document.getElementById("btn-google-login-text");
            if (btnText) {
                btnText.textContent = "Đang chuyển hướng tới Google...";
            }
        });
    }

    loginForm.addEventListener("submit", async (e) => {
        e.preventDefault();
        errorBox.style.display = "none";
        submitBtn.disabled = true;
        submitBtn.innerText = "Đang xử lý...";

        const email = document.getElementById("login-email").value.trim();
        const password = document.getElementById("login-password").value;

        // Call REST API POST /login
        const res = await apiRequest("/login", {
            method: "POST",
            body: { email, password }
        });

        submitBtn.disabled = false;
        submitBtn.innerText = "Đăng Nhập";

        if (res && res.success && res.data && res.data.token) {
            // Save token and user info
            TokenStorage.setToken(res.data.token, res.data.user);
            showToast("Đăng nhập thành công! Chào mừng " + (res.data.user.name || ""), "success");

            // Redirect to desired page (Role-aware default destination, prevent open redirect)
            const urlParams = new URLSearchParams(window.location.search);
            let redirectUrl = urlParams.get("redirect");
            if (!redirectUrl || !redirectUrl.startsWith("/") || redirectUrl.startsWith("//") || redirectUrl.includes("://")) {
                if (res.data.user && res.data.user.role === "admin") {
                    redirectUrl = "/admin/dashboard";
                } else if (res.data.user && res.data.user.role === "company") {
                    redirectUrl = "/company/dashboard";
                } else {
                    redirectUrl = "/viec-lam";
                }
            }

            setTimeout(() => {
                window.location.href = redirectUrl;
            }, 600);
        } else {
            // Display error cleanly without raw backend stack trace
            errorBox.innerText = res && res.message ? res.message : "Email hoặc mật khẩu không chính xác.";
            errorBox.style.display = "block";
        }
    });
});
</script>
