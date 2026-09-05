<div class="auth-wrapper">
    <div class="auth-card" style="max-width:520px;">
        <h1 class="auth-title">Đăng Ký Tài Khoản</h1>
        <p class="auth-subtitle">Tham gia nền tảng kết nối việc làm part-time hàng đầu</p>

        <!-- Role Selector Tabs -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.5rem;margin-bottom:1.5rem;background:var(--bg);padding:0.35rem;border-radius:var(--radius-sm);border:1px solid var(--border);">
            <button type="button" id="tab-student" class="btn btn-sm btn-primary" onclick="setRegisterRole('student')">
                🎓 Sinh viên tìm việc
            </button>
            <button type="button" id="tab-company" class="btn btn-sm btn-outline" onclick="setRegisterRole('company')">
                🏢 Nhà tuyển dụng
            </button>
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
    document.getElementById("register-role").value = role;
    const tabStudent = document.getElementById("tab-student");
    const tabCompany = document.getElementById("tab-company");
    const labelName = document.getElementById("label-name");
    const inputName = document.getElementById("register-name");

    if (role === "student") {
        tabStudent.className = "btn btn-sm btn-primary";
        tabCompany.className = "btn btn-sm btn-outline";
        labelName.innerText = "Họ và tên sinh viên";
        inputName.placeholder = "Nguyễn Văn A";
    } else {
        tabCompany.className = "btn btn-sm btn-primary";
        tabStudent.className = "btn btn-sm btn-outline";
        labelName.innerText = "Tên công ty / Chuỗi cửa hàng";
        inputName.placeholder = "Highlands Coffee, The Coffee House...";
    }
}

document.addEventListener("DOMContentLoaded", () => {
    const registerForm = document.getElementById("register-form");
    const errorBox = document.getElementById("register-error");
    const submitBtn = document.getElementById("btn-submit-register");

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
