<div class="container">
    <div class="page-layout">
        <!-- Sidebar Filters -->
        <aside class="filter-sidebar">
            <div class="filter-title">
                <span>Bộ Lọc Tìm Kiếm</span>
                <button type="button" id="btn-reset-filters" class="btn btn-outline btn-sm" style="font-size:0.75rem;padding:0.2rem 0.5rem;">Đặt lại</button>
            </div>

            <form id="filter-form">
                <div class="form-group" style="margin-bottom:1.25rem;">
                    <label class="form-label" for="filter-keyword">Từ khóa</label>
                    <input type="text" id="filter-keyword" name="keyword" class="form-control" placeholder="Tên việc, công ty...">
                </div>

                <div class="form-group" style="margin-bottom:1.25rem;">
                    <label class="form-label" for="filter-category">Ngành nghề</label>
                    <select id="filter-category" name="category_id" class="form-control">
                        <option value="">Tất cả ngành nghề</option>
                        <option value="cat-001">F&B - Phục Vụ & Pha Chế</option>
                        <option value="cat-002">Bán Lẻ & Thu Ngân</option>
                        <option value="cat-003">Gia Sư & Trợ Giảng</option>
                        <option value="cat-004">Hành Chính & Văn Phòng</option>
                        <option value="cat-005">Sự Kiện & Tiếp Thị</option>
                    </select>
                </div>

                <div class="form-group" style="margin-bottom:1.25rem;">
                    <label class="form-label" for="filter-location">Địa điểm</label>
                    <select id="filter-location" name="location_id" class="form-control">
                        <option value="">Tất cả địa điểm</option>
                        <option value="loc-001">Hà Nội - Cầu Giấy</option>
                        <option value="loc-002">Hà Nội - Đống Đa</option>
                        <option value="loc-003">Hà Nội - Hai Bà Trưng</option>
                        <option value="loc-004">TP. HCM - Quận 1</option>
                        <option value="loc-005">TP. HCM - Bình Thạnh</option>
                    </select>
                </div>

                <div class="form-group" style="margin-bottom:1.25rem;">
                    <label class="form-label" for="filter-shift">Ca làm việc</label>
                    <select id="filter-shift" name="shift_type" class="form-control">
                        <option value="">Tất cả các ca</option>
                        <option value="morning">Ca Sáng (08:00 - 12:00)</option>
                        <option value="afternoon">Ca Chiều (13:00 - 17:00)</option>
                        <option value="evening">Ca Tối (18:00 - 22:00)</option>
                        <option value="flexible">Linh hoạt theo lịch học</option>
                    </select>
                </div>

                <div class="form-group" style="margin-bottom:1.25rem;">
                    <label class="form-label" for="filter-salary-min">Lương tối thiểu (đ/giờ)</label>
                    <input type="number" id="filter-salary-min" name="salary_min" class="form-control" placeholder="VD: 25000" step="5000">
                </div>

                <button type="submit" class="btn btn-primary" style="width:100%;">Áp Dụng Bộ Lọc</button>
            </form>
        </aside>

        <!-- Main Content (Job List) -->
        <div>
            <!-- Top Controls (Count & Sort) -->
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;background:#fff;padding:1rem 1.25rem;border-radius:var(--radius);border:1px solid var(--border);flex-wrap:wrap;gap:1rem;">
                <div>
                    <h1 style="font-size:1.35rem;font-weight:700;color:var(--dark);">Việc Làm Part-Time Sinh Viên</h1>
                    <div id="job-count-text" style="font-size:0.88rem;color:var(--text-muted);">Đang tải danh sách việc làm...</div>
                </div>

                <div style="display:flex;align-items:center;gap:0.5rem;">
                    <label for="sort-select" style="font-size:0.88rem;color:var(--text-muted);white-space:nowrap;">Sắp xếp:</label>
                    <select id="sort-select" class="form-control" style="width:auto;padding:0.4rem 0.8rem;font-size:0.88rem;">
                        <option value="newest">Mới nhất</option>
                        <option value="salary_desc">Lương cao nhất</option>
                        <option value="salary_asc">Lương thấp nhất</option>
                    </select>
                </div>
            </div>

            <!-- Jobs Cards Container -->
            <div id="jobs-container" class="job-grid" style="grid-template-columns:1fr;">
                <!-- Skeletons -->
                <div class="job-card skeleton" style="height:180px;"></div>
                <div class="job-card skeleton" style="height:180px;"></div>
                <div class="job-card skeleton" style="height:180px;"></div>
            </div>

            <!-- Pagination Container -->
            <div id="pagination-container" class="pagination"></div>
        </div>
    </div>
</div>

<script>
let currentPage = 1;
let currentSort = "newest";

document.addEventListener("DOMContentLoaded", () => {
    // 1. Parse initial query params from URL
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has("keyword")) document.getElementById("filter-keyword").value = urlParams.get("keyword");
    if (urlParams.has("category_id")) document.getElementById("filter-category").value = urlParams.get("category_id");
    if (urlParams.has("location_id")) document.getElementById("filter-location").value = urlParams.get("location_id");
    if (urlParams.has("shift_type")) document.getElementById("filter-shift").value = urlParams.get("shift_type");
    if (urlParams.has("salary_min")) document.getElementById("filter-salary-min").value = urlParams.get("salary_min");
    if (urlParams.has("sort_by")) {
        currentSort = urlParams.get("sort_by");
        document.getElementById("sort-select").value = currentSort;
    }
    if (urlParams.has("page")) {
        currentPage = parseInt(urlParams.get("page")) || 1;
    }

    // 2. Load Jobs
    loadJobs();

    // 3. Form Filter Submit Event
    document.getElementById("filter-form").addEventListener("submit", (e) => {
        e.preventDefault();
        currentPage = 1;
        loadJobs();
    });

    // 4. Reset Filters Event
    document.getElementById("btn-reset-filters").addEventListener("click", () => {
        document.getElementById("filter-form").reset();
        currentPage = 1;
        loadJobs();
    });

    // 5. Sort Change Event
    document.getElementById("sort-select").addEventListener("change", (e) => {
        currentSort = e.target.value;
        currentPage = 1;
        loadJobs();
    });
});

async function loadJobs() {
    const container = document.getElementById("jobs-container");
    const countText = document.getElementById("job-count-text");
    const paginationContainer = document.getElementById("pagination-container");

    // Show loading skeleton
    container.innerHTML = `
        <div class="job-card skeleton" style="height:180px;"></div>
        <div class="job-card skeleton" style="height:180px;"></div>
        <div class="job-card skeleton" style="height:180px;"></div>
    `;

    // Construct API query
    const params = new URLSearchParams();
    const keyword = document.getElementById("filter-keyword").value.trim();
    const categoryId = document.getElementById("filter-category").value;
    const locationId = document.getElementById("filter-location").value;
    const shiftType = document.getElementById("filter-shift").value;
    const salaryMin = document.getElementById("filter-salary-min").value.trim();

    if (keyword) params.append("keyword", keyword);
    if (categoryId) params.append("category_id", categoryId);
    if (locationId) params.append("location_id", locationId);
    if (shiftType) params.append("shift_type", shiftType);
    if (salaryMin) params.append("salary_min", salaryMin);

    params.append("sort_by", currentSort);
    params.append("page", currentPage);
    params.append("per_page", 10);

    // Update browser URL without reloading
    const newUrl = `${window.location.pathname}?${params.toString()}`;
    window.history.pushState({}, "", newUrl);

    // Fetch from Backend REST API
    const res = await apiRequest(`/jobs?${params.toString()}`);

    if (res && res.success && Array.isArray(res.data)) {
        const jobs = res.data;
        const meta = res.meta || {};
        const total = meta.total || jobs.length;

        countText.innerText = `Tìm thấy ${total} việc làm phù hợp (Trang ${meta.page || 1}/${meta.total_pages || 1})`;

        if (jobs.length === 0) {
            container.innerHTML = `
                <div class="empty-state" style="background:#fff;border-radius:var(--radius);border:1px solid var(--border);">
                    <div class="empty-icon">📂</div>
                    <h3 style="font-size:1.2rem;font-weight:700;color:var(--dark);">Không tìm thấy việc làm nào</h3>
                    <p>Hãy thử thay đổi tiêu chí lọc hoặc tìm kiếm với từ khóa khác.</p>
                </div>
            `;
            paginationContainer.innerHTML = "";
            return;
        }

        // Determine role-appropriate CTA label
        const currentUser = (typeof TokenStorage !== "undefined") ? TokenStorage.getUser() : null;
        const isJobSeeker = !currentUser || currentUser.role === "student" || currentUser.role === "developer";
        const ctaLabel = isJobSeeker ? "Ứng tuyển ngay &rarr;" : "Xem chi tiết &rarr;";

        // Render Cards safely
        container.innerHTML = jobs.map(job => `
            <div class="job-card" style="flex-direction:row;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1.5rem;">
                <div style="display:flex;gap:1.25rem;align-items:center;">
                    <div class="company-logo">${escapeHtml(job.company_name ? job.company_name.substring(0, 1) : "J")}</div>
                    <div>
                        <h2 class="job-title" style="font-size:1.15rem;margin-bottom:0.35rem;">
                            <a href="/viec-lam/${encodeURIComponent(job.id)}">${escapeHtml(job.title)}</a>
                        </h2>
                        <div class="company-name" style="margin-bottom:0.5rem;">${escapeHtml(job.company_name || "Nhà tuyển dụng")}</div>
                        <div class="job-badges" style="margin:0;">
                            <span class="badge badge-salary">💰 ${formatCurrency(job.salary_min)} - ${formatCurrency(job.salary_max)}</span>
                            <span class="badge badge-shift">⏰ ${getShiftLabel(job.shift_type)}</span>
                            <span class="badge badge-location">📍 ${escapeHtml(job.location_name || job.city || "Hà Nội")}</span>
                            <span class="badge" style="background:#f1f5f9;color:var(--text-muted);">${getWorkTypeLabel(job.work_type)}</span>
                        </div>
                    </div>
                </div>

                <div style="display:flex;flex-direction:column;align-items:flex-end;gap:0.5rem;">
                    <span style="font-size:0.82rem;color:var(--text-muted);">Hạn nộp: ${formatDate(job.application_deadline) || "Còn tuyển"}</span>
                    <a href="/viec-lam/${encodeURIComponent(job.id)}" class="btn btn-primary btn-sm">${ctaLabel}</a>
                </div>
            </div>
        `).join("");

        // Render Pagination
        renderPagination(meta.page || 1, meta.total_pages || 1);
    } else {
        container.innerHTML = `
            <div class="empty-state" style="background:#fff;border-radius:var(--radius);border:1px solid var(--border);">
                <div class="empty-icon" style="color:var(--danger);">⚠️</div>
                <h3 style="font-size:1.2rem;font-weight:700;color:var(--dark);">Đã xảy ra lỗi khi tải dữ liệu</h3>
                <p>${escapeHtml(res && res.message ? res.message : "Vui lòng thử lại sau.")}</p>
                <button onclick="loadJobs()" class="btn btn-outline btn-sm" style="margin-top:1rem;">Tải lại trang</button>
            </div>
        `;
    }
}

function renderPagination(current, totalPages) {
    const container = document.getElementById("pagination-container");
    if (!container) return;

    if (totalPages <= 1) {
        container.innerHTML = "";
        return;
    }

    let html = "";

    // Previous Button
    html += `<button class="page-item ${current <= 1 ? "disabled" : ""}" onclick="changePage(${current - 1})" ${current <= 1 ? "disabled" : ""}>&laquo;</button>`;

    // Page Numbers
    for (let i = 1; i <= totalPages; i++) {
        html += `<button class="page-item ${i === current ? "active" : ""}" onclick="changePage(${i})">${i}</button>`;
    }

    // Next Button
    html += `<button class="page-item ${current >= totalPages ? "disabled" : ""}" onclick="changePage(${current + 1})" ${current >= totalPages ? "disabled" : ""}>&raquo;</button>`;

    container.innerHTML = html;
}

function changePage(page) {
    currentPage = page;
    loadJobs();
    window.scrollTo({ top: 0, behavior: "smooth" });
}
</script>
