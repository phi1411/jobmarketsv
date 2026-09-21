/**
 * Custom Select Enhancement (JobMarketSV UI/UX)
 * Tự động chuyển đổi các thẻ <select> thành custom dropdown:
 * - Khung hiển thị bo góc mượt mà (border-radius: 12px)
 * - Hiệu ứng hover cho từng mục lựa chọn (hover background, subtle slide)
 * - Tích hợp biểu tượng Remix Icon checkmark khi active
 * - Đồng bộ 100% hai chiều với thẻ <select> gốc (change events, validation, form reset)
 */

(function () {
    'use strict';

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

        // Hàm render lại các options
        function renderOptions() {
            menu.innerHTML = '';
            const options = Array.from(selectEl.options);
            const selectedOpt = selectEl.options[selectEl.selectedIndex] || options[0];

            label.textContent = selectedOpt ? selectedOpt.textContent : '';

            options.forEach((opt, index) => {
                const item = document.createElement('div');
                item.className = 'custom-select-option';
                item.setAttribute('role', 'option');
                item.dataset.value = opt.value;

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

                menu.appendChild(item);
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

            // Tự cuộn tới option đang active
            const activeItem = menu.querySelector('.custom-select-option.active');
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

            menu.querySelectorAll('.custom-select-option').forEach(optEl => {
                const isSelected = optEl.dataset.value === selectEl.value;
                optEl.classList.toggle('active', isSelected);
                optEl.setAttribute('aria-selected', isSelected ? 'true' : 'false');
            });
        });

        // 6. Theo dõi thay đổi options động (vd: VietnamAddressPicker đổ dữ liệu quận/huyện)
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
                    menu.querySelectorAll('.custom-select-option').forEach(optEl => {
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

    // Theo dõi các phần tử <select> được chèn động vào DOM sau này (vd: modal xuất hiện)
    const domObserver = new MutationObserver((mutations) => {
        for (const mutation of mutations) {
            if (mutation.addedNodes.length > 0) {
                mutation.addedNodes.forEach(node => {
                    if (node.nodeType === 1) { // ELEMENT_NODE
                        if (node.tagName === 'SELECT') {
                            initCustomSelect(node);
                        } else {
                            node.querySelectorAll && node.querySelectorAll('select').forEach(initCustomSelect);
                        }
                    }
                });
            }
        }
    });

    domObserver.observe(document.body || document.documentElement, {
        childList: true,
        subtree: true
    });

    // Xuất ra window để gọi thủ công nếu cần
    window.CustomSelect = {
        init: initCustomSelect,
        scan: scanAndInit
    };
})();
