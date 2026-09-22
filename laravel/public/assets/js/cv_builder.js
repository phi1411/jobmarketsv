/**
 * JobMarketplace - CV Builder & Template Catalog Client (Vanilla JS)
 * Handles: Template Filtering, CV Management, Responsive Split Editor,
 * Debounced Autosave (1000ms), Version Conflict 409 Dialog, A4 Zoom Preview.
 */

/* ==========================================================================
   1. TEMPLATE CATALOG MODULE (/mau-cv-sinh-vien)
   ========================================================================== */
window.CvTemplatesApp = {
    templates: [],
    activeFilter: 'all',
    selectedTemplateKey: 'student-simple',
    createSources: null,

    async init() {
        this.bindFilters();
        await this.loadTemplates();
    },

    bindFilters() {
        const filterContainer = document.getElementById('cv-template-filters');
        if (!filterContainer) return;

        filterContainer.querySelectorAll('.cv-filter-pill').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const target = e.currentTarget;
                filterContainer.querySelectorAll('.cv-filter-pill').forEach(b => {
                    b.classList.remove('active');
                    b.setAttribute('aria-selected', 'false');
                });
                target.classList.add('active');
                target.setAttribute('aria-selected', 'true');
                this.activeFilter = target.getAttribute('data-filter') || 'all';
                this.renderTemplates();
            });
        });
    },

    async loadTemplates() {
        const container = document.getElementById('cv-templates-container');
        if (!container) return;

        const res = await apiRequest('/cv/templates');
        if (res && res.success && Array.isArray(res.data)) {
            this.templates = res.data;
            this.renderTemplates();
        } else {
            container.innerHTML = `
                <div style="grid-column: 1/-1; text-align: center; padding: 3rem 1rem;">
                    <p style="color: var(--danger, #ef4444); font-weight: 600;">Không thể tải danh sách mẫu CV.</p>
                    <button type="button" class="btn btn-outline btn-sm" onclick="window.CvTemplatesApp.loadTemplates()">Thử Lại</button>
                </div>
            `;
        }
    },

    renderTemplates() {
        const container = document.getElementById('cv-templates-container');
        if (!container) return;

        const filtered = this.templates.filter(t => {
            if (this.activeFilter === 'all') return true;
            if (this.activeFilter === 'ats') return t.ats_friendly === true;
            return Array.isArray(t.tags) && t.tags.includes(this.activeFilter);
        });

        if (filtered.length === 0) {
            container.innerHTML = `
                <div style="grid-column: 1/-1; text-align: center; padding: 3rem 1rem;">
                    <p style="color: var(--text-muted, #64748b);">Không tìm thấy mẫu nào phù hợp với bộ lọc đã chọn.</p>
                </div>
            `;
            return;
        }

        container.innerHTML = filtered.map(t => {
            const key = escapeHtml(t.key);
            const name = escapeHtml(t.name);
            const desc = escapeHtml(t.description || '');
            const atsBadge = t.ats_friendly ? `<span class="cv-badge-ats">✓ Chuẩn ATS</span>` : '';
            const color = escapeHtml(t.default_accent_color || '#0f766e');

            const tagsHtml = (t.tags || []).map(tag => {
                const label = tag === 'student' ? 'Sinh viên' : (tag === 'ats' ? 'ATS' : (tag === 'simple' ? 'Tối giản' : (tag === 'modern' ? 'Hiện đại' : tag)));
                return `<span class="cv-card-tag">${escapeHtml(label)}</span>`;
            }).join('');

            return `
                <article class="cv-template-card" data-key="${key}">
                    ${atsBadge}
                    <div class="cv-card-preview-thumb">
                        <div class="mockup-sheet ${key}">
                            <div class="mockup-header-bar" style="background:${color};">
                                <div class="mockup-line" style="width:40%;background:#ffffff;"></div>
                            </div>
                            <div class="mockup-line mockup-line-primary" style="width:60%;margin-top:4px;"></div>
                            <div class="mockup-line" style="width:90%;"></div>
                            <div class="mockup-line" style="width:85%;"></div>
                            <div class="mockup-line mockup-line-accent" style="width:50%;margin-top:8px;"></div>
                            <div class="mockup-line" style="width:95%;"></div>
                            <div class="mockup-line" style="width:80%;"></div>
                            <div class="mockup-line mockup-line-accent" style="width:45%;margin-top:8px;"></div>
                            <div class="mockup-line" style="width:88%;"></div>
                            <div class="mockup-line" style="width:70%;"></div>
                        </div>
                    </div>
                    <div class="cv-card-body">
                        <div class="cv-card-tags">${tagsHtml}</div>
                        <h2 class="cv-card-title">${name}</h2>
                        <p class="cv-card-desc">${desc}</p>
                        <div class="cv-card-actions">
                            <button type="button" class="btn btn-outline btn-sm" onclick="window.CvTemplatesApp.openPreviewModal('${key}')">
                                👁️ Xem Mẫu
                            </button>
                            <button type="button" class="btn btn-primary btn-sm" onclick="window.CvTemplatesApp.startCreateWithTemplate('${key}', '${name}')">
                                ⚡ Dùng Mẫu Này
                            </button>
                        </div>
                    </div>
                </article>
            `;
        }).join('');
    },

    openPreviewModal(templateKey) {
        const t = this.templates.find(item => item.key === templateKey);
        if (!t) return;

        this.selectedTemplateKey = templateKey;
        const modal = document.getElementById('modal-preview-template');
        const body = document.getElementById('modal-preview-body');
        const useBtn = document.getElementById('btn-modal-use-template');

        if (!modal || !body) return;

        const name = escapeHtml(t.name);
        const desc = escapeHtml(t.description || '');
        const color = escapeHtml(t.default_accent_color || '#0f766e');
        const atsText = t.ats_friendly ? 'Được định dạng chuẩn cấu trúc giúp hệ thống Applicant Tracking System (ATS) dễ dàng phân tích từ khóa và học vấn.' : 'Thiết kế trực quan cho nhà tuyển dụng xem trực tiếp.';

        body.innerHTML = `
            <div style="display:flex;gap:1.5rem;align-items:flex-start;flex-wrap:wrap;margin-bottom:1.25rem;">
                <div style="width:160px;height:220px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:6px;padding:8px;box-shadow:0 4px 12px rgba(0,0,0,0.06);display:flex;flex-direction:column;gap:5px;flex-shrink:0;">
                    <div style="height:24px;background:${color};border-radius:2px;"></div>
                    <div style="height:5px;background:#cbd5e1;border-radius:2px;width:70%;"></div>
                    <div style="height:5px;background:#e2e8f0;border-radius:2px;width:90%;"></div>
                    <div style="height:5px;background:#e2e8f0;border-radius:2px;width:80%;"></div>
                    <div style="height:5px;background:#cbd5e1;border-radius:2px;width:50%;margin-top:6px;"></div>
                    <div style="height:5px;background:#e2e8f0;border-radius:2px;width:95%;"></div>
                    <div style="height:5px;background:#e2e8f0;border-radius:2px;width:60%;"></div>
                </div>
                <div style="flex:1;min-width:240px;">
                    <h3 style="margin:0 0 0.5rem;color:var(--dark,#0f172a);">${name}</h3>
                    <p style="color:var(--text-muted,#64748b);font-size:0.9rem;line-height:1.5;margin-bottom:0.75rem;">${desc}</p>
                    <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:0.75rem;font-size:0.85rem;color:#166534;margin-bottom:0.75rem;">
                        <strong>🤖 Tối ưu hóa ATS:</strong> ${atsText}
                    </div>
                    <ul style="margin:0;padding-left:1.2rem;font-size:0.85rem;color:var(--text,#1e293b);line-height:1.6;">
                        <li>Hỗ trợ khổ giấy chuẩn A4 chuẩn quốc tế</li>
                        <li>Đổi màu sắc chủ đạo, phông chữ, cỡ chữ linh hoạt</li>
                        <li>Tự động căn lề và định dạng xuất PDF sắc nét</li>
                    </ul>
                </div>
            </div>
        `;

        if (useBtn) {
            useBtn.onclick = () => {
                this.closePreviewModal();
                this.startCreateWithTemplate(t.key, t.name);
            };
        }

        modal.style.display = 'flex';
    },

    closePreviewModal() {
        const modal = document.getElementById('modal-preview-template');
        if (modal) modal.style.display = 'none';
    },

    startCreateWithTemplate(templateKey, templateName) {
        if (!TokenStorage.isLoggedIn()) {
            showToast('Vui lòng đăng nhập để bắt đầu tạo CV của bạn.', 'info');
            window.location.href = `/login?redirect=${encodeURIComponent('/mau-cv-sinh-vien')}`;
            return;
        }

        this.selectedTemplateKey = templateKey;
        const modal = document.getElementById('modal-create-cv');
        const titleInput = document.getElementById('create-cv-title');
        const tplKeyInput = document.getElementById('create-cv-template-key');

        if (tplKeyInput) tplKeyInput.value = templateKey;
        if (titleInput) {
            titleInput.value = `CV ${templateName || 'Mới'}`;
            setTimeout(() => titleInput.focus(), 100);
        }
        if (modal) modal.style.display = 'flex';
        this.loadCreateSources();
    },

    async loadCreateSources() {
        const loading = document.getElementById('create-cv-source-loading');
        const options = document.getElementById('create-cv-source-options');
        if (!loading || !options) return;

        loading.hidden = false;
        options.hidden = true;
        const res = await apiRequest('/student/cvs/sources');
        this.createSources = res && res.success && res.data ? res.data : null;
        this.renderCreateSources();
    },

    renderCreateSources() {
        const loading = document.getElementById('create-cv-source-loading');
        const options = document.getElementById('create-cv-source-options');
        const profileRadio = document.getElementById('create-cv-source-profile');
        const profileCard = document.getElementById('create-cv-source-profile-card');
        const profileDescription = document.getElementById('create-cv-profile-description');
        const existingRadio = document.getElementById('create-cv-source-existing');
        const existingCard = document.getElementById('create-cv-source-existing-card');
        const sourceSelect = document.getElementById('create-cv-source-id');
        const blankRadio = document.getElementById('create-cv-source-blank');
        if (!loading || !options || !profileRadio || !existingRadio || !sourceSelect || !blankRadio) return;

        loading.hidden = true;
        options.hidden = false;
        const data = this.createSources || { profile: { available: false }, cvs: [], default_source: 'blank' };
        const profile = data.profile || {};
        const cvs = Array.isArray(data.cvs) ? data.cvs : [];

        profileRadio.disabled = !profile.available;
        profileCard?.classList.toggle('is-disabled', !profile.available);
        if (profileDescription) {
            if (profile.available) {
                const sections = Array.isArray(profile.sections) ? profile.sections.join(', ') : '';
                const file = profile.source_file ? ` Dữ liệu đã được lưu từ ${profile.source_file}.` : '';
                profileDescription.textContent = `Điền sẵn ${sections || 'thông tin đã lưu'} (${parseInt(profile.completion_percent || 0, 10)}% nội dung CV).${file}`;
            } else {
                profileDescription.textContent = 'Hồ sơ chưa có dữ liệu. Hãy cập nhật trang cá nhân hoặc đọc CV bằng Gemini trước.';
            }
        }

        existingRadio.disabled = cvs.length === 0;
        existingCard?.classList.toggle('is-disabled', cvs.length === 0);
        sourceSelect.innerHTML = '<option value="">Chọn một CV...</option>' + cvs.map(cv => {
            const title = escapeHtml(cv.title || 'CV chưa đặt tên');
            const id = escapeHtml(cv.id || '');
            const percent = parseInt(cv.completion_percent || 0, 10);
            return `<option value="${id}">${title} (${percent}%)</option>`;
        }).join('');

        const defaultSource = data.default_source === 'profile' && profile.available ? 'profile' : 'blank';
        profileRadio.checked = defaultSource === 'profile';
        blankRadio.checked = defaultSource === 'blank';
        existingRadio.checked = false;
        sourceSelect.disabled = true;

        const syncExistingSelect = () => {
            sourceSelect.disabled = !existingRadio.checked || existingRadio.disabled;
            if (!sourceSelect.disabled && !sourceSelect.value && sourceSelect.options.length > 1) {
                sourceSelect.selectedIndex = 1;
            }
        };
        options.querySelectorAll('input[name="create-cv-source"]').forEach(input => {
            input.onchange = syncExistingSelect;
        });
        sourceSelect.onchange = () => {
            if (sourceSelect.value) {
                existingRadio.checked = true;
                syncExistingSelect();
            }
        };
    }
};

window.closePreviewTemplateModal = function() {
    window.CvTemplatesApp.closePreviewModal();
};

window.closeCreateCvModal = function() {
    const modal = document.getElementById('modal-create-cv');
    if (modal) modal.style.display = 'none';
};

window.handleCreateCvSubmit = async function(e) {
    e.preventDefault();
    const btn = document.getElementById('btn-submit-create-cv');
    const titleInput = document.getElementById('create-cv-title');
    const tplKeyInput = document.getElementById('create-cv-template-key');
    const langInput = document.getElementById('create-cv-language');
    const sourceInput = document.querySelector('input[name="create-cv-source"]:checked');
    const sourceCvInput = document.getElementById('create-cv-source-id');

    const title = titleInput ? titleInput.value.trim() : '';
    const template_key = tplKeyInput ? tplKeyInput.value : 'student-simple';
    const language = langInput ? langInput.value : 'vi';
    const source_type = sourceInput ? sourceInput.value : 'blank';
    const source_cv_id = source_type === 'existing_cv' && sourceCvInput ? sourceCvInput.value : '';

    if (!title) {
        showToast('Vui lòng nhập tên cho CV của bạn.', 'error');
        return;
    }

    if (source_type === 'existing_cv' && !source_cv_id) {
        showToast('Vui lòng chọn CV muốn dùng làm nội dung ban đầu.', 'error');
        sourceCvInput?.focus();
        return;
    }

    if (btn) {
        btn.disabled = true;
        btn.textContent = 'Đang tạo CV...';
    }

    const res = await apiRequest('/student/cvs', {
        method: 'POST',
        body: { title, template_key, language, source_type, source_cv_id }
    });

    if (res && res.success && res.data && res.data.id) {
        showToast('Tạo CV thành công! Đang chuyển vào trình soạn thảo...', 'success');
        setTimeout(() => {
            window.location.href = `/student/cvs/${res.data.id}/edit`;
        }, 400);
    } else {
        if (btn) {
            btn.disabled = false;
            btn.textContent = '🚀 Bắt Đầu Soạn CV';
        }
        showToast((res && res.message) ? res.message : 'Không thể tạo CV. Vui lòng thử lại.', 'error');
    }
};

/* ==========================================================================
   2. STUDENT CV MANAGER MODULE (/student/cvs)
   ========================================================================== */
window.CvManagerApp = {
    cvList: [],
    selectedCvForDelete: null,
    selectedCvForShare: null,

    async init() {
        if (!TokenStorage.isLoggedIn()) {
            window.location.href = `/login?redirect=${encodeURIComponent('/student/cvs')}`;
            return;
        }
        await this.loadCvs();
    },

    async loadCvs() {
        const container = document.getElementById('cv-list-container');
        if (!container) return;

        const res = await apiRequest('/student/cvs');
        if (res && res.success && Array.isArray(res.data)) {
            this.cvList = res.data;
            this.renderList();
        } else {
            container.innerHTML = `
                <div style="grid-column: 1/-1; text-align: center; padding: 3rem 1rem;">
                    <p style="color: var(--danger, #ef4444); font-weight: 600;">Không thể tải danh sách CV.</p>
                    <button type="button" class="btn btn-outline btn-sm" onclick="window.CvManagerApp.loadCvs()">Thử Lại</button>
                </div>
            `;
        }
    },

    renderList() {
        const container = document.getElementById('cv-list-container');
        const countNum = document.getElementById('cv-count-num');
        if (!container) return;

        if (countNum) countNum.textContent = this.cvList.length;

        if (this.cvList.length === 0) {
            container.innerHTML = `
                <div style="grid-column: 1/-1; text-align: center; padding: 4rem 1.5rem; background: var(--surface,#fff); border-radius: var(--radius, 16px); border: 1px dashed var(--border,#e2e8f0);">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">📄</div>
                    <h3 style="font-size: 1.35rem; font-weight: 800; color: var(--dark, #0f172a); margin: 0 0 0.5rem;">Bạn chưa có bản CV online nào</h3>
                    <p style="color: var(--text-muted, #64748b); max-width: 480px; margin: 0 auto 1.5rem; line-height: 1.6;">
                        Tạo một bản CV chuẩn sinh viên và tối ưu ATS để sẵn sàng ứng tuyển các việc làm bán thời gian hấp dẫn nhất.
                    </p>
                    <a href="/mau-cv-sinh-vien" class="btn btn-primary" style="font-weight: 700;">
                        ✨ Khám Phá Kho Mẫu CV
                    </a>
                </div>
            `;
            return;
        }

        container.innerHTML = this.cvList.map(cv => {
            const id = escapeHtml(cv.id);
            const title = escapeHtml(cv.title || 'CV Chưa đặt tên');
            const templateKey = escapeHtml(cv.template_key || 'student-simple');
            const lang = cv.language === 'en' ? 'English' : 'Tiếng Việt';
            const percent = parseInt(cv.completion_percent || 0, 10);
            const isPrimary = cv.is_primary === 1 || cv.is_primary === true;
            const isPublic = cv.is_public === 1 || cv.is_public === true;
            const updated = cv.updated_at ? this.formatDate(cv.updated_at) : '';

            const primaryBadge = isPrimary
                ? `<span class="badge-cv-primary">⭐ CV Chính</span>`
                : `<button type="button" class="btn btn-outline btn-sm" style="font-size:0.75rem;padding:0.2rem 0.5rem;" onclick="window.CvManagerApp.setPrimary('${id}', ${cv.version})">Đặt CV chính</button>`;

            const publicBadge = isPublic
                ? `<span class="badge-cv-public">🌐 Công khai</span>`
                : `<span class="badge-cv-private">🔒 Riêng tư</span>`;

            return `
                <article class="cv-item-card ${isPrimary ? 'is-primary' : ''}" id="cv-card-${id}">
                    <div class="cv-item-top">
                        <div class="cv-item-title-col">
                            <h3 class="cv-item-title">
                                <a href="/student/cvs/${id}/edit" style="color:inherit;text-decoration:none;">${title}</a>
                            </h3>
                            <div class="cv-item-badges">
                                ${primaryBadge}
                                ${publicBadge}
                                <span class="cv-card-tag">${templateKey}</span>
                                <span class="cv-card-tag">${lang}</span>
                            </div>
                        </div>
                    </div>

                    <div class="cv-progress-section">
                        <div class="cv-progress-label">
                            <span>Độ hoàn thiện hồ sơ</span>
                            <strong>${percent}%</strong>
                        </div>
                        <div class="cv-progress-track">
                            <div class="cv-progress-bar" style="width:${percent}%;"></div>
                        </div>
                    </div>

                    <div style="display:flex;flex-wrap:wrap;gap:0.4rem;margin-bottom:1rem;">
                        <button type="button" class="btn btn-outline btn-sm" onclick="window.CvManagerApp.activateCv('${id}', ${cv.version})" title="Tạo bản snapshot PDF và dùng làm CV ứng tuyển trực tiếp">
                            🚀 Dùng để ứng tuyển
                        </button>
                        <button type="button" class="btn btn-outline btn-sm" onclick="window.CvManagerApp.duplicateCv('${id}')" title="Tạo một bản sao mới từ CV này">
                            📋 Nhân bản
                        </button>
                        ${isPublic && cv.public_slug ? `
                            <button type="button" class="btn btn-outline btn-sm" onclick="window.CvManagerApp.copyShareLink('${escapeHtml(cv.public_slug)}')" title="Sao chép đường dẫn xem công khai">
                                🔗 Link chia sẻ
                            </button>
                        ` : ''}
                        <button type="button" class="btn btn-outline btn-sm" onclick="window.CvManagerApp.togglePublicPrompt('${id}', ${!isPublic}, ${cv.version})">
                            ${isPublic ? '🔒 Tắt chia sẻ' : '🌐 Bật chia sẻ'}
                        </button>
                    </div>

                    <div class="cv-item-footer">
                        <span>Cập nhật: ${updated}</span>
                        <div class="cv-item-main-actions">
                            <button type="button" class="btn btn-outline btn-sm" onclick="window.CvManagerApp.openPreview('${id}')">
                                👁️ Xem
                            </button>
                            <button type="button" class="btn btn-outline btn-sm" onclick="window.CvManagerApp.downloadPdf('${id}', '${title}')">
                                📥 PDF
                            </button>
                            <a href="/student/cvs/${id}/edit" class="btn btn-primary btn-sm">
                                ✏️ Sửa
                            </a>
                            <button type="button" class="btn btn-outline btn-sm" style="color:var(--danger);border-color:#fca5a5;" onclick="window.CvManagerApp.confirmDelete('${id}', '${title}')" title="Xóa CV này">
                                🗑️
                            </button>
                        </div>
                    </div>
                </article>
            `;
        }).join('');
    },

    formatDate(dateStr) {
        try {
            const d = new Date(dateStr.replace(/-/g, '/'));
            if (isNaN(d.getTime())) return dateStr;
            return d.toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit', year: 'numeric' });
        } catch(e) {
            return dateStr;
        }
    },

    async openPreview(cvId) {
        const modal = document.getElementById('modal-preview-cv');
        const iframe = document.getElementById('cv-preview-modal-iframe');
        const dlBtn = document.getElementById('btn-modal-download-pdf');
        if (!modal || !iframe) return;

        modal.style.display = 'flex';
        iframe.src = 'about:blank';

        const token = TokenStorage.getToken();
        const res = await fetch(`/student/cvs/${cvId}/preview`, {
            headers: token ? { 'Authorization': `Bearer ${token}` } : {}
        });
        if (res.ok) {
            const html = await res.text();
            iframe.srcdoc = html;
        } else {
            iframe.srcdoc = `<p style="padding:2rem;color:red;text-align:center;">Không thể tải bản xem trước CV (${res.status}).</p>`;
        }

        if (dlBtn) {
            dlBtn.onclick = () => this.downloadPdf(cvId, 'CV');
        }
    },

    async downloadPdf(cvId, title) {
        showToast('Đang tạo và tải file PDF từ máy chủ...', 'info');
        const token = TokenStorage.getToken();
        try {
            const res = await fetch(`/student/cvs/${cvId}/export.pdf`, {
                headers: token ? { 'Authorization': `Bearer ${token}` } : {}
            });
            if (!res.ok) {
                showToast('Không thể xuất PDF. Vui lòng kiểm tra lại.', 'error');
                return;
            }
            const blob = await res.blob();
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `${title || 'CV'}.pdf`;
            document.body.appendChild(a);
            a.click();
            a.remove();
            window.URL.revokeObjectURL(url);
            showToast('Tải PDF thành công!', 'success');
        } catch(e) {
            showToast('Lỗi khi tải file PDF.', 'error');
        }
    },

    async setPrimary(cvId, version) {
        const res = await apiRequest(`/student/cvs/${cvId}/primary`, {
            method: 'POST',
            body: { expected_version: version }
        });
        if (res && res.success) {
            showToast('Đã đặt làm CV chính của tài khoản!', 'success');
            await this.loadCvs();
        } else {
            showToast((res && res.message) ? res.message : 'Không thể đặt CV chính.', 'error');
        }
    },

    async activateCv(cvId, version) {
        showToast('Đang kích hoạt CV và chuẩn bị bản nộp đơn...', 'info');
        const res = await apiRequest(`/student/cvs/${cvId}/activate`, {
            method: 'POST',
            body: { expected_version: version }
        });
        if (res && res.success) {
            showToast('Thành công! CV này đã trở thành hồ sơ chính dùng khi ứng tuyển.', 'success');
            await this.loadCvs();
        } else {
            showToast((res && res.message) ? res.message : 'Kích hoạt thất bại. Vui lòng thử lại.', 'error');
        }
    },

    async duplicateCv(cvId) {
        showToast('Đang nhân bản CV...', 'info');
        const res = await apiRequest(`/student/cvs/${cvId}/duplicate`, {
            method: 'POST'
        });
        if (res && res.success) {
            showToast('Nhân bản CV thành công!', 'success');
            await this.loadCvs();
        } else {
            showToast((res && res.message) ? res.message : 'Không thể nhân bản CV.', 'error');
        }
    },

    togglePublicPrompt(cvId, willBePublic, version) {
        if (willBePublic) {
            this.selectedCvForShare = { cvId, version };
            const modal = document.getElementById('modal-public-warning');
            const confirmBtn = document.getElementById('btn-confirm-toggle-public');
            if (confirmBtn) {
                confirmBtn.onclick = () => this.executeTogglePublic(cvId, true, version);
            }
            if (modal) modal.style.display = 'flex';
        } else {
            this.executeTogglePublic(cvId, false, version);
        }
    },

    async executeTogglePublic(cvId, isPublic, version) {
        closePublicWarningModal();
        const res = await apiRequest(`/student/cvs/${cvId}/visibility`, {
            method: 'PATCH',
            body: { is_public: isPublic, expected_version: version }
        });
        if (res && res.success) {
            showToast(isPublic ? 'Đã bật chia sẻ công khai.' : 'Đã chuyển CV sang chế độ riêng tư.', 'success');
            await this.loadCvs();
        } else {
            showToast((res && res.message) ? res.message : 'Không thể thay đổi trạng thái chia sẻ.', 'error');
        }
    },

    copyShareLink(publicSlug) {
        const fullUrl = `${window.location.origin}/cv/${publicSlug}`;
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(fullUrl).then(() => {
                showToast('Đã sao chép liên kết CV vào bộ nhớ tạm!', 'success');
            }).catch(() => {
                prompt('Sao chép liên kết CV:', fullUrl);
            });
        } else {
            prompt('Sao chép liên kết CV:', fullUrl);
        }
    },

    confirmDelete(cvId, title) {
        this.selectedCvForDelete = cvId;
        const modal = document.getElementById('modal-delete-cv');
        const nameEl = document.getElementById('delete-cv-name');
        const confirmBtn = document.getElementById('btn-confirm-delete-cv');

        if (nameEl) nameEl.textContent = `"${title}"`;
        if (confirmBtn) {
            confirmBtn.onclick = () => this.executeDelete();
        }
        if (modal) modal.style.display = 'flex';
    },

    async executeDelete() {
        if (!this.selectedCvForDelete) return;
        const cvId = this.selectedCvForDelete;
        closeDeleteModal();

        const res = await apiRequest(`/student/cvs/${cvId}`, {
            method: 'DELETE'
        });
        if (res && res.success) {
            showToast('Đã xóa CV.', 'success');
            await this.loadCvs();
        } else {
            showToast((res && res.message) ? res.message : 'Không thể xóa CV.', 'error');
        }
    }
};

window.closePreviewCvModal = function() {
    const modal = document.getElementById('modal-preview-cv');
    if (modal) modal.style.display = 'none';
};

window.closeDeleteModal = function() {
    const modal = document.getElementById('modal-delete-cv');
    if (modal) modal.style.display = 'none';
};

window.closePublicWarningModal = function() {
    const modal = document.getElementById('modal-public-warning');
    if (modal) modal.style.display = 'none';
};

/* ==========================================================================
   3. RESPONSIVE CV EDITOR MODULE (/student/cvs/{id}/edit)
   ========================================================================== */
window.CvEditorApp = {
    cvId: null,
    currentVersion: 1,
    cvData: null,
    isDirty: false,
    isSaving: false,
    autosaveTimer: null,
    zoomLevel: 0.9,
    sectionOrder: ['personal', 'summary', 'education', 'skills', 'projects', 'experience', 'activities', 'certifications', 'awards', 'languages', 'interests', 'custom_sections'],
    hiddenSections: [],

    async init(cvId) {
        this.cvId = cvId;
        if (!TokenStorage.isLoggedIn()) {
            window.location.href = `/login?redirect=${encodeURIComponent(window.location.pathname)}`;
            return;
        }

        this.bindTitleInput();
        await this.loadCvData();
        this.resetZoom();
        window.addEventListener('resize', () => this.handleWindowResize());
    },

    bindTitleInput() {
        const titleEl = document.getElementById('cv-editor-title');
        if (!titleEl) return;

        titleEl.addEventListener('input', () => {
            this.triggerAutosave();
        });
    },

    async loadCvData() {
        const res = await apiRequest(`/student/cvs/${this.cvId}`);
        if (res && res.success && res.data) {
            this.cvData = res.data;
            this.currentVersion = res.data.version || 1;
            this.sectionOrder = Array.isArray(res.data.section_order) ? res.data.section_order : this.sectionOrder;
            this.hiddenSections = Array.isArray(res.data.hidden_sections) ? res.data.hidden_sections : [];

            this.populateTopBar();
            this.populateStyleSettings();
            this.renderSectionOrderManager();
            this.renderFormSections();
            await this.refreshPreviewIframe();
        } else {
            showToast('Không thể tải dữ liệu CV. Đang quay lại danh sách...', 'error');
            setTimeout(() => window.location.href = '/student/cvs', 1500);
        }
    },

    populateTopBar() {
        const titleEl = document.getElementById('cv-editor-title');
        const badgeEl = document.getElementById('cv-completion-badge');
        if (titleEl) titleEl.value = this.cvData.title || 'CV';
        if (badgeEl) badgeEl.textContent = `Hoàn thiện ${this.cvData.completion_percent || 0}%`;
        this.setAutosaveStatus('saved', 'Đã lưu');
    },

    populateStyleSettings() {
        const style = this.cvData.style || {};
        const tplSelect = document.getElementById('style-template-key');
        const langSelect = document.getElementById('style-language');
        const fontSelect = document.getElementById('style-font-family');
        const fontSizeInput = document.getElementById('style-font-size');
        const lineHeightInput = document.getElementById('style-line-height');
        const valFontSize = document.getElementById('val-font-size');
        const valLineHeight = document.getElementById('val-line-height');

        if (tplSelect) tplSelect.value = this.cvData.template_key || 'student-simple';
        if (langSelect) langSelect.value = this.cvData.language || 'vi';
        if (fontSelect) fontSelect.value = style.font_family || 'DejaVu Sans';
        if (fontSizeInput) {
            fontSizeInput.value = style.font_size || 10;
            if (valFontSize) valFontSize.textContent = fontSizeInput.value;
        }
        if (lineHeightInput) {
            lineHeightInput.value = style.line_height || 1.45;
            if (valLineHeight) valLineHeight.textContent = parseFloat(lineHeightInput.value).toFixed(2);
        }

        this.highlightAccentColor(style.accent_color || '#0f766e');
    },

    highlightAccentColor(color) {
        const dots = document.querySelectorAll('.cv-color-dot');
        let matched = false;
        dots.forEach(d => {
            if (d.getAttribute('data-color').toLowerCase() === color.toLowerCase()) {
                d.classList.add('active');
                matched = true;
            } else {
                d.classList.remove('active');
            }
        });
        const customInput = document.getElementById('custom-accent-color');
        if (customInput && !matched) {
            customInput.value = color;
        }
    },

    setAccentColor(color) {
        if (!this.cvData.style) this.cvData.style = {};
        this.cvData.style.accent_color = color;
        this.highlightAccentColor(color);
        this.triggerAutosave();
    },

    handleTemplateChange(val) {
        this.cvData.template_key = val;
        this.triggerAutosave();
    },

    handleLanguageChange(val) {
        this.cvData.language = val;
        this.triggerAutosave();
    },

    handleFontChange(val) {
        if (!this.cvData.style) this.cvData.style = {};
        this.cvData.style.font_family = val;
        this.triggerAutosave();
    },

    handleStyleSlider(prop, val) {
        if (!this.cvData.style) this.cvData.style = {};
        this.cvData.style[prop] = val;
        this.triggerAutosave();
    },

    toggleAccordion(bodyId) {
        const body = document.getElementById(bodyId);
        if (!body) return;
        const isOpen = body.style.display !== 'none';
        body.style.display = isOpen ? 'none' : 'block';
        const icon = document.getElementById(`icon-toggle-${bodyId.replace('section-', '')}`);
        if (icon) icon.textContent = isOpen ? '▶' : '▼';
    },

    /* --- Section Order & Visibility --- */
    renderSectionOrderManager() {
        const container = document.getElementById('section-reorder-list');
        if (!container) return;

        const sectionLabels = {
            personal: 'Thông tin cá nhân (Header cố định)',
            summary: 'Tóm tắt / Mục tiêu nghề nghiệp',
            education: 'Học vấn & Bằng cấp',
            skills: 'Kỹ năng chuyên môn',
            projects: 'Dự án nổi bật',
            experience: 'Kinh nghiệm làm việc',
            activities: 'Hoạt động & Câu lạc bộ',
            certifications: 'Chứng chỉ',
            awards: 'Giải thưởng',
            languages: 'Ngoại ngữ',
            interests: 'Sở thích cá nhân',
            custom_sections: 'Mục tự chọn (Tùy biến)'
        };

        container.innerHTML = this.sectionOrder.map((sec, idx) => {
            const isPersonal = sec === 'personal';
            const isHidden = this.hiddenSections.includes(sec);
            const label = sectionLabels[sec] || sec;

            return `
                <div style="display:flex;align-items:center;justify-content:space-between;padding:0.45rem 0.75rem;background:#ffffff;border:1px solid #e2e8f0;border-radius:6px;font-size:0.85rem;${isHidden ? 'opacity:0.6;' : ''}">
                    <div style="display:flex;align-items:center;gap:0.5rem;">
                        <span style="color:var(--text-muted);font-weight:700;min-width:18px;">${idx + 1}.</span>
                        <strong style="color:var(--dark,#0f172a);">${escapeHtml(label)}</strong>
                        ${isHidden ? '<span class="badge-cv-private" style="font-size:0.7rem;padding:0.1rem 0.4rem;">Đang ẩn</span>' : ''}
                    </div>
                    <div style="display:flex;align-items:center;gap:0.25rem;">
                        ${!isPersonal ? `
                            <button type="button" class="cv-mini-btn" title="Chuyển lên trên" onclick="window.CvEditorApp.moveSection(${idx}, -1)">▲</button>
                            <button type="button" class="cv-mini-btn" title="Chuyển xuống dưới" onclick="window.CvEditorApp.moveSection(${idx}, 1)">▼</button>
                            <button type="button" class="cv-mini-btn" title="${isHidden ? 'Hiện mục này' : 'Ẩn mục này'}" onclick="window.CvEditorApp.toggleSectionVisibility('${sec}')">
                                ${isHidden ? '👁️' : '🚫'}
                            </button>
                        ` : '<span style="font-size:0.75rem;color:var(--text-muted);">Cố định</span>'}
                    </div>
                </div>
            `;
        }).join('');
    },

    moveSection(fromIdx, direction) {
        const toIdx = fromIdx + direction;
        // Personal is locked at index 0
        if (toIdx <= 0 || toIdx >= this.sectionOrder.length) return;

        const temp = this.sectionOrder[fromIdx];
        this.sectionOrder[fromIdx] = this.sectionOrder[toIdx];
        this.sectionOrder[toIdx] = temp;

        this.renderSectionOrderManager();
        this.renderFormSections();
        this.triggerAutosave();
    },

    toggleSectionVisibility(sec) {
        if (sec === 'personal') return;
        const idx = this.hiddenSections.indexOf(sec);
        if (idx >= 0) {
            this.hiddenSections.splice(idx, 1);
        } else {
            this.hiddenSections.push(sec);
        }
        this.renderSectionOrderManager();
        this.renderFormSections();
        this.triggerAutosave();
    },

    /* --- Dynamic Form Sections --- */
    renderFormSections() {
        const form = document.getElementById('cv-sections-form');
        if (!form) return;

        const content = this.cvData.content || {};

        form.innerHTML = this.sectionOrder.map(sec => {
            const isHidden = this.hiddenSections.includes(sec);
            return `
                <div id="section-wrapper-${sec}" style="${isHidden ? 'display:none;' : ''}">
                    ${this.buildSectionHtml(sec, content[sec])}
                </div>
            `;
        }).join('');

        this.bindDynamicFormInputs();
    },

    buildSectionHtml(sec, data) {
        switch (sec) {
            case 'personal':
                return this.buildPersonalSection(data || {});
            case 'summary':
                return this.buildSummarySection(data || '');
            case 'education':
                return this.buildListSection('education', '🎓 Học Vấn & Bằng Cấp', data || [], [
                    { id: 'school', label: 'Trường / Cơ sở đào tạo', type: 'text', placeholder: 'VD: Đại học Bách Khoa Hà Nội' },
                    { id: 'degree', label: 'Bằng cấp / Trình độ', type: 'text', placeholder: 'VD: Cử nhân' },
                    { id: 'major', label: 'Chuyên ngành', type: 'text', placeholder: 'VD: Khoa học máy tính' },
                    { id: 'start_date', label: 'Bắt đầu', type: 'text', placeholder: 'VD: 09/2021' },
                    { id: 'end_date', label: 'Kết thúc (hoặc Hiện tại)', type: 'text', placeholder: 'VD: 06/2025' },
                    { id: 'gpa', label: 'Điểm GPA', type: 'text', placeholder: 'VD: 3.4/4.0' },
                    { id: 'description', label: 'Mô tả thêm / Thành tích', type: 'textarea', placeholder: 'Học bổng khuyến khích học tập...' }
                ]);
            case 'skills':
                return this.buildListSection('skills', '⚡ Kỹ Năng Chuyên Môn', data || [], [
                    { id: 'name', label: 'Tên kỹ năng', type: 'text', placeholder: 'VD: PHP / MySQL / Giao tiếp' },
                    { id: 'level', label: 'Mức độ', type: 'text', placeholder: 'VD: Thành thạo, Khá, Cơ bản' }
                ]);
            case 'projects':
                return this.buildListSection('projects', '🚀 Dự Án Thực Hiện', data || [], [
                    { id: 'name', label: 'Tên dự án', type: 'text', placeholder: 'VD: Website Bán Hàng Trực Tuyến' },
                    { id: 'role', label: 'Vai trò trong dự án', type: 'text', placeholder: 'VD: Lập trình viên Backend' },
                    { id: 'technologies', label: 'Công nghệ sử dụng', type: 'text', placeholder: 'VD: PHP, MySQL, Git, Docker' },
                    { id: 'url', label: 'Đường dẫn dự án / Github', type: 'text', placeholder: 'https://github.com/...' },
                    { id: 'start_date', label: 'Bắt đầu', type: 'text', placeholder: 'VD: 01/2024' },
                    { id: 'end_date', label: 'Kết thúc', type: 'text', placeholder: 'VD: 04/2024' },
                    { id: 'description', label: 'Mô tả kết quả và đóng góp', type: 'textarea', placeholder: 'Xây dựng API xác thực người dùng, tích hợp cổng thanh toán...' }
                ]);
            case 'experience':
                return this.buildListSection('experience', '💼 Kinh Nghiệm Làm Việc', data || [], [
                    { id: 'organization', label: 'Công ty / Cửa hàng', type: 'text', placeholder: 'VD: The Coffee House, Highlands Coffee' },
                    { id: 'position', label: 'Vị trí đảm nhiệm', type: 'text', placeholder: 'VD: Nhân viên phục vụ part-time' },
                    { id: 'start_date', label: 'Bắt đầu', type: 'text', placeholder: 'VD: 06/2023' },
                    { id: 'end_date', label: 'Kết thúc', type: 'text', placeholder: 'VD: 12/2023' },
                    { id: 'description', label: 'Mô tả công việc & kỹ năng', type: 'textarea', placeholder: 'Chăm sóc khách hàng, phối hợp làm việc nhóm theo ca...' }
                ]);
            case 'activities':
                return this.buildListSection('activities', '🤝 Hoạt Động & Câu Lạc Bộ', data || [], [
                    { id: 'organization', label: 'Tổ chức / CLB', type: 'text', placeholder: 'VD: CLB Tình Nguyện Sinh Viên' },
                    { id: 'role', label: 'Vai trò', type: 'text', placeholder: 'VD: Trưởng ban Truyền thông' },
                    { id: 'start_date', label: 'Bắt đầu', type: 'text', placeholder: 'VD: 09/2022' },
                    { id: 'end_date', label: 'Kết thúc', type: 'text', placeholder: 'VD: 08/2023' },
                    { id: 'description', label: 'Mô tả hoạt động', type: 'textarea', placeholder: 'Tổ chức các sự kiện tiếp sức mùa thi...' }
                ]);
            case 'certifications':
                return this.buildListSection('certifications', '📜 Chứng Chỉ & Khóa Học', data || [], [
                    { id: 'name', label: 'Tên chứng chỉ', type: 'text', placeholder: 'VD: TOEIC 750 / AWS Certified' },
                    { id: 'issuer', label: 'Đơn vị cấp', type: 'text', placeholder: 'VD: IIG Vietnam / Amazon Web Services' },
                    { id: 'issued_date', label: 'Ngày cấp', type: 'text', placeholder: 'VD: 10/2023' },
                    { id: 'url', label: 'Đường dẫn xác minh', type: 'text', placeholder: 'https://...' }
                ]);
            case 'awards':
                return this.buildListSection('awards', '🏆 Giải Thưởng & Khen Thưởng', data || [], [
                    { id: 'name', label: 'Tên giải thưởng', type: 'text', placeholder: 'VD: Giải Ba Olympic Tin Học Sinh Viên' },
                    { id: 'issuer', label: 'Đơn vị trao giải', type: 'text', placeholder: 'VD: Hội Tin Học Việt Nam' },
                    { id: 'issued_date', label: 'Năm / Tháng', type: 'text', placeholder: 'VD: 2023' },
                    { id: 'description', label: 'Mô tả chi tiết', type: 'textarea', placeholder: 'Thi đấu thuật toán theo đội...' }
                ]);
            case 'languages':
                return this.buildListSection('languages', '🌐 Ngoại Ngữ', data || [], [
                    { id: 'name', label: 'Tên ngoại ngữ', type: 'text', placeholder: 'VD: Tiếng Anh, Tiếng Nhật' },
                    { id: 'level', label: 'Trình độ', type: 'text', placeholder: 'VD: Giao tiếp tốt / N3 / IELTS 6.5' }
                ]);
            case 'interests':
                return this.buildInterestsSection(data || '');
            case 'custom_sections':
                return this.buildCustomSections(data || []);
            default:
                return '';
        }
    },

    buildPersonalSection(p) {
        return `
            <section class="cv-section-box">
                <div class="cv-section-header" onclick="window.CvEditorApp.toggleAccordion('section-personal-body')">
                    <div class="cv-section-title-wrap">
                        <span class="cv-section-icon">👤</span>
                        <h3>Thông Tin Cá Nhân</h3>
                    </div>
                    <span id="icon-toggle-personal-body">▼</span>
                </div>
                <div class="cv-section-body" id="section-personal-body">
                    <div class="cv-form-row">
                        <div class="cv-form-group">
                            <label class="cv-form-label" for="cv-p-fullname">Họ và tên <span style="color:var(--danger)">*</span></label>
                            <input type="text" id="cv-p-fullname" class="cv-input cv-data-input" data-path="personal.full_name" value="${escapeHtml(p.full_name || '')}" placeholder="Nguyễn Văn A" maxlength="150" required>
                        </div>
                        <div class="cv-form-group">
                            <label class="cv-form-label" for="cv-p-jobtitle">Vị trí mong muốn / Ngành học</label>
                            <input type="text" id="cv-p-jobtitle" class="cv-input cv-data-input" data-path="personal.job_title" value="${escapeHtml(p.job_title || '')}" placeholder="VD: Thực tập sinh Lập trình Web" maxlength="150">
                        </div>
                    </div>
                    <div class="cv-form-row">
                        <div class="cv-form-group">
                            <label class="cv-form-label" for="cv-p-email">Email liên hệ <span style="color:var(--danger)">*</span></label>
                            <input type="email" id="cv-p-email" class="cv-input cv-data-input" data-path="personal.email" value="${escapeHtml(p.email || '')}" placeholder="email@example.com" maxlength="180">
                        </div>
                        <div class="cv-form-group">
                            <label class="cv-form-label" for="cv-p-phone">Số điện thoại</label>
                            <input type="tel" id="cv-p-phone" class="cv-input cv-data-input" data-path="personal.phone" value="${escapeHtml(p.phone || '')}" placeholder="0901234567" maxlength="30">
                        </div>
                    </div>
                    <div class="cv-form-row">
                        <div class="cv-form-group">
                            <label class="cv-form-label" for="cv-p-dob">Ngày sinh</label>
                            <input type="text" id="cv-p-dob" class="cv-input cv-data-input" data-path="personal.date_of_birth" value="${escapeHtml(p.date_of_birth || '')}" placeholder="VD: 20/10/2004" maxlength="20">
                        </div>
                        <div class="cv-form-group">
                            <label class="cv-form-label" for="cv-p-address">Địa chỉ sinh sống</label>
                            <input type="text" id="cv-p-address" class="cv-input cv-data-input" data-path="personal.address" value="${escapeHtml(p.address || '')}" placeholder="VD: Cầu Giấy, Hà Nội" maxlength="255">
                        </div>
                    </div>
                    <div class="cv-form-row">
                        <div class="cv-form-group">
                            <label class="cv-form-label" for="cv-p-website">Website / Portfolio cá nhân</label>
                            <input type="url" id="cv-p-website" class="cv-input cv-data-input" data-path="personal.website" value="${escapeHtml(p.website || '')}" placeholder="https://portfolio.me" maxlength="500">
                        </div>
                        <div class="cv-form-group">
                            <label class="cv-form-label" for="cv-p-linkedin">LinkedIn / Github</label>
                            <input type="url" id="cv-p-linkedin" class="cv-input cv-data-input" data-path="personal.linkedin" value="${escapeHtml(p.linkedin || '')}" placeholder="https://linkedin.com/in/..." maxlength="500">
                        </div>
                    </div>
                </div>
            </section>
        `;
    },

    buildSummarySection(summaryText) {
        return `
            <section class="cv-section-box">
                <div class="cv-section-header" onclick="window.CvEditorApp.toggleAccordion('section-summary-body')">
                    <div class="cv-section-title-wrap">
                        <span class="cv-section-icon">🎯</span>
                        <h3>Mục Tiêu Nghề Nghiệp / Tóm Tắt</h3>
                    </div>
                    <span id="icon-toggle-summary-body">▼</span>
                </div>
                <div class="cv-section-body" id="section-summary-body">
                    <div class="cv-form-group">
                        <label class="cv-form-label" for="cv-summary-input">Giới thiệu ngắn về bản thân và mục tiêu ứng tuyển</label>
                        <textarea id="cv-summary-input" class="cv-textarea cv-data-input" data-path="summary" placeholder="Sinh viên năm 3 chuyên ngành Công nghệ thông tin với nền tảng lập trình tốt, mong muốn tìm kiếm cơ hội thực tập..." maxlength="4000">${escapeHtml(summaryText)}</textarea>
                    </div>
                </div>
            </section>
        `;
    },

    buildListSection(sectionKey, title, items, fieldsConfig) {
        const itemsHtml = items.map((item, idx) => {
            const formFields = fieldsConfig.map(f => {
                const val = item[f.id] || '';
                const path = `${sectionKey}.${idx}.${f.id}`;
                if (f.type === 'textarea') {
                    return `
                        <div class="cv-form-group" style="grid-column: 1/-1;">
                            <label class="cv-form-label">${escapeHtml(f.label)}</label>
                            <textarea class="cv-textarea cv-data-input" data-path="${path}" placeholder="${escapeHtml(f.placeholder)}">${escapeHtml(val)}</textarea>
                        </div>
                    `;
                }
                return `
                    <div class="cv-form-group">
                        <label class="cv-form-label">${escapeHtml(f.label)}</label>
                        <input type="${f.type}" class="cv-input cv-data-input" data-path="${path}" value="${escapeHtml(val)}" placeholder="${escapeHtml(f.placeholder)}">
                    </div>
                `;
            }).join('');

            return `
                <div class="cv-item-repeater-card" data-idx="${idx}">
                    <div class="cv-item-repeater-header">
                        <span class="cv-item-num">Mục #${idx + 1}</span>
                        <div class="cv-item-tools">
                            ${idx > 0 ? `<button type="button" class="cv-mini-btn" title="Lên trên" onclick="window.CvEditorApp.moveListItem('${sectionKey}', ${idx}, -1)">▲</button>` : ''}
                            ${idx < items.length - 1 ? `<button type="button" class="cv-mini-btn" title="Xuống dưới" onclick="window.CvEditorApp.moveListItem('${sectionKey}', ${idx}, 1)">▼</button>` : ''}
                            <button type="button" class="cv-mini-btn btn-delete" title="Xóa mục này" onclick="window.CvEditorApp.deleteListItem('${sectionKey}', ${idx})">✕</button>
                        </div>
                    </div>
                    <div class="cv-form-row">
                        ${formFields}
                    </div>
                </div>
            `;
        }).join('');

        return `
            <section class="cv-section-box">
                <div class="cv-section-header" onclick="window.CvEditorApp.toggleAccordion('section-${sectionKey}-body')">
                    <div class="cv-section-title-wrap">
                        <h3>${title}</h3>
                    </div>
                    <span id="icon-toggle-${sectionKey}-body">▼</span>
                </div>
                <div class="cv-section-body" id="section-${sectionKey}-body">
                    <div id="list-container-${sectionKey}">
                        ${itemsHtml}
                    </div>
                    <button type="button" class="cv-btn-add-item" onclick="window.CvEditorApp.addListItem('${sectionKey}')">
                        <span>➕</span> Thêm một mục mới
                    </button>
                </div>
            </section>
        `;
    },

    buildInterestsSection(interestsText) {
        return `
            <section class="cv-section-box">
                <div class="cv-section-header" onclick="window.CvEditorApp.toggleAccordion('section-interests-body')">
                    <div class="cv-section-title-wrap">
                        <span class="cv-section-icon">☕</span>
                        <h3>Sở Thích Cá Nhân</h3>
                    </div>
                    <span id="icon-toggle-interests-body">▼</span>
                </div>
                <div class="cv-section-body" id="section-interests-body">
                    <div class="cv-form-group">
                        <textarea class="cv-textarea cv-data-input" data-path="interests" placeholder="Đọc sách công nghệ, đá bóng, chạy bộ cuối tuần..." maxlength="1000">${escapeHtml(interestsText)}</textarea>
                    </div>
                </div>
            </section>
        `;
    },

    buildCustomSections(sections) {
        const sectionsHtml = sections.map((sec, secIdx) => {
            const items = Array.isArray(sec.items) ? sec.items : [];
            const itemsHtml = items.map((item, itemIdx) => {
                return `
                    <div class="cv-item-repeater-card" style="margin-bottom:0.75rem;">
                        <div class="cv-item-repeater-header">
                            <span class="cv-item-num">Nội dung #${itemIdx + 1}</span>
                            <div class="cv-item-tools">
                                <button type="button" class="cv-mini-btn btn-delete" onclick="window.CvEditorApp.deleteCustomSectionItem(${secIdx}, ${itemIdx})">✕</button>
                            </div>
                        </div>
                        <div class="cv-form-row">
                            <div class="cv-form-group">
                                <label class="cv-form-label">Tiêu đề</label>
                                <input type="text" class="cv-input cv-data-input" data-path="custom_sections.${secIdx}.items.${itemIdx}.title" value="${escapeHtml(item.title || '')}" placeholder="VD: Tham gia Hackathon">
                            </div>
                            <div class="cv-form-group">
                                <label class="cv-form-label">Phụ đề</label>
                                <input type="text" class="cv-input cv-data-input" data-path="custom_sections.${secIdx}.items.${itemIdx}.subtitle" value="${escapeHtml(item.subtitle || '')}" placeholder="VD: Đội trưởng">
                            </div>
                            <div class="cv-form-group">
                                <label class="cv-form-label">Thời gian</label>
                                <input type="text" class="cv-input cv-data-input" data-path="custom_sections.${secIdx}.items.${itemIdx}.date" value="${escapeHtml(item.date || '')}" placeholder="VD: 11/2023">
                            </div>
                        </div>
                        <div class="cv-form-group">
                            <label class="cv-form-label">Chi tiết</label>
                            <textarea class="cv-textarea cv-data-input" data-path="custom_sections.${secIdx}.items.${itemIdx}.description" placeholder="Mô tả tóm tắt...">${escapeHtml(item.description || '')}</textarea>
                        </div>
                    </div>
                `;
            }).join('');

            return `
                <div class="cv-item-repeater-card" style="background:#ffffff;border:1px solid #cbd5e1;margin-bottom:1.25rem;">
                    <div class="cv-item-repeater-header">
                        <input type="text" class="cv-input cv-data-input" data-path="custom_sections.${secIdx}.title" value="${escapeHtml(sec.title || 'Mục Tùy Chọn')}" style="font-weight:700;max-width:300px;" placeholder="Tên mục tùy chọn">
                        <button type="button" class="btn btn-outline btn-sm" style="color:var(--danger);border-color:#fca5a5;" onclick="window.CvEditorApp.deleteCustomSection(${secIdx})">
                            Xóa Mục Này
                        </button>
                    </div>
                    <div>
                        ${itemsHtml}
                    </div>
                    <button type="button" class="btn btn-outline btn-sm" style="margin-top:0.5rem;" onclick="window.CvEditorApp.addCustomSectionItem(${secIdx})">
                        ➕ Thêm dòng nội dung
                    </button>
                </div>
            `;
        }).join('');

        return `
            <section class="cv-section-box">
                <div class="cv-section-header" onclick="window.CvEditorApp.toggleAccordion('section-custom_sections-body')">
                    <div class="cv-section-title-wrap">
                        <span class="cv-section-icon">➕</span>
                        <h3>Mục Tùy Chọn Thêm</h3>
                    </div>
                    <span id="icon-toggle-custom_sections-body">▼</span>
                </div>
                <div class="cv-section-body" id="section-custom_sections-body">
                    ${sectionsHtml}
                    <button type="button" class="cv-btn-add-item" onclick="window.CvEditorApp.addCustomSection()">
                        <span>➕</span> Thêm một danh mục tùy chọn mới
                    </button>
                </div>
            </section>
        `;
    },

    bindDynamicFormInputs() {
        const form = document.getElementById('cv-sections-form');
        if (!form) return;

        form.querySelectorAll('.cv-data-input').forEach(input => {
            input.addEventListener('input', (e) => {
                const target = e.target;
                const path = target.getAttribute('data-path');
                if (path) {
                    this.setValueByPath(this.cvData.content, path, target.value);
                    this.triggerAutosave();
                }
            });
        });
    },

    setValueByPath(obj, path, value) {
        const keys = path.split('.');
        let cur = obj;
        for (let i = 0; i < keys.length - 1; i++) {
            const key = keys[i];
            if (!cur[key]) {
                cur[key] = isNaN(keys[i + 1]) ? {} : [];
            }
            cur = cur[key];
        }
        cur[keys[keys.length - 1]] = value;
    },

    /* --- List Item Manipulation --- */
    addListItem(sectionKey) {
        if (!this.cvData.content[sectionKey]) {
            this.cvData.content[sectionKey] = [];
        }
        this.cvData.content[sectionKey].push({});
        this.renderFormSections();
        this.triggerAutosave();
    },

    deleteListItem(sectionKey, idx) {
        if (!this.cvData.content[sectionKey]) return;
        this.cvData.content[sectionKey].splice(idx, 1);
        this.renderFormSections();
        this.triggerAutosave();
    },

    moveListItem(sectionKey, idx, direction) {
        const list = this.cvData.content[sectionKey];
        if (!Array.isArray(list)) return;
        const toIdx = idx + direction;
        if (toIdx < 0 || toIdx >= list.length) return;

        const temp = list[idx];
        list[idx] = list[toIdx];
        list[toIdx] = temp;

        this.renderFormSections();
        this.triggerAutosave();
    },

    addCustomSection() {
        if (!Array.isArray(this.cvData.content.custom_sections)) {
            this.cvData.content.custom_sections = [];
        }
        this.cvData.content.custom_sections.push({
            id: 'custom-' + Date.now(),
            title: 'Mục Mới',
            items: []
        });
        this.renderFormSections();
        this.triggerAutosave();
    },

    deleteCustomSection(idx) {
        if (!Array.isArray(this.cvData.content.custom_sections)) return;
        this.cvData.content.custom_sections.splice(idx, 1);
        this.renderFormSections();
        this.triggerAutosave();
    },

    addCustomSectionItem(secIdx) {
        const sec = this.cvData.content.custom_sections[secIdx];
        if (!sec) return;
        if (!Array.isArray(sec.items)) sec.items = [];
        sec.items.push({});
        this.renderFormSections();
        this.triggerAutosave();
    },

    deleteCustomSectionItem(secIdx, itemIdx) {
        const sec = this.cvData.content.custom_sections[secIdx];
        if (!sec || !Array.isArray(sec.items)) return;
        sec.items.splice(itemIdx, 1);
        this.renderFormSections();
        this.triggerAutosave();
    },

    /* --- Autosave & Version Conflict 409 --- */
    triggerAutosave() {
        this.isDirty = true;
        this.setAutosaveStatus('saving', 'Đang soạn thảo...');

        if (this.autosaveTimer) {
            clearTimeout(this.autosaveTimer);
        }

        this.autosaveTimer = setTimeout(() => {
            this.executeAutosave();
        }, 1000);
    },

    async executeAutosave() {
        if (this.isSaving) {
            // Re-schedule if another save is in progress
            this.autosaveTimer = setTimeout(() => this.executeAutosave(), 500);
            return;
        }

        this.isSaving = true;
        this.setAutosaveStatus('saving', 'Đang lưu...');

        const titleEl = document.getElementById('cv-editor-title');
        const title = titleEl ? titleEl.value.trim() : (this.cvData.title || 'CV');

        const payload = {
            expected_version: this.currentVersion,
            title,
            template_key: this.cvData.template_key,
            language: this.cvData.language,
            content: this.cvData.content,
            style: this.cvData.style,
            section_order: this.sectionOrder,
            hidden_sections: this.hiddenSections
        };

        const res = await apiRequest(`/student/cvs/${this.cvId}`, {
            method: 'PATCH',
            body: payload
        });

        this.isSaving = false;

        if (res && res.success && res.data) {
            this.isDirty = false;
            this.currentVersion = res.data.version;
            this.cvData.completion_percent = res.data.completion_percent;

            const badgeEl = document.getElementById('cv-completion-badge');
            if (badgeEl) badgeEl.textContent = `Hoàn thiện ${res.data.completion_percent || 0}%`;

            const now = new Date();
            const timeStr = now.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
            this.setAutosaveStatus('saved', `Đã lưu lúc ${timeStr}`);

            await this.refreshPreviewIframe();
        } else if (res && res.status === 409) {
            // 409 Conflict: Stop retrying and show dialog
            if (this.autosaveTimer) clearTimeout(this.autosaveTimer);
            this.setAutosaveStatus('error', 'Xung đột phiên bản!');
            this.showConflictModal();
        } else {
            this.setAutosaveStatus('error', 'Lỗi lưu');
            showToast((res && res.message) ? res.message : 'Không thể lưu CV. Vui lòng kiểm tra kết nối.', 'error');
        }
    },

    setAutosaveStatus(status, text) {
        const indicator = document.getElementById('cv-autosave-indicator');
        const icon = document.getElementById('cv-autosave-icon');
        const textEl = document.getElementById('cv-autosave-text');
        if (!indicator || !icon || !textEl) return;

        indicator.className = `cv-autosave-status ${status}`;
        textEl.textContent = text;
        if (status === 'saving') {
            icon.textContent = '⏳';
        } else if (status === 'saved') {
            icon.textContent = '✓';
        } else {
            icon.textContent = '⚠️';
        }
    },

    showConflictModal() {
        const modal = document.getElementById('modal-conflict-409');
        if (modal) modal.style.display = 'flex';
    },

    dismissConflictModal() {
        const modal = document.getElementById('modal-conflict-409');
        if (modal) modal.style.display = 'none';
        showToast('Bạn đang giữ bản nháp hiện tại. Hãy sao chép nội dung quan trọng trước khi tải lại trang.', 'info');
    },

    async reloadLatestFromServer() {
        const modal = document.getElementById('modal-conflict-409');
        if (modal) modal.style.display = 'none';
        showToast('Đang tải lại bản mới nhất từ máy chủ...', 'info');
        await this.loadCvData();
    },

    /* --- Preview & Zoom --- */
    async refreshPreviewIframe() {
        const iframe = document.getElementById('cv-editor-preview-iframe');
        if (!iframe) return;

        const token = TokenStorage.getToken();
        try {
            const res = await fetch(`/student/cvs/${this.cvId}/preview`, {
                headers: token ? { 'Authorization': `Bearer ${token}` } : {}
            });
            if (res.ok) {
                const html = await res.text();
                iframe.srcdoc = html;
            }
        } catch(e) {}
    },

    reloadPreviewIframe() {
        this.refreshPreviewIframe();
        showToast('Đã làm mới khung xem trước.', 'info');
    },

    changeZoom(delta) {
        this.zoomLevel = Math.max(0.4, Math.min(1.5, this.zoomLevel + delta));
        this.applyZoom();
    },

    resetZoom() {
        const viewport = document.getElementById('cv-preview-viewport');
        if (viewport) {
            const w = viewport.clientWidth - 48;
            // A4 width is 210mm ~ 794px
            const a4WidthPx = 794;
            this.zoomLevel = Math.min(1.0, Math.max(0.45, w / a4WidthPx));
        } else {
            this.zoomLevel = 0.9;
        }
        this.applyZoom();
    },

    applyZoom() {
        const wrapper = document.getElementById('cv-preview-iframe-wrapper');
        const zoomText = document.getElementById('zoom-level-text');
        if (wrapper) {
            wrapper.style.transform = `scale(${this.zoomLevel})`;
        }
        if (zoomText) {
            zoomText.textContent = `${Math.round(this.zoomLevel * 100)}%`;
        }
    },

    handleWindowResize() {
        if (window.innerWidth >= 1024) {
            const formCol = document.getElementById('cv-editor-form-column');
            const prevCol = document.getElementById('cv-editor-preview-column');
            if (formCol) formCol.classList.remove('hidden-on-mobile');
            if (prevCol) prevCol.classList.remove('active-on-mobile');
        }
    },

    switchMobileTab(tab) {
        const formCol = document.getElementById('cv-editor-form-column');
        const prevCol = document.getElementById('cv-editor-preview-column');
        const btnForm = document.getElementById('tab-btn-form');
        const btnPrev = document.getElementById('tab-btn-preview');

        if (tab === 'form') {
            if (formCol) formCol.classList.remove('hidden-on-mobile');
            if (prevCol) prevCol.classList.remove('active-on-mobile');
            if (btnForm) btnForm.classList.add('active');
            if (btnPrev) btnPrev.classList.remove('active');
        } else {
            if (formCol) formCol.classList.add('hidden-on-mobile');
            if (prevCol) prevCol.classList.add('active-on-mobile');
            if (btnForm) btnForm.classList.remove('active');
            if (btnPrev) btnPrev.classList.add('active');
            this.resetZoom();
            this.refreshPreviewIframe();
        }
    },

    /* --- Header Action Handlers --- */
    async downloadPdf() {
        const title = this.cvData ? this.cvData.title : 'CV';
        showToast('Đang xuất PDF chuẩn A4 từ máy chủ...', 'info');
        const token = TokenStorage.getToken();
        try {
            const res = await fetch(`/student/cvs/${this.cvId}/export.pdf`, {
                headers: token ? { 'Authorization': `Bearer ${token}` } : {}
            });
            if (!res.ok) {
                showToast('Không thể tạo PDF. Vui lòng lưu CV trước khi tải.', 'error');
                return;
            }
            const blob = await res.blob();
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `${title}.pdf`;
            document.body.appendChild(a);
            a.click();
            a.remove();
            window.URL.revokeObjectURL(url);
            showToast('Tải PDF thành công!', 'success');
        } catch(e) {
            showToast('Lỗi khi tải file PDF.', 'error');
        }
    },

    async activateForApplication() {
        if (this.isDirty) {
            await this.executeAutosave();
        }
        showToast('Đang tạo snapshot PDF vào hồ sơ ứng tuyển...', 'info');
        const res = await apiRequest(`/student/cvs/${this.cvId}/activate`, {
            method: 'POST',
            body: { expected_version: this.currentVersion }
        });
        if (res && res.success) {
            this.currentVersion = res.data.cv ? res.data.cv.version : this.currentVersion;
            showToast('Thành công! CV này đã trở thành hồ sơ chính dùng khi ứng tuyển.', 'success');
        } else {
            showToast((res && res.message) ? res.message : 'Không thể kích hoạt CV.', 'error');
        }
    },

    toggleShareModal() {
        const modal = document.getElementById('modal-editor-share');
        const check = document.getElementById('check-is-public');
        const group = document.getElementById('share-link-group');
        const input = document.getElementById('share-public-url');

        if (!modal) return;
        const isPublic = this.cvData.is_public === 1 || this.cvData.is_public === true;
        if (check) check.checked = isPublic;

        if (group && input) {
            if (isPublic && this.cvData.public_slug) {
                group.style.display = 'block';
                input.value = `${window.location.origin}/cv/${this.cvData.public_slug}`;
            } else {
                group.style.display = 'none';
            }
        }
        modal.style.display = 'flex';
    },

    closeShareModal() {
        const modal = document.getElementById('modal-editor-share');
        if (modal) modal.style.display = 'none';
    },

    async handleTogglePublic(isChecked) {
        if (isChecked) {
            const confirmed = confirm('Lưu ý: Khi bật chia sẻ công khai, họ tên và thông tin liên hệ của bạn sẽ xuất hiện trên Internet cho bất kỳ ai có đường dẫn. Bạn có muốn tiếp tục?');
            if (!confirmed) {
                const check = document.getElementById('check-is-public');
                if (check) check.checked = false;
                return;
            }
        }

        const res = await apiRequest(`/student/cvs/${this.cvId}/visibility`, {
            method: 'PATCH',
            body: { is_public: isChecked, expected_version: this.currentVersion }
        });

        if (res && res.success && res.data) {
            this.cvData.is_public = res.data.is_public;
            this.cvData.public_slug = res.data.public_slug;
            this.currentVersion = res.data.version;

            const group = document.getElementById('share-link-group');
            const input = document.getElementById('share-public-url');
            if (group && input) {
                if (isChecked && this.cvData.public_slug) {
                    group.style.display = 'block';
                    input.value = `${window.location.origin}/cv/${this.cvData.public_slug}`;
                } else {
                    group.style.display = 'none';
                }
            }
            showToast(isChecked ? 'Đã bật chia sẻ công khai!' : 'Đã tắt chia sẻ công khai.', 'success');
        } else {
            const check = document.getElementById('check-is-public');
            if (check) check.checked = !isChecked;
            showToast((res && res.message) ? res.message : 'Không thể cập nhật trạng thái chia sẻ.', 'error');
        }
    },

    copyShareLink() {
        const input = document.getElementById('share-public-url');
        if (!input || !input.value) return;

        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(input.value).then(() => {
                showToast('Đã sao chép liên kết vào bộ nhớ tạm!', 'success');
            }).catch(() => {
                input.select();
                document.execCommand('copy');
                showToast('Đã sao chép liên kết!', 'success');
            });
        } else {
            input.select();
            document.execCommand('copy');
            showToast('Đã sao chép liên kết!', 'success');
        }
    }
};
