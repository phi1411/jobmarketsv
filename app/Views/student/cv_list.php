<?php
require_once dirname(__DIR__) . "/student/nav.php";
?>

<div class="container" style="max-width: 1200px; padding: 2rem 1rem 4rem;">
    <!-- Top Management Bar -->
    <div class="cv-manager-header">
        <div class="cv-manager-title-area">
            <h2>Quản Lý CV Online Của Bạn</h2>
            <div class="cv-manager-meta">
                <span>Tạo tối đa 20 CV cho các vị trí ứng tuyển khác nhau.</span>
                <span id="cv-active-count" class="cv-manager-count-badge">
                    <span>📄</span> <span id="cv-count-num">0</span> / 20 CV
                </span>
            </div>
        </div>
        <div>
            <a href="/mau-cv-sinh-vien" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 0.45rem; font-weight: 700;">
                <span>➕</span> Tạo CV Mới
            </a>
        </div>
    </div>

    <!-- CV Cards Grid Container -->
    <div id="cv-list-container" class="cv-grid" aria-live="polite">
        <!-- Skeleton Loaders -->
        <div class="cv-item-card cv-skeleton" style="height: 260px;"></div>
        <div class="cv-item-card cv-skeleton" style="height: 260px;"></div>
        <div class="cv-item-card cv-skeleton" style="height: 260px;"></div>
    </div>
</div>

<!-- Modal: Xem Trước CV (Iframe) -->
<div id="modal-preview-cv" class="cv-modal-backdrop" style="display: none;" role="dialog" aria-modal="true" aria-labelledby="modal-preview-cv-title">
    <div class="cv-modal-card" style="max-width: 860px; height: 90vh;">
        <div class="cv-modal-header">
            <h3 id="modal-preview-cv-title">Xem Trước CV Online</h3>
            <button type="button" class="cv-modal-close-btn" onclick="closePreviewCvModal()" aria-label="Đóng">&times;</button>
        </div>
        <div class="cv-modal-body" style="padding: 0; flex-grow: 1; display: flex; background: #334155; overflow: hidden;">
            <iframe id="cv-preview-modal-iframe" style="width: 100%; height: 100%; border: none; background: #ffffff;" title="Xem trước bản in CV"></iframe>
        </div>
        <div class="cv-modal-footer">
            <button type="button" class="btn btn-outline btn-sm" onclick="closePreviewCvModal()">Đóng</button>
            <button type="button" id="btn-modal-download-pdf" class="btn btn-primary btn-sm">
                📥 Tải Bản PDF A4
            </button>
        </div>
    </div>
</div>

<!-- Modal: Xác Nhận Xóa CV -->
<div id="modal-delete-cv" class="cv-modal-backdrop" style="display: none;" role="dialog" aria-modal="true" aria-labelledby="modal-delete-title">
    <div class="cv-modal-card" style="max-width: 460px;">
        <div class="cv-modal-header" style="border-bottom: 1px solid #fee2e2;">
            <h3 id="modal-delete-title" style="color: var(--danger, #ef4444); display: flex; align-items: center; gap: 0.5rem;">
                <span>⚠️</span> Xác Nhận Xóa CV
            </h3>
            <button type="button" class="cv-modal-close-btn" onclick="closeDeleteModal()" aria-label="Đóng">&times;</button>
        </div>
        <div class="cv-modal-body">
            <p style="margin: 0 0 0.75rem; color: var(--text, #1e293b); line-height: 1.5;">
                Bạn có chắc chắn muốn xóa CV <strong id="delete-cv-name">...</strong>?
            </p>
            <p style="margin: 0; color: var(--text-muted, #64748b); font-size: 0.85rem; line-height: 1.4;">
                Hành động này sẽ hủy liên kết chia sẻ công khai (nếu có). Bạn có thể tạo lại bất cứ lúc nào.
            </p>
        </div>
        <div class="cv-modal-footer">
            <button type="button" class="btn btn-outline btn-sm" onclick="closeDeleteModal()">Hủy Bỏ</button>
            <button type="button" id="btn-confirm-delete-cv" class="btn btn-danger btn-sm" style="background: var(--danger, #ef4444); color:#fff; border-color: var(--danger, #ef4444);">
                Đồng Ý Xóa
            </button>
        </div>
    </div>
</div>

<!-- Modal: Cảnh Báo Chia Sẻ Công Khai -->
<div id="modal-public-warning" class="cv-modal-backdrop" style="display: none;" role="dialog" aria-modal="true" aria-labelledby="modal-public-warning-title">
    <div class="cv-modal-card" style="max-width: 500px;">
        <div class="cv-modal-header">
            <h3 id="modal-public-warning-title" style="display: flex; align-items: center; gap: 0.5rem;">
                <span>🌐</span> Chia Sẻ CV Công Khai
            </h3>
            <button type="button" class="cv-modal-close-btn" onclick="closePublicWarningModal()" aria-label="Đóng">&times;</button>
        </div>
        <div class="cv-modal-body">
            <div style="background: #fffbeb; border: 1px solid #fde68a; border-radius: 8px; padding: 0.85rem 1rem; margin-bottom: 1rem; font-size: 0.88rem; color: #92400e;">
                <strong>Lưu ý quyền riêng tư:</strong> Khi bật chia sẻ, họ tên, email, số điện thoại và thông tin trong CV sẽ xuất hiện công khai trên Internet cho bất kỳ ai có liên kết.
            </div>
            <p style="margin: 0; font-size: 0.9rem; color: var(--text, #1e293b);">
                Bạn có thể tắt tính năng chia sẻ này bất cứ lúc nào trong trang quản lý.
            </p>
        </div>
        <div class="cv-modal-footer">
            <button type="button" class="btn btn-outline btn-sm" onclick="closePublicWarningModal()">Hủy</button>
            <button type="button" id="btn-confirm-toggle-public" class="btn btn-primary btn-sm">
                Xác Nhận Bật Chia Sẻ
            </button>
        </div>
    </div>
</div>

<script src="/assets/js/cv_builder.js?v=<?= time() ?>"></script>
<script>
document.addEventListener("DOMContentLoaded", () => {
    if (window.CvManagerApp) {
        window.CvManagerApp.init();
    }
});
</script>
