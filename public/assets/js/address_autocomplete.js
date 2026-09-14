/**
 * AddressAutocomplete - Component gợi ý và chuẩn hóa địa chỉ dùng chung cho JobMarketSV
 * Kết nối qua backend proxy /map/places/autocomplete (không gọi Goong trực tiếp từ browser)
 * Tuân thủ chuẩn Accessibility ARIA combobox/listbox, bàn phím và xử lý rate-limit 429.
 */

class AddressAutocomplete {
    /**
     * @param {HTMLElement|string} container - Phần tử DOM chứa component
     * @param {Object} options
     * @param {string} [options.id] - ID cho input
     * @param {string} [options.name] - Name cho input
     * @param {string} [options.label] - Label hiển thị
     * @param {string} [options.placeholder] - Placeholder cho ô nhập
     * @param {string} [options.initialValue] - Giá trị ban đầu
     * @param {boolean} [options.required] - Có bắt buộc không
     * @param {Function} [options.onSelect] - Callback khi chọn kết quả: (place) => {}
     * @param {Function} [options.onClear] - Callback khi xóa hoặc sửa text làm mất place_id: () => {}
     * @param {Function} [options.onChange] - Callback khi text thay đổi: (val, isSelected) => {}
     * @param {Object} [options.biasLocation] - { latitude, longitude } để ưu tiên gợi ý gần
     */
    constructor(container, options = {}) {
        this.container = typeof container === "string" ? document.querySelector(container) : container;
        if (!this.container) {
            throw new Error("AddressAutocomplete: Container không hợp lệ.");
        }

        this.options = Object.assign({
            id: "ac-" + Math.random().toString(36).substring(2, 9),
            name: "address",
            label: "",
            placeholder: "Nhập địa chỉ, tên đường, phường/xã...",
            initialValue: "",
            required: false,
            onSelect: null,
            onClear: null,
            onChange: null,
            biasLocation: null
        }, options);

        this.sessionToken = this.generateUuid();
        this.selectedPlace = null;
        this.results = [];
        this.activeIndex = -1;
        this.abortController = null;
        this.debounceTimeout = null;
        this.isLockedRateLimit = false;

        this.init();
    }

    generateUuid() {
        if (typeof crypto !== "undefined" && typeof crypto.randomUUID === "function") {
            return crypto.randomUUID();
        }
        return "xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx".replace(/[xy]/g, function(c) {
            const r = (Math.random() * 16) | 0;
            const v = c === "x" ? r : (r & 0x3) | 0x8;
            return v.toString(16);
        });
    }

    init() {
        const inputId = this.options.id;
        const listboxId = `${inputId}-listbox`;

        let labelHtml = "";
        if (this.options.label) {
            labelHtml = `<label class="form-label" for="${inputId}">${escapeHtml(this.options.label)}${this.options.required ? ' <span style="color:var(--danger)">*</span>' : ''}</label>`;
        }

        this.container.innerHTML = `
            ${labelHtml}
            <div class="autocomplete-wrapper">
                <div class="autocomplete-input-group" role="combobox" aria-expanded="false" aria-haspopup="listbox" aria-owns="${listboxId}">
                    <span class="autocomplete-icon-left">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                            <circle cx="12" cy="10" r="3"></circle>
                        </svg>
                    </span>
                    <input type="text"
                           id="${inputId}"
                           name="${escapeHtml(this.options.name)}"
                           class="autocomplete-input"
                           placeholder="${escapeHtml(this.options.placeholder)}"
                           value="${escapeHtml(this.options.initialValue || "")}"
                           autocomplete="off"
                           aria-autocomplete="list"
                           aria-controls="${listboxId}"
                           ${this.options.required ? "required" : ""}>
                    <div id="${inputId}-spinner" class="autocomplete-spinner" style="display:none;"></div>
                    <button type="button"
                            id="${inputId}-clear"
                            class="autocomplete-clear-btn"
                            title="Xóa địa chỉ"
                            style="display:${this.options.initialValue ? "flex" : "none"};"
                            aria-label="Xóa địa chỉ">
                        &times;
                    </button>
                </div>
                <ul id="${listboxId}"
                    class="autocomplete-dropdown"
                    role="listbox"
                    aria-label="Danh sách gợi ý địa chỉ"
                    style="display:none;">
                </ul>
            </div>
        `;

        this.inputEl = this.container.querySelector(`#${inputId}`);
        this.listboxEl = this.container.querySelector(`#${listboxId}`);
        this.spinnerEl = this.container.querySelector(`#${inputId}-spinner`);
        this.clearBtn = this.container.querySelector(`#${inputId}-clear`);
        this.comboboxEl = this.container.querySelector(`[role="combobox"]`);

        this.bindEvents();
    }

    bindEvents() {
        this.inputEl.addEventListener("focus", () => {
            // Khởi tạo một session_token mới cho phiên chọn địa chỉ này nếu chưa có
            if (!this.sessionToken) {
                this.sessionToken = this.generateUuid();
            }
            if (this.results.length > 0 && this.inputEl.value.trim().length >= 2) {
                this.openDropdown();
            }
        });

        this.inputEl.addEventListener("input", (e) => {
            const query = e.target.value;

            // Nếu người dùng sửa text sau khi đã chọn 1 place, xóa place đã chọn
            if (this.selectedPlace) {
                this.selectedPlace = null;
                if (typeof this.options.onClear === "function") {
                    this.options.onClear();
                }
            }

            if (typeof this.options.onChange === "function") {
                this.options.onChange(query, false);
            }

            if (query.trim().length > 0) {
                this.clearBtn.style.display = "flex";
            } else {
                this.clearBtn.style.display = "none";
                this.closeDropdown();
                return;
            }

            if (query.trim().length < 2) {
                this.closeDropdown();
                return;
            }

            this.debounceSearch(query.trim());
        });

        this.inputEl.addEventListener("keydown", (e) => this.handleKeyDown(e));

        this.clearBtn.addEventListener("click", () => {
            this.clear();
            this.inputEl.focus();
        });

        // Click ngoài để đóng dropdown
        document.addEventListener("click", (e) => {
            if (!this.container.contains(e.target)) {
                this.closeDropdown();
            }
        });
    }

    debounceSearch(query) {
        clearTimeout(this.debounceTimeout);
        this.debounceTimeout = setTimeout(() => {
            this.fetchSuggestions(query);
        }, 350); // Debounce 350ms
    }

    async fetchSuggestions(query) {
        if (this.isLockedRateLimit) return;

        // Hủy request cũ nếu có
        if (this.abortController) {
            this.abortController.abort();
        }
        this.abortController = new AbortController();

        this.showLoading(true);

        const params = new URLSearchParams({
            input: query,
            limit: "8",
            radius_km: "50",
            session_token: this.sessionToken
        });

        if (this.options.biasLocation && this.options.biasLocation.latitude && this.options.biasLocation.longitude) {
            params.append("latitude", this.options.biasLocation.latitude);
            params.append("longitude", this.options.biasLocation.longitude);
        }

        try {
            const res = await apiRequest(`/map/places/autocomplete?${params.toString()}`, {
                signal: this.abortController.signal
            });

            this.showLoading(false);

            if (res && res.success && Array.isArray(res.data)) {
                this.results = res.data;
                this.renderDropdown(this.results);
            } else if (res && res.status === 429) {
                const retrySeconds = Number(res.errors && res.errors.retry_after_seconds)
                    || Number(res.retry_after_seconds)
                    || 5;
                this.handleRateLimit(retrySeconds);
            } else {
                this.results = [];
                this.renderEmpty("Không tìm thấy địa điểm phù hợp.");
            }
        } catch (err) {
            if (err.name === "AbortError") return;
            this.showLoading(false);
            console.error("Lỗi khi tìm kiếm địa chỉ:", err);
            this.renderEmpty("Lỗi mạng khi tải gợi ý. Vui lòng thử lại.");
        }
    }

    handleRateLimit(retrySeconds) {
        this.isLockedRateLimit = true;
        this.inputEl.disabled = true;
        this.listboxEl.innerHTML = `
            <li class="autocomplete-rate-limit" role="alert">
                ⏳ Bạn thao tác quá nhanh. Vui lòng chờ <span id="ac-retry-sec">${retrySeconds}</span>s...
            </li>
        `;
        this.openDropdown();

        let remaining = retrySeconds;
        const interval = setInterval(() => {
            remaining--;
            const secEl = this.listboxEl.querySelector("#ac-retry-sec");
            if (secEl) secEl.innerText = remaining;
            if (remaining <= 0) {
                clearInterval(interval);
                this.isLockedRateLimit = false;
                this.inputEl.disabled = false;
                this.closeDropdown();
                this.inputEl.focus();
            }
        }, 1000);
    }

    renderDropdown(items) {
        this.activeIndex = -1;
        if (items.length === 0) {
            this.renderEmpty("Không tìm thấy địa điểm nào.");
            return;
        }

        this.listboxEl.innerHTML = items.map((item, index) => {
            const commune = item.commune || "";
            const province = item.province || "";
            const legacyDistrict = item.district_text_legacy || "";

            let subHtml = "";
            if (commune || province) {
                subHtml = `
                    <div class="autocomplete-item-sub">
                        <span>📍 ${escapeHtml([commune, province].filter(Boolean).join(", "))}</span>
                        ${legacyDistrict ? `<span class="autocomplete-item-chip">${escapeHtml(legacyDistrict)}</span>` : ''}
                    </div>
                `;
            }

            return `
                <li id="${this.options.id}-opt-${index}"
                    class="autocomplete-item"
                    role="option"
                    aria-selected="false"
                    data-index="${index}">
                    <div class="autocomplete-item-desc">${escapeHtml(item.description)}</div>
                    ${subHtml}
                </li>
            `;
        }).join("");

        // Gắn sự kiện click cho từng gợi ý
        this.listboxEl.querySelectorAll(".autocomplete-item").forEach(el => {
            el.addEventListener("click", () => {
                const idx = parseInt(el.getAttribute("data-index"), 10);
                this.selectItem(idx);
            });
        });

        this.openDropdown();
    }

    renderEmpty(message) {
        this.activeIndex = -1;
        this.listboxEl.innerHTML = `<li class="autocomplete-empty">${escapeHtml(message)}</li>`;
        this.openDropdown();
    }

    openDropdown() {
        this.listboxEl.style.display = "block";
        this.comboboxEl.setAttribute("aria-expanded", "true");
    }

    closeDropdown() {
        this.listboxEl.style.display = "none";
        this.comboboxEl.setAttribute("aria-expanded", "false");
        this.activeIndex = -1;
    }

    showLoading(isLoading) {
        this.spinnerEl.style.display = isLoading ? "block" : "none";
        if (isLoading) {
            this.clearBtn.style.display = "none";
        } else if (this.inputEl.value.trim().length > 0) {
            this.clearBtn.style.display = "flex";
        }
    }

    handleKeyDown(e) {
        if (this.listboxEl.style.display === "none" && e.key !== "ArrowDown") return;

        const items = this.listboxEl.querySelectorAll(".autocomplete-item");
        if (items.length === 0) return;

        switch (e.key) {
            case "ArrowDown":
                e.preventDefault();
                if (this.listboxEl.style.display === "none") {
                    this.openDropdown();
                    return;
                }
                this.activeIndex = (this.activeIndex + 1) % items.length;
                this.updateActiveItem(items);
                break;

            case "ArrowUp":
                e.preventDefault();
                this.activeIndex = (this.activeIndex - 1 + items.length) % items.length;
                this.updateActiveItem(items);
                break;

            case "Enter":
                if (this.activeIndex >= 0 && this.activeIndex < items.length) {
                    e.preventDefault();
                    this.selectItem(this.activeIndex);
                }
                break;

            case "Escape":
                e.preventDefault();
                this.closeDropdown();
                break;
        }
    }

    updateActiveItem(items) {
        items.forEach((item, index) => {
            const isActive = index === this.activeIndex;
            item.classList.toggle("active", isActive);
            item.setAttribute("aria-selected", isActive ? "true" : "false");
            if (isActive) {
                item.scrollIntoView({ block: "nearest" });
                this.inputEl.setAttribute("aria-activedescendant", item.id);
            }
        });
    }

    selectItem(index) {
        const item = this.results[index];
        if (!item) return;

        this.selectedPlace = {
            place_id: item.place_id,
            description: item.description,
            session_token: this.sessionToken,
            commune: item.commune || null,
            province: item.province || null,
            district_text_legacy: item.district_text_legacy || null
        };

        this.inputEl.value = item.description;
        this.clearBtn.style.display = "flex";
        this.closeDropdown();

        if (typeof this.options.onSelect === "function") {
            this.options.onSelect(this.selectedPlace);
        }
        if (typeof this.options.onChange === "function") {
            this.options.onChange(item.description, true);
        }

        // Tạo token mới cho lần tìm kiếm sau
        this.sessionToken = this.generateUuid();
    }

    /**
     * Lấy dữ liệu place đang chọn
     * @returns {Object|null}
     */
    getSelected() {
        return this.selectedPlace;
    }

    /**
     * Lấy text hiện tại
     * @returns {string}
     */
    getValue() {
        return this.inputEl.value.trim();
    }

    /**
     * Gán giá trị thủ công
     */
    setValue(text, selectedData = null) {
        this.inputEl.value = text || "";
        this.selectedPlace = selectedData;
        this.clearBtn.style.display = text ? "flex" : "none";
    }

    /**
     * Xóa toàn bộ nội dung
     */
    clear() {
        this.inputEl.value = "";
        this.selectedPlace = null;
        this.results = [];
        this.clearBtn.style.display = "none";
        this.closeDropdown();
        this.sessionToken = this.generateUuid();

        if (typeof this.options.onClear === "function") {
            this.options.onClear();
        }
        if (typeof this.options.onChange === "function") {
            this.options.onChange("", false);
        }
    }

    setBiasLocation(lat, lng) {
        this.options.biasLocation = { latitude: lat, longitude: lng };
    }
}

// Gắn vào window để sử dụng toàn cục
window.AddressAutocomplete = AddressAutocomplete;
