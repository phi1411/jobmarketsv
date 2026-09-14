<!-- 3D Interactive Hero Section (Trang Đầu) -->
<section class="dashboard-hero-3d" style="padding: 2rem 0 1rem 0;">
    <div class="container">
        <div class="hero-3d-card" id="homeHero3d" style="padding: 3rem 2.5rem;">
            <div class="hero-3d-mesh"></div>
            <div class="hero-3d-glow hero-3d-glow-1"></div>
            <div class="hero-3d-glow hero-3d-glow-2"></div>

            <div class="hero-3d-content" style="align-items: center; margin-bottom: 2rem;">
                <div class="hero-3d-text" style="max-width: 720px;">
                    <div class="hero-badge-pill">
                        <span class="hero-pulse-dot"></span>
                        <span>Cổng Thông Tin Việc Làm Sinh Viên 2.0 • AI Matching</span>
                    </div>
                    <h1 class="hero-3d-title" style="font-size: 2.35rem; line-height: 1.2; margin-bottom: 0.85rem;">
                        Tìm Việc Làm Part-Time Sinh Viên<br>
                        <span class="hero-gradient-text">Linh Hoạt Theo Lịch Học</span> 🎓
                    </h1>
                    <p class="hero-3d-subtitle" style="font-size: 1.05rem; margin-bottom: 1.5rem;">
                        Kết nối sinh viên với hàng trăm việc làm bán thời gian uy tín trên toàn quốc: phục vụ, pha chế, thu ngân, gia sư và văn phòng. Tự động đề xuất việc làm phù hợp bằng AI.
                    </p>
                    <div class="hero-3d-actions">
                        <a href="/viec-lam" class="btn-hero btn-hero--primary">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                            <span>Khám Phá Việc Làm Ngay</span>
                        </a>
                        <a href="/student/dashboard" class="btn-hero btn-hero--glass">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
                            <span>Cổng Sinh Viên</span>
                        </a>
                    </div>
                </div>

                <div class="hero-3d-visual">
                    <!-- Floating 3D Animated Badges -->
                    <div class="floating-badge floating-badge--1">
                        <span class="badge-icon">⚡</span>
                        <div class="badge-content">
                            <strong>AI Match 95%</strong>
                            <small>Độ chính xác ghép việc</small>
                        </div>
                    </div>
                    <div class="floating-badge floating-badge--2">
                        <span class="badge-icon">💼</span>
                        <div class="badge-content">
                            <strong>140+ Việc Part-time</strong>
                            <small>Đang tuyển mới hôm nay</small>
                        </div>
                    </div>
                    <div class="floating-badge floating-badge--3">
                        <span class="badge-icon">⏰</span>
                        <div class="badge-content">
                            <strong>Ca Linh Hoạt</strong>
                            <small>Đổi ca theo lịch học</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Integrated Search Card inside Hero -->
            <div style="position:relative;z-index:2;background:rgba(255,255,255,0.96);backdrop-filter:blur(16px);border-radius:var(--radius-md);padding:1.25rem 1.5rem;box-shadow:0 12px 30px rgba(0,0,0,0.15);border:1px solid rgba(255,255,255,0.6);">
                <form action="/viec-lam" method="GET" class="search-grid" style="margin:0;">
                    <div class="form-group" style="margin:0;">
                        <label class="form-label" for="home-keyword" style="font-size:0.84rem;font-weight:700;color:#1e293b;">Từ khóa tìm kiếm</label>
                        <input type="text" id="home-keyword" name="keyword" class="form-control" placeholder="Tên công việc, vị trí, công ty..." style="background:#f8fafc;border-color:#cbd5e1;">
                    </div>

                    <div class="form-group" style="margin:0;">
                        <label class="form-label" for="home-location-picker" style="font-size:0.84rem;font-weight:700;color:#1e293b;">Tỉnh/Thành phố, Phường/Xã</label>
                        <input type="hidden" id="home-location" name="location_id" value="">
                        <button type="button" id="home-location-picker" class="location-picker-trigger" style="min-height:42px;background:#f8fafc;border-color:#cbd5e1;">
                            <span id="home-location-label" class="location-picker-label">Tất cả địa điểm</span>
                            <span aria-hidden="true">⌄</span>
                        </button>
                    </div>

                    <div class="form-group" style="margin:0;">
                        <label class="form-label" for="home-shift" style="font-size:0.84rem;font-weight:700;color:#1e293b;">Ca làm việc</label>
                        <select id="home-shift" name="shift_type" class="form-control" style="background:#f8fafc;border-color:#cbd5e1;">
                            <option value="">Tất cả các ca</option>
                            <option value="morning">Ca Sáng (08:00 - 12:00)</option>
                            <option value="afternoon">Ca Chiều (13:00 - 17:00)</option>
                            <option value="evening">Ca Tối (18:00 - 22:00)</option>
                            <option value="flexible">Linh hoạt theo lịch học</option>
                        </select>
                    </div>

                    <div class="form-group" style="margin:0;align-self:flex-end;">
                        <button type="submit" class="btn btn-primary" style="height:42px;width:100%;font-weight:700;">
                            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                            Tìm việc ngay
                        </button>
                    </div>
                </form>

                <div style="margin-top:0.85rem;display:flex;gap:0.5rem;align-items:center;flex-wrap:wrap;font-size:0.82rem;">
                    <span style="color:#64748b;font-weight:600;">Gợi ý nhanh:</span>
                    <a href="/viec-lam?keyword=Pha+chế" class="badge badge-shift" style="text-decoration:none;">Pha chế</a>
                    <a href="/viec-lam?keyword=Thu+ngân" class="badge badge-shift" style="text-decoration:none;">Thu ngân</a>
                    <a href="/viec-lam?shift_type=evening" class="badge badge-shift" style="text-decoration:none;">Ca tối</a>
                    <a href="/viec-lam?shift_type=flexible" class="badge badge-shift" style="text-decoration:none;">Ca linh hoạt</a>
                    <a href="/viec-lam?salary_min=30000" class="badge badge-salary" style="text-decoration:none;">&ge; 30.000 đ/h</a>
                </div>
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

        <div id="home-jobs-container" class="topcv-job-grid">
            <!-- Skeleton Loading Placeholders -->
            <div class="job-card skeleton" style="height:140px;"></div>
            <div class="job-card skeleton" style="height:140px;"></div>
            <div class="job-card skeleton" style="height:140px;"></div>
            <div class="job-card skeleton" style="height:140px;"></div>
            <div class="job-card skeleton" style="height:140px;"></div>
            <div class="job-card skeleton" style="height:140px;"></div>
        </div>
    </div>
</section>

<!-- Call to Action Banner -->
<section class="container" style="margin-bottom:3rem;">
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
    initHomeHeroTilt();

    new LargeLocationPicker({
        mode: "single",
        title: "Chọn Tỉnh/Thành phố và Phường/Xã",
        trigger: "#home-location-picker",
        labelElement: "#home-location-label",
        hiddenInput: "#home-location"
    });

    const container = document.getElementById("home-jobs-container");
    if (!container) return;

    // Fetch latest jobs from REST API (6 items for 2 full rows of 3 columns)
    const res = await apiRequest("/jobs?per_page=6&sort_by=newest");

    if (res && res.success && Array.isArray(res.data) && res.data.length > 0) {
        container.innerHTML = res.data.map(job => `
            <div class="topcv-job-card" onclick="window.location.href='/viec-lam/${encodeURIComponent(job.id)}'">
                <div>
                    <div class="topcv-card-top">
                        <div class="topcv-logo-wrapper">
                            ${job.company_logo ? `<img src="${escapeHtml(job.company_logo)}" alt="${escapeHtml(job.company_name)}" class="topcv-logo-img" onerror="this.outerHTML='<div class=\\'topcv-logo-fallback\\'>${escapeHtml(job.company_name ? job.company_name.substring(0, 1) : 'J')}</div>'">` : `<div class="topcv-logo-fallback">${escapeHtml(job.company_name ? job.company_name.substring(0, 1) : "J")}</div>`}
                        </div>
                        <div class="topcv-card-info">
                            <div class="topcv-job-title" title="${escapeHtml(job.title)}">
                                ${job.is_featured ? '<span class="topcv-badge-hot">HOT</span>' : ''}
                                ${job.is_new ? '<span class="topcv-badge-new">MỚI</span>' : ''}
                                ${escapeHtml(job.title)}
                            </div>
                            <div class="topcv-company-name" title="${escapeHtml(job.company_name || 'Nhà tuyển dụng')}">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18M3 7v14M21 7v14M6 11h2M6 15h2M16 11h2M16 15h2M10 21V3h4v18"/></svg>
                                <span>${escapeHtml(job.company_name || "Nhà tuyển dụng")}</span>
                            </div>
                        </div>
                        <button type="button" class="topcv-bookmark-btn" onclick="event.stopPropagation(); window.location.href='/viec-lam/${encodeURIComponent(job.id)}'" title="Xem chi tiết">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                        </button>
                    </div>
                </div>
                <div class="topcv-pills-row">
                    <span class="topcv-pill topcv-pill-salary">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v12M15 9.5a3.5 3.5 0 1 0-7 0 3.5 3.5 0 1 0 7 0z"/></svg>
                        ${formatCurrency(job.salary_min)} - ${formatCurrency(job.salary_max)}
                    </span>
                    <span class="topcv-pill topcv-pill-shift">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        ${getShiftLabel(job.shift_type)}
                    </span>
                    <span class="topcv-pill topcv-pill-location">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                        ${escapeHtml(job.location_name || job.city || "Toàn quốc")}
                    </span>
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

function initHomeHeroTilt() {
    const card = document.getElementById("homeHero3d");
    if (!card) return;

    card.addEventListener("mousemove", (e) => {
        const rect = card.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const y = e.clientY - rect.top;
        const centerX = rect.width / 2;
        const centerY = rect.height / 2;
        const rotateX = ((y - centerY) / centerY) * -5;
        const rotateY = ((x - centerX) / centerX) * 5;

        card.style.transform = `perspective(1000px) rotateX(${rotateX.toFixed(2)}deg) rotateY(${rotateY.toFixed(2)}deg) translateY(-2px)`;
    });

    card.addEventListener("mouseleave", () => {
        card.style.transform = "perspective(1000px) rotateX(0deg) rotateY(0deg) translateY(0)";
    });
}
</script>
