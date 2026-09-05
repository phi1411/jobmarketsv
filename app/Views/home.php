<!-- Hero Section -->
<section class="hero">
    <div class="container">
        <h1 class="hero-title">Tìm Việc Làm Part-Time Sinh Viên<br>Linh Hoạt Theo Lịch Học</h1>
        <p class="hero-subtitle">Kết nối sinh viên với hàng trăm việc làm bán thời gian uy tín: phục vụ, pha chế, thu ngân, gia sư và văn phòng tại Hà Nội & TP. HCM.</p>

        <!-- Search Form -->
        <div class="search-card">
            <form action="/viec-lam" method="GET" class="search-grid">
                <div class="form-group">
                    <label class="form-label" for="home-keyword">Từ khóa tìm kiếm</label>
                    <input type="text" id="home-keyword" name="keyword" class="form-control" placeholder="Tên công việc, vị trí, công ty...">
                </div>

                <div class="form-group">
                    <label class="form-label" for="home-location">Địa điểm</label>
                    <select id="home-location" name="location_id" class="form-control">
                        <option value="">Tất cả địa điểm</option>
                        <option value="loc-001">Hà Nội - Cầu Giấy</option>
                        <option value="loc-002">Hà Nội - Đống Đa</option>
                        <option value="loc-003">Hà Nội - Hai Bà Trưng</option>
                        <option value="loc-004">TP. HCM - Quận 1</option>
                        <option value="loc-005">TP. HCM - Bình Thạnh</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="home-shift">Ca làm việc</label>
                    <select id="home-shift" name="shift_type" class="form-control">
                        <option value="">Tất cả các ca</option>
                        <option value="morning">Ca Sáng (08:00 - 12:00)</option>
                        <option value="afternoon">Ca Chiều (13:00 - 17:00)</option>
                        <option value="evening">Ca Tối (18:00 - 22:00)</option>
                        <option value="flexible">Linh hoạt theo lịch học</option>
                    </select>
                </div>

                <div class="form-group" style="align-self:flex-end;">
                    <button type="submit" class="btn btn-primary" style="height:42px;width:100%;">
                        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        Tìm việc
                    </button>
                </div>
            </form>

            <div style="margin-top:1rem;display:flex;gap:0.5rem;align-items:center;flex-wrap:wrap;font-size:0.85rem;">
                <span style="color:var(--text-muted);">Gợi ý phổ biến:</span>
                <a href="/viec-lam?keyword=Pha+chế" class="badge badge-shift">Pha chế</a>
                <a href="/viec-lam?keyword=Thu+ngân" class="badge badge-shift">Thu ngân</a>
                <a href="/viec-lam?shift_type=evening" class="badge badge-shift">Ca tối</a>
                <a href="/viec-lam?salary_min=30000" class="badge badge-salary">&ge; 30.000 đ/h</a>
            </div>
        </div>
    </div>
</section>

<!-- Categories Section -->
<section class="section">
    <div class="container">
        <div class="section-header">
            <div>
                <h2 class="section-title">Ngành Nghề Phổ Biến Cho Sinh Viên</h2>
                <p class="section-subtitle">Các nhóm công việc part-time có nhu cầu tuyển dụng sinh viên cao nhất</p>
            </div>
            <a href="/viec-lam" class="btn btn-outline btn-sm">Xem tất cả</a>
        </div>

        <div class="category-grid">
            <a href="/viec-lam?category_id=cat-001" class="category-card">
                <div class="category-icon">☕</div>
                <div class="category-name">F&B - Phục Vụ & Pha Chế</div>
                <div style="font-size:0.82rem;color:var(--text-muted);margin-top:0.25rem;">Quán café, trà sữa, nhà hàng</div>
            </a>
            <a href="/viec-lam?category_id=cat-002" class="category-card">
                <div class="category-icon">🛍️</div>
                <div class="category-name">Bán Lẻ & Thu Ngân</div>
                <div style="font-size:0.82rem;color:var(--text-muted);margin-top:0.25rem;">Cửa hàng tiện lợi, siêu thị</div>
            </a>
            <a href="/viec-lam?category_id=cat-003" class="category-card">
                <div class="category-icon">📚</div>
                <div class="category-name">Gia Sư & Trợ Giảng</div>
                <div style="font-size:0.82rem;color:var(--text-muted);margin-top:0.25rem;">Dạy kèm, trung tâm ngoại ngữ</div>
            </a>
            <a href="/viec-lam?category_id=cat-004" class="category-card">
                <div class="category-icon">💻</div>
                <div class="category-name">Hành Chính & Văn Phòng</div>
                <div style="font-size:0.82rem;color:var(--text-muted);margin-top:0.25rem;">Nhập liệu, trực page, CSKH</div>
            </a>
            <a href="/viec-lam?category_id=cat-005" class="category-card">
                <div class="category-icon">🎪</div>
                <div class="category-name">Sự Kiện & Tiếp Thị</div>
                <div style="font-size:0.82rem;color:var(--text-muted);margin-top:0.25rem;">PG/PB, hỗ trợ sự kiện</div>
            </a>
        </div>
    </div>
</section>

<!-- Latest Jobs Section -->
<section class="section" style="background-color:#fff;border-top:1px solid var(--border);border-bottom:1px solid var(--border);">
    <div class="container">
        <div class="section-header">
            <div>
                <h2 class="section-title">Việc Làm Part-Time Mới Nhất</h2>
                <p class="section-subtitle">Các vị trí việc làm vừa được đăng và kiểm duyệt trên hệ thống</p>
            </div>
            <a href="/viec-lam" class="btn btn-outline btn-sm">Xem tất cả việc làm &rarr;</a>
        </div>

        <div id="home-jobs-container" class="job-grid">
            <!-- Skeleton Loading Placeholders -->
            <div class="job-card skeleton" style="height:220px;"></div>
            <div class="job-card skeleton" style="height:220px;"></div>
            <div class="job-card skeleton" style="height:220px;"></div>
        </div>
    </div>
</section>

<!-- Call to Action Banner -->
<section class="container">
    <div class="cta-banner">
        <h2 class="cta-title">Bạn Là Sinh Viên Tìm Việc Hay Doanh Nghiệp Tuyển Dụng?</h2>
        <p class="cta-desc">Hệ thống tạo điều kiện thuận lợi nhất để sinh viên kiếm thêm thu nhập theo lịch học linh hoạt và hỗ trợ doanh nghiệp tìm kiếm nhân sự trẻ năng động.</p>
        <div class="cta-actions">
            <a href="/register" class="btn btn-primary btn-lg">Tạo Hồ Sơ Sinh Viên</a>
            <a href="/register" class="btn btn-secondary btn-lg">Đăng Tin Tuyển Dụng</a>
        </div>
    </div>
</section>

<script>
document.addEventListener("DOMContentLoaded", async () => {
    const container = document.getElementById("home-jobs-container");
    if (!container) return;

    // Fetch latest jobs from REST API
    const res = await apiRequest("/jobs?per_page=6&sort_by=newest");

    if (res && res.success && Array.isArray(res.data) && res.data.length > 0) {
        container.innerHTML = res.data.map(job => `
            <div class="job-card">
                <div>
                    <div class="job-card-header">
                        <div class="company-logo">${escapeHtml(job.company_name ? job.company_name.substring(0, 1) : "J")}</div>
                        <div class="job-card-meta">
                            <h3 class="job-title">
                                <a href="/viec-lam/${encodeURIComponent(job.id)}">${escapeHtml(job.title)}</a>
                            </h3>
                            <div class="company-name">${escapeHtml(job.company_name || "Nhà tuyển dụng")}</div>
                        </div>
                    </div>

                    <div class="job-badges">
                        <span class="badge badge-salary">💰 ${formatCurrency(job.salary_min)} - ${formatCurrency(job.salary_max)}</span>
                        <span class="badge badge-shift">⏰ ${getShiftLabel(job.shift_type)}</span>
                        <span class="badge badge-location">📍 ${escapeHtml(job.location_name || job.city || "Hà Nội")}</span>
                    </div>
                </div>

                <div class="job-card-footer">
                    <span>Hạn: ${formatDate(job.application_deadline) || "Còn tuyển"}</span>
                    <a href="/viec-lam/${encodeURIComponent(job.id)}" class="btn btn-outline btn-sm">Xem chi tiết</a>
                </div>
            </div>
        `).join("");
    } else {
        container.innerHTML = `
            <div class="empty-state" style="grid-column:1/-1;">
                <div class="empty-icon">🔍</div>
                <h3>Chưa có việc làm nào</h3>
                <p>Hiện tại hệ thống đang cập nhật các vị trí việc làm mới. Vui lòng quay lại sau!</p>
            </div>
        `;
    }
});
</script>
