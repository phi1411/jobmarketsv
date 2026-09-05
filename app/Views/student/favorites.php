<?php include __DIR__ . "/nav.php"; ?>

<div class="container" style="margin-bottom:3rem;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;flex-wrap:wrap;gap:1rem;">
        <div>
            <h2 style="font-size:1.25rem;font-weight:700;color:var(--dark);margin:0;">
                Danh Sách Việc Làm Đã Lưu
            </h2>
            <span id="fav-count-text" style="font-size:0.88rem;color:var(--text-muted);">Đang tải danh sách...</span>
        </div>
        <a href="/viec-lam" class="btn btn-outline btn-sm">Tìm Thêm Việc Mới</a>
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
    <div id="fav-empty" class="surface-card empty-state" style="display:none;">
        <div class="empty-state-icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
        </div>
        <h3 class="empty-state-title">Danh Sách Yêu Thích Trống</h3>
        <p class="empty-state-text">
            Bạn chưa lưu việc làm part-time nào. Khi xem chi tiết một tin tuyển dụng, hãy bấm nút <strong>"Lưu tin"</strong> để theo dõi hạn nộp hồ sơ tại đây.
        </p>
        <div class="empty-state-action">
            <a href="/viec-lam" class="btn btn-primary">Tìm Việc Làm Ngay &rarr;</a>
        </div>
    </div>
</div>

<script>
function initStudentFavoritesPage() {
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
}

if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initStudentFavoritesPage);
} else {
    initStudentFavoritesPage();
}

async function loadFavorites() {
    const loadingEl = document.getElementById("fav-loading");
    const container = document.getElementById("fav-container");
    const emptyEl = document.getElementById("fav-empty");
    const countText = document.getElementById("fav-count-text");

    if (loadingEl) loadingEl.style.display = "block";
    if (container) container.innerHTML = "";
    if (emptyEl) emptyEl.style.display = "none";

    try {
        const res = await apiRequest("/favorites/jobs", { requireAuth: true });

        if (res && res.success && Array.isArray(res.data)) {
            const jobs = res.data;
            if (countText) countText.innerText = `Đang lưu ${jobs.length} công việc`;

            if (jobs.length === 0) {
                if (emptyEl) emptyEl.style.display = "block";
                return;
            }

            container.innerHTML = jobs.map(job => `
                <div class="data-card" style="margin:0;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1.25rem;">
                    <div style="flex:1;min-width:280px;">
                        <h3 style="font-size:1.15rem;font-weight:700;margin-bottom:0.35rem;">
                            <a href="/viec-lam/${encodeURIComponent(job.id)}">${escapeHtml(job.title)}</a>
                        </h3>
                        <div style="font-size:0.88rem;color:var(--text-muted);margin-bottom:0.5rem;">
                            <strong>${escapeHtml(job.company_name || "Doanh nghiệp")}</strong> &bull;
                            📍 ${escapeHtml(job.location_name || job.city || "Hà Nội")}
                        </div>
                        <div class="job-badges" style="margin:0;">
                            <span class="badge badge-salary">${formatCurrency(job.salary_min)} - ${formatCurrency(job.salary_max)}</span>
                            <span class="badge badge-shift">${getShiftLabel(job.shift_type)}</span>
                            <span class="badge" style="background:#fee2e2;color:#991b1b;font-size:0.75rem;">
                                Hạn nộp: ${formatDate(job.application_deadline) || "Còn tuyển"}
                            </span>
                        </div>
                    </div>

                    <div class="row-actions">
                        <a href="/viec-lam/${encodeURIComponent(job.id)}" class="btn btn-primary btn-sm row-action-btn">
                            Xem & Ứng tuyển &rarr;
                        </a>
                        <button onclick="handleRemoveFavorite('${escapeHtml(job.id)}')" class="btn btn-sm row-action-btn" style="background:#fff;border:1px solid var(--border);color:var(--text-muted);" title="Bỏ lưu việc làm" aria-label="Bỏ lưu việc làm">
                            Bỏ lưu
                        </button>
                    </div>
                </div>
            `).join("");
        } else {
            if (emptyEl) emptyEl.style.display = "block";
            if (countText) countText.innerText = "Không thể tải danh sách.";
        }
    } catch (err) {
        console.error("Error loading favorites:", err);
        if (emptyEl) emptyEl.style.display = "block";
        if (countText) countText.innerText = "Lỗi kết nối máy chủ.";
    } finally {
        if (loadingEl) loadingEl.style.display = "none";
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
