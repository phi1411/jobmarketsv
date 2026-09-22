/**
 * Custom Select Enhancement (JobMarketSV UI/UX)
 * Tự động chuyển đổi các thẻ <select> thành custom dropdown:
 * - Khung hiển thị bo góc mượt mà (border-radius: 12px)
 * - Hiệu ứng hover cho từng mục lựa chọn (hover background, subtle slide)
 * - Tích hợp biểu tượng Remix Icon checkmark khi active
 * - Hỗ trợ tìm kiếm & gõ nhập tay linh hoạt (Searchable & Type-to-Enter)
 * - Đồng bộ 100% hai chiều với thẻ <select> gốc (change events, validation, form reset)
 */

(function () {
    'use strict';

    function removeAccents(str) {
        if (!str) return '';
        return str.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
    }

    function escapeHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function initCustomSelect(selectEl) {
        if (!selectEl || selectEl.dataset.customSelectInitialized === 'true') {
            return;
        }

        // Bỏ qua nếu có cờ data-no-custom hoặc là select multiple
        if (selectEl.hasAttribute('data-no-custom') || selectEl.multiple) {
            return;
        }

        selectEl.dataset.customSelectInitialized = 'true';

        // 1. Tạo Wrapper
        const wrapper = document.createElement('div');
        wrapper.className = 'custom-select-wrapper';

        // Nếu select có style width: auto hoặc hiển thị inline
        const computedStyle = window.getComputedStyle(selectEl);
        if (selectEl.style.width === 'auto' || computedStyle.display === 'inline-block') {
            wrapper.classList.add('custom-select-wrapper--inline');
        }
        if (selectEl.disabled) {
            wrapper.classList.add('disabled');
        }

        // 2. Tạo Trigger button
        const trigger = document.createElement('button');
        trigger.type = 'button';
        trigger.className = 'custom-select-trigger';
        trigger.setAttribute('aria-haspopup', 'listbox');
        trigger.setAttribute('aria-expanded', 'false');
        if (selectEl.disabled) {
            trigger.disabled = true;
        }

        const label = document.createElement('span');
        label.className = 'custom-select-label';

        const chevron = document.createElement('span');
        chevron.className = 'custom-select-chevron';
        chevron.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m6 9 6 6 6-6"/></svg>';

        trigger.appendChild(label);
        trigger.appendChild(chevron);

        // 3. Tạo Menu Popup
        const menu = document.createElement('div');
        menu.className = 'custom-select-menu';
        menu.setAttribute('role', 'listbox');

        // Search container & list container
        let searchBox = null;
        let searchInput = null;
        let searchClear = null;
        let listContainer = document.createElement('div');
        listContainer.className = 'custom-select-options-list';

        function checkSearchable(optionCount) {
            return selectEl.dataset.searchable === 'true' ||
                   selectEl.dataset.allowCustom === 'true' ||
                   optionCount >= 6;
        }

        function setupSearchBox() {
            if (searchBox) return;

            searchBox = document.createElement('div');
            searchBox.className = 'custom-select-search-box';

            const searchIcon = document.createElement('i');
            searchIcon.className = 'ri-search-line custom-select-search-icon';

            searchInput = document.createElement('input');
            searchInput.type = 'text';
            searchInput.className = 'custom-select-search-input';
            searchInput.placeholder = selectEl.dataset.placeholderSearch || 'Tìm kiếm hoặc gõ để nhập...';
            searchInput.autocomplete = 'off';

            searchClear = document.createElement('button');
            searchClear.type = 'button';
            searchClear.className = 'custom-select-search-clear';
            searchClear.innerHTML = '<i class="ri-close-line"></i>';
            searchClear.style.display = 'none';

            searchBox.appendChild(searchIcon);
            searchBox.appendChild(searchInput);
            searchBox.appendChild(searchClear);

            searchBox.addEventListener('click', (e) => e.stopPropagation());

            searchInput.addEventListener('input', () => {
                const q = searchInput.value.trim();
                searchClear.style.display = q ? 'inline-flex' : 'none';
                filterOptions(q);
            });

            searchClear.addEventListener('click', (e) => {
                e.stopPropagation();
                searchInput.value = '';
                searchClear.style.display = 'none';
                filterOptions('');
                searchInput.focus();
            });

            searchInput.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    e.stopPropagation();
                    const q = searchInput.value.trim();
                    const visibleOptions = Array.from(listContainer.querySelectorAll('.custom-select-option:not(.custom-select-option--custom)')).filter(el => el.style.display !== 'none');
                    if (visibleOptions.length === 1) {
                        visibleOptions[0].click();
                    } else if (q && (selectEl.dataset.allowCustom === 'true' || selectEl.dataset.searchable === 'true')) {
                        applyCustomValue(q);
                    } else if (visibleOptions.length > 0) {
                        visibleOptions[0].click();
                    }
                } else if (e.key === 'Escape') {
                    closeMenu();
                }
            });

            menu.insertBefore(searchBox, listContainer);
        }

        function filterOptions(query) {
            const normQ = removeAccents(query);
            let matchCount = 0;
            let exactMatch = false;

            listContainer.querySelectorAll('.custom-select-option:not(.custom-select-option--custom)').forEach(item => {
                const itemText = item.dataset.label || item.textContent;
                const normText = removeAccents(itemText);
                const isMatch = !normQ || normText.includes(normQ);
                item.style.display = isMatch ? 'flex' : 'none';
                if (isMatch) matchCount++;
                if (normText === normQ) exactMatch = true;
            });

            // Handle custom value option
            let customItem = listContainer.querySelector('.custom-select-option--custom');
            const allowCustom = selectEl.dataset.allowCustom === 'true' || selectEl.dataset.searchable === 'true';

            if (allowCustom && query && !exactMatch) {
                if (!customItem) {
                    customItem = document.createElement('div');
                    customItem.className = 'custom-select-option custom-select-option--custom';
                    listContainer.appendChild(customItem);
                }
                customItem.style.display = 'flex';
                customItem.innerHTML = `<span style="display:inline-flex;align-items:center;gap:0.35rem;color:var(--primary,#059669);font-weight:600;"><i class="ri-add-circle-line" style="font-size:1.05rem;"></i> Sử dụng: "<strong>${escapeHtml(query)}</strong>"</span>`;
                customItem.onclick = (e) => {
                    e.stopPropagation();
                    applyCustomValue(query);
                };
            } else if (customItem) {
                customItem.style.display = 'none';
            }

            // No results message
            let noResults = listContainer.querySelector('.custom-select-no-results');
            if (matchCount === 0 && (!allowCustom || !query)) {
                if (!noResults) {
                    noResults = document.createElement('div');
                    noResults.className = 'custom-select-no-results';
                    noResults.textContent = 'Không tìm thấy ngành nghề phù hợp';
                    listContainer.appendChild(noResults);
                }
                noResults.style.display = 'block';
            } else if (noResults) {
                noResults.style.display = 'none';
            }
        }

        function applyCustomValue(val) {
            let existingOpt = Array.from(selectEl.options).find(o => o.value === val || o.textContent.trim() === val.trim());
            if (!existingOpt) {
                existingOpt = document.createElement('option');
                existingOpt.value = val;
                existingOpt.textContent = val;
                selectEl.appendChild(existingOpt);
            }
            existingOpt.selected = true;
            selectEl.value = existingOpt.value;

            label.textContent = val;
            selectEl.dispatchEvent(new Event('change', { bubbles: true }));
            selectEl.dispatchEvent(new Event('input', { bubbles: true }));
            closeMenu();
        }

        // Hàm render lại các options
        function renderOptions() {
            listContainer.innerHTML = '';
            const options = Array.from(selectEl.options);
            const selectedOpt = selectEl.options[selectEl.selectedIndex] || options[0];

            label.textContent = selectedOpt ? selectedOpt.textContent : '';

            // Setup search if needed
            if (checkSearchable(options.length)) {
                setupSearchBox();
            }

            if (!menu.contains(listContainer)) {
                menu.appendChild(listContainer);
            }

            options.forEach((opt, index) => {
                const item = document.createElement('div');
                item.className = 'custom-select-option';
                item.setAttribute('role', 'option');
                item.dataset.value = opt.value;
                item.dataset.label = opt.textContent;

                if (opt.selected) {
                    item.classList.add('active');
                    item.setAttribute('aria-selected', 'true');
                }

                const itemText = document.createElement('span');
                itemText.textContent = opt.textContent;

                const checkIcon = document.createElement('i');
                checkIcon.className = 'ri-check-line custom-select-check';

                item.appendChild(itemText);
                item.appendChild(checkIcon);

                item.addEventListener('click', (e) => {
                    e.stopPropagation();
                    if (opt.disabled) return;

                    selectEl.selectedIndex = index;
                    selectEl.value = opt.value;

                    // Phát sự kiện change và input để kích hoạt listener của view
                    selectEl.dispatchEvent(new Event('change', { bubbles: true }));
                    selectEl.dispatchEvent(new Event('input', { bubbles: true }));

                    closeMenu();
                });

                listContainer.appendChild(item);
            });
        }

        renderOptions();

        // 4. Sự kiện đóng / mở Menu
        function openMenu() {
            if (selectEl.disabled) return;
            // Đóng tất cả dropdown khác trước
            document.querySelectorAll('.custom-select-wrapper.open').forEach(w => {
                if (w !== wrapper) {
                    w.classList.remove('open');
                    const trig = w.querySelector('.custom-select-trigger');
                    if (trig) trig.setAttribute('aria-expanded', 'false');
                }
            });

            wrapper.classList.add('open');
            trigger.setAttribute('aria-expanded', 'true');

            if (searchInput) {
                searchInput.value = '';
                if (searchClear) searchClear.style.display = 'none';
                filterOptions('');
                setTimeout(() => {
                    searchInput.focus();
                }, 50);
            }

            // Tự cuộn tới option đang active
            const activeItem = listContainer.querySelector('.custom-select-option.active');
            if (activeItem) {
                activeItem.scrollIntoView({ block: 'nearest' });
            }
        }

        function closeMenu() {
            wrapper.classList.remove('open');
            trigger.setAttribute('aria-expanded', 'false');
        }

        trigger.addEventListener('click', (e) => {
            e.stopPropagation();
            if (wrapper.classList.contains('open')) {
                closeMenu();
            } else {
                openMenu();
            }
        });

        // 5. Đồng bộ khi giá trị select thay đổi từ code JS bên ngoài
        selectEl.addEventListener('change', () => {
            const selectedOpt = selectEl.options[selectEl.selectedIndex];
            label.textContent = selectedOpt ? selectedOpt.textContent : '';

            listContainer.querySelectorAll('.custom-select-option:not(.custom-select-option--custom)').forEach(optEl => {
                const isSelected = optEl.dataset.value === selectEl.value;
                optEl.classList.toggle('active', isSelected);
                optEl.setAttribute('aria-selected', isSelected ? 'true' : 'false');
            });
        });

        // 6. Theo dõi thay đổi options động (vd: syncCategoriesList hoặc đổ dữ liệu API)
        const observer = new MutationObserver(() => {
            renderOptions();
            if (selectEl.disabled) {
                wrapper.classList.add('disabled');
                trigger.disabled = true;
            } else {
                wrapper.classList.remove('disabled');
                trigger.disabled = false;
            }
        });

        observer.observe(selectEl, {
            childList: true,
            attributes: true,
            attributeFilter: ['disabled']
        });

        // 7. Đồng bộ khi form cha bị reset
        if (selectEl.form) {
            selectEl.form.addEventListener('reset', () => {
                setTimeout(() => {
                    const selectedOpt = selectEl.options[selectEl.selectedIndex];
                    label.textContent = selectedOpt ? selectedOpt.textContent : '';
                    listContainer.querySelectorAll('.custom-select-option:not(.custom-select-option--custom)').forEach(optEl => {
                        const isSelected = optEl.dataset.value === selectEl.value;
                        optEl.classList.toggle('active', isSelected);
                    });
                }, 10);
            });
        }

        // 8. Ẩn select gốc một cách an toàn và chèn wrapper
        selectEl.classList.add('custom-select-native-hidden');
        selectEl.parentNode.insertBefore(wrapper, selectEl);
        wrapper.appendChild(selectEl);
        wrapper.appendChild(trigger);
        wrapper.appendChild(menu);
    }

    // Tự động khởi tạo cho tất cả <select> hiện có
    function scanAndInit() {
        document.querySelectorAll('select').forEach(initCustomSelect);
    }

    // Làm mới trạng thái hiển thị của custom dropdown khi value của select bị thay đổi bằng code
    function refreshCustomSelect(selectEl) {
        if (!selectEl) return;
        const wrapper = selectEl.closest('.custom-select-wrapper');
        if (!wrapper) {
            initCustomSelect(selectEl);
            return;
        }
        const selectedOpt = selectEl.options[selectEl.selectedIndex];
        const label = wrapper.querySelector('.custom-select-label');
        if (label) label.textContent = selectedOpt ? selectedOpt.textContent : '';
        const listContainer = wrapper.querySelector('.custom-select-options-list');
        if (listContainer) {
            listContainer.querySelectorAll('.custom-select-option:not(.custom-select-option--custom)').forEach(optEl => {
                const isSelected = optEl.dataset.value === selectEl.value;
                optEl.classList.toggle('active', isSelected);
                optEl.setAttribute('aria-selected', isSelected ? 'true' : 'false');
            });
        }
    }

    // Đóng dropdown khi click bên ngoài
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.custom-select-wrapper')) {
            document.querySelectorAll('.custom-select-wrapper.open').forEach(wrapper => {
                wrapper.classList.remove('open');
                const trig = wrapper.querySelector('.custom-select-trigger');
                if (trig) trig.setAttribute('aria-expanded', 'false');
            });
        }
    });

    // Hỗ trợ phím Escape
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            document.querySelectorAll('.custom-select-wrapper.open').forEach(wrapper => {
                wrapper.classList.remove('open');
                const trig = wrapper.querySelector('.custom-select-trigger');
                if (trig) trig.setAttribute('aria-expanded', 'false');
            });
        }
    });

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', scanAndInit);
    } else {
        scanAndInit();
    }

    window.initCustomSelect = initCustomSelect;
    window.scanAndInitCustomSelect = scanAndInit;
    window.refreshCustomSelect = refreshCustomSelect;
})();

