<?php include __DIR__ . "/nav.php"; ?>

<div class="container" style="margin-bottom:3rem;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;flex-wrap:wrap;gap:1rem;">
        <div>
            <h2 style="font-size:1.25rem;font-weight:700;color:var(--dark);margin:0;">
                ⭐ Danh Sách Việc Làm Đã Lưu
            </h2>
            <span id="fav-count-text" style="font-size:0.88rem;color:var(--text-muted);">Đang tải danh sách...</span>
        </div>
        <a href="/viec-lam" class="btn btn-outline btn-sm">🔍 Tìm Thêm Việc Mới</a>
    </div>

    <!-- Loading State -->
    <div id="fav-loading" style="text-align:center;padding:2rem 0;">
        <div class="job-card skeleton" style="height:120px;margin-bottom:1rem;"></div>
        <div class="job-card skeleton" style="height:120px;margin-bottom:1rem;"></div>
        <div class="job-card skeleton" style="height:120px;"></div>
    </div>

    <!-- Container -->
    <div id="fav-container" style="display:flex;flex-direction:column;gap:1rem;"></div>

    <!-- Empty State -->
    <div id="fav-empty" style="display:none;background:#fff;border-radius:var(--radius);border:1px solid var(--border);padding:3.5rem 1.5rem;text-align:center;">
        <div style="font-size:3rem;margin-bottom:1rem;">⭐</div>
        <h3 style="font-size:1.25rem;font-weight:700;color:var(--dark);margin-bottom:0.5rem;">Danh Sách Yêu Thích Trống</h3>
        <p style="color:var(--text-muted);max-width:450px;margin:0 auto 1.5rem;">
            Bạn chưa lưu việc làm part-time nào. Khi xem chi tiết một tin tuyển dụng, hãy bấm nút <strong>"Lưu tin"</strong> để theo dõi hạn nộp hồ sơ tại đây.
        </p>
        <a href="/viec-lam" class="btn btn-primary">Tìm Việc Làm Ngay &rarr;</a>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {
    if (!TokenStorage.isLoggedIn()) {
        showToast("Vui lòng đăng nhập để xem danh sách yêu thích.", "error");
        window.location.href = `/login?login_required=1&redirect=${encodeURIComponent(window.location.pathname)}`;
        return;
    }
    const user = TokenStorage.getUser();
    if (!user || (user.role !== "student" && user.role !== "developer")) {
        showToast("Chỉ tài khoản Sinh viên mới có quyền xem trang này.", "error");
        setTimeout(() => { window.location.href = "/viec-lam"; }, 1000);
        return;
    }

    loadFavorites();
});

async function loadFavorites() {
    const loadingEl = document.getElementById("fav-loading");
    const container = document.getElementById("fav-container");
    const emptyEl = document.getElementById("fav-empty");
    const countText = document.getElementById("fav-count-text");

    loadingEl.style.display = "block";
    container.innerHTML = "";
    emptyEl.style.display = "none";

    const res = await apiRequest("/favorites/jobs", { requireAuth: true });
    loadingEl.style.display = "none";

    if (res && res.success && Array.isArray(res.data)) {
        const jobs = res.data;
        countText.innerText = `Đang lưu ${jobs.length} công việc`;

        if (jobs.length === 0) {
            emptyEl.style.display = "block";
            return;
        }

        container.innerHTML = jobs.map(job => `
            <div class="job-card" style="padding:1.25rem 1.5rem;margin:0;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;">
                <div style="flex:1;min-width:280px;">
                    <h3 style="font-size:1.15rem;font-weight:700;margin-bottom:0.35rem;">
                        <a href="/viec-lam/${encodeURIComponent(job.id)}">${escapeHtml(job.title)}</a>
                    </h3>
                    <div style="font-size:0.88rem;color:var(--text-muted);margin-bottom:0.5rem;">
                        🏢 <strong>${escapeHtml(job.company_name || "Doanh nghiệp")}</strong> &bull; 
                        📍 ${escapeHtml(job.location_name || job.city || "Hà Nội")}
                    </div>
                    <div class="job-badges" style="margin:0;">
                        <span class="badge badge-salary">💰 ${formatCurrency(job.salary_min)} - ${formatCurrency(job.salary_max)}</span>
                        <span class="badge badge-shift">⏰ ${getShiftLabel(job.shift_type)}</span>
                        <span class="badge" style="background:#fee2e2;color:#991b1b;font-size:0.75rem;">
                            Hạn nộp: ${formatDate(job.application_deadline) || "Còn tuyển"}
                        </span>
                    </div>
                </div>

                <div style="display:flex;gap:0.75rem;align-items:center;">
                    <a href="/viec-lam/${encodeURIComponent(job.id)}" class="btn btn-primary btn-sm">
                        Xem & Ứng tuyển &rarr;
                    </a>
                    <button onclick="handleRemoveFavorite('${escapeHtml(job.id)}')" class="btn btn-sm" style="background:#fff;border:1px solid #cbd5e1;color:#64748b;" title="Bỏ lưu việc làm">
                        💔 Bỏ lưu
                    </button>
                </div>
            </div>
        `).join("");
    } else {
        emptyEl.style.display = "block";
        countText.innerText = "Không thể tải danh sách.";
    }
}

async function handleRemoveFavorite(jobId) {
    if (!confirm("Bạn có muốn bỏ lưu việc làm này khỏi danh sách yêu thích?")) {
        return;
    }

    const res = await apiRequest(`/favorites/jobs/${encodeURIComponent(jobId)}`, {
        method: "DELETE",
        requireAuth: true
    });

    if (res && res.success) {
        showToast("Đã bỏ lưu việc làm.", "info");
        loadFavorites();
    } else {
        showToast((res && res.message) ? res.message : "Thao tác thất bại.", "error");
    }
}
</script>
