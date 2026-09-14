/**
 * LargeLocationPicker - Bộ chọn địa điểm lớn 2 cột phong cách TopCV
 * JobMarketSV Frontend Component
 * 
 * Hỗ trợ:
 * - Multi-select (tối đa 20 khu vực, chọn nhanh cả tỉnh, tag chips)
 * - Single-select (chọn 1 khu vực cho nhà tuyển dụng đăng tin)
 * - Tìm kiếm thời gian thực tỉnh thành và quận huyện
 * - Tối ưu hoá mobile: chế độ stepped view linh hoạt
 * - Cache dữ liệu phân cấp từ GET /locations/hierarchy
 */

(function (window, document) {
    'use strict';

    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function removeAccents(str) {
        if (!str) return '';
        return str.normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/đ/g, 'd')
            .replace(/Đ/g, 'D')
            .toLowerCase()
            .trim();
    }

    class LargeLocationPicker {
        static hierarchyCache = null;
        static fetchPromise = null;
        static locationMap = new Map();
        static provinceMap = new Map();

        /**
         * Lấy danh sách phân cấp từ GET /locations/hierarchy (chỉ fetch 1 lần duy nhất)
         */
        static async fetchHierarchy() {
            if (LargeLocationPicker.hierarchyCache) {
                return LargeLocationPicker.hierarchyCache;
            }
            if (LargeLocationPicker.fetchPromise) {
                return LargeLocationPicker.fetchPromise;
            }

            LargeLocationPicker.fetchPromise = (async () => {
                try {
                    let res;
                    if (typeof window.apiRequest === 'function') {
                        res = await window.apiRequest('/locations/hierarchy');
                    } else {
                        const response = await fetch('/api/locations/hierarchy');
                        res = await response.json();
                    }

                    if (res && res.success && Array.isArray(res.data)) {
                        LargeLocationPicker.hierarchyCache = res.data;
                        LargeLocationPicker.locationMap.clear();
                        LargeLocationPicker.provinceMap.clear();

                        res.data.forEach(p => {
                            const pName = p.province_name;
                            LargeLocationPicker.provinceMap.set(pName, p);
                            if (Array.isArray(p.areas)) {
                                p.areas.forEach(a => {
                                    LargeLocationPicker.locationMap.set(String(a.id), {
                                        id: String(a.id),
                                        name: a.name,
                                        area_name: a.area_name,
                                        province_name: pName
                                    });
                                });
                            }
                        });

                        return LargeLocationPicker.hierarchyCache;
                    }
                    console.error('Failed to load locations hierarchy:', res);
                    return [];
                } catch (err) {
                    console.error('Error fetching locations hierarchy:', err);
                    return [];
                } finally {
                    LargeLocationPicker.fetchPromise = null;
                }
            })();

            return LargeLocationPicker.fetchPromise;
        }

        constructor(options = {}) {
            this.options = Object.assign({
                mode: 'multi', // 'multi' | 'single'
                maxSelect: 20,
                title: options.mode === 'single' ? 'Chọn khu vực làm việc' : 'Chọn địa điểm làm việc',
                trigger: null, // DOM selector or element
                labelElement: null, // DOM selector or element for text
                badgeElement: null, // DOM selector or element for count badge
                hiddenInput: null, // DOM selector or element for value
                initialSelected: [],
                onApply: null // Callback: (selectedIds, selectedItems, displayText) => {}
            }, options);

            this.selectedIds = new Set();
            this.draftSelectedIds = new Set();
            this.activeProvince = null;
            this.provinceSearchKeyword = '';
            this.districtSearchKeyword = '';
            this.mobileView = 'provinces'; // 'provinces' | 'districts' on mobile screens

            if (this.options.initialSelected) {
                this.setSelected(this.options.initialSelected, false);
            }

            this._initTrigger();
            this._buildModal();
        }

        _initTrigger() {
            if (!this.options.trigger) return;
            const triggerEl = typeof this.options.trigger === 'string'
                ? document.querySelector(this.options.trigger)
                : this.options.trigger;
            if (triggerEl) {
                this.triggerEl = triggerEl;
                triggerEl.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.open();
                });
            }
        }

        _buildModal() {
            this.modalId = 'llp-modal-' + Math.random().toString(36).substr(2, 9);

            const backdrop = document.createElement('div');
            backdrop.className = 'llp-modal-backdrop';
            backdrop.id = this.modalId;
            backdrop.setAttribute('role', 'dialog');
            backdrop.setAttribute('aria-modal', 'true');
            backdrop.setAttribute('aria-label', this.options.title);
            backdrop.style.display = 'none';

            backdrop.innerHTML = `
                <div class="llp-modal-dialog">
                    <!-- Header -->
                    <div class="llp-modal-header">
                        <div class="llp-header-title">
                            <svg class="llp-header-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                <circle cx="12" cy="10" r="3"></circle>
                            </svg>
                            <h3 class="llp-modal-title-text">${escapeHtml(this.options.title)}</h3>
                        </div>
                        <button type="button" class="llp-btn-close" aria-label="Đóng">&times;</button>
                    </div>

                    <!-- Selected Chips Bar (Multi mode only) -->
                    <div class="llp-selected-bar" style="${this.options.mode === 'single' ? 'display:none;' : ''}">
                        <div class="llp-selected-bar-header">
                            <span class="llp-selected-bar-title">Đã chọn:</span>
                            <span class="llp-selected-count-badge">0/${this.options.maxSelect}</span>
                        </div>
                        <div class="llp-chips-container">
                            <span class="llp-chips-empty">Chưa chọn địa điểm nào (tối đa ${this.options.maxSelect} khu vực)</span>
                        </div>
                    </div>

                    <!-- Single Mode Display Bar -->
                    <div class="llp-single-selected-bar" style="${this.options.mode === 'single' ? 'display:flex;' : 'display:none;'}">
                        <span class="llp-single-label">Khu vực đã chọn:</span>
                        <span class="llp-single-value">Chưa chọn khu vực</span>
                    </div>

                    <!-- Body (2 columns) -->
                    <div class="llp-modal-body">
                        <!-- Left Column: Provinces -->
                        <div class="llp-col-provinces">
                            <div class="llp-col-header">
                                <div class="llp-search-wrapper">
                                    <svg class="llp-search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="11" cy="11" r="8"></circle>
                                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                                    </svg>
                                    <input type="text" class="llp-search-input llp-search-province" placeholder="Tìm tỉnh, thành phố...">
                                    <button type="button" class="llp-search-clear" style="display:none;" title="Xóa tìm kiếm">&times;</button>
                                </div>
                            </div>
                            <div class="llp-province-list" role="listbox" aria-label="Danh sách Tỉnh/Thành phố">
                                <div class="llp-loading-skeleton">Đang tải danh mục địa điểm...</div>
                            </div>
                        </div>

                        <!-- Right Column: Districts -->
                        <div class="llp-col-districts">
                            <div class="llp-col-header">
                                <div class="llp-districts-top-bar">
                                    <button type="button" class="llp-btn-mobile-back" title="Quay lại danh sách tỉnh">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <polyline points="15 18 9 12 15 6"></polyline>
                                        </svg>
                                        <span>Chọn tỉnh thành</span>
                                    </button>
                                    <div class="llp-district-active-title">Tất cả khu vực</div>
                                </div>

                                <div class="llp-search-wrapper" style="margin-top:0.5rem;">
                                    <svg class="llp-search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="11" cy="11" r="8"></circle>
                                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                                    </svg>
                                    <input type="text" class="llp-search-input llp-search-district" placeholder="Tìm quận, huyện...">
                                    <button type="button" class="llp-search-clear" style="display:none;" title="Xóa tìm kiếm">&times;</button>
                                </div>

                                <!-- Multi mode: Select all province toggle -->
                                <div class="llp-check-all-province-wrapper" style="${this.options.mode === 'single' ? 'display:none;' : 'display:flex;'}">
                                    <label class="llp-checkbox-label">
                                        <input type="checkbox" class="llp-check-all-province-input">
                                        <span class="llp-check-all-province-text">Tất cả quận/huyện trong tỉnh này</span>
                                    </label>
                                </div>
                            </div>

                            <div class="llp-district-list" role="listbox" aria-label="Danh sách Quận/Huyện">
                                <div class="llp-loading-skeleton">Đang tải danh mục khu vực...</div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="llp-modal-footer">
                        <div class="llp-footer-left">
                            <button type="button" class="llp-btn-clear-all" style="${this.options.mode === 'single' ? 'display:none;' : ''}">
                                Bỏ chọn tất cả
                            </button>
                        </div>
                        <div class="llp-footer-right">
                            <button type="button" class="btn btn-outline llp-btn-cancel">Hủy</button>
                            <button type="button" class="btn btn-primary llp-btn-apply">Áp dụng</button>
                        </div>
                    </div>
                </div>
            `;

            document.body.appendChild(backdrop);
            this.modalEl = backdrop;

            this._bindModalEvents();
        }

        _bindModalEvents() {
            const m = this.modalEl;

            // Close on backdrop click
            m.addEventListener('click', (e) => {
                if (e.target === m) this.close();
            });

            // Close on close button or cancel
            m.querySelector('.llp-btn-close').addEventListener('click', () => this.close());
            m.querySelector('.llp-btn-cancel').addEventListener('click', () => this.close());

            // Apply button
            m.querySelector('.llp-btn-apply').addEventListener('click', () => this.apply());

            // Clear all button
            const clearAllBtn = m.querySelector('.llp-btn-clear-all');
            if (clearAllBtn) {
                clearAllBtn.addEventListener('click', () => {
                    this.draftSelectedIds.clear();
                    this._renderAll();
                });
            }

            // Mobile back button
            const mobileBackBtn = m.querySelector('.llp-btn-mobile-back');
            if (mobileBackBtn) {
                mobileBackBtn.addEventListener('click', () => {
                    this.mobileView = 'provinces';
                    this._updateMobileView();
                });
            }

            // Province search input
            const provInput = m.querySelector('.llp-search-province');
            const provClear = provInput.nextElementSibling;
            provInput.addEventListener('input', (e) => {
                this.provinceSearchKeyword = e.target.value.trim();
                provClear.style.display = this.provinceSearchKeyword ? 'block' : 'none';
                this._renderProvincesList();
            });
            provClear.addEventListener('click', () => {
                provInput.value = '';
                this.provinceSearchKeyword = '';
                provClear.style.display = 'none';
                this._renderProvincesList();
            });

            // District search input
            const distInput = m.querySelector('.llp-search-district');
            const distClear = distInput.nextElementSibling;
            distInput.addEventListener('input', (e) => {
                this.districtSearchKeyword = e.target.value.trim();
                distClear.style.display = this.districtSearchKeyword ? 'block' : 'none';
                this._renderDistrictsList();
            });
            distClear.addEventListener('click', () => {
                distInput.value = '';
                this.districtSearchKeyword = '';
                distClear.style.display = 'none';
                this._renderDistrictsList();
            });

            // Select all in province checkbox
            const checkAllProv = m.querySelector('.llp-check-all-province-input');
            if (checkAllProv) {
                checkAllProv.addEventListener('change', (e) => {
                    if (!this.activeProvince) return;
                    const pData = LargeLocationPicker.provinceMap.get(this.activeProvince);
                    if (!pData || !Array.isArray(pData.areas)) return;

                    const provinceIds = pData.areas.map(a => String(a.id));
                    if (e.target.checked) {
                        // Check if adding exceeds maxSelect
                        const newTotal = new Set([...this.draftSelectedIds, ...provinceIds]).size;
                        if (newTotal > this.options.maxSelect) {
                            alert(`Bạn chỉ có thể chọn tối đa ${this.options.maxSelect} khu vực.`);
                            e.target.checked = false;
                            return;
                        }
                        provinceIds.forEach(id => this.draftSelectedIds.add(id));
                    } else {
                        provinceIds.forEach(id => this.draftSelectedIds.delete(id));
                    }

                    this._renderAll();
                });
            }

            // Keyboard Escape
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && this.modalEl.style.display === 'flex') {
                    this.close();
                }
            });
        }

        async open(initialIds = null) {
            if (initialIds !== null) {
                this.draftSelectedIds = new Set(Array.isArray(initialIds) ? initialIds.map(String) : [String(initialIds)]);
            } else {
                this.draftSelectedIds = new Set(this.selectedIds);
            }

            this.mobileView = 'provinces';
            this.modalEl.style.display = 'flex';
            document.body.style.overflow = 'hidden';

            // Ensure hierarchy is loaded
            await LargeLocationPicker.fetchHierarchy();

            // Determine active province
            if (!this.activeProvince) {
                if (this.draftSelectedIds.size > 0) {
                    const firstId = Array.from(this.draftSelectedIds)[0];
                    const item = LargeLocationPicker.locationMap.get(firstId);
                    if (item && item.province_name) {
                        this.activeProvince = item.province_name;
                    }
                }
                if (!this.activeProvince && LargeLocationPicker.hierarchyCache && LargeLocationPicker.hierarchyCache.length > 0) {
                    this.activeProvince = LargeLocationPicker.hierarchyCache[0].province_name;
                }
            }

            this._renderAll();
            this._updateMobileView();

            // Autofocus search on desktop
            if (window.innerWidth > 768) {
                const searchEl = this.modalEl.querySelector('.llp-search-province');
                if (searchEl) searchEl.focus();
            }
        }

        close() {
            this.modalEl.style.display = 'none';
            document.body.style.overflow = '';
            this.provinceSearchKeyword = '';
            this.districtSearchKeyword = '';
            const pInput = this.modalEl.querySelector('.llp-search-province');
            const dInput = this.modalEl.querySelector('.llp-search-district');
            if (pInput) pInput.value = '';
            if (dInput) dInput.value = '';
        }

        apply() {
            this.selectedIds = new Set(this.draftSelectedIds);
            const selectedArray = Array.from(this.selectedIds);
            const selectedItems = this.getSelectedItems();
            const displayText = this.getDisplayText();

            this._updateTriggerUI();

            if (typeof this.options.onApply === 'function') {
                this.options.onApply(selectedArray, selectedItems, displayText);
            }

            this.close();
        }

        setSelected(ids, updateUI = true) {
            let list = [];
            if (Array.isArray(ids)) {
                list = ids;
            } else if (typeof ids === 'string' && ids.trim()) {
                list = ids.split(',').map(s => s.trim()).filter(Boolean);
            }
            this.selectedIds = new Set(list.map(String));
            this.draftSelectedIds = new Set(this.selectedIds);

            if (updateUI) {
                this._updateTriggerUI();
            }
        }

        getSelected() {
            return Array.from(this.selectedIds);
        }

        getSelectedItems() {
            const items = [];
            this.selectedIds.forEach(id => {
                const item = LargeLocationPicker.locationMap.get(id);
                if (item) {
                    items.push(item);
                } else {
                    items.push({ id, name: id, area_name: id, province_name: '' });
                }
            });
            return items;
        }

        getDisplayText() {
            const count = this.selectedIds.size;
            if (count === 0) {
                return this.options.mode === 'single' ? '-- Chọn khu vực làm việc --' : 'Tất cả địa điểm';
            }

            const items = this.getSelectedItems();
            if (this.options.mode === 'single') {
                const first = items[0];
                if (first) {
                    return first.province_name ? `${first.area_name}, ${first.province_name}` : first.name;
                }
                return 'Đã chọn 1 khu vực';
            }

            // Multi-mode text summary
            if (count === 1) {
                const first = items[0];
                return first.province_name ? `${first.area_name}, ${first.province_name}` : first.name;
            }

            // Check if all selected belong to a single province
            const provinces = new Set(items.map(i => i.province_name).filter(Boolean));
            if (provinces.size === 1) {
                const provName = Array.from(provinces)[0];
                return `${provName} (${count} khu vực)`;
            }

            return `${count} khu vực đã chọn`;
        }

        clear(triggerApply = false) {
            this.selectedIds.clear();
            this.draftSelectedIds.clear();
            this._updateTriggerUI();

            if (triggerApply && typeof this.options.onApply === 'function') {
                this.options.onApply([], [], this.getDisplayText());
            }
        }

        selectProvince(provinceName, triggerApply = true) {
            if (!LargeLocationPicker.provinceMap.has(provinceName)) {
                // Try searching case-insensitively
                const norm = removeAccents(provinceName);
                for (const [key, val] of LargeLocationPicker.provinceMap.entries()) {
                    if (removeAccents(key) === norm) {
                        provinceName = key;
                        break;
                    }
                }
            }

            const pData = LargeLocationPicker.provinceMap.get(provinceName);
            if (pData && Array.isArray(pData.areas)) {
                this.selectedIds.clear();
                pData.areas.forEach(a => this.selectedIds.add(String(a.id)));
                this.draftSelectedIds = new Set(this.selectedIds);
                this.activeProvince = provinceName;
                this._updateTriggerUI();

                if (triggerApply && typeof this.options.onApply === 'function') {
                    this.options.onApply(Array.from(this.selectedIds), this.getSelectedItems(), this.getDisplayText());
                }
            }
        }

        _updateTriggerUI() {
            const count = this.selectedIds.size;
            const text = this.getDisplayText();

            // Update Label
            let labelEl = null;
            if (this.options.labelElement) {
                labelEl = typeof this.options.labelElement === 'string'
                    ? document.querySelector(this.options.labelElement)
                    : this.options.labelElement;
            } else if (this.triggerEl) {
                labelEl = this.triggerEl.querySelector('.location-picker-label') || this.triggerEl.querySelector('span');
            }
            if (labelEl) {
                labelEl.textContent = text;
            }

            // Update Badge
            let badgeEl = null;
            if (this.options.badgeElement) {
                badgeEl = typeof this.options.badgeElement === 'string'
                    ? document.querySelector(this.options.badgeElement)
                    : this.options.badgeElement;
            } else if (this.triggerEl) {
                badgeEl = this.triggerEl.querySelector('.location-picker-badge');
            }
            if (badgeEl) {
                if (count > 0 && this.options.mode === 'multi') {
                    badgeEl.textContent = count;
                    badgeEl.style.display = 'inline-block';
                } else {
                    badgeEl.style.display = 'none';
                }
            }

            // Update Hidden Input
            let hiddenEl = null;
            if (this.options.hiddenInput) {
                hiddenEl = typeof this.options.hiddenInput === 'string'
                    ? document.querySelector(this.options.hiddenInput)
                    : this.options.hiddenInput;
            }
            if (hiddenEl) {
                hiddenEl.value = Array.from(this.selectedIds).join(',');
                // Dispatch change event
                hiddenEl.dispatchEvent(new Event('change', { bubbles: true }));
            }
        }

        _renderAll() {
            this._renderChips();
            this._renderProvincesList();
            this._renderDistrictsList();
        }

        _renderChips() {
            const container = this.modalEl.querySelector('.llp-chips-container');
            const countBadge = this.modalEl.querySelector('.llp-selected-count-badge');
            const singleValEl = this.modalEl.querySelector('.llp-single-value');

            const count = this.draftSelectedIds.size;
            if (countBadge) {
                countBadge.textContent = `${count}/${this.options.maxSelect}`;
            }

            // In Single Mode
            if (this.options.mode === 'single') {
                if (singleValEl) {
                    if (count === 0) {
                        singleValEl.textContent = 'Chưa chọn khu vực';
                        singleValEl.classList.remove('has-value');
                    } else {
                        const firstId = Array.from(this.draftSelectedIds)[0];
                        const item = LargeLocationPicker.locationMap.get(firstId);
                        singleValEl.textContent = item ? `${item.area_name} (${item.province_name})` : firstId;
                        singleValEl.classList.add('has-value');
                    }
                }
                return;
            }

            // Multi Mode
            if (!container) return;

            if (count === 0) {
                container.innerHTML = `<span class="llp-chips-empty">Chưa chọn địa điểm nào (tối đa ${this.options.maxSelect} khu vực)</span>`;
                return;
            }

            let html = '';
            this.draftSelectedIds.forEach(id => {
                const item = LargeLocationPicker.locationMap.get(id);
                const title = item ? `${item.area_name} (${item.province_name})` : id;
                html += `
                    <span class="llp-chip" data-id="${escapeHtml(id)}">
                        <span class="llp-chip-text" title="${escapeHtml(title)}">${escapeHtml(title)}</span>
                        <button type="button" class="llp-chip-remove" data-id="${escapeHtml(id)}" aria-label="Xóa ${escapeHtml(title)}">&times;</button>
                    </span>
                `;
            });

            container.innerHTML = html;

            container.querySelectorAll('.llp-chip-remove').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const id = btn.getAttribute('data-id');
                    this.draftSelectedIds.delete(id);
                    this._renderAll();
                });
            });
        }

        _renderProvincesList() {
            const listEl = this.modalEl.querySelector('.llp-province-list');
            if (!listEl) return;

            const hierarchy = LargeLocationPicker.hierarchyCache;
            if (!hierarchy || hierarchy.length === 0) {
                listEl.innerHTML = '<div class="llp-empty-notice">Đang tải dữ liệu tỉnh thành...</div>';
                return;
            }

            const searchKeyNorm = removeAccents(this.provinceSearchKeyword);

            const filtered = hierarchy.filter(p => {
                if (!searchKeyNorm) return true;
                return removeAccents(p.province_name).includes(searchKeyNorm);
            });

            if (filtered.length === 0) {
                listEl.innerHTML = '<div class="llp-empty-notice">Không tìm thấy tỉnh, thành phố phù hợp.</div>';
                return;
            }

            let html = '';
            filtered.forEach(p => {
                const isActive = p.province_name === this.activeProvince;
                const pData = LargeLocationPicker.provinceMap.get(p.province_name);
                let selectedInProvCount = 0;
                if (pData && Array.isArray(pData.areas)) {
                    pData.areas.forEach(a => {
                        if (this.draftSelectedIds.has(String(a.id))) selectedInProvCount++;
                    });
                }

                html += `
                    <div class="llp-province-item ${isActive ? 'active' : ''}" data-province="${escapeHtml(p.province_name)}" role="option" aria-selected="${isActive}">
                        <div class="llp-province-name-wrap">
                            <span class="llp-province-name">${escapeHtml(p.province_name)}</span>
                            ${selectedInProvCount > 0 ? `<span class="llp-province-count-badge">${selectedInProvCount}</span>` : ''}
                        </div>
                        <svg class="llp-province-arrow" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="9 18 15 12 9 6"></polyline>
                        </svg>
                    </div>
                `;
            });

            listEl.innerHTML = html;

            listEl.querySelectorAll('.llp-province-item').forEach(item => {
                item.addEventListener('click', () => {
                    const pName = item.getAttribute('data-province');
                    this.activeProvince = pName;
                    this.mobileView = 'districts';
                    this._renderProvincesList();
                    this._renderDistrictsList();
                    this._updateMobileView();
                });
            });
        }

        _renderDistrictsList() {
            const listEl = this.modalEl.querySelector('.llp-district-list');
            const titleEl = this.modalEl.querySelector('.llp-district-active-title');
            const checkAllInput = this.modalEl.querySelector('.llp-check-all-province-input');
            const checkAllText = this.modalEl.querySelector('.llp-check-all-province-text');

            if (!listEl) return;

            if (!this.activeProvince) {
                listEl.innerHTML = '<div class="llp-empty-notice">Vui lòng chọn một tỉnh/thành phố bên trái.</div>';
                if (titleEl) titleEl.textContent = 'Khu vực';
                return;
            }

            if (titleEl) {
                titleEl.textContent = `Khu vực tại ${this.activeProvince}`;
            }
            if (checkAllText) {
                checkAllText.textContent = `Tất cả quận/huyện tại ${this.activeProvince}`;
            }

            const pData = LargeLocationPicker.provinceMap.get(this.activeProvince);
            if (!pData || !Array.isArray(pData.areas) || pData.areas.length === 0) {
                listEl.innerHTML = '<div class="llp-empty-notice">Chưa có dữ liệu quận/huyện cho tỉnh này.</div>';
                if (checkAllInput) {
                    checkAllInput.checked = false;
                    checkAllInput.indeterminate = false;
                    checkAllInput.disabled = true;
                }
                return;
            }

            const searchKeyNorm = removeAccents(this.districtSearchKeyword);
            const areas = pData.areas.filter(a => {
                if (!searchKeyNorm) return true;
                return removeAccents(a.area_name).includes(searchKeyNorm) || removeAccents(a.name).includes(searchKeyNorm);
            });

            // Update check-all status
            if (checkAllInput && this.options.mode === 'multi') {
                checkAllInput.disabled = false;
                const allAreaIds = pData.areas.map(a => String(a.id));
                const selectedInProvCount = allAreaIds.filter(id => this.draftSelectedIds.has(id)).length;
                if (selectedInProvCount === allAreaIds.length && allAreaIds.length > 0) {
                    checkAllInput.checked = true;
                    checkAllInput.indeterminate = false;
                } else if (selectedInProvCount > 0) {
                    checkAllInput.checked = false;
                    checkAllInput.indeterminate = true;
                } else {
                    checkAllInput.checked = false;
                    checkAllInput.indeterminate = false;
                }
            }

            if (areas.length === 0) {
                listEl.innerHTML = '<div class="llp-empty-notice">Không tìm thấy quận/huyện nào phù hợp.</div>';
                return;
            }

            let html = '';
            areas.forEach(area => {
                const areaId = String(area.id);
                const isChecked = this.draftSelectedIds.has(areaId);

                if (this.options.mode === 'single') {
                    html += `
                        <div class="llp-district-item llp-district-item-single ${isChecked ? 'selected' : ''}" data-id="${escapeHtml(areaId)}" role="option" aria-selected="${isChecked}">
                            <div class="llp-radio-circle ${isChecked ? 'checked' : ''}"></div>
                            <span class="llp-district-name">${escapeHtml(area.area_name)}</span>
                        </div>
                    `;
                } else {
                    html += `
                        <label class="llp-district-item ${isChecked ? 'selected' : ''}" data-id="${escapeHtml(areaId)}">
                            <input type="checkbox" class="llp-district-checkbox" value="${escapeHtml(areaId)}" ${isChecked ? 'checked' : ''}>
                            <span class="llp-district-name">${escapeHtml(area.area_name)}</span>
                        </label>
                    `;
                }
            });

            listEl.innerHTML = html;

            // Bind District Click Handlers
            if (this.options.mode === 'single') {
                listEl.querySelectorAll('.llp-district-item-single').forEach(item => {
                    item.addEventListener('click', () => {
                        const id = item.getAttribute('data-id');
                        this.draftSelectedIds.clear();
                        this.draftSelectedIds.add(id);
                        this._renderAll();
                    });
                });
            } else {
                listEl.querySelectorAll('.llp-district-checkbox').forEach(cb => {
                    cb.addEventListener('change', (e) => {
                        const id = cb.value;
                        if (e.target.checked) {
                            if (this.draftSelectedIds.size >= this.options.maxSelect) {
                                alert(`Bạn chỉ có thể chọn tối đa ${this.options.maxSelect} khu vực.`);
                                e.target.checked = false;
                                return;
                            }
                            this.draftSelectedIds.add(id);
                        } else {
                            this.draftSelectedIds.delete(id);
                        }
                        this._renderAll();
                    });
                });
            }
        }

        _updateMobileView() {
            if (window.innerWidth > 768) {
                this.modalEl.querySelector('.llp-col-provinces').style.display = '';
                this.modalEl.querySelector('.llp-col-districts').style.display = '';
                return;
            }

            const colProvinces = this.modalEl.querySelector('.llp-col-provinces');
            const colDistricts = this.modalEl.querySelector('.llp-col-districts');

            if (this.mobileView === 'provinces') {
                colProvinces.style.display = 'flex';
                colDistricts.style.display = 'none';
            } else {
                colProvinces.style.display = 'none';
                colDistricts.style.display = 'flex';
            }
        }
    }

    // Expose to window
    window.LargeLocationPicker = LargeLocationPicker;

})(window, document);
