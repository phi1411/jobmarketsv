<div class="cv-editor-wrapper">
    <!-- Topbar Controls -->
    <header class="cv-editor-topbar">
        <div class="cv-editor-title-box">
            <a href="/student/cvs" class="btn btn-outline btn-sm" title="Quay lại danh sách CV">
                <i class="ri-arrow-left-line"></i> Quay lại
            </a>
            <input type="text" id="cv-editor-title" class="cv-editor-title-input" value="Đang tải..." aria-label="Tiêu đề CV" maxlength="150">
            <div id="cv-autosave-indicator" class="cv-autosave-status saved" aria-live="polite">
                <span id="cv-autosave-icon"><i class="ri-check-line"></i></span>
                <span id="cv-autosave-text">Đã lưu</span>
            </div>
            <span id="cv-completion-badge" class="badge-cv-primary">0%</span>
        </div>

        <div class="cv-editor-top-actions">
            <button type="button" class="btn btn-outline btn-sm" id="btn-topbar-share" onclick="window.CvEditorApp.toggleShareModal()" title="Bật hoặc tắt chia sẻ công khai">
                <i class="ri-share-line"></i> Chia sẻ
            </button>
            <button type="button" class="btn btn-outline btn-sm" id="btn-topbar-download-pdf" onclick="window.CvEditorApp.downloadPdf()" title="Tải file PDF chuẩn A4">
                <i class="ri-download-2-line"></i> Tải PDF
            </button>
            <button type="button" class="btn btn-primary btn-sm" id="btn-topbar-activate" onclick="window.CvEditorApp.activateForApplication()" title="Dùng CV này làm hồ sơ ứng tuyển chính trong hệ thống">
                <i class="ri-send-plane-fill"></i> Dùng ứng tuyển
            </button>
        </div>
    </header>

    <!-- Mobile Switch Tabs (< 1024px) -->
    <nav class="cv-editor-mobile-tabs" role="tablist" aria-label="Chế độ xem editor">
        <button type="button" id="tab-btn-form" class="cv-tab-btn active" role="tab" aria-selected="true" onclick="window.CvEditorApp.switchMobileTab('form')">
            <i class="ri-edit-line"></i> Nội Dung Chỉnh Sửa
        </button>
        <button type="button" id="tab-btn-preview" class="cv-tab-btn" role="tab" aria-selected="false" onclick="window.CvEditorApp.switchMobileTab('preview')">
            <i class="ri-eye-line"></i> Xem Trước (A4)
        </button>
    </nav>

    <!-- Split Screen Layout -->
    <div class="cv-editor-layout">
        <!-- Left Form Column -->
        <main class="cv-editor-form-col" id="cv-editor-form-column">
            <!-- Style & Design Panel Box -->
            <section class="cv-section-box" id="section-design-settings">
                <div class="cv-section-header" onclick="window.CvEditorApp.toggleAccordion('section-design-body')">
                    <div class="cv-section-title-wrap">
                        <span class="cv-section-icon"><i class="ri-palette-line"></i></span>
                        <h3>Giao Diện & Định Dạng (Thiết Kế)</h3>
                    </div>
                    <i id="icon-toggle-design-body" class="ri-arrow-down-s-line"></i>
                </div>
                <div class="cv-section-body" id="section-design-body">
                    <div class="cv-form-row">
                        <div class="cv-form-group">
                            <label class="cv-form-label" for="style-template-key">Mẫu CV</label>
                            <select id="style-template-key" class="cv-select" onchange="window.CvEditorApp.handleTemplateChange(this.value)">
                                <option value="student-simple">Sinh viên tối giản (1 cột, dễ đọc)</option>
                                <option value="student-modern">Sinh viên hiện đại (Header màu, thanh lịch)</option>
                                <option value="ats-classic">ATS cổ điển (Đen trắng chuẩn sàng lọc)</option>
                            </select>
                        </div>
                        <div class="cv-form-group">
                            <label class="cv-form-label" for="style-language">Ngôn ngữ</label>
                            <select id="style-language" class="cv-select" onchange="window.CvEditorApp.handleLanguageChange(this.value)">
                                <option value="vi">Tiếng Việt</option>
                                <option value="en">English</option>
                            </select>
                        </div>
                    </div>

                    <div class="cv-form-row">
                        <div class="cv-form-group">
                            <label class="cv-form-label">Màu sắc chủ đạo (Accent Color)</label>
                            <div class="cv-style-palette" id="color-palette-container">
                                <button type="button" class="cv-color-dot" data-color="#0f766e" style="background:#0f766e;" title="Xanh Ngọc Teal" onclick="window.CvEditorApp.setAccentColor('#0f766e')"></button>
                                <button type="button" class="cv-color-dot" data-color="#2563eb" style="background:#2563eb;" title="Xanh Dương Royal" onclick="window.CvEditorApp.setAccentColor('#2563eb')"></button>
                                <button type="button" class="cv-color-dot" data-color="#10b981" style="background:#10b981;" title="Xanh Lá Emerald" onclick="window.CvEditorApp.setAccentColor('#10b981')"></button>
                                <button type="button" class="cv-color-dot" data-color="#7c3aed" style="background:#7c3aed;" title="Tím Violet" onclick="window.CvEditorApp.setAccentColor('#7c3aed')"></button>
                                <button type="button" class="cv-color-dot" data-color="#ea580c" style="background:#ea580c;" title="Cam Năng Động" onclick="window.CvEditorApp.setAccentColor('#ea580c')"></button>
                                <button type="button" class="cv-color-dot" data-color="#111827" style="background:#111827;" title="Đen Trầm Classic" onclick="window.CvEditorApp.setAccentColor('#111827')"></button>
                                <input type="color" id="custom-accent-color" class="cv-custom-color-input" title="Tùy chọn màu mã Hex" onchange="window.CvEditorApp.setAccentColor(this.value)">
                            </div>
                        </div>

                        <div class="cv-form-group">
                            <label class="cv-form-label" for="style-font-family">Phông chữ</label>
                            <select id="style-font-family" class="cv-select" onchange="window.CvEditorApp.handleFontChange(this.value)">
                                <option value="DejaVu Sans">DejaVu Sans (Mặc định chuẩn A4)</option>
                                <option value="Arial">Arial (Hiện đại, dễ đọc)</option>
                                <option value="Times New Roman">Times New Roman (Cổ điển)</option>
                            </select>
                        </div>
                    </div>

                    <div class="cv-form-row">
                        <div class="cv-form-group">
                            <label class="cv-form-label" for="style-font-size">Cỡ chữ (pt): <strong id="val-font-size">10</strong>pt</label>
                            <input type="range" id="style-font-size" class="cv-input" min="9" max="14" step="1" value="10" oninput="document.getElementById('val-font-size').textContent = this.value; window.CvEditorApp.handleStyleSlider('font_size', parseInt(this.value, 10))">
                        </div>

                        <div class="cv-form-group">
                            <label class="cv-form-label" for="style-line-height">Giãn dòng: <strong id="val-line-height">1.45</strong></label>
                            <input type="range" id="style-line-height" class="cv-input" min="1.1" max="2.0" step="0.05" value="1.45" oninput="document.getElementById('val-line-height').textContent = parseFloat(this.value).toFixed(2); window.CvEditorApp.handleStyleSlider('line_height', parseFloat(this.value))">
                        </div>
                    </div>
                </div>
            </section>

            <!-- Section Order & Visibility Manager Toolbar -->
            <section class="cv-section-box" id="section-order-manager">
                <div class="cv-section-header" onclick="window.CvEditorApp.toggleAccordion('section-order-body')">
                    <div class="cv-section-title-wrap">
                        <span class="cv-section-icon"><i class="ri-file-list-3-line"></i></span>
                        <h3>Quản Lý Mục & Đổi Thứ Tự (Sections)</h3>
                    </div>
                    <i id="icon-toggle-order-body" class="ri-arrow-down-s-line"></i>
                </div>
                <div class="cv-section-body" id="section-order-body">
                    <p style="margin: 0 0 0.85rem; font-size: 0.82rem; color: var(--text-muted, #64748b);">
                        Nhấn <i class="ri-arrow-up-s-line"></i> / <i class="ri-arrow-down-s-line"></i> để đổi thứ tự hiển thị của từng mục. Nhấn biểu tượng <i class="ri-eye-line"></i> / <i class="ri-eye-off-line"></i> để ẩn/hiện mục mà không làm mất dữ liệu đã nhập.
                    </p>
                    <div id="section-reorder-list" style="display: flex; flex-direction: column; gap: 0.4rem;">
                        <!-- Rendered by JS -->
                    </div>
                </div>
            </section>

            <!-- Dynamic Form Sections Container -->
            <form id="cv-sections-form" onsubmit="event.preventDefault()">
                <!-- Rendered dynamically by CvEditorApp according to section_order -->
            </form>
        </main>

        <!-- Right Preview Column -->
        <aside class="cv-editor-preview-col" id="cv-editor-preview-column" aria-label="Xem trước bản in CV">
            <div class="cv-preview-toolbar">
                <span style="font-size: 0.85rem; font-weight: 600; display: flex; align-items: center; gap: 0.4rem;">
                    <i class="ri-file-text-line"></i> Bản Xem Trước A4
                </span>
                <div class="cv-zoom-controls">
                    <button type="button" class="cv-zoom-btn" onclick="window.CvEditorApp.changeZoom(-0.1)" title="Thu nhỏ" aria-label="Thu nhỏ">−</button>
                    <span id="zoom-level-text" style="font-size: 0.8rem; min-width: 44px; text-align: center;">90%</span>
                    <button type="button" class="cv-zoom-btn" onclick="window.CvEditorApp.changeZoom(0.1)" title="Phóng to" aria-label="Phóng to">+</button>
                    <button type="button" class="cv-zoom-btn" style="width: auto; padding: 0 8px; font-size: 0.78rem;" onclick="window.CvEditorApp.resetZoom()" title="Vừa chiều ngang">Fit</button>
                    <button type="button" class="cv-zoom-btn" onclick="window.CvEditorApp.reloadPreviewIframe()" title="Tải lại preview" aria-label="Tải lại"><i class="ri-refresh-line"></i></button>
                </div>
            </div>

            <div class="cv-preview-viewport" id="cv-preview-viewport">
                <div class="cv-preview-iframe-wrapper" id="cv-preview-iframe-wrapper">
                    <iframe id="cv-editor-preview-iframe" class="cv-preview-iframe" title="Khung xem trước CV A4"></iframe>
                </div>
            </div>
        </aside>
    </div>
</div>

<!-- Modal 409 Conflict: Xung Đột Phiên Bản -->
<div id="modal-conflict-409" class="cv-modal-backdrop" style="display: none;" role="dialog" aria-modal="true" aria-labelledby="modal-conflict-title">
    <div class="cv-modal-card" style="max-width: 520px; border-top: 6px solid var(--danger, #ef4444);">
        <div class="cv-modal-header">
            <h3 id="modal-conflict-title" style="color: var(--danger, #ef4444); display: flex; align-items: center; gap: 0.5rem;">
                <i class="ri-alert-line"></i> Xung Đột Phiên Bản CV
            </h3>
        </div>
        <div class="cv-modal-body">
            <p style="margin: 0 0 0.85rem; font-weight: 700; color: var(--dark, #0f172a);">
                CV của bạn vừa được cập nhật ở một tab trình duyệt hoặc thiết bị khác!
            </p>
            <p style="margin: 0 0 0.85rem; font-size: 0.88rem; color: var(--text-muted, #64748b); line-height: 1.5;">
                Để đảm bảo tính toàn vẹn dữ liệu, hệ thống đã tạm dừng tự động lưu để tránh ghi đè mất thông tin mới trên máy chủ.
            </p>
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 0.85rem; font-size: 0.82rem;">
                <i class="ri-lightbulb-line" style="color:var(--primary);margin-right:4px;"></i> <strong>Khuyến nghị:</strong> Bạn có thể chọn <em>"Tải lại từ máy chủ"</em> để nhận phiên bản mới nhất, hoặc <em>"Giữ bản nháp để sao chép"</em> để copy nội dung vừa gõ ra ngoài trước.
            </div>
        </div>
        <div class="cv-modal-footer">
            <button type="button" class="btn btn-outline btn-sm" onclick="window.CvEditorApp.dismissConflictModal()">
                <i class="ri-file-copy-line"></i> Giữ Bản Nháp Để Sao Chép
            </button>
            <button type="button" class="btn btn-primary btn-sm" onclick="window.CvEditorApp.reloadLatestFromServer()">
                <i class="ri-refresh-line"></i> Tải Lại Bản Mới Từ Máy Chủ
            </button>
        </div>
    </div>
</div>

<!-- Modal Chia Sẻ & Liên Kết Công Khai -->
<div id="modal-editor-share" class="cv-modal-backdrop" style="display: none;" role="dialog" aria-modal="true" aria-labelledby="modal-editor-share-title">
    <div class="cv-modal-card" style="max-width: 520px;">
        <div class="cv-modal-header">
            <h3 id="modal-editor-share-title" style="display: flex; align-items: center; gap: 0.5rem;">
                <i class="ri-global-line"></i> Chia Sẻ CV Trực Tuyến
            </h3>
            <button type="button" class="cv-modal-close-btn" onclick="window.CvEditorApp.closeShareModal()" aria-label="Đóng">&times;</button>
        </div>
        <div class="cv-modal-body">
            <div id="share-toggle-row" style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem; padding-bottom: 1rem; border-bottom: 1px solid #e2e8f0;">
                <div>
                    <div style="font-weight: 700; color: var(--dark, #0f172a);">Trạng thái công khai</div>
                    <small style="color: var(--text-muted, #64748b);">Cho phép nhà tuyển dụng xem CV qua đường dẫn web</small>
                </div>
                <label style="display: flex; align-items: center; cursor: pointer;">
                    <input type="checkbox" id="check-is-public" style="width: 20px; height: 20px; accent-color: var(--primary);" onchange="window.CvEditorApp.handleTogglePublic(this.checked)">
                </label>
            </div>

            <div id="share-link-group" style="display: none;">
                <label class="cv-form-label" for="share-public-url">Đường dẫn CV công khai:</label>
                <div style="display: flex; gap: 0.5rem; margin-top: 0.35rem;">
                    <input type="text" id="share-public-url" class="cv-input" readonly>
                    <button type="button" class="btn btn-outline btn-sm" onclick="window.CvEditorApp.copyShareLink()">
                        <i class="ri-file-copy-line"></i> Sao Chép
                    </button>
                </div>
                <small style="display: block; margin-top: 0.5rem; color: #059669;">
                    <i class="ri-check-line" style="color:#059669;margin-right:4px;"></i> Ai có liên kết này đều có thể xem và tải bản in PDF của bạn.
                </small>
            </div>
        </div>
        <div class="cv-modal-footer">
            <button type="button" class="btn btn-outline btn-sm" onclick="window.CvEditorApp.closeShareModal()">Đóng</button>
        </div>
    </div>
</div>

<script>
window.CV_ID = <?= json_encode($cvId ?? '') ?>;
</script>
<script src="/assets/js/cv_builder.js?v=<?= time() ?>"></script>
<script>
document.addEventListener("DOMContentLoaded", () => {
    if (window.CvEditorApp && window.CV_ID) {
        window.CvEditorApp.init(window.CV_ID);
    }
});
</script>
