<div class="cv-templates-hero">
    <div class="container">
        <div class="cv-hero-badge">
            <span><i class="ri-sparkling-fill" style="color:#fbbf24;margin-right:4px;"></i> Kho Mẫu CV Chuẩn Sinh Viên & ATS</span>
        </div>
        <h1 class="cv-hero-title">Tạo CV Chuyên Nghiệp Dành Riêng Cho Sinh Viên</h1>
        <p class="cv-hero-subtitle">
            Thiết kế tối ưu cho sinh viên ứng tuyển việc làm bán thời gian, thực tập và công việc đầu tiên. 
            Chuẩn cấu trúc sàng lọc ATS, dễ đọc và hoàn toàn miễn phí.
        </p>

        <!-- Quick Filter Pills -->
        <div class="cv-filter-bar" id="cv-template-filters" role="tablist" aria-label="Bộ lọc mẫu CV">
            <button type="button" class="cv-filter-pill active" data-filter="all" role="tab" aria-selected="true">
                <span><i class="ri-apps-line"></i> Tất Cả Mẫu</span>
            </button>
            <button type="button" class="cv-filter-pill" data-filter="student" role="tab" aria-selected="false">
                <span><i class="ri-graduation-cap-line"></i> Sinh Viên</span>
            </button>
            <button type="button" class="cv-filter-pill" data-filter="ats" role="tab" aria-selected="false">
                <span><i class="ri-robot-2-line"></i> Chuẩn ATS</span>
            </button>
            <button type="button" class="cv-filter-pill" data-filter="simple" role="tab" aria-selected="false">
                <span><i class="ri-file-text-line"></i> Tối Giản</span>
            </button>
            <button type="button" class="cv-filter-pill" data-filter="modern" role="tab" aria-selected="false">
                <span><i class="ri-palette-line"></i> Hiện Đại</span>
            </button>
        </div>
    </div>
</div>

<div class="container" style="max-width: 1200px; padding-bottom: 4rem;">
    <!-- Template Grid Container -->
    <div id="cv-templates-container" class="cv-template-grid" aria-live="polite">
        <!-- Skeleton Loading Placeholders -->
        <div class="cv-template-card cv-skeleton" style="height: 520px;"></div>
        <div class="cv-template-card cv-skeleton" style="height: 520px;"></div>
        <div class="cv-template-card cv-skeleton" style="height: 520px;"></div>
    </div>
</div>

<!-- Modal: Tạo CV Mới Từ Mẫu -->
<div id="modal-create-cv" class="cv-modal-backdrop" style="display: none;" role="dialog" aria-modal="true" aria-labelledby="modal-create-cv-title">
    <div class="cv-modal-card">
        <div class="cv-modal-header">
            <h3 id="modal-create-cv-title">Khởi Tạo CV Mới</h3>
            <button type="button" class="cv-modal-close-btn" onclick="closeCreateCvModal()" aria-label="Đóng">&times;</button>
        </div>
        <form id="form-create-cv" onsubmit="handleCreateCvSubmit(event)">
            <div class="cv-modal-body">
                <input type="hidden" id="create-cv-template-key" value="student-simple">

                <div class="cv-form-group" style="margin-bottom: 1.25rem;">
                    <label class="cv-form-label" for="create-cv-title">Đặt tên cho CV của bạn <span style="color:var(--danger)">*</span></label>
                    <input type="text" id="create-cv-title" class="cv-input" placeholder="VD: CV Thực tập IT, CV Bán hàng part-time..." required maxlength="150" autofocus>
                    <small style="color:var(--text-muted);font-size:0.8rem;margin-top:0.3rem;">
                        Tên này giúp bạn phân biệt các CV trong tài khoản của mình.
                    </small>
                </div>

                <div class="cv-form-group">
                    <label class="cv-form-label" for="create-cv-language">Ngôn ngữ viết CV</label>
                    <select id="create-cv-language" class="cv-select">
                        <option value="vi" selected>Tiếng Việt</option>
                        <option value="en">English</option>
                    </select>
                </div>

                <fieldset class="cv-source-fieldset">
                    <legend>Nội dung ban đầu</legend>
                    <div id="create-cv-source-loading" class="cv-source-loading">
                        <i class="ri-loader-4-line ri-spin"></i> Đang kiểm tra dữ liệu hồ sơ...
                    </div>
                    <div id="create-cv-source-options" class="cv-source-options" hidden>
                        <label class="cv-source-option" id="create-cv-source-profile-card">
                            <input type="radio" name="create-cv-source" value="profile" id="create-cv-source-profile">
                            <span class="cv-source-option-copy">
                                <span class="cv-source-option-title">
                                    <i class="ri-user-3-line"></i> Dùng hồ sơ cá nhân
                                    <span class="cv-source-recommended">Khuyên dùng</span>
                                </span>
                                <span class="cv-source-option-description" id="create-cv-profile-description">
                                    Điền sẵn thông tin đã lưu trên trang cá nhân.
                                </span>
                            </span>
                        </label>

                        <label class="cv-source-option" id="create-cv-source-existing-card">
                            <input type="radio" name="create-cv-source" value="existing_cv" id="create-cv-source-existing">
                            <span class="cv-source-option-copy">
                                <span class="cv-source-option-title"><i class="ri-file-copy-2-line"></i> Dùng nội dung từ CV đã có</span>
                                <span class="cv-source-option-description">Sao chép nội dung thành một CV mới độc lập.</span>
                                <select id="create-cv-source-id" class="cv-select cv-source-select" aria-label="Chọn CV nguồn" disabled>
                                    <option value="">Chọn một CV...</option>
                                </select>
                            </span>
                        </label>

                        <label class="cv-source-option">
                            <input type="radio" name="create-cv-source" value="blank" id="create-cv-source-blank" checked>
                            <span class="cv-source-option-copy">
                                <span class="cv-source-option-title"><i class="ri-file-add-line"></i> Tạo CV trống</span>
                                <span class="cv-source-option-description">Chỉ điền sẵn họ tên và email tài khoản.</span>
                            </span>
                        </label>
                    </div>
                    <p class="cv-source-note"><i class="ri-information-line"></i> Dữ liệu chỉ được sao chép một lần. Chỉnh sửa CV này sẽ không làm thay đổi hồ sơ cá nhân hoặc CV gốc.</p>
                </fieldset>
            </div>
            <div class="cv-modal-footer">
                <button type="button" class="btn btn-outline btn-sm" onclick="closeCreateCvModal()">Hủy Bỏ</button>
                <button type="submit" id="btn-submit-create-cv" class="btn btn-primary btn-sm">
                    <i class="ri-file-add-line"></i> Bắt Đầu Soạn CV
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Xem Chi Tiết Mẫu -->
<div id="modal-preview-template" class="cv-modal-backdrop" style="display: none;" role="dialog" aria-modal="true" aria-labelledby="modal-preview-title">
    <div class="cv-modal-card cv-template-preview-modal">
        <div class="cv-modal-header">
            <h3 id="modal-preview-title">Chi Tiết Mẫu CV</h3>
            <button type="button" class="cv-modal-close-btn" onclick="closePreviewTemplateModal()" aria-label="Đóng">&times;</button>
        </div>
        <div class="cv-modal-body" id="modal-preview-body">
            <!-- Rendered dynamically -->
        </div>
        <div class="cv-modal-footer">
            <button type="button" class="btn btn-outline btn-sm" onclick="closePreviewTemplateModal()">Đóng</button>
            <button type="button" id="btn-modal-use-template" class="btn btn-primary btn-sm">
                <i class="ri-file-edit-line"></i> Dùng Mẫu Này
            </button>
        </div>
    </div>
</div>

<script src="/assets/js/cv_builder.js?v=<?= time() ?>"></script>
<script>
document.addEventListener("DOMContentLoaded", () => {
    if (window.CvTemplatesApp) {
        window.CvTemplatesApp.init();
    }
});
</script>
