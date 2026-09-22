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

                <button type="button" id="btn-nearby-jobs" class="nearby-cta-btn" onclick="toggleNearbyJobs()" title="Tìm việc làm xung quanh vị trí hiện tại của bạn" aria-pressed="false">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                    <span>Việc làm gần tôi</span>
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
                <button type="button" class="quick-pill-btn quick-pill-nearby" onclick="toggleNearbyJobs()" style="background:var(--primary-light);color:var(--primary-text);border:1.5px solid var(--primary-border);font-weight:700;display:inline-flex;align-items:center;gap:0.35rem;">
                    <i class="ri-map-pin-user-line"></i> <span>Việc làm gần tôi</span>
                </button>
                <button type="button" class="quick-pill-btn" data-pill-type="shift" data-pill-val="morning"><i class="ri-sun-cloudy-line"></i> Ca Sáng</button>
                <button type="button" class="quick-pill-btn" data-pill-type="shift" data-pill-val="afternoon"><i class="ri-sun-line"></i> Ca Chiều</button>
                <button type="button" class="quick-pill-btn" data-pill-type="shift" data-pill-val="evening"><i class="ri-moon-clear-line"></i> Ca Tối</button>
                <button type="button" class="quick-pill-btn" data-pill-type="shift" data-pill-val="flexible"><i class="ri-flashlight-line"></i> Ca Linh Hoạt</button>
                <button type="button" class="quick-pill-btn" data-pill-type="location" data-pill-val="Hà Nội"><i class="ri-map-pin-2-line"></i> Hà Nội</button>
                <button type="button" class="quick-pill-btn" data-pill-type="location" data-pill-val="TP. HCM"><i class="ri-map-pin-2-line"></i> TP. HCM</button>
                <button type="button" class="quick-pill-btn" data-pill-type="salary" data-pill-val="25000"><i class="ri-money-dollar-circle-line"></i> Lương > 25k/h</button>
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
                        <label class="form-label" for="btn-open-location-picker" style="font-size:0.84rem;font-weight:600;">Khu vực / Địa điểm</label>
                        <button type="button" id="btn-open-location-picker" class="location-picker-trigger" aria-haspopup="dialog" title="Mở bộ chọn khu vực làm việc">
                            <span class="loc-trigger-content">
                                <svg class="loc-trigger-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                    <circle cx="12" cy="10" r="3"></circle>
                                </svg>
                                <span id="location-picker-label" class="location-picker-label">Tất cả địa điểm</span>
                            </span>
                            <span id="location-picker-badge" class="location-picker-badge" style="display:none;">0</span>
                            <svg class="loc-chevron-down" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="m6 9 6 6 6-6"></path>
                            </svg>
                        </button>
                        <input type="hidden" id="filter-location" name="location_ids" value="">
                        <input type="hidden" id="filter-city" name="city" value="">
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
        <div style="display:flex;align-items:center;gap:0.65rem;flex-wrap:wrap;">
            <div class="nearby-chips-group">
                <span class="nearby-radius-label"><i class="ri-radar-line"></i> Bán kính:</span>
                <button type="button" class="radius-chip" data-radius="2" onclick="setNearbyRadius(2)">2 km</button>
                <button type="button" class="radius-chip" data-radius="5" onclick="setNearbyRadius(5)">5 km</button>
                <button type="button" class="radius-chip active" data-radius="10" onclick="setNearbyRadius(10)">10 km</button>
                <button type="button" class="radius-chip" data-radius="20" onclick="setNearbyRadius(20)">20 km</button>
            </div>

            <div id="nearby-origin-info" style="font-size:0.84rem;color:var(--text);background:var(--primary-light);padding:0.25rem 0.65rem;border-radius:4px;border:1px solid var(--primary-border);display:inline-flex;align-items:center;gap:0.4rem;">
                <span style="color:#64748b;">Tâm tìm:</span>
                <strong id="nearby-origin-label" style="max-width:240px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">Vị trí của bạn</strong>
                <button type="button" onclick="openStudentNearbyModal()" style="background:none;border:none;color:var(--primary);cursor:pointer;font-size:0.75rem;font-weight:700;text-decoration:underline;">[Đổi]</button>
            </div>

            <button type="button" id="btn-save-preferred-loc" class="btn btn-outline btn-sm" style="display:none;font-size:0.78rem;padding:0.25rem 0.65rem;border-color:#10b981;color:#059669;font-weight:700;" onclick="saveCurrentNearbyToPreferred()">
                <i class="ri-save-line"></i> Lưu làm khu vực mong muốn
            </button>
        </div>

        <div style="display:flex;align-items:center;gap:0.75rem;flex-wrap:wrap;">
            <div class="nearby-privacy-notice">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                <span>Vị trí chỉ dùng cho lần tìm kiếm này và không tự động lưu.</span>
            </div>
            <button type="button" class="btn-exit-nearby" onclick="exitNearbyMode()"><i class="ri-close-line"></i> Bỏ lọc gần tôi</button>
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

<!-- Modal: Chọn Vị Trí Tìm Việc Gần Bạn Cho Sinh Viên -->
<div id="modal-student-nearby" style="display:none;position:fixed;inset:0;background:rgba(15,23,42,.6);z-index:10000;align-items:center;justify-content:center;padding:1rem;" role="dialog" aria-modal="true" aria-labelledby="modal-student-nearby-title">
    <div class="modal-card" style="background:var(--surface);border-radius:var(--radius);max-width:580px;width:100%;max-height:90vh;overflow-y:auto;padding:1.5rem;box-shadow:var(--shadow);position:relative;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.25rem;padding-bottom:0.75rem;border-bottom:1px solid var(--border);">
            <h3 id="modal-student-nearby-title" style="font-size:1.15rem;font-weight:700;color:var(--dark);margin:0;">Chọn Vị Trí Tìm Việc Làm Gần Bạn</h3>
            <button type="button" onclick="closeStudentNearbyModal()" class="modal-close-btn" aria-label="Đóng">&times;</button>
        </div>

        <!-- Hai lựa chọn lớn: Dùng vị trí hiện tại vs Nhập địa chỉ -->
        <div class="loc-method-selector">
            <button type="button" class="loc-method-btn active" id="btn-stu-tab-gps" onclick="switchStudentLocMethod('gps')">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v4m0 12v4M2 12h4m12 0h4m-7 0a5 5 0 1 1-10 0 5 5 0 0 1 10 0z"/></svg>
                <span>Dùng vị trí hiện tại</span>
            </button>
            <button type="button" class="loc-method-btn" id="btn-stu-tab-manual" onclick="switchStudentLocMethod('manual')">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                <span>Nhập địa chỉ</span>
            </button>
        </div>

        <!-- Panel 1: Dùng vị trí hiện tại (GPS) -->
        <div id="panel-stu-gps" class="loc-gps-box">
            <div class="loc-gps-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><polygon points="12 8 8 12 12 16 16 12 12 8"></polygon></svg>
            </div>
            <div style="font-weight:700;color:var(--dark);font-size:0.95rem;margin-bottom:0.35rem;">Lấy vị trí GPS từ thiết bị</div>
            <div class="loc-gps-desc">
                Hệ thống sẽ lấy tọa độ GPS từ trình duyệt và chuẩn hóa qua Goong để tìm việc quanh bạn. Vị trí không lưu vào bộ nhớ hay gửi cookie.
                <div style="margin-top:0.4rem;color:#d97706;font-size:0.8rem;font-weight:600;">⚠️ Yêu cầu kết nối bảo mật HTTPS để kích hoạt GPS.</div>
            </div>
            <div class="loc-gps-actions">
                <button type="button" id="btn-get-stu-gps" class="btn btn-outline" style="font-weight:600;display:inline-flex;align-items:center;gap:0.45rem;" onclick="fetchStudentGpsLocation()">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
                    <span>Lấy vị trí GPS</span>
                </button>
            </div>
        </div>

        <!-- Panel 2: Nhập địa chỉ (Manual) -->
        <div id="panel-stu-manual" style="display:none;margin-bottom:1.25rem;">
            <!-- Subtabs: Hiện hành vs Cũ -->
            <div class="loc-subtabs">
                <button type="button" class="loc-subtab-btn active" id="subtab-stu-mode-current" onclick="switchStudentManualMode('current')">
                    Địa chỉ hiện hành
                </button>
                <button type="button" class="loc-subtab-btn" id="subtab-stu-mode-legacy" onclick="switchStudentManualMode('legacy')">
                    Địa chỉ cũ (có Quận/Huyện)
                </button>
            </div>

            <!-- Fields: Hiện hành -->
            <div id="fields-stu-mode-current">
                <div class="form-grid-address-2" style="margin-bottom:0.75rem;">
                    <div class="form-group" style="margin:0;">
                        <label class="form-label" style="font-size:0.84rem;">Tỉnh / Thành phố *</label>
                        <select id="stu-manual-curr-province" class="form-control" data-vn-address-group="student-current" data-vn-address-level="province" data-vn-address-schema="current" data-vn-address-autoload>
                            <option value="">Đang tải Tỉnh/Thành phố...</option>
                        </select>
                    </div>
                    <div class="form-group" style="margin:0;">
                        <label class="form-label" style="font-size:0.84rem;">Phường / Xã *</label>
                        <select id="stu-manual-curr-ward" class="form-control" data-vn-address-group="student-current" data-vn-address-level="commune" disabled>
                            <option value="">Chọn Tỉnh/Thành phố trước</option>
                        </select>
                    </div>
                </div>
                <div class="form-group" style="margin-bottom:0.75rem;">
                    <label class="form-label" style="font-size:0.84rem;">Địa chỉ chi tiết *</label>
                    <input type="text" id="stu-manual-curr-detail" class="form-control" placeholder="Số nhà, tên đường, tên tòa nhà/ký túc xá...">
                </div>
            </div>

            <!-- Fields: Cũ -->
            <div id="fields-stu-mode-legacy" style="display:none;">
                <div class="form-grid-address-3" style="margin-bottom:0.75rem;">
                    <div class="form-group" style="margin:0;">
                        <label class="form-label" style="font-size:0.84rem;">Tỉnh / TP *</label>
                        <select id="stu-manual-leg-province" class="form-control" data-vn-address-group="student-legacy" data-vn-address-level="province" data-vn-address-schema="legacy">
                            <option value="">-- Chọn Tỉnh/Thành phố --</option>
                        </select>
                    </div>
                    <div class="form-group" style="margin:0;">
                        <label class="form-label" style="font-size:0.84rem;">Quận / Huyện *</label>
                        <select id="stu-manual-leg-district" class="form-control" data-vn-address-group="student-legacy" data-vn-address-level="district" disabled>
                            <option value="">Chọn Tỉnh/Thành phố trước</option>
                        </select>
                    </div>
                    <div class="form-group" style="margin:0;">
                        <label class="form-label" style="font-size:0.84rem;">Phường / Xã *</label>
                        <select id="stu-manual-leg-ward" class="form-control" data-vn-address-group="student-legacy" data-vn-address-level="commune" disabled>
                            <option value="">Chọn Quận/Huyện trước</option>
                        </select>
                    </div>
                </div>
                <div class="form-group" style="margin-bottom:0.75rem;">
                    <label class="form-label" style="font-size:0.84rem;">Địa chỉ chi tiết *</label>
                    <input type="text" id="stu-manual-leg-detail" class="form-control" placeholder="Số nhà, tên đường, tên trường học/KTX...">
                </div>
            </div>

            <div style="display:flex;justify-content:flex-end;">
                <button type="button" id="btn-stu-resolve-manual" class="btn btn-outline btn-sm" style="font-weight:600;display:inline-flex;align-items:center;gap:0.35rem;" onclick="resolveStudentManualAddress()">
                    <i class="ri-map-pin-user-line"></i> Xác thực & Chuẩn hóa địa chỉ
                </button>
            </div>
        </div>

        <!-- Hộp kết quả chuẩn hóa (Result Preview Box) -->
        <div id="stu-resolved-preview" class="loc-resolved-preview" style="display:none;">
            <div class="loc-resolved-header">
                <span class="loc-resolved-badge">✓ Đã xác thực tọa độ tìm kiếm</span>
                <span id="stu-preview-coords" class="resolved-coords-chip">10.77, 106.70</span>
            </div>
            <div id="stu-preview-address" class="loc-resolved-address">Địa chỉ hiển thị ở đây</div>
            <div class="loc-resolved-details">
                <span id="stu-preview-province" class="loc-resolved-tag">Tỉnh/TP: ...</span>
                <span id="stu-preview-commune" class="loc-resolved-tag">Phường/Xã: ...</span>
                <span id="stu-preview-district" class="loc-resolved-tag" style="display:none;">Quận/Huyện cũ: ...</span>
            </div>
        </div>

        <!-- Modal Actions -->
        <div style="display:flex;justify-content:flex-end;gap:0.75rem;margin-top:1rem;border-top:1px solid var(--border);padding-top:1rem;">
            <button type="button" class="btn btn-outline btn-sm" onclick="closeStudentNearbyModal()">Hủy</button>
            <button type="button" id="btn-apply-stu-nearby" class="btn btn-primary btn-sm" disabled onclick="applyStudentNearbySearch()">
                Áp Dụng Tìm Việc Quanh Đây
            </button>
        </div>
    </div>
</div>

<script>
let currentPage = 1;
let currentSort = "newest";
let userCoords = null; // Ephemeral only - never saved to storage or cookies
let isNearbyMode = false;
let nearbyRadiusKm = 10;
let locationPickerInstance = null;

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

let studentResolvedLocation = null;

function openStudentNearbyModal() {
    studentResolvedLocation = null;
    const previewBox = document.getElementById("stu-resolved-preview");
    if (previewBox) previewBox.style.display = "none";
    const applyBtn = document.getElementById("btn-apply-stu-nearby");
    if (applyBtn) applyBtn.disabled = true;

    // Reset inputs
    const setVal = (id, val) => { const el = document.getElementById(id); if (el) el.value = val; };
    VietnamAddressPicker.reset("student-current");
    setVal("stu-manual-curr-detail", "");
    VietnamAddressPicker.reset("student-legacy");
    setVal("stu-manual-leg-detail", "");

    switchStudentLocMethod("gps");
    switchStudentManualMode("current");

    const modal = document.getElementById("modal-student-nearby");
    if (modal) modal.style.display = "flex";
}

function closeStudentNearbyModal() {
    const modal = document.getElementById("modal-student-nearby");
    if (modal) modal.style.display = "none";
}

function switchStudentLocMethod(method) {
    const btnGps = document.getElementById("btn-stu-tab-gps");
    const btnManual = document.getElementById("btn-stu-tab-manual");
    const panelGps = document.getElementById("panel-stu-gps");
    const panelManual = document.getElementById("panel-stu-manual");

    if (method === "gps") {
        btnGps.classList.add("active");
        btnManual.classList.remove("active");
        panelGps.style.display = "block";
        panelManual.style.display = "none";
    } else {
        btnManual.classList.add("active");
        btnGps.classList.remove("active");
        panelGps.style.display = "none";
        panelManual.style.display = "block";
    }
}

async function switchStudentManualMode(mode) {
    const btnCurr = document.getElementById("subtab-stu-mode-current");
    const btnLeg = document.getElementById("subtab-stu-mode-legacy");
    const fieldsCurr = document.getElementById("fields-stu-mode-current");
    const fieldsLeg = document.getElementById("fields-stu-mode-legacy");

    if (mode === "current") {
        btnCurr.classList.add("active");
        btnLeg.classList.remove("active");
        fieldsCurr.style.display = "block";
        fieldsLeg.style.display = "none";
        await VietnamAddressPicker.initGroup("student-current");
    } else {
        btnLeg.classList.add("active");
        btnCurr.classList.remove("active");
        fieldsCurr.style.display = "none";
        fieldsLeg.style.display = "block";
        await VietnamAddressPicker.initGroup("student-legacy");
    }
}

function renderStudentResolvedPreview(data) {
    studentResolvedLocation = data;
    const previewBox = document.getElementById("stu-resolved-preview");
    const coordsEl = document.getElementById("stu-preview-coords");
    const addrEl = document.getElementById("stu-preview-address");
    const provEl = document.getElementById("stu-preview-province");
    const commEl = document.getElementById("stu-preview-commune");
    const distEl = document.getElementById("stu-preview-district");
    const applyBtn = document.getElementById("btn-apply-stu-nearby");

    if (!data || data.latitude == null || data.longitude == null) {
        if (previewBox) previewBox.style.display = "none";
        if (applyBtn) applyBtn.disabled = true;
        return;
    }

    const latStr = typeof data.latitude === "number" ? data.latitude.toFixed(5) : data.latitude;
    const lngStr = typeof data.longitude === "number" ? data.longitude.toFixed(5) : data.longitude;

    if (coordsEl) coordsEl.innerText = `${latStr}, ${lngStr}`;
    if (addrEl) addrEl.innerText = data.address_text || "Vị trí đã chọn";
    if (provEl) provEl.innerText = `Tỉnh/TP: ${data.province || "Chưa rõ"}`;
    if (commEl) commEl.innerText = `Phường/Xã: ${data.commune || "Chưa rõ"}`;

    if (distEl) {
        if (data.district_text_legacy) {
            distEl.innerText = `Quận/Huyện cũ: ${data.district_text_legacy}`;
            distEl.style.display = "inline-block";
        } else {
            distEl.style.display = "none";
        }
    }

    if (previewBox) previewBox.style.display = "block";
    if (applyBtn) applyBtn.disabled = false;
}

async function fetchStudentGpsLocation() {
    if (!window.isSecureContext && location.hostname !== "localhost" && location.hostname !== "127.0.0.1") {
        showToast("Tính năng GPS chỉ hoạt động trên HTTPS.", "warning");
    }

    if (!navigator.geolocation) {
        showToast("Trình duyệt của bạn không hỗ trợ định vị GPS.", "error");
        return;
    }

    const btn = document.getElementById("btn-get-stu-gps");
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = `<span class="autocomplete-spinner" style="position:static;width:14px;height:14px;display:inline-block;margin-right:0.35rem;"></span> Đang lấy vị trí GPS...`;

    navigator.geolocation.getCurrentPosition(
        async (pos) => {
            const lat = pos.coords.latitude;
            const lng = pos.coords.longitude;
            // Tọa độ GPS chỉ lưu trong bộ nhớ phiên này, KHÔNG lưu xuống localStorage hay cookies

            try {
                const res = await apiRequest("/map/resolve-location", {
                    method: "POST",
                    body: {
                        source: "gps",
                        latitude: lat,
                        longitude: lng
                    }
                });

                if (res && res.success && res.data) {
                    renderStudentResolvedPreview(res.data);
                    showToast("Đã xác thực vị trí GPS thành công qua Goong.", "success");
                } else {
                    showToast((res && res.message) ? res.message : "Không thể nhận diện địa chỉ từ GPS.", "error");
                }
            } catch (err) {
                console.error("GPS resolve error:", err);
                showToast("Lỗi khi xác thực vị trí GPS.", "error");
            } finally {
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        },
        (err) => {
            console.warn("Student GPS error:", err);
            btn.disabled = false;
            btn.innerHTML = originalText;

            let msg = "Không thể lấy vị trí GPS.";
            if (err.code === err.PERMISSION_DENIED) {
                msg = "Bạn đã từ chối quyền truy cập vị trí. Hãy chuyển sang tab Nhập địa chỉ.";
            } else if (err.code === err.POSITION_UNAVAILABLE) {
                msg = "Không xác định được vị trí GPS. Vui lòng chuyển sang tab Nhập địa chỉ.";
            } else if (err.code === err.TIMEOUT) {
                msg = "Quá thời gian chờ định vị GPS. Vui lòng thử lại hoặc nhập địa chỉ.";
            }
            showToast(msg, "warning");
        },
        { enableHighAccuracy: true, timeout: 10000 }
    );
}

async function resolveStudentManualAddress() {
    const isLegacy = document.getElementById("subtab-stu-mode-legacy").classList.contains("active");
    let province = "";
    let district = "";
    let ward = "";
    let detail = "";

    if (isLegacy) {
        province = document.getElementById("stu-manual-leg-province").value.trim();
        district = document.getElementById("stu-manual-leg-district").value.trim();
        ward = document.getElementById("stu-manual-leg-ward").value.trim();
        detail = document.getElementById("stu-manual-leg-detail").value.trim();

        if (!province) { showToast("Vui lòng chọn Tỉnh/Thành phố.", "warning"); return; }
        if (!district) { showToast("Vui lòng chọn Quận/Huyện cũ.", "warning"); return; }
        if (!ward) { showToast("Vui lòng chọn Phường/Xã.", "warning"); return; }
        if (!detail || detail.length < 3) { showToast("Vui lòng nhập địa chỉ chi tiết.", "warning"); return; }
    } else {
        province = document.getElementById("stu-manual-curr-province").value.trim();
        ward = document.getElementById("stu-manual-curr-ward").value.trim();
        detail = document.getElementById("stu-manual-curr-detail").value.trim();

        if (!province) { showToast("Vui lòng chọn Tỉnh/Thành phố.", "warning"); return; }
        if (!ward) { showToast("Vui lòng chọn Phường/Xã.", "warning"); return; }
        if (!detail || detail.length < 3) { showToast("Vui lòng nhập địa chỉ chi tiết.", "warning"); return; }
    }

    const btn = document.getElementById("btn-stu-resolve-manual");
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = `<span class="autocomplete-spinner" style="position:static;width:14px;height:14px;display:inline-block;margin-right:0.35rem;"></span> Đang xác thực...`;

    try {
        const administrativeCodes = VietnamAddressPicker.getCodes(isLegacy ? "student-legacy" : "student-current");
        const payload = {
            source: "manual",
            administrative_mode: isLegacy ? "legacy" : "current",
            province: province,
            province_code: administrativeCodes.province_code,
            district_code: administrativeCodes.district_code,
            ward: ward,
            commune_code: administrativeCodes.commune_code,
            address_detail: detail
        };
        if (isLegacy && district) {
            payload.district = district;
        }

        const res = await apiRequest("/map/resolve-location", {
            method: "POST",
            body: payload
        });

        if (res && res.success && res.data) {
            renderStudentResolvedPreview(res.data);
            showToast("Địa chỉ đã được chuẩn hóa và xác thực tọa độ.", "success");
        } else {
            showToast((res && res.message) ? res.message : "Goong không tìm thấy địa chỉ phù hợp.", "error");
        }
    } catch (err) {
        console.error("Manual address resolve error:", err);
        showToast("Lỗi khi kết nối chuẩn hóa địa chỉ.", "error");
    } finally {
        btn.disabled = false;
        btn.innerHTML = originalText;
    }
}

function applyStudentNearbySearch() {
    if (!studentResolvedLocation || studentResolvedLocation.latitude == null || studentResolvedLocation.longitude == null) {
        showToast("Chưa có tọa độ hợp lệ để tìm kiếm.", "warning");
        return;
    }

    // Tọa độ chỉ lưu tạm trong bộ nhớ phiên này, KHÔNG lưu xuống localStorage hay cookies
    userCoords = {
        latitude: studentResolvedLocation.latitude,
        longitude: studentResolvedLocation.longitude,
        address_text: studentResolvedLocation.address_text || "Vị trí đã chọn",
        province: studentResolvedLocation.province || null,
        commune: studentResolvedLocation.commune || null,
        district_text_legacy: studentResolvedLocation.district_text_legacy || null
    };

    isNearbyMode = true;
    closeStudentNearbyModal();

    document.getElementById("btn-nearby-jobs").classList.add("active");
    document.getElementById("btn-nearby-jobs").setAttribute("aria-pressed", "true");
    document.querySelectorAll(".quick-pill-nearby").forEach(b => b.classList.add("active"));
    document.getElementById("nearby-filter-bar").style.display = "flex";

    const originLabel = document.getElementById("nearby-origin-label");
    if (originLabel) {
        originLabel.innerText = userCoords.address_text;
        originLabel.title = userCoords.address_text;
    }

    // Hiển thị nút "Lưu làm khu vực mong muốn" nếu là sinh viên đã đăng nhập
    const savePrefBtn = document.getElementById("btn-save-preferred-loc");
    if (savePrefBtn) {
        const user = (typeof TokenStorage !== "undefined" && TokenStorage.isLoggedIn()) ? TokenStorage.getUser() : null;
        if (user && (user.role === "student" || user.role === "developer")) {
            savePrefBtn.style.display = "inline-flex";
            savePrefBtn.innerHTML = '<i class="ri-save-line"></i> Lưu làm khu vực mong muốn';
            savePrefBtn.disabled = false;
        } else {
            savePrefBtn.style.display = "none";
        }
    }

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
    sortSel.disabled = true;
    currentSort = "nearby_distance";

    loadJobs(1);
}

async function saveCurrentNearbyToPreferred() {
    if (typeof TokenStorage === "undefined" || !TokenStorage.isLoggedIn()) {
        showToast("Vui lòng đăng nhập với tài khoản sinh viên để lưu khu vực mong muốn.", "warning");
        return;
    }
    const user = TokenStorage.getUser();
    if (!user || (user.role !== "student" && user.role !== "developer")) {
        showToast("Chỉ tài khoản sinh viên mới có tính năng này.", "warning");
        return;
    }
    if (!userCoords || userCoords.latitude == null || userCoords.longitude == null) {
        showToast("Chưa có vị trí hợp lệ để lưu.", "warning");
        return;
    }

    const btn = document.getElementById("btn-save-preferred-loc");
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerText = "Đang lưu...";

    try {
        const getRes = await apiRequest("/student/preferred-locations", { requireAuth: true });
        let existingList = (getRes && getRes.success && Array.isArray(getRes.data)) ? getRes.data : [];

        const isDuplicate = existingList.some(item =>
            Math.abs(item.latitude - userCoords.latitude) < 0.001 &&
            Math.abs(item.longitude - userCoords.longitude) < 0.001
        );

        if (isDuplicate) {
            showToast("Vị trí này đã có trong danh sách khu vực mong muốn của bạn.", "info");
            btn.innerHTML = "✓ Đã lưu trước đó";
            return;
        }

        if (existingList.length >= 10) {
            showToast("Bạn đã lưu tối đa 10 khu vực mong muốn trong hồ sơ.", "warning");
            btn.innerHTML = originalText;
            btn.disabled = false;
            return;
        }

        const newEntry = {
            address_text: userCoords.address_text || "Vị trí đã chọn",
            province: userCoords.province || null,
            commune: userCoords.commune || null,
            district_text_legacy: userCoords.district_text_legacy || null,
            latitude: userCoords.latitude,
            longitude: userCoords.longitude,
            preferred_radius_km: nearbyRadiusKm
        };

        const updatedLocations = [...existingList, newEntry];

        const saveRes = await apiRequest("/student/preferred-locations", {
            method: "PUT",
            body: {
                locations: updatedLocations
            },
            requireAuth: true
        });

        if (saveRes && saveRes.success) {
            showToast("Đã lưu khu vực vào danh sách mong muốn thành công!", "success");
            btn.innerHTML = "✓ Đã lưu khu vực";
        } else {
            showToast((saveRes && saveRes.message) ? saveRes.message : "Không thể lưu khu vực mong muốn.", "error");
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    } catch (err) {
        console.error("Save preferred location error:", err);
        showToast("Lỗi khi lưu khu vực mong muốn.", "error");
        btn.innerHTML = originalText;
        btn.disabled = false;
    }
}

async function toggleNearbyJobs() {
    if (isNearbyMode) {
        exitNearbyMode();
        return;
    }
    openStudentNearbyModal();
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
    const btnNearby = document.getElementById("btn-nearby-jobs");
    if (btnNearby) {
        btnNearby.classList.remove("active");
        btnNearby.setAttribute("aria-pressed", "false");
    }
    document.querySelectorAll(".quick-pill-nearby").forEach(b => b.classList.remove("active"));
    const nearbyBar = document.getElementById("nearby-filter-bar");
    if (nearbyBar) nearbyBar.style.display = "none";
    hideNearbyBanner();

    // Revert sort selection if it was "nearby_distance"
    const sortSel = document.getElementById("sort-select");
    const distOpt = sortSel.querySelector('option[value="nearby_distance"]');
    if (distOpt) distOpt.remove();
    sortSel.disabled = false;
    sortSel.value = "newest";
    currentSort = "newest";

    loadJobs(1);
}

document.addEventListener("DOMContentLoaded", () => {
    // 0. Khởi tạo bộ chọn địa điểm lớn 2 cột (Multi-select)
    locationPickerInstance = new LargeLocationPicker({
        mode: 'multi',
        maxSelect: 20,
        trigger: '#btn-open-location-picker',
        labelElement: '#location-picker-label',
        badgeElement: '#location-picker-badge',
        hiddenInput: '#filter-location',
        onApply: (selectedIds, selectedItems, displayText) => {
            document.getElementById("filter-city").value = "";
            updateFilterBadge();
            currentPage = 1;
            loadJobs();
        }
    });

    // 1. Parse initial query params from URL
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has("keyword")) document.getElementById("filter-keyword").value = urlParams.get("keyword");
    if (urlParams.has("category_id")) document.getElementById("filter-category").value = urlParams.get("category_id");
    if (urlParams.has("location_ids")) {
        const locIds = urlParams.get("location_ids");
        document.getElementById("filter-location").value = locIds;
        locationPickerInstance.setSelected(locIds);
    } else if (urlParams.has("location_id")) {
        const locId = urlParams.get("location_id");
        document.getElementById("filter-location").value = locId;
        locationPickerInstance.setSelected(locId);
    }
    if (urlParams.has("city")) document.getElementById("filter-city").value = urlParams.get("city");
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
                document.getElementById("filter-city").value = "";
                document.getElementById("filter-salary-min").value = "";
                if (locationPickerInstance) locationPickerInstance.clear(false);
            } else if (pillType === "shift") {
                document.getElementById("filter-shift").value = pillVal || "";
            } else if (pillType === "location") {
                if (locationPickerInstance) {
                    locationPickerInstance.clear(false);
                }
                document.getElementById("filter-city").value = (pillVal || "").includes("Hà Nội") ? "Hà Nội" : "Hồ Chí Minh";
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
            document.getElementById("btn-nearby-jobs").setAttribute("aria-pressed", "false");
            document.querySelectorAll(".quick-pill-nearby").forEach(b => b.classList.remove("active"));
            document.getElementById("nearby-filter-bar").style.display = "none";
            hideNearbyBanner();
            const sortSel = document.getElementById("sort-select");
            const distOpt = sortSel.querySelector('option[value="nearby_distance"]');
            if (distOpt) distOpt.remove();
            sortSel.disabled = false;
            sortSel.value = "newest";
            currentSort = "newest";
        }
        document.getElementById("filter-form").reset();
        document.getElementById("filter-city").value = "";
        if (locationPickerInstance) locationPickerInstance.clear(false);
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
    const city = document.getElementById("filter-city").value;
    const shift = document.getElementById("filter-shift").value;
    const salary = document.getElementById("filter-salary-min").value;
    let count = 0;
    if (category) count++;
    if (location) count++;
    if (city) count++;
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
        const keyword = document.getElementById("filter-keyword") ? document.getElementById("filter-keyword").value.trim() : "";
        if (keyword) payload.keyword = keyword;
        const salaryMin = document.getElementById("filter-salary-min") ? document.getElementById("filter-salary-min").value.trim() : "";
        if (salaryMin) payload.salary_min = Number(salaryMin);
        const locationId = document.getElementById("filter-location") ? document.getElementById("filter-location").value.trim() : "";
        if (locationId) {
            payload.location_id = locationId;
            payload.location_ids = locationId.split(",").map(s => s.trim()).filter(Boolean);
        }
        const city = document.getElementById("filter-city") ? document.getElementById("filter-city").value.trim() : "";
        if (city) payload.city = city;

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
                        <div class="empty-icon" style="font-size:2.5rem;margin-bottom:0.75rem;color:var(--text-muted);"><i class="ri-map-pin-line"></i></div>
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
    const locationId = document.getElementById("filter-location") ? document.getElementById("filter-location").value.trim() : "";
    const city = document.getElementById("filter-city") ? document.getElementById("filter-city").value.trim() : "";
    const shiftType = document.getElementById("filter-shift").value;
    const salaryMin = document.getElementById("filter-salary-min").value.trim();

    if (keyword) params.append("keyword", keyword);
    if (categoryId) params.append("category_id", categoryId);
    if (locationId) params.append("location_ids", locationId);
    if (city) params.append("city", city);
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
                    <div class="empty-icon" style="font-size:2.5rem;margin-bottom:0.75rem;color:var(--text-muted);"><i class="ri-folder-open-line"></i></div>
                    <h3 style="font-size:1.2rem;font-weight:700;color:var(--dark);margin-bottom:0.5rem;">Không tìm thấy việc làm nào</h3>
                    <p style="color:var(--text-muted);max-width:460px;margin:0 auto;">Hãy thử thay đổi từ khóa tìm kiếm hoặc bấm nút "Đặt lại" để xem toàn bộ danh sách.</p>
                </div>
            `;
            paginationContainer.innerHTML = "";
            return;
        }

        // Render TopCV Cards safely in 3-column grid
        container.innerHTML = jobs.map(job => {
            let locDisplay = job.location_name || job.city || "Toàn quốc";
            let extraLocationsBadge = "";
            if (Array.isArray(job.work_locations) && job.work_locations.length > 0) {
                const primaryLoc = job.work_locations.find(l => l.is_primary) || job.work_locations[0];
                if (primaryLoc) {
                    const parts = [primaryLoc.commune, primaryLoc.province].filter(Boolean);
                    locDisplay = parts.length > 0 ? parts.join(", ") : (primaryLoc.address_text || locDisplay);
                    if (primaryLoc.branch_name) {
                        locDisplay = `${primaryLoc.branch_name} (${locDisplay})`;
                    }
                }
                if (job.work_locations.length > 1) {
                    extraLocationsBadge = `<span class="topcv-pill topcv-pill-extra-locs">+${job.work_locations.length - 1} địa điểm khác</span>`;
                }
            }
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
                        ${escapeHtml(locDisplay)}
                    </span>
                    ${extraLocationsBadge}
                </div>
            </div>
            `;
        }).join("");

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
