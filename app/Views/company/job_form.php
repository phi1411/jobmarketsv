<?php include __DIR__ . "/nav.php"; ?>

<?php
$isEditMode = !empty($isEdit);
$editingJobId = $jobId ?? "";
?>

<div class="container" style="margin-bottom:3rem;max-width:900px;">
    <!-- Breadcrumb / Header -->
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;flex-wrap:wrap;gap:1rem;">
        <div>
            <a href="/company/jobs" style="color:var(--primary);text-decoration:none;font-size:0.88rem;font-weight:600;">
                &larr; Quay lại danh sách tin
            </a>
            <h2 style="font-size:1.4rem;font-weight:800;color:var(--dark);margin-top:0.35rem;margin-bottom:0;">
                <?= $isEditMode ? "Chỉnh Sửa Tin Tuyển Dụng" : "Đăng Tin Tuyển Dụng Mới" ?>
            </h2>
        </div>
        <div id="company-verify-notice" style="display:none;"></div>
    </div>

    <!-- Loading Skeleton for Edit -->
    <div id="job-form-loading" style="display:<?= $isEditMode ? 'block' : 'none' ?>;">
        <div class="job-card skeleton" style="height:450px;"></div>
    </div>

    <!-- Alert Box -->
    <div id="job-form-alert" style="display:none;margin-bottom:1.5rem;" class="toast"></div>

    <!-- Form Container -->
    <div id="job-form-container" class="form-card" style="display:<?= $isEditMode ? 'none' : 'block' ?>;">
        <form id="form-job" onsubmit="handleSubmitJob(event)">
            <!-- 1. Tiêu đề việc làm -->
            <div class="form-group" style="margin-bottom:1.25rem;">
                <label class="form-label" for="job-title">Tiêu đề việc làm <span style="color:var(--danger)">*</span></label>
                <input type="text" id="job-title" class="form-control" placeholder="Ví dụ: Nhân viên phục vụ & Phụ quầy Part-time (Ca sáng/tối)" required minlength="5">
                <small class="form-help">Tối thiểu 5 ký tự. Nên ghi rõ chức danh và ca làm việc để thu hút sinh viên.</small>
            </div>

            <!-- 2. Ngành nghề & Địa điểm -->
            <div class="form-grid-2" style="margin-bottom:1.25rem;">
                <div class="form-group">
                    <label class="form-label" for="job-category">Ngành nghề / Lĩnh vực <span style="color:var(--danger)">*</span></label>
                    <select id="job-category" class="form-control" required data-searchable="true" data-allow-custom="true" data-placeholder-search="Tìm hoặc gõ ngành nghề...">
                        <option value="">-- Chọn ngành nghề --</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="btn-company-location-picker">Tỉnh/Thành phố và Phường/Xã làm việc <span style="color:var(--danger)">*</span></label>
                    <input type="hidden" id="job-location" name="location_id" required value="">
                    <button type="button" id="btn-company-location-picker" class="location-picker-trigger" aria-haspopup="dialog" style="min-height:42px;">
                        <span class="loc-trigger-content">
                            <svg class="loc-trigger-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                <circle cx="12" cy="10" r="3"></circle>
                            </svg>
                            <span id="company-location-picker-label" class="location-picker-label">-- Chọn Tỉnh/Thành phố, Phường/Xã --</span>
                        </span>
                        <svg class="loc-chevron-down" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="m6 9 6 6 6-6"></path>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- 3. Hình thức làm việc & Chế độ làm việc -->
            <div class="form-grid-2" style="margin-bottom:1.25rem;">
                <div class="form-group">
                    <label class="form-label" for="job-work-type">Hình thức tuyển dụng</label>
                    <select id="job-work-type" class="form-control">
                        <option value="part_time">Bán thời gian (Part-time)</option>
                        <option value="internship">Thực tập sinh (Internship)</option>
                        <option value="freelance">Làm tự do / Theo dự án (Freelance)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="job-work-mode">Chế độ làm việc</label>
                    <select id="job-work-mode" class="form-control">
                        <option value="onsite">Trực tiếp tại cơ sở (On-site)</option>
                        <option value="hybrid">Kết hợp linh hoạt (Hybrid)</option>
                        <option value="remote">Làm việc từ xa (Remote)</option>
                    </select>
                </div>
            </div>

            <!-- 4. Lương: Loại lương, Min, Max -->
            <div class="fieldset-card">
                <label class="fieldset-card-title">Chế độ Tiền lương</label>
                <div class="form-grid-3">
                    <div class="form-group">
                        <label class="form-label" for="job-salary-type">Loại hình trả lương</label>
                        <select id="job-salary-type" class="form-control">
                            <option value="hourly">Theo giờ (Hourly - VND/h)</option>
                            <option value="monthly">Theo tháng (Monthly - VND/tháng)</option>
                            <option value="daily">Theo ngày / ca (Daily)</option>
                            <option value="negotiable">Thỏa thuận (Negotiable)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="job-salary-min">Lương tối thiểu (VND)</label>
                        <input type="number" id="job-salary-min" class="form-control" placeholder="25000" min="0" step="1000">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="job-salary-max">Lương tối đa (VND)</label>
                        <input type="number" id="job-salary-max" class="form-control" placeholder="35000" min="0" step="1000">
                    </div>
                </div>
            </div>

            <!-- 5. Ca làm việc & Lịch làm chi tiết -->
            <div class="form-grid-2" style="margin-bottom:1.25rem;">
                <div class="form-group">
                    <label class="form-label" for="job-shift-type">Ca làm việc chính</label>
                    <select id="job-shift-type" class="form-control">
                        <option value="">Không yêu cầu ca cố định</option>
                        <option value="morning">Ca Sáng (08:00 - 12:00)</option>
                        <option value="afternoon">Ca Chiều (13:00 - 17:00)</option>
                        <option value="evening">Ca Tối (18:00 - 22:00)</option>
                        <option value="night">Ca Đêm</option>
                        <option value="rotating">Xoay ca</option>
                        <option value="weekend">Cuối tuần</option>
                        <option value="flexible">Linh hoạt theo lịch học</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="job-working-schedule">Lịch làm việc chi tiết</label>
                    <input type="text" id="job-working-schedule" class="form-control" placeholder="Ví dụ: Đăng ký tối thiểu 4 buổi/tuần, 4-6h/buổi">
                </div>
            </div>

            <!-- 6. Độ tuổi yêu cầu -->
            <div class="fieldset-card">
                <label class="fieldset-card-title">Độ tuổi ứng viên</label>
                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label" for="job-minimum-age">Tuổi tối thiểu</label>
                        <input type="number" id="job-minimum-age" class="form-control" placeholder="Để trống nếu không yêu cầu" min="15" max="80">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="job-maximum-age">Tuổi tối đa</label>
                        <input type="number" id="job-maximum-age" class="form-control" placeholder="Để trống nếu không yêu cầu" min="15" max="80">
                    </div>
                </div>
                <small class="form-help">Bỏ trống cả hai ô thì mọi sinh viên đều đạt tiêu chí độ tuổi.</small>
            </div>

            <!-- 7. Số lượng tuyển & Hạn nộp -->
            <div class="form-grid-2" style="margin-bottom:1.25rem;">
                <div class="form-group">
                    <label class="form-label" for="job-quantity">Số lượng cần tuyển</label>
                    <input type="number" id="job-quantity" class="form-control" placeholder="Ví dụ: 5" min="1">
                </div>
                <div class="form-group">
                    <label class="form-label" for="job-deadline">Hạn nộp hồ sơ <span style="color:var(--danger)">*</span></label>
                    <input type="date" id="job-deadline" class="form-control" required>
                    <small class="form-help">Ngày hết hạn phải ở tương lai.</small>
                </div>
            </div>

            <!-- 8. Kỹ năng yêu cầu (Skills) -->
            <div class="form-group" style="margin-bottom:1.25rem;">
                <label class="form-label">Kỹ năng / Phẩm chất mong muốn</label>
                <div id="job-skills-container" style="display:flex;flex-wrap:wrap;gap:0.5rem;padding:0.75rem;background:#f8fafc;border:1px solid var(--border);border-radius:var(--radius);max-height:150px;overflow-y:auto;">
                    <!-- Checkboxes rendered dynamically -->
                </div>
                <label class="form-label" for="job-custom-skills" style="margin-top:.75rem;">Kỹ năng khác</label>
                <input type="text" id="job-custom-skills" class="form-control" maxlength="1000" placeholder="Ví dụ: Chụp hình sản phẩm, quản lý fanpage (ngăn cách bằng dấu phẩy)">
                <small class="form-help">Có thể vừa tích kỹ năng có sẵn, vừa nhập kỹ năng riêng. Mỗi kỹ năng cách nhau bằng dấu phẩy.</small>
            </div>

            <!-- 9. Mô tả công việc -->
            <div class="form-group" style="margin-bottom:1.25rem;">
                <label class="form-label" for="job-desc">Mô tả công việc <span style="color:var(--danger)">*</span></label>
                <textarea id="job-desc" rows="5" class="form-control" placeholder="Chi tiết các nhiệm vụ hàng ngày sinh viên sẽ thực hiện..." required minlength="10"></textarea>
                <small class="form-help">Tối thiểu 10 ký tự.</small>
            </div>

            <!-- 10. Yêu cầu ứng viên -->
            <div class="form-group" style="margin-bottom:1.25rem;">
                <label class="form-label" for="job-req">Yêu cầu ứng viên</label>
                <textarea id="job-req" rows="4" class="form-control" placeholder="Sinh viên năm 1-4, chăm chỉ, đúng giờ, giao tiếp tốt, không yêu cầu kinh nghiệm..."></textarea>
            </div>

            <!-- 11. Quyền lợi được hưởng -->
            <div class="form-group" style="margin-bottom:1.25rem;">
                <label class="form-label" for="job-benefits">Quyền lợi & Đãi ngộ</label>
                <textarea id="job-benefits" rows="4" class="form-control" placeholder="Hỗ trợ gửi xe, phụ cấp ăn trưa/tối, thưởng theo năng suất, linh hoạt đổi ca thi cử..."></textarea>
            </div>

            <!-- Section: Địa điểm làm việc cụ thể (Work Locations) -->
            <div class="job-locations-section" id="section-job-locations">
                <div class="job-locations-header">
                    <div>
                        <h3 class="job-locations-title">
                            <span><i class="ri-map-pin-2-line"></i> Địa Điểm Làm Việc Cụ Thể</span>
                            <span id="locs-count-badge" class="job-locations-counter">0 / 20</span>
                        </h3>
                        <small style="color:var(--text-muted);font-size:0.83rem;">
                            Thêm các chi nhánh, cơ sở hoặc điểm làm việc cụ thể của tin tuyển dụng này để sinh viên dễ dàng tìm thấy việc làm gần mình.
                        </small>
                    </div>
                    <button type="button" id="btn-open-add-location" class="btn btn-outline btn-sm" onclick="openAddLocationModal()" style="display:inline-flex;align-items:center;gap:0.35rem;">
                        <span>➕</span> <span>Thêm địa điểm</span>
                    </button>
                </div>

                <div id="job-locations-list" class="job-locations-grid">
                    <!-- Cards rendered dynamically -->
                </div>

                <div id="job-locations-empty" style="display:none;padding:1.5rem;text-align:center;background:#f8fafc;border:1px dashed #cbd5e1;border-radius:var(--radius-sm);">
                    <div style="font-size:1.5rem;margin-bottom:0.35rem;"><i class="ri-map-pin-2-line"></i></div>
                    <div style="font-weight:600;color:var(--dark);font-size:0.92rem;">Chưa có địa điểm làm việc cụ thể</div>
                    <p style="font-size:0.82rem;color:var(--text-muted);margin:0.25rem auto 0.75rem;max-width:420px;">
                        Nhấn nút <strong>"Thêm địa điểm"</strong> ở trên để thêm cơ sở làm việc qua bản đồ Goong. Tin có địa điểm chính xác sẽ được ưu tiên hiển thị cho sinh viên ở gần.
                    </p>
                </div>
            </div>

            <!-- 12. Trạng thái xuất bản -->
            <div class="fieldset-card" style="margin-bottom:0;">
                <label class="form-label" for="job-status" style="font-weight:700;">Trạng thái xuất bản tin</label>
                <select id="job-status" class="form-control" style="font-weight:600;">
                    <option value="published">Công khai tuyển dụng ngay (Published)</option>
                    <option value="draft">Lưu bản nháp (Draft)</option>
                    <option value="pending_approval">Gửi chờ xét duyệt (Pending Approval)</option>
                    <?php if ($isEditMode): ?>
                    <option value="closed">Đóng tuyển dụng (Closed)</option>
                    <?php endif; ?>
                </select>
                <small id="status-warning" style="display:none;color:var(--warning-text);margin-top:0.4rem;font-weight:600;">
                    Lưu ý: Doanh nghiệp của bạn chưa được xác minh (verified) nên chưa thể công khai trực tiếp. Vui lòng chọn "Lưu bản nháp" hoặc "Gửi chờ xét duyệt".
                </small>
            </div>

            <!-- Submit Buttons -->
            <div class="form-actions">
                <a href="/company/jobs" class="btn btn-outline">Hủy Bỏ</a>
                <button type="submit" id="btn-submit-job" class="btn btn-primary">
                    <?= $isEditMode ? "Cập Nhật Tin Tuyển Dụng" : "Đăng Tin Tuyển Dụng" ?>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Thêm / Sửa Địa Điểm Làm Việc -->
<div id="modal-location-form" style="display:none;position:fixed;inset:0;background:rgba(15,23,42,.6);z-index:10000;align-items:center;justify-content:center;padding:1rem;" role="dialog" aria-modal="true" aria-labelledby="modal-loc-title">
    <div class="modal-card" style="background:var(--surface);border-radius:var(--radius);max-width:600px;width:100%;max-height:90vh;overflow-y:auto;padding:1.5rem;box-shadow:var(--shadow);position:relative;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.25rem;padding-bottom:0.75rem;border-bottom:1px solid var(--border);">
            <h3 id="modal-loc-title" style="font-size:1.15rem;font-weight:700;color:var(--dark);margin:0;">Thêm Địa Điểm Làm Việc</h3>
            <button type="button" onclick="closeLocationModal()" class="modal-close-btn" aria-label="Đóng">&times;</button>
        </div>

        <form id="form-location-modal" onsubmit="handleSaveLocation(event)">
            <input type="hidden" id="loc-edit-id" value="">

            <!-- Hai lựa chọn lớn: Dùng vị trí hiện tại vs Nhập địa chỉ -->
            <div class="loc-method-selector">
                <button type="button" class="loc-method-btn active" id="btn-tab-gps" onclick="switchLocationMethod('gps')">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v4m0 12v4M2 12h4m12 0h4m-7 0a5 5 0 1 1-10 0 5 5 0 0 1 10 0z"/></svg>
                    <span>Dùng vị trí hiện tại</span>
                </button>
                <button type="button" class="loc-method-btn" id="btn-tab-manual" onclick="switchLocationMethod('manual')">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                    <span>Nhập địa chỉ</span>
                </button>
            </div>

            <!-- Panel 1: Dùng vị trí hiện tại (GPS) -->
            <div id="panel-loc-gps" class="loc-gps-box">
                <div class="loc-gps-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><polygon points="12 8 8 12 12 16 16 12 12 8"></polygon></svg>
                </div>
                <div style="font-weight:700;color:var(--dark);font-size:0.95rem;margin-bottom:0.35rem;">Định vị GPS từ thiết bị</div>
                <div class="loc-gps-desc">
                    Trình duyệt sẽ lấy tọa độ GPS thực tế và hệ thống sẽ tự động xác thực địa chỉ qua Goong. Tọa độ chỉ dùng cho phiên này và không lưu vào bộ nhớ trình duyệt.
                    <div style="margin-top:0.4rem;color:#d97706;font-size:0.8rem;font-weight:600;">⚠️ Lưu ý: GPS chỉ hoạt động trên kết nối an toàn HTTPS hoặc localhost.</div>
                </div>
                <div class="loc-gps-actions">
                    <button type="button" id="btn-get-modal-gps" class="btn btn-outline" style="font-weight:600;display:inline-flex;align-items:center;gap:0.45rem;" onclick="fetchModalGpsLocation()">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
                        <span>Lấy vị trí GPS</span>
                    </button>
                </div>
            </div>

            <!-- Panel 2: Nhập địa chỉ (Manual) -->
            <div id="panel-loc-manual" style="display:none;margin-bottom:1.25rem;">
                <!-- Subtabs: Hiện hành vs Cũ -->
                <div class="loc-subtabs">
                    <button type="button" class="loc-subtab-btn active" id="subtab-mode-current" onclick="switchManualMode('current')">
                        Địa chỉ hiện hành
                    </button>
                    <button type="button" class="loc-subtab-btn" id="subtab-mode-legacy" onclick="switchManualMode('legacy')">
                        Địa chỉ cũ (có Quận/Huyện)
                    </button>
                </div>

                <!-- Fields: Địa chỉ hiện hành -->
                <div id="fields-mode-current">
                    <div class="form-grid-address-2" style="margin-bottom:0.75rem;">
                        <div class="form-group" style="margin:0;">
                            <label class="form-label" style="font-size:0.84rem;">Tỉnh / Thành phố *</label>
                            <select id="manual-curr-province" class="form-control" data-vn-address-group="company-current" data-vn-address-level="province" data-vn-address-schema="current" data-vn-address-autoload>
                                <option value="">Đang tải Tỉnh/Thành phố...</option>
                            </select>
                        </div>
                        <div class="form-group" style="margin:0;">
                            <label class="form-label" style="font-size:0.84rem;">Phường / Xã *</label>
                            <select id="manual-curr-ward" class="form-control" data-vn-address-group="company-current" data-vn-address-level="commune" disabled>
                                <option value="">Chọn Tỉnh/Thành phố trước</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group" style="margin-bottom:0.75rem;">
                        <label class="form-label" style="font-size:0.84rem;">Địa chỉ chi tiết *</label>
                        <input type="text" id="manual-curr-detail" class="form-control" placeholder="Số nhà, tên đường, tên tòa nhà/cửa hàng...">
                    </div>
                </div>

                <!-- Fields: Địa chỉ cũ -->
                <div id="fields-mode-legacy" style="display:none;">
                    <div class="form-grid-address-3" style="margin-bottom:0.75rem;">
                        <div class="form-group" style="margin:0;">
                            <label class="form-label" style="font-size:0.84rem;">Tỉnh / TP *</label>
                            <select id="manual-leg-province" class="form-control" data-vn-address-group="company-legacy" data-vn-address-level="province" data-vn-address-schema="legacy">
                                <option value="">-- Chọn Tỉnh/Thành phố --</option>
                            </select>
                        </div>
                        <div class="form-group" style="margin:0;">
                            <label class="form-label" style="font-size:0.84rem;">Quận / Huyện *</label>
                            <select id="manual-leg-district" class="form-control" data-vn-address-group="company-legacy" data-vn-address-level="district" disabled>
                                <option value="">Chọn Tỉnh/Thành phố trước</option>
                            </select>
                        </div>
                        <div class="form-group" style="margin:0;">
                            <label class="form-label" style="font-size:0.84rem;">Phường / Xã *</label>
                            <select id="manual-leg-ward" class="form-control" data-vn-address-group="company-legacy" data-vn-address-level="commune" disabled>
                                <option value="">Chọn Quận/Huyện trước</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group" style="margin-bottom:0.75rem;">
                        <label class="form-label" style="font-size:0.84rem;">Địa chỉ chi tiết *</label>
                        <input type="text" id="manual-leg-detail" class="form-control" placeholder="Số nhà, tên đường, tên tòa nhà/cửa hàng...">
                    </div>
                </div>

                <div style="display:flex;justify-content:flex-end;">
                    <button type="button" id="btn-resolve-manual" class="btn btn-outline btn-sm" style="font-weight:600;" onclick="resolveManualAddressModal()">
                        <i class="ri-search-line"></i> Xác thực & Chuẩn hóa địa chỉ
                    </button>
                </div>
            </div>

            <!-- Autocomplete fallback / quick search container -->
            <div id="loc-autocomplete-wrapper" style="display:none;margin-bottom:1rem;">
                <div id="loc-autocomplete-container"></div>
            </div>

            <!-- Hộp kết quả chuẩn hóa (Result Preview Box) -->
            <div id="loc-resolved-preview" class="loc-resolved-preview" style="display:none;">
                <div class="loc-resolved-header">
                    <span class="loc-resolved-badge">✓ Đã chuẩn hóa & xác thực tọa độ</span>
                    <span id="loc-preview-coords" class="resolved-coords-chip">10.77, 106.70</span>
                </div>
                <div id="loc-preview-address" class="loc-resolved-address">Địa chỉ hiển thị ở đây</div>
                <div class="loc-resolved-details">
                    <span id="loc-preview-province" class="loc-resolved-tag">Tỉnh/TP: ...</span>
                    <span id="loc-preview-commune" class="loc-resolved-tag">Phường/Xã: ...</span>
                    <span id="loc-preview-district" class="loc-resolved-tag" style="display:none;">Quận/Huyện cũ: ...</span>
                </div>
            </div>

            <!-- Trường chi nhánh -->
            <div class="form-group" style="margin-bottom:1rem;">
                <label class="form-label" for="loc-branch-name">Tên chi nhánh / cơ sở (Tùy chọn)</label>
                <input type="text" id="loc-branch-name" class="form-control" placeholder="Ví dụ: Chi nhánh Nguyễn Huệ, Cửa hàng số 2..." maxlength="150">
            </div>

            <!-- Trường làm địa điểm chính -->
            <div class="form-group" style="margin-bottom:1.25rem;">
                <label style="display:inline-flex;align-items:center;gap:0.5rem;cursor:pointer;font-size:0.9rem;font-weight:600;color:var(--dark);">
                    <input type="checkbox" id="loc-is-primary" style="width:17px;height:17px;">
                    <span>Đặt làm địa điểm chính (Hiển thị nổi bật trên tin tuyển dụng)</span>
                </label>
            </div>

            <!-- Actions -->
            <div style="display:flex;justify-content:flex-end;gap:0.75rem;">
                <button type="button" class="btn btn-outline btn-sm" onclick="closeLocationModal()">Hủy</button>
                <button type="submit" id="btn-save-loc" class="btn btn-primary btn-sm" disabled>Lưu Địa Điểm</button>
            </div>
        </form>
    </div>
</div>

<script>
const IS_EDIT_MODE = <?= $isEditMode ? "true" : "false" ?>;
const EDIT_JOB_ID = "<?= htmlspecialchars($editingJobId, ENT_QUOTES, 'UTF-8') ?>";
let companyVerificationStatus = "pending";
let jobLocations = [];
let locAutocompleteInstance = null;
let companyLocationPicker = null;

document.addEventListener("DOMContentLoaded", async () => {
    // Auth UX Guard
    if (!TokenStorage.isLoggedIn()) {
        showToast("Vui lòng đăng nhập với tài khoản Doanh nghiệp.", "error");
        window.location.href = `/login?login_required=1&redirect=${encodeURIComponent(window.location.pathname)}`;
        return;
    }
    const user = TokenStorage.getUser();
    if (!user || user.role !== "company") {
        showToast("Chỉ tài khoản Nhà tuyển dụng mới có quyền truy cập trang này.", "error");
        setTimeout(() => {
            window.location.href = (user && user.role === "student") ? "/student/dashboard" : "/";
        }, 800);
        return;
    }

    companyLocationPicker = new LargeLocationPicker({
        mode: 'single',
        title: 'Chọn khu vực làm việc chính',
        trigger: '#btn-company-location-picker',
        labelElement: '#company-location-picker-label',
        hiddenInput: '#job-location',
        onApply: (selectedIds, selectedItems, displayText) => {
            const locId = selectedIds[0] || "";
            document.getElementById("job-location").value = locId;
        }
    });

    locAutocompleteInstance = new AddressAutocomplete("#loc-autocomplete-container", {
        id: "job-work-address",
        label: "Địa chỉ chính xác",
        placeholder: "Nhập và chọn một địa chỉ từ gợi ý Goong...",
        required: true
    });
    renderJobLocations();

    // Set default deadline to +30 days
    if (!IS_EDIT_MODE) {
        const d = new Date();
        d.setDate(d.getDate() + 30);
        document.getElementById("job-deadline").value = d.toISOString().split('T')[0];
    }

    await Promise.all([loadCategories(), loadLocations(), loadSkills(), checkCompanyVerification()]);

    if (IS_EDIT_MODE && EDIT_JOB_ID) {
        loadJobForEditing(EDIT_JOB_ID);
    }
});

async function checkCompanyVerification() {
    const res = await apiRequest("/company/profile", { requireAuth: true });
    if (res && res.success && res.data) {
        companyVerificationStatus = res.data.verification_status || "pending";
        const warningEl = document.getElementById("status-warning");
        const statusSel = document.getElementById("job-status");

        if (companyVerificationStatus !== "verified") {
            warningEl.style.display = "block";
            // Default to draft or pending if unverified
            if (!IS_EDIT_MODE) {
                statusSel.value = "draft";
            }
        }
    }
}

async function loadCategories() {
    const res = await apiRequest("/categories");
    if (res && res.success && Array.isArray(res.data)) {
        const sel = document.getElementById("job-category");
        const currentVal = sel.value;
        res.data.forEach(c => {
            if (!Array.from(sel.options).some(o => o.value === c.id)) {
                const opt = document.createElement("option");
                opt.value = c.id;
                opt.textContent = c.name;
                sel.appendChild(opt);
            }
        });
        if (currentVal) {
            sel.value = currentVal;
            window.refreshCustomSelect?.(sel);
        }
    }
}

async function loadLocations() {
    await LargeLocationPicker.fetchHierarchy();
}

async function loadSkills() {
    const res = await apiRequest("/skills");
    if (res && res.success && Array.isArray(res.data)) {
        const container = document.getElementById("job-skills-container");
        container.innerHTML = res.data.map(s => `
            <label style="display:inline-flex;align-items:center;gap:0.35rem;padding:0.3rem 0.6rem;background:#fff;border:1px solid var(--border);border-radius:var(--radius);font-size:0.85rem;cursor:pointer;">
                <input type="checkbox" name="skill_id" value="${escapeHtml(s.id)}">
                ${escapeHtml(s.name)}
            </label>
        `).join("");
    }
}

/* ==========================================================================
   LOCATION MANAGEMENT (CRUD & MULTI-BRANCH)
   ========================================================================== */
async function loadJobLocations(jobId) {
    const res = await apiRequest(`/jobs/${encodeURIComponent(jobId)}/locations`);
    if (res && res.success && Array.isArray(res.data)) {
        jobLocations = res.data;
    } else {
        jobLocations = [];
    }
    renderJobLocations();
}

function renderJobLocations() {
    const container = document.getElementById("job-locations-list");
    const emptyEl = document.getElementById("job-locations-empty");
    const badgeEl = document.getElementById("locs-count-badge");
    const addBtn = document.getElementById("btn-open-add-location");

    badgeEl.innerText = `${jobLocations.length} / 20`;
    if (addBtn) {
        addBtn.disabled = jobLocations.length >= 20;
        if (jobLocations.length >= 20) {
            addBtn.title = "Đã đạt số lượng tối đa 20 địa điểm.";
        } else {
            addBtn.title = "";
        }
    }

    if (jobLocations.length === 0) {
        container.innerHTML = "";
        emptyEl.style.display = "block";
        return;
    }

    emptyEl.style.display = "none";
    container.innerHTML = jobLocations.map((loc, idx) => {
        const locId = loc.id || loc.temp_id || `loc-${idx}`;
        const isPrimary = !!loc.is_primary;
        const isVerified = loc.geocode_status === "verified" || loc.provider === "goong";
        const branchName = loc.branch_name ? escapeHtml(loc.branch_name) : `Cơ sở ${idx + 1}`;
        const addressText = escapeHtml(loc.address_text || "");

        const parts = [loc.commune, loc.province].filter(Boolean);
        const subAddress = parts.length > 0 ? parts.join(", ") : "";
        const legacyDistrict = loc.district_text_legacy ? escapeHtml(loc.district_text_legacy) : "";

        return `
            <div class="location-card ${isPrimary ? 'is-primary' : ''}">
                <div class="location-card-info">
                    <div class="location-card-title">
                        <span>${branchName}</span>
                        ${isPrimary ? '<span class="badge-loc badge-loc-primary">★ Địa điểm chính</span>' : ''}
                        ${isVerified ? '<span class="badge-loc badge-loc-verified">✓ Đã xác thực</span>' : '<span class="badge-loc badge-loc-manual">✎ Nhập thủ công</span>'}
                    </div>
                    <div class="location-card-address">${addressText}</div>
                    <div class="location-card-meta">
                        ${subAddress ? `<span style="font-size:0.8rem;color:var(--text-muted);"><i class="ri-map-pin-2-line"></i> ${escapeHtml(subAddress)}</span>` : ''}
                        ${legacyDistrict ? `<span class="badge-loc" style="background:#e2e8f0;color:#475569;">${legacyDistrict}</span>` : ''}
                    </div>
                </div>
                <div class="location-card-actions">
                    ${!isPrimary ? `<button type="button" class="btn-set-primary" onclick="handleSetPrimaryLocation('${escapeHtml(locId)}')">Đặt làm chính</button>` : ''}
                    <button type="button" class="btn-loc-action" onclick="openEditLocationModal('${escapeHtml(locId)}')">Sửa</button>
                    <button type="button" class="btn-loc-action btn-danger-action" onclick="handleDeleteLocation('${escapeHtml(locId)}')">Xóa</button>
                </div>
            </div>
        `;
    }).join("");
}

let currentResolvedLocation = null;

function switchLocationMethod(method) {
    const btnGps = document.getElementById("btn-tab-gps");
    const btnManual = document.getElementById("btn-tab-manual");
    const panelGps = document.getElementById("panel-loc-gps");
    const panelManual = document.getElementById("panel-loc-manual");

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

async function switchManualMode(mode) {
    const btnCurr = document.getElementById("subtab-mode-current");
    const btnLeg = document.getElementById("subtab-mode-legacy");
    const fieldsCurr = document.getElementById("fields-mode-current");
    const fieldsLeg = document.getElementById("fields-mode-legacy");

    if (mode === "current") {
        btnCurr.classList.add("active");
        btnLeg.classList.remove("active");
        fieldsCurr.style.display = "block";
        fieldsLeg.style.display = "none";
        await VietnamAddressPicker.initGroup("company-current");
    } else {
        btnLeg.classList.add("active");
        btnCurr.classList.remove("active");
        fieldsCurr.style.display = "none";
        fieldsLeg.style.display = "block";
        await VietnamAddressPicker.initGroup("company-legacy");
    }
}

function renderResolvedPreview(data) {
    currentResolvedLocation = data;
    const previewBox = document.getElementById("loc-resolved-preview");
    const coordsEl = document.getElementById("loc-preview-coords");
    const addrEl = document.getElementById("loc-preview-address");
    const provEl = document.getElementById("loc-preview-province");
    const commEl = document.getElementById("loc-preview-commune");
    const distEl = document.getElementById("loc-preview-district");
    const saveBtn = document.getElementById("btn-save-loc");

    if (!data || data.latitude == null || data.longitude == null) {
        if (previewBox) previewBox.style.display = "none";
        if (saveBtn) saveBtn.disabled = true;
        return;
    }

    const latStr = typeof data.latitude === "number" ? data.latitude.toFixed(5) : data.latitude;
    const lngStr = typeof data.longitude === "number" ? data.longitude.toFixed(5) : data.longitude;

    if (coordsEl) coordsEl.innerText = `${latStr}, ${lngStr}`;
    if (addrEl) addrEl.innerText = data.address_text || "Địa chỉ không tên";
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
    if (saveBtn) saveBtn.disabled = false;
}

async function fetchModalGpsLocation() {
    if (!window.isSecureContext && location.hostname !== "localhost" && location.hostname !== "127.0.0.1") {
        showToast("Tính năng GPS chỉ hoạt động trên kết nối an toàn HTTPS.", "warning");
    }

    if (!navigator.geolocation) {
        showToast("Trình duyệt không hỗ trợ Geolocation GPS.", "error");
        return;
    }

    const btn = document.getElementById("btn-get-modal-gps");
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = `<span class="autocomplete-spinner" style="position:static;width:14px;height:14px;display:inline-block;margin-right:0.35rem;"></span> Đang lấy vị trí GPS...`;

    navigator.geolocation.getCurrentPosition(
        async (pos) => {
            const lat = pos.coords.latitude;
            const lng = pos.coords.longitude;
            // Tọa độ GPS chỉ lưu tạm trong bộ nhớ phiên, tuyệt đối KHÔNG lưu vào localStorage hay sessionStorage

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
                    renderResolvedPreview(res.data);
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
            console.warn("Modal GPS error:", err);
            btn.disabled = false;
            btn.innerHTML = originalText;

            let msg = "Không thể lấy vị trí GPS.";
            if (err.code === err.PERMISSION_DENIED) {
                msg = "Bạn đã từ chối quyền truy cập vị trí GPS. Hãy nhập địa chỉ thủ công ở tab bên cạnh.";
            } else if (err.code === err.POSITION_UNAVAILABLE) {
                msg = "Vị trí GPS không khả dụng. Vui lòng chuyển sang tab Nhập địa chỉ.";
            } else if (err.code === err.TIMEOUT) {
                msg = "Quá thời gian chờ định vị GPS. Vui lòng thử lại hoặc nhập địa chỉ.";
            }
            showToast(msg, "warning");
        },
        { enableHighAccuracy: true, timeout: 10000 }
    );
}

async function resolveManualAddressModal() {
    const isLegacy = document.getElementById("subtab-mode-legacy").classList.contains("active");
    let province = "";
    let district = "";
    let ward = "";
    let detail = "";

    if (isLegacy) {
        province = document.getElementById("manual-leg-province").value.trim();
        district = document.getElementById("manual-leg-district").value.trim();
        ward = document.getElementById("manual-leg-ward").value.trim();
        detail = document.getElementById("manual-leg-detail").value.trim();

        if (!province) { showToast("Vui lòng chọn Tỉnh/Thành phố.", "warning"); return; }
        if (!district) { showToast("Vui lòng chọn Quận/Huyện cũ.", "warning"); return; }
        if (!ward) { showToast("Vui lòng chọn Phường/Xã.", "warning"); return; }
        if (!detail || detail.length < 3) { showToast("Vui lòng nhập địa chỉ chi tiết (số nhà, đường...).", "warning"); return; }
    } else {
        province = document.getElementById("manual-curr-province").value.trim();
        ward = document.getElementById("manual-curr-ward").value.trim();
        detail = document.getElementById("manual-curr-detail").value.trim();

        if (!province) { showToast("Vui lòng chọn Tỉnh/Thành phố.", "warning"); return; }
        if (!ward) { showToast("Vui lòng chọn Phường/Xã.", "warning"); return; }
        if (!detail || detail.length < 3) { showToast("Vui lòng nhập địa chỉ chi tiết (số nhà, đường...).", "warning"); return; }
    }

    const btn = document.getElementById("btn-resolve-manual");
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = `<span class="autocomplete-spinner" style="position:static;width:14px;height:14px;display:inline-block;margin-right:0.35rem;"></span> Đang xác thực...`;

    try {
        const administrativeCodes = VietnamAddressPicker.getCodes(isLegacy ? "company-legacy" : "company-current");
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
            renderResolvedPreview(res.data);
            showToast("Địa chỉ đã được chuẩn hóa và xác thực tọa độ thành công.", "success");
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

function openAddLocationModal() {
    if (jobLocations.length >= 20) {
        showToast("Mỗi tin được có tối đa 20 địa điểm làm việc.", "warning");
        return;
    }
    document.getElementById("modal-loc-title").innerText = "Thêm Địa Điểm Làm Việc";
    document.getElementById("loc-edit-id").value = "";
    document.getElementById("loc-branch-name").value = "";
    document.getElementById("loc-is-primary").checked = jobLocations.length === 0;

    // Reset inputs
    VietnamAddressPicker.reset("company-current");
    document.getElementById("manual-curr-detail").value = "";
    VietnamAddressPicker.reset("company-legacy");
    document.getElementById("manual-leg-detail").value = "";

    if (locAutocompleteInstance) locAutocompleteInstance.clear();
    currentResolvedLocation = null;
    renderResolvedPreview(null);

    switchLocationMethod("gps");
    switchManualMode("current");

    document.getElementById("modal-location-form").style.display = "flex";
}

async function openEditLocationModal(id) {
    const loc = jobLocations.find(l => (l.id || l.temp_id) === id);
    if (!loc) return;

    document.getElementById("modal-loc-title").innerText = "Chỉnh Sửa Địa Điểm Làm Việc";
    document.getElementById("loc-edit-id").value = id;
    document.getElementById("loc-branch-name").value = loc.branch_name || "";
    document.getElementById("loc-is-primary").checked = !!loc.is_primary;

    currentResolvedLocation = {
        address_text: loc.address_text,
        province: loc.province,
        commune: loc.commune,
        district_text_legacy: loc.district_text_legacy,
        latitude: loc.latitude,
        longitude: loc.longitude,
        provider_place_id: loc.provider_place_id || loc.place_id || null,
        geocode_status: loc.geocode_status || "verified"
    };

    // Populate manual inputs for editing convenience
    if (loc.district_text_legacy) {
        await switchManualMode("legacy");
        await VietnamAddressPicker.setValues("company-legacy", {
            province: loc.province,
            district: loc.district_text_legacy,
            commune: loc.commune
        });
        document.getElementById("manual-leg-detail").value = loc.address_text || "";
    } else {
        await switchManualMode("current");
        await VietnamAddressPicker.setValues("company-current", {
            province: loc.province,
            commune: loc.commune
        });
        document.getElementById("manual-curr-detail").value = loc.address_text || "";
    }

    renderResolvedPreview(currentResolvedLocation);
    switchLocationMethod("manual");

    document.getElementById("modal-location-form").style.display = "flex";
}

function closeLocationModal() {
    document.getElementById("modal-location-form").style.display = "none";
}

async function handleSaveLocation(e) {
    e.preventDefault();
    const editId = document.getElementById("loc-edit-id").value.trim();
    const branchName = document.getElementById("loc-branch-name").value.trim();
    const isPrimary = document.getElementById("loc-is-primary").checked;

    // Check if valid coordinates have been obtained
    if (!currentResolvedLocation || currentResolvedLocation.latitude == null || currentResolvedLocation.longitude == null) {
        showToast("Vui lòng chọn một địa chỉ trong danh sách gợi ý Goong hoặc xác thực vị trí để có tọa độ hợp lệ.", "warning");
        return;
    }

    const saveBtn = document.getElementById("btn-save-loc");
    saveBtn.disabled = true;
    saveBtn.innerText = "Đang lưu...";

    const payload = {
        branch_name: branchName || null,
        is_primary: isPrimary,
        address_text: currentResolvedLocation.address_text,
        province: currentResolvedLocation.province || null,
        commune: currentResolvedLocation.commune || null,
        district_text_legacy: currentResolvedLocation.district_text_legacy || null,
        latitude: currentResolvedLocation.latitude,
        longitude: currentResolvedLocation.longitude,
        provider_place_id: currentResolvedLocation.provider_place_id || null,
        geocode_status: currentResolvedLocation.geocode_status || "verified"
    };

    if (IS_EDIT_MODE && EDIT_JOB_ID) {
        try {
            if (editId) {
                const res = await apiRequest(`/company/jobs/${encodeURIComponent(EDIT_JOB_ID)}/locations/${encodeURIComponent(editId)}`, {
                    method: "PATCH",
                    body: payload,
                    requireAuth: true
                });

                if (res && res.success) {
                    showToast("Đã cập nhật địa điểm làm việc.", "success");
                    closeLocationModal();
                    await loadJobLocations(EDIT_JOB_ID);
                } else {
                    showToast((res && res.message) ? res.message : "Cập nhật địa điểm thất bại.", "error");
                }
            } else {
                const res = await apiRequest(`/company/jobs/${encodeURIComponent(EDIT_JOB_ID)}/locations`, {
                    method: "POST",
                    body: payload,
                    requireAuth: true
                });

                if (res && res.success) {
                    showToast("Đã thêm địa điểm làm việc.", "success");
                    closeLocationModal();
                    await loadJobLocations(EDIT_JOB_ID);
                } else {
                    showToast((res && res.message) ? res.message : "Thêm địa điểm thất bại.", "error");
                }
            }
        } catch (err) {
            console.error("Save location error:", err);
            showToast("Lỗi khi lưu địa điểm.", "error");
        }
    } else {
        // Draft job: persist in frontend jobLocations array
        if (editId) {
            const idx = jobLocations.findIndex(l => (l.id || l.temp_id) === editId);
            if (idx !== -1) {
                if (isPrimary) {
                    jobLocations.forEach(l => l.is_primary = false);
                }
                jobLocations[idx] = {
                    ...jobLocations[idx],
                    ...payload,
                    temp_id: editId
                };
            }
        } else {
            if (isPrimary) {
                jobLocations.forEach(l => l.is_primary = false);
            }
            jobLocations.push({
                temp_id: "draft-" + Date.now() + "-" + Math.random().toString(36).substring(2, 6),
                ...payload,
                is_primary: isPrimary || jobLocations.length === 0
            });
        }
        closeLocationModal();
        renderJobLocations();
        showToast("Đã lưu địa điểm vào danh sách chờ.", "success");
    }

    saveBtn.disabled = false;
    saveBtn.innerText = "Lưu Địa Điểm";
}

async function handleDeleteLocation(id) {
    if (!confirm("Bạn có chắc chắn muốn xóa địa điểm làm việc này khỏi tin tuyển dụng?")) {
        return;
    }

    if (IS_EDIT_MODE && EDIT_JOB_ID) {
        try {
            const res = await apiRequest(`/company/jobs/${encodeURIComponent(EDIT_JOB_ID)}/locations/${encodeURIComponent(id)}`, {
                method: "DELETE",
                requireAuth: true
            });
            if (res && res.success) {
                showToast("Đã xóa địa điểm làm việc.", "info");
                await loadJobLocations(EDIT_JOB_ID);
            } else {
                showToast((res && res.message) ? res.message : "Xóa địa điểm thất bại.", "error");
            }
        } catch (err) {
            console.error("Delete location error:", err);
            showToast("Lỗi khi xóa địa điểm.", "error");
        }
    } else {
        const deletedWasPrimary = jobLocations.find(l => (l.id || l.temp_id) === id)?.is_primary;
        jobLocations = jobLocations.filter(l => (l.id || l.temp_id) !== id);
        if (deletedWasPrimary && jobLocations.length > 0) {
            jobLocations[0].is_primary = true;
        }
        renderJobLocations();
        showToast("Đã xóa địa điểm khỏi danh sách.", "info");
    }
}

async function handleSetPrimaryLocation(id) {
    if (IS_EDIT_MODE && EDIT_JOB_ID) {
        try {
            const res = await apiRequest(`/company/jobs/${encodeURIComponent(EDIT_JOB_ID)}/locations/${encodeURIComponent(id)}`, {
                method: "PATCH",
                body: { is_primary: true },
                requireAuth: true
            });
            if (res && res.success) {
                showToast("Đã đặt làm địa điểm chính.", "success");
                await loadJobLocations(EDIT_JOB_ID);
            } else {
                showToast((res && res.message) ? res.message : "Không thể đặt địa điểm chính.", "error");
            }
        } catch (err) {
            console.error("Set primary error:", err);
            showToast("Lỗi thao tác.", "error");
        }
    } else {
        jobLocations.forEach(l => {
            l.is_primary = (l.id || l.temp_id) === id;
        });
        renderJobLocations();
        showToast("Đã đặt làm địa điểm chính.", "success");
    }
}

async function loadJobForEditing(id) {
    const loadingEl = document.getElementById("job-form-loading");
    const container = document.getElementById("job-form-container");

    loadingEl.style.display = "block";
    container.style.display = "none";

    const res = await apiRequest(`/jobs/${encodeURIComponent(id)}`, { requireAuth: true });
    loadingEl.style.display = "none";

    if (res && res.success && res.data) {
        container.style.display = "block";
        const j = res.data;

        document.getElementById("job-title").value = j.title || "";
        const catVal = j.category_id || j.category || "";
        const catSel = document.getElementById("job-category");
        if (catSel && catVal) {
            let opt = Array.from(catSel.options).find(o => o.value === catVal || o.textContent === catVal);
            if (!opt) {
                opt = document.createElement("option");
                opt.value = catVal;
                opt.textContent = catVal;
                catSel.appendChild(opt);
            }
            catSel.value = opt.value;
            window.refreshCustomSelect?.(catSel);
        }
        document.getElementById("job-location").value = j.location_id || "";
        if (companyLocationPicker && j.location_id) {
            companyLocationPicker.setSelected([j.location_id]);
        }
        document.getElementById("job-work-type").value = j.work_type || "part_time";
        document.getElementById("job-work-mode").value = j.work_mode || "onsite";
        document.getElementById("job-salary-type").value = j.salary_type || "hourly";
        document.getElementById("job-salary-min").value = j.salary_min || "";
        document.getElementById("job-salary-max").value = j.salary_max || "";
        document.getElementById("job-shift-type").value = j.shift_type || "";
        document.getElementById("job-minimum-age").value = j.minimum_age ?? "";
        document.getElementById("job-maximum-age").value = j.maximum_age ?? "";
        document.getElementById("job-working-schedule").value = j.working_schedule || "";
        document.getElementById("job-quantity").value = j.quantity || "";
        document.getElementById("job-deadline").value = j.application_deadline ? j.application_deadline.substring(0, 10) : "";
        document.getElementById("job-desc").value = j.description || "";
        document.getElementById("job-req").value = j.requirements || "";
        document.getElementById("job-benefits").value = j.benefits || "";
        document.getElementById("job-status").value = j.status || "published";
        await loadJobLocations(id);

        // Pre-check skills if available
        let skillIds = [];
        if (Array.isArray(j.skills)) {
            skillIds = j.skills.map(s => typeof s === "object" && s !== null ? s.id : s);
        } else if (Array.isArray(j.required_skills)) {
            skillIds = j.required_skills.map(s => typeof s === "object" && s !== null ? s.id : s);
        } else if (typeof j.required_skills === "string") {
            try {
                const parsed = JSON.parse(j.required_skills);
                if (Array.isArray(parsed)) {
                    skillIds = parsed;
                }
            } catch (e) {
                skillIds = j.required_skills.split(",").map(s => s.trim()).filter(Boolean);
            }
        }
        const knownSkillIds = new Set(Array.from(document.querySelectorAll("input[name='skill_id']")).map(cb => cb.value));
        const customSkills = [];
        document.querySelectorAll("input[name='skill_id']").forEach(cb => {
            if (skillIds.map(String).includes(cb.value)) cb.checked = true;
        });
        skillIds.forEach(skill => {
            const value = String(skill || "").trim();
            if (value && !knownSkillIds.has(value)) customSkills.push(value);
        });
        document.getElementById("job-custom-skills").value = customSkills.join(", ");
    } else {
        showToast((res && res.message) ? res.message : "Không thể tải thông tin tin việc làm.", "error");
        setTimeout(() => { window.location.href = "/company/jobs"; }, 1500);
    }
}

async function handleSubmitJob(e) {
    e.preventDefault();
    const btn = document.getElementById("btn-submit-job");
    const alertBox = document.getElementById("job-form-alert");
    alertBox.style.display = "none";

    // Collect selected skills
    const selectedSkills = [];
    document.querySelectorAll("input[name='skill_id']:checked").forEach(cb => {
        selectedSkills.push(cb.value);
    });
    document.getElementById("job-custom-skills").value
        .split(/[,;\n]+/)
        .map(skill => skill.trim())
        .filter(Boolean)
        .forEach(skill => selectedSkills.push(skill));
    const uniqueSkills = Array.from(new Map(selectedSkills.map(skill => [skill.toLocaleLowerCase("vi"), skill])).values());

    const chosenLocId = document.getElementById("job-location").value.trim();
    if (!chosenLocId) {
        alertBox.className = "toast toast-error";
        alertBox.innerText = "Vui lòng chọn Tỉnh/Thành phố và Phường/Xã làm việc chính.";
        alertBox.style.display = "block";
        document.getElementById("btn-company-location-picker").scrollIntoView({ behavior: "smooth", block: "center" });
        return;
    }

    const statusVal = document.getElementById("job-status").value;
    const workMode = document.getElementById("job-work-mode").value;

    if (["onsite", "hybrid"].includes(workMode) && jobLocations.length === 0) {
        alertBox.className = "toast toast-error";
        alertBox.innerText = "Vui lòng thêm ít nhất một địa điểm làm việc đã chọn từ gợi ý Goong.";
        alertBox.style.display = "block";
        document.getElementById("section-job-locations").scrollIntoView({ behavior: "smooth", block: "center" });
        return;
    }

    // UX Pre-check for unverified company trying to publish
    if (statusVal === "published" && companyVerificationStatus !== "verified") {
        alertBox.className = "toast toast-error";
        alertBox.innerText = "Công ty của bạn chưa được xác minh (verified) nên chưa thể công khai tin tuyển dụng. Vui lòng chọn 'Lưu bản nháp' (Draft) hoặc 'Chờ duyệt' (Pending Approval).";
        alertBox.style.display = "block";
        window.scrollTo({ top: 0, behavior: 'smooth' });
        return;
    }

    const payload = {
        title: document.getElementById("job-title").value.trim(),
        category_id: document.getElementById("job-category").value || null,
        category: document.getElementById("job-category").value || null,
        location_id: document.getElementById("job-location").value || null,
        work_type: document.getElementById("job-work-type").value,
        work_mode: workMode,
        salary_type: document.getElementById("job-salary-type").value,
        salary_min: document.getElementById("job-salary-min").value ? parseInt(document.getElementById("job-salary-min").value) : null,
        salary_max: document.getElementById("job-salary-max").value ? parseInt(document.getElementById("job-salary-max").value) : null,
        shift_type: document.getElementById("job-shift-type").value || null,
        working_schedule: document.getElementById("job-working-schedule").value.trim() || null,
        minimum_age: document.getElementById("job-minimum-age").value ? parseInt(document.getElementById("job-minimum-age").value, 10) : null,
        maximum_age: document.getElementById("job-maximum-age").value ? parseInt(document.getElementById("job-maximum-age").value, 10) : null,
        quantity: document.getElementById("job-quantity").value ? parseInt(document.getElementById("job-quantity").value) : 1,
        application_deadline: document.getElementById("job-deadline").value || null,
        description: document.getElementById("job-desc").value.trim(),
        requirements: document.getElementById("job-req").value.trim() || null,
        benefits: document.getElementById("job-benefits").value.trim() || null,
        status: statusVal,
        required_skills: uniqueSkills
    };

    btn.disabled = true;
    btn.innerText = "Đang lưu...";

    const url = IS_EDIT_MODE ? `/jobs/${encodeURIComponent(EDIT_JOB_ID)}` : "/jobs";
    const method = IS_EDIT_MODE ? "PUT" : "POST";

    const res = await apiRequest(url, {
        method: method,
        // Tin mới luôn được tạo ở trạng thái nháp trước. Chỉ công khai/chờ duyệt
        // sau khi toàn bộ địa điểm đã được lưu thành công.
        body: IS_EDIT_MODE ? payload : { ...payload, status: "draft" },
        requireAuth: true
    });

    if (res && res.success) {
        if (!IS_EDIT_MODE) {
            const createdJobId = res.data && res.data.id ? String(res.data.id) : "";
            if (!createdJobId) {
                btn.disabled = false;
                btn.innerText = "Đăng Tin Tuyển Dụng";
                alertBox.className = "toast toast-error";
                alertBox.innerText = "Tin đã được tạo nhưng máy chủ không trả về mã tin để lưu địa điểm. Vui lòng mở danh sách tin và bổ sung lại địa điểm.";
                alertBox.style.display = "block";
                return;
            }

            btn.innerText = "Đang lưu địa điểm...";
            const locationErrors = await persistDraftJobLocations(createdJobId);
            if (locationErrors.length > 0) {
                btn.disabled = false;
                btn.innerText = "Đăng Tin Tuyển Dụng";
                alertBox.className = "toast toast-error";
                alertBox.innerText = `Tin đã được lưu an toàn ở trạng thái nháp, nhưng ${locationErrors.length} địa điểm chưa lưu được. Hãy mở tin vừa tạo để bổ sung lại.`;
                alertBox.style.display = "block";
                setTimeout(() => {
                    window.location.href = `/company/jobs/${encodeURIComponent(createdJobId)}/edit?location_error=1`;
                }, 1400);
                return;
            }

            if (statusVal !== "draft") {
                btn.innerText = "Đang hoàn tất tin...";
                const finalizeResult = await apiRequest(`/jobs/${encodeURIComponent(createdJobId)}`, {
                    method: "PUT",
                    body: payload,
                    requireAuth: true
                });
                if (!finalizeResult || !finalizeResult.success) {
                    btn.disabled = false;
                    btn.innerText = "Đăng Tin Tuyển Dụng";
                    alertBox.className = "toast toast-error";
                    alertBox.innerText = "Tin và địa điểm đã được lưu ở trạng thái nháp, nhưng chưa thể chuyển sang trạng thái bạn chọn. Bạn có thể hoàn tất từ trang quản lý tin.";
                    alertBox.style.display = "block";
                    setTimeout(() => {
                        window.location.href = `/company/jobs/${encodeURIComponent(createdJobId)}/edit?finalize_error=1`;
                    }, 1400);
                    return;
                }
            }
        }

        btn.disabled = false;
        btn.innerText = IS_EDIT_MODE ? "Cập Nhật Tin Tuyển Dụng" : "Đăng Tin Tuyển Dụng";
        showToast(IS_EDIT_MODE ? "Cập nhật tin tuyển dụng thành công!" : "Đăng tin tuyển dụng thành công!", "success");
        setTimeout(() => {
            window.location.href = "/company/jobs";
        }, 600);
    } else {
        btn.disabled = false;
        btn.innerText = IS_EDIT_MODE ? "Cập Nhật Tin Tuyển Dụng" : "Đăng Tin Tuyển Dụng";
        let msg = (res && res.message) ? res.message : "Thao tác thất bại.";
        if (res && res.errors) {
            msg += "\n" + Object.values(res.errors).flat().map(escapeHtml).join(" | ");
        }
        alertBox.className = "toast toast-error";
        alertBox.innerText = msg;
        alertBox.style.display = "block";
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
}

async function persistDraftJobLocations(jobId) {
    const errors = [];
    for (const location of jobLocations) {
        const payload = {
            branch_name: location.branch_name || null,
            is_primary: !!location.is_primary,
            address_text: location.address_text,
            province: location.province || null,
            commune: location.commune || null,
            district_text_legacy: location.district_text_legacy || null,
            latitude: location.latitude,
            longitude: location.longitude,
            provider_place_id: location.provider_place_id || location.place_id || null,
            geocode_status: location.geocode_status || "verified"
        };
        if (location.place_id) {
            payload.place_id = location.place_id;
        }
        if (location.session_token) {
            payload.session_token = location.session_token;
        }
        const result = await apiRequest(`/company/jobs/${encodeURIComponent(jobId)}/locations`, {
            method: "POST",
            body: payload,
            requireAuth: true
        });
        if (!result || !result.success) {
            errors.push(result && result.message ? result.message : "Không thể lưu địa điểm.");
        }
    }
    return errors;
}
</script>
