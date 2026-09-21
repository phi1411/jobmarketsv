<div class="cv-templates-hero">
    <div class="container">
        <div class="cv-hero-badge">
            <span>✨ Kho Mẫu CV Chuẩn Sinh Viên & ATS</span>
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
                <span>🤖 Chuẩn ATS</span>
            </button>
            <button type="button" class="cv-filter-pill" data-filter="simple" role="tab" aria-selected="false">
                <span>📄 Tối Giản</span>
            </button>
            <button type="button" class="cv-filter-pill" data-filter="modern" role="tab" aria-selected="false">
                <span>🎨 Hiện Đại</span>
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
                        <option value="vi" selected>🇻🇳 Tiếng Việt</option>
                        <option value="en">🇬🇧 English</option>
                    </select>
                </div>
            </div>
            <div class="cv-modal-footer">
                <button type="button" class="btn btn-outline btn-sm" onclick="closeCreateCvModal()">Hủy Bỏ</button>
                <button type="submit" id="btn-submit-create-cv" class="btn btn-primary btn-sm">
                    🚀 Bắt Đầu Soạn CV
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
