<div class="container" style="margin-bottom:3rem;">
    <!-- Top Filter Toolbar (Search + Quick Pills + Advanced Toggle) -->
    <div class="top-filter-toolbar">
        <form id="filter-form">
            <div class="filter-search-row">
                <div class="filter-search-box">
                    <svg class="filter-search-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input type="text" id="filter-keyword" name="keyword" class="filter-search-input" placeholder="Tìm kiếm việc làm theo chức danh, kỹ năng hoặc công ty...">
                </div>

                <button type="button" id="btn-toggle-advanced" class="btn-filter-toggle" aria-expanded="false" title="Mở bộ lọc nâng cao">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="4" y1="21" x2="4" y2="14"/><line x1="4" y1="10" x2="4" y2="3"/><line x1="12" y1="21" x2="12" y2="12"/><line x1="12" y1="8" x2="12" y2="3"/><line x1="20" y1="21" x2="20" y2="16"/><line x1="20" y1="12" x2="20" y2="3"/><line x1="1" y1="14" x2="7" y2="14"/><line x1="9" y1="8" x2="15" y2="8"/><line x1="17" y1="16" x2="23" y2="16"/></svg>
                    <span>Bộ lọc nâng cao</span>
                    <span id="active-filter-badge" class="filter-count-badge" style="display:none;">0</span>
                    <svg class="theme-chevron" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                </button>

                <button type="submit" class="btn btn-primary" style="padding:0.7rem 1.4rem;font-weight:700;">
                    <span>Tìm Kiếm</span>
                </button>

                <button type="button" id="btn-reset-filters" class="btn btn-outline" style="padding:0.7rem 1rem;">
                    <span>Đặt lại</span>
                </button>
            </div>

            <!-- Quick filter pills -->
            <div class="quick-filter-pills-row">
                <span class="quick-pill-label">Gợi ý nhanh:</span>
                <button type="button" class="quick-pill-btn active" data-pill-type="all">Tất cả</button>
                <button type="button" class="quick-pill-btn" data-pill-type="shift" data-pill-val="morning">🌅 Ca Sáng</button>
                <button type="button" class="quick-pill-btn" data-pill-type="shift" data-pill-val="afternoon">☀️ Ca Chiều</button>
                <button type="button" class="quick-pill-btn" data-pill-type="shift" data-pill-val="evening">🌙 Ca Tối</button>
                <button type="button" class="quick-pill-btn" data-pill-type="shift" data-pill-val="flexible">⚡ Ca Linh Hoạt</button>
                <button type="button" class="quick-pill-btn" data-pill-type="location" data-pill-val="loc-001">📍 Hà Nội</button>
                <button type="button" class="quick-pill-btn" data-pill-type="location" data-pill-val="loc-004">📍 TP. HCM</button>
                <button type="button" class="quick-pill-btn" data-pill-type="salary" data-pill-val="25000">💰 Lương > 25k/h</button>
            </div>

            <!-- Collapsible Advanced Filter Panel -->
            <div id="advanced-filter-panel" class="advanced-filter-panel">
                <div class="filter-grid-options">
                    <div class="form-group" style="margin:0;">
                        <label class="form-label" for="filter-category" style="font-size:0.84rem;font-weight:600;">Ngành nghề</label>
                        <select id="filter-category" name="category_id" class="form-control" style="font-size:0.875rem;">
                            <option value="">Tất cả ngành nghề</option>
                            <option value="cat-001">F&B - Phục Vụ & Pha Chế</option>
                            <option value="cat-002">Bán Lẻ & Thu Ngân</option>
                            <option value="cat-003">Gia Sư & Trợ Giảng</option>
                            <option value="cat-004">Hành Chính & Văn Phòng</option>
                            <option value="cat-005">Sự Kiện & Tiếp Thị</option>
                        </select>
                    </div>

                    <div class="form-group" style="margin:0;">
                        <label class="form-label" for="filter-location" style="font-size:0.84rem;font-weight:600;">Khu vực / Địa điểm</label>
                        <select id="filter-location" name="location_id" class="form-control" style="font-size:0.875rem;">
                            <option value="">Tất cả địa điểm</option>
                            <option value="loc-001">Hà Nội - Cầu Giấy</option>
                            <option value="loc-002">Hà Nội - Đống Đa</option>
                            <option value="loc-003">Hà Nội - Hai Bà Trưng</option>
                            <option value="loc-004">TP. HCM - Quận 1</option>
                            <option value="loc-005">TP. HCM - Bình Thạnh</option>
                        </select>
                    </div>

                    <div class="form-group" style="margin:0;">
                        <label class="form-label" for="filter-shift" style="font-size:0.84rem;font-weight:600;">Ca làm việc</label>
                        <select id="filter-shift" name="shift_type" class="form-control" style="font-size:0.875rem;">
                            <option value="">Tất cả các ca</option>
                            <option value="morning">Ca Sáng (08:00 - 12:00)</option>
                            <option value="afternoon">Ca Chiều (13:00 - 17:00)</option>
                            <option value="evening">Ca Tối (18:00 - 22:00)</option>
                            <option value="flexible">Linh hoạt theo lịch học</option>
                        </select>
                    </div>

                    <div class="form-group" style="margin:0;">
                        <label class="form-label" for="filter-salary-min" style="font-size:0.84rem;font-weight:600;">Lương tối thiểu (đ/h)</label>
                        <input type="number" id="filter-salary-min" name="salary_min" class="form-control" placeholder="VD: 25000" step="5000" style="font-size:0.875rem;">
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Nearby Filter Bar (Active when user clicks "Việc làm gần tôi") -->
    <div id="nearby-filter-bar" class="nearby-filter-bar" style="display:none;">
        <div class="nearby-chips-group">
            <span class="nearby-radius-label">📍 Bán kính:</span>
            <button type="button" class="radius-chip" data-radius="2" onclick="setNearbyRadius(2)">2 km</button>
            <button type="button" class="radius-chip" data-radius="5" onclick="setNearbyRadius(5)">5 km</button>
            <button type="button" class="radius-chip active" data-radius="10" onclick="setNearbyRadius(10)">10 km</button>
            <button type="button" class="radius-chip" data-radius="20" onclick="setNearbyRadius(20)">20 km</button>
        </div>

        <div style="display:flex;align-items:center;gap:0.75rem;flex-wrap:wrap;">
            <div class="nearby-privacy-notice">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                <span>Vị trí chỉ dùng cho lần tìm kiếm này và không được lưu.</span>
            </div>
            <button type="button" class="btn-exit-nearby" onclick="exitNearbyMode()">✕ Bỏ lọc gần tôi</button>
        </div>
    </div>

    <!-- Nearby Permission / Status Alert Banner -->
    <div id="nearby-status-banner" style="display:none;margin-bottom:1.25rem;"></div>

    <!-- Results Header Toolbar -->
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.25rem;flex-wrap:wrap;gap:1rem;">
        <div>
            <h1 style="font-size:1.35rem;font-weight:800;color:var(--dark);margin-bottom:0.25rem;">Việc Làm Part-Time Dành Cho Sinh Viên</h1>
            <div id="job-count-text" style="font-size:0.88rem;color:var(--text-muted);">Đang tải danh sách việc làm...</div>
        </div>

        <div style="display:flex;align-items:center;gap:0.6rem;">
            <label for="sort-select" style="font-size:0.88rem;color:var(--text-muted);white-space:nowrap;font-weight:600;">Ưu tiên:</label>
            <select id="sort-select" class="form-control" style="width:auto;padding:0.45rem 0.85rem;font-size:0.875rem;font-weight:600;">
                <option value="newest">Mới nhất</option>
                <option value="salary_desc">Lương cao nhất</option>
                <option value="salary_asc">Lương thấp nhất</option>
            </select>
        </div>
    </div>

    <!-- TopCV-style 3 Columns Job Grid -->
    <div id="jobs-container" class="topcv-job-grid">
        <!-- Skeletons -->
        <div class="job-card skeleton" style="height:150px;"></div>
        <div class="job-card skeleton" style="height:150px;"></div>
        <div class="job-card skeleton" style="height:150px;"></div>
        <div class="job-card skeleton" style="height:150px;"></div>
        <div class="job-card skeleton" style="height:150px;"></div>
        <div class="job-card skeleton" style="height:150px;"></div>
    </div>

    <!-- Pagination Container -->
    <div id="pagination-container" class="pagination" style="margin-top:2rem;"></div>
</div>

<script>
let currentPage = 1;
let currentSort = "newest";
let userCoords = null; // Ephemeral only - never saved to storage or cookies
let isNearbyMode = false;
let nearbyRadiusKm = 10;

function formatDistance(km) {
    if (km === null || km === undefined) return "";
    return new Intl.NumberFormat('vi-VN', { maximumFractionDigits: 1 }).format(km) + " km";
}

function showNearbyBanner(htmlContent, type = "info") {
    const banner = document.getElementById("nearby-status-banner");
    if (!banner) return;
    banner.className = `toast toast-${type}`;
    banner.style.display = "block";
    banner.innerHTML = htmlContent;
}

function hideNearbyBanner() {
    const banner = document.getElementById("nearby-status-banner");
    if (banner) banner.style.display = "none";
}

async function toggleNearbyJobs() {
    if (isNearbyMode) {
        exitNearbyMode();
        return;
    }

    if (!navigator.geolocation) {
        showNearbyBanner("Trình duyệt của bạn không hỗ trợ xác định vị trí địa lý.", "error");
        return;
    }

    showNearbyBanner(`
        <div style="display:flex;align-items:center;gap:0.6rem;">
            <div class="autocomplete-spinner" style="position:static;width:16px;height:16px;"></div>
            <span>Đang yêu cầu quyền truy cập vị trí hiện tại của bạn...</span>
        </div>
    `, "info");

    navigator.geolocation.getCurrentPosition(
        (position) => {
            // Tọa độ chỉ được lưu vào biến bộ nhớ phiên này, KHÔNG lưu xuống localStorage hay cookies
            userCoords = {
                latitude: position.coords.latitude,
                longitude: position.coords.longitude
            };
            isNearbyMode = true;
            hideNearbyBanner();

            document.getElementById("btn-nearby-jobs").classList.add("active");
            document.getElementById("nearby-filter-bar").style.display = "flex";

            // Thêm tùy chọn "Gần nhất" vào sort-select nếu chưa có
            const sortSel = document.getElementById("sort-select");
            let hasDistOpt = Array.from(sortSel.options).some(opt => opt.value === "nearby_distance");
            if (!hasDistOpt) {
                const opt = document.createElement("option");
                opt.value = "nearby_distance";
                opt.textContent = "Gần nhất";
                sortSel.insertBefore(opt, sortSel.firstChild);
            }
            sortSel.value = "nearby_distance";

            loadJobs(1);
        },
        (error) => {
            console.warn("Geolocation error:", error);
            let msg = "";
            switch (error.code) {
                case error.PERMISSION_DENIED:
                    msg = "Bạn đã từ chối chia sẻ vị trí. Bạn có thể chọn khu vực mong muốn ở bộ lọc phía trên hoặc cho phép lại quyền vị trí trong cài đặt trình duyệt.";
                    break;
                case error.POSITION_UNAVAILABLE:
                    msg = "Không thể xác định vị trí hiện tại của thiết bị. Vui lòng thử lại hoặc chọn tỉnh thành trong bộ lọc.";
                    break;
                case error.TIMEOUT:
                    msg = "Yêu cầu vị trí quá thời gian chờ (timeout). Vui lòng kiểm tra lại kết nối mạng hoặc GPS.";
                    break;
                default:
                    msg = "Đã xảy ra sự cố khi xác định vị trí của bạn.";
                    break;
            }

            showNearbyBanner(`
                <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:0.5rem;">
                    <span>⚠️ ${escapeHtml(msg)}</span>
                    <button type="button" class="btn btn-outline btn-sm" onclick="hideNearbyBanner()" style="font-size:0.75rem;padding:0.2rem 0.5rem;">Đóng</button>
                </div>
            `, "warning");
        },
        {
            timeout: 10000,
            enableHighAccuracy: false
        }
    );
}

function setNearbyRadius(km) {
    nearbyRadiusKm = km;
    document.querySelectorAll(".radius-chip").forEach(chip => {
        const r = parseInt(chip.getAttribute("data-radius"), 10);
        chip.classList.toggle("active", r === km);
    });
    if (isNearbyMode) {
        loadJobs(1);
    }
}

function exitNearbyMode() {
    isNearbyMode = false;
    document.getElementById("btn-nearby-jobs").classList.remove("active");
    document.getElementById("nearby-filter-bar").style.display = "none";
    hideNearbyBanner();

    // Revert sort selection if it was "nearby_distance"
    const sortSel = document.getElementById("sort-select");
    const distOpt = sortSel.querySelector('option[value="nearby_distance"]');
    if (distOpt) distOpt.remove();
    sortSel.value = "newest";

    loadJobs(1);
}

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

    // 2. Setup Advanced Filter Toggle
    const btnToggle = document.getElementById("btn-toggle-advanced");
    const panel = document.getElementById("advanced-filter-panel");
    if (btnToggle && panel) {
        btnToggle.addEventListener("click", () => {
            const isOpen = panel.classList.toggle("open");
            btnToggle.classList.toggle("active", isOpen);
            btnToggle.setAttribute("aria-expanded", isOpen ? "true" : "false");
        });
    }

    // 3. Setup Quick Filter Pills
    document.querySelectorAll(".quick-pill-btn").forEach(btn => {
        btn.addEventListener("click", () => {
            document.querySelectorAll(".quick-pill-btn").forEach(b => b.classList.remove("active"));
            btn.classList.add("active");

            const pillType = btn.getAttribute("data-pill-type");
            const pillVal = btn.getAttribute("data-pill-val");

            if (pillType === "all") {
                document.getElementById("filter-shift").value = "";
                document.getElementById("filter-location").value = "";
                document.getElementById("filter-salary-min").value = "";
            } else if (pillType === "shift") {
                document.getElementById("filter-shift").value = pillVal || "";
            } else if (pillType === "location") {
                document.getElementById("filter-location").value = pillVal || "";
            } else if (pillType === "salary") {
                document.getElementById("filter-salary-min").value = pillVal || "";
            }

            updateFilterBadge();
            currentPage = 1;
            loadJobs();
        });
    });

    // 4. Form Filter Submit Event
    document.getElementById("filter-form").addEventListener("submit", (e) => {
        e.preventDefault();
        updateFilterBadge();
        currentPage = 1;
        loadJobs();
    });

    // 5. Reset Filters Event
    document.getElementById("btn-reset-filters").addEventListener("click", () => {
        if (isNearbyMode) {
            isNearbyMode = false;
            document.getElementById("btn-nearby-jobs").classList.remove("active");
            document.getElementById("nearby-filter-bar").style.display = "none";
            hideNearbyBanner();
        }
        document.getElementById("filter-form").reset();
        document.querySelectorAll(".quick-pill-btn").forEach(b => b.classList.remove("active"));
        const allBtn = document.querySelector('.quick-pill-btn[data-pill-type="all"]');
        if (allBtn) allBtn.classList.add("active");
        updateFilterBadge();
        currentPage = 1;
        loadJobs();
    });

    // 6. Sort Change Event
    document.getElementById("sort-select").addEventListener("change", (e) => {
        currentSort = e.target.value;
        currentPage = 1;
        loadJobs();
    });

    updateFilterBadge();
    loadJobs();
});

function updateFilterBadge() {
    const category = document.getElementById("filter-category").value;
    const location = document.getElementById("filter-location").value;
    const shift = document.getElementById("filter-shift").value;
    const salary = document.getElementById("filter-salary-min").value;
    let count = 0;
    if (category) count++;
    if (location) count++;
    if (shift) count++;
    if (salary) count++;

    const badge = document.getElementById("active-filter-badge");
    if (badge) {
        if (count > 0) {
            badge.innerText = count;
            badge.style.display = "inline-block";
        } else {
            badge.style.display = "none";
        }
    }
}

async function loadJobs(page = null) {
    if (page !== null) currentPage = page;
    const container = document.getElementById("jobs-container");
    const countText = document.getElementById("job-count-text");
    const paginationContainer = document.getElementById("pagination-container");

    // Show loading skeleton grid (6 items)
    container.innerHTML = `
        <div class="job-card skeleton" style="height:150px;"></div>
        <div class="job-card skeleton" style="height:150px;"></div>
        <div class="job-card skeleton" style="height:150px;"></div>
        <div class="job-card skeleton" style="height:150px;"></div>
        <div class="job-card skeleton" style="height:150px;"></div>
        <div class="job-card skeleton" style="height:150px;"></div>
    `;

    // 1. NEARBY SEARCH MODE
    if (isNearbyMode && userCoords) {
        const payload = {
            latitude: userCoords.latitude,
            longitude: userCoords.longitude,
            radius_km: nearbyRadiusKm,
            page: currentPage,
            per_page: 15
        };

        const catId = document.getElementById("filter-category") ? document.getElementById("filter-category").value : "";
        if (catId) payload.category_id = catId;
        const shiftType = document.getElementById("filter-shift") ? document.getElementById("filter-shift").value : "";
        if (shiftType) payload.shift_type = shiftType;

        const res = await apiRequest("/jobs/nearby-search", {
            method: "POST",
            body: payload
        });

        if (res && res.success && Array.isArray(res.data)) {
            const jobs = res.data;
            const meta = res.meta || {};
            const total = meta.total || jobs.length;

            countText.innerText = `Tìm thấy ${total} việc làm trong bán kính ${nearbyRadiusKm} km (Sắp xếp: Gần nhất)`;

            if (jobs.length === 0) {
                container.innerHTML = `
                    <div class="empty-state" style="background:var(--surface);border-radius:var(--radius);border:1px solid var(--border);grid-column:1/-1;padding:3rem 1.5rem;">
                        <div class="empty-icon" style="font-size:2.5rem;margin-bottom:0.75rem;">📍</div>
                        <h3 style="font-size:1.2rem;font-weight:700;color:var(--dark);margin-bottom:0.5rem;">Không tìm thấy việc làm trong bán kính ${nearbyRadiusKm} km</h3>
                        <p style="color:var(--text-muted);max-width:460px;margin:0 auto 1rem;">Hãy thử mở rộng bán kính tìm kiếm (20 km) hoặc chuyển sang tìm việc theo tỉnh thành.</p>
                        <button type="button" class="btn btn-primary btn-sm" onclick="setNearbyRadius(20)">Mở rộng bán kính 20 km</button>
                    </div>
                `;
                paginationContainer.innerHTML = "";
                return;
            }

            container.innerHTML = jobs.map(job => {
                const nearest = job.nearest_location;
                let nearestName = "";
                if (nearest) {
                    const parts = [nearest.commune, nearest.province].filter(Boolean);
                    nearestName = parts.length > 0 ? parts.join(", ") : (nearest.address_text || "");
                    if (nearest.branch_name) {
                        nearestName = `${nearest.branch_name} (${nearestName})`;
                    }
                }
                if (!nearestName) {
                    nearestName = job.location_name || job.city || "Việt Nam";
                }

                const extraCount = Array.isArray(job.work_locations) && job.work_locations.length > 1
                    ? job.work_locations.length - 1
                    : 0;

                const distanceFormatted = formatDistance(job.distance_km);

                return `
                    <div class="topcv-job-card" onclick="window.location.href='/viec-lam/${encodeURIComponent(job.id)}'">
                        <div>
                            <div class="topcv-card-top">
                                <div class="topcv-logo-wrapper">
                                    ${job.company_logo ? `<img src="${escapeHtml(job.company_logo)}" alt="${escapeHtml(job.company_name)}" class="topcv-logo-img" onerror="this.outerHTML='<div class=\\\'topcv-logo-fallback\\\'>${escapeHtml(job.company_name ? job.company_name.substring(0, 1) : 'J')}</div>'">` : `<div class="topcv-logo-fallback">${escapeHtml(job.company_name ? job.company_name.substring(0, 1) : "J")}</div>`}
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
                                <button type="button" class="topcv-bookmark-btn" onclick="event.stopPropagation(); toggleFavoriteJob('${encodeURIComponent(job.id)}', this)" title="Lưu việc làm">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
                                </button>
                            </div>
                        </div>
                        <div class="topcv-pills-row">
                            <span class="topcv-pill topcv-pill-distance" title="Khoảng cách theo đường chim bay từ vị trí hiện tại">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 14 14"/></svg>
                                Cách bạn ${distanceFormatted}
                            </span>
                            <span class="topcv-pill topcv-pill-location" title="${escapeHtml(nearest ? nearest.address_text : nearestName)}">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                                ${escapeHtml(nearestName)}
                            </span>
                            ${extraCount > 0 ? `<span class="topcv-pill topcv-pill-extra-locs">+${extraCount} địa điểm khác</span>` : ''}
                            <span class="topcv-pill topcv-pill-salary">
                                ${formatCurrency(job.salary_min)} - ${formatCurrency(job.salary_max)}
                            </span>
                        </div>
                    </div>
                `;
            }).join("");

            renderPagination(meta.page || 1, meta.total_pages || 1);
            return;
        } else {
            container.innerHTML = `
                <div class="empty-state" style="background:var(--surface);border-radius:var(--radius);border:1px solid var(--border);grid-column:1/-1;">
                    <div class="empty-icon" style="color:var(--danger);">⚠️</div>
                    <h3 style="font-size:1.2rem;font-weight:700;color:var(--dark);">Đã xảy ra lỗi khi tìm kiếm việc làm gần bạn</h3>
                    <p>${escapeHtml(res && res.message ? res.message : "Vui lòng thử lại sau.")}</p>
                    <button onclick="loadJobs()" class="btn btn-outline btn-sm" style="margin-top:1rem;">Tải lại trang</button>
                </div>
            `;
            return;
        }
    }

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
    params.append("per_page", 15);

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
                <div class="empty-state" style="background:var(--surface);border-radius:var(--radius);border:1px solid var(--border);grid-column:1/-1;padding:3rem 1.5rem;">
                    <div class="empty-icon" style="font-size:2.5rem;margin-bottom:0.75rem;">📂</div>
                    <h3 style="font-size:1.2rem;font-weight:700;color:var(--dark);margin-bottom:0.5rem;">Không tìm thấy việc làm nào</h3>
                    <p style="color:var(--text-muted);max-width:460px;margin:0 auto;">Hãy thử thay đổi từ khóa tìm kiếm hoặc bấm nút "Đặt lại" để xem toàn bộ danh sách.</p>
                </div>
            `;
            paginationContainer.innerHTML = "";
            return;
        }

        // Render TopCV Cards safely in 3-column grid
        container.innerHTML = jobs.map(job => `
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
                        <button type="button" class="topcv-bookmark-btn" onclick="event.stopPropagation(); toggleFavoriteJob('${encodeURIComponent(job.id)}', this)" title="Lưu việc làm">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
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

        // Render Pagination
        renderPagination(meta.page || 1, meta.total_pages || 1);
    } else {
        container.innerHTML = `
            <div class="empty-state" style="background:var(--surface);border-radius:var(--radius);border:1px solid var(--border);grid-column:1/-1;">
                <div class="empty-icon" style="color:var(--danger);">⚠️</div>
                <h3 style="font-size:1.2rem;font-weight:700;color:var(--dark);">Đã xảy ra lỗi khi tải dữ liệu</h3>
                <p>${escapeHtml(res && res.message ? res.message : "Vui lòng thử lại sau.")}</p>
                <button onclick="loadJobs()" class="btn btn-outline btn-sm" style="margin-top:1rem;">Tải lại trang</button>
            </div>
        `;
    }
}

async function toggleFavoriteJob(jobId, btnEl) {
    if (!TokenStorage.isLoggedIn()) {
        showToast("Vui lòng đăng nhập để lưu việc làm.", "error");
        return;
    }
    const isFavorited = btnEl.classList.contains("active");
    try {
        if (isFavorited) {
            const res = await apiRequest(`/favorites/jobs/${encodeURIComponent(jobId)}`, {
                method: "DELETE",
                requireAuth: true
            });
            if (res && res.success) {
                btnEl.classList.remove("active");
                showToast("Đã bỏ lưu việc làm.", "info");
            }
        } else {
            const res = await apiRequest(`/favorites/jobs/${encodeURIComponent(jobId)}`, {
                method: "POST",
                requireAuth: true
            });
            if (res && res.success) {
                btnEl.classList.add("active");
                showToast("Đã lưu việc làm vào danh sách yêu thích!", "success");
            }
        }
    } catch (err) {
        console.error("Error toggling favorite:", err);
        showToast("Thao tác thất bại.", "error");
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
