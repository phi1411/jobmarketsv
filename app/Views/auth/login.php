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

            // Redirect to desired page (Prevent Open Redirect: strictly relative path)
            const urlParams = new URLSearchParams(window.location.search);
            let redirectUrl = urlParams.get("redirect") || "/viec-lam";
            if (!redirectUrl.startsWith("/") || redirectUrl.startsWith("//") || redirectUrl.includes("://")) {
                redirectUrl = "/viec-lam";
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
