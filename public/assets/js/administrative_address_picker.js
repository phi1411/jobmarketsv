/**
 * Structured nationwide Vietnam address selects.
 * Current schema: Province/City -> Ward/Commune (34 provinces/cities).
 * Legacy schema: Province/City -> District -> Ward/Commune (63 provinces/cities).
 */
(function (window, document) {
    "use strict";

    const cache = new Map();
    const pending = new Map();
    const groups = new Map();

    function normalize(value) {
        return String(value || "")
            .normalize("NFD")
            .replace(/[\u0300-\u036f]/g, "")
            .replace(/đ/g, "d")
            .replace(/Đ/g, "D")
            .toLowerCase()
            .replace(/^(tinh|thanh pho|tp\.?|quan|huyen|thi xa|phuong|xa|thi tran)\s+/i, "")
            .replace(/[^a-z0-9]+/g, " ")
            .trim();
    }

    async function loadSchema(schema) {
        if (cache.has(schema)) return cache.get(schema);
        if (pending.has(schema)) return pending.get(schema);

        const request = (async () => {
            const response = await window.apiRequest(`/locations/administrative?schema=${encodeURIComponent(schema)}`);
            if (!response || !response.success || !response.data || !Array.isArray(response.data.provinces)) {
                throw new Error((response && response.message) || "Không tải được danh sách đơn vị hành chính.");
            }
            cache.set(schema, response.data);
            return response.data;
        })().finally(() => pending.delete(schema));

        pending.set(schema, request);
        return request;
    }

    function emptySelect(select, placeholder, disabled = false) {
        if (!select) return;
        select.innerHTML = "";
        const option = document.createElement("option");
        option.value = "";
        option.textContent = placeholder;
        select.appendChild(option);
        select.disabled = disabled;
    }

    function fillSelect(select, units, placeholder) {
        if (!select) return;
        emptySelect(select, placeholder, false);
        const fragment = document.createDocumentFragment();
        (units || []).forEach((unit) => {
            if (!unit || !unit.name) return;
            const option = document.createElement("option");
            option.value = unit.name;
            option.textContent = unit.name;
            option.dataset.code = String(unit.code || "");
            fragment.appendChild(option);
        });
        select.appendChild(fragment);
    }

    function findUnit(units, name) {
        const wanted = normalize(name);
        if (!wanted) return null;
        return (units || []).find((unit) => normalize(unit.name) === wanted)
            || (units || []).find((unit) => normalize(unit.name).includes(wanted) || wanted.includes(normalize(unit.name)))
            || null;
    }

    class AddressGroup {
        constructor(name) {
            this.name = name;
            this.province = document.querySelector(`[data-vn-address-group="${name}"][data-vn-address-level="province"]`);
            this.district = document.querySelector(`[data-vn-address-group="${name}"][data-vn-address-level="district"]`);
            this.commune = document.querySelector(`[data-vn-address-group="${name}"][data-vn-address-level="commune"]`);
            this.schema = (this.province && this.province.dataset.vnAddressSchema) || "current";
            this.data = null;
            this.bound = false;
        }

        async initialize() {
            if (!this.province || !this.commune) return this;
            if (this.data) return this;
            if (!this.bound) {
                this.bound = true;
                this.province.addEventListener("change", () => this.onProvinceChange());
                if (this.district) this.district.addEventListener("change", () => this.onDistrictChange());
                emptySelect(this.province, "Đang tải Tỉnh/Thành phố...", true);
                emptySelect(this.commune, "Chọn Tỉnh/Thành phố trước", true);
                if (this.district) emptySelect(this.district, "Chọn Tỉnh/Thành phố trước", true);
            }

            try {
                this.data = await loadSchema(this.schema);
                fillSelect(this.province, this.data.provinces, "-- Chọn Tỉnh/Thành phố --");
            } catch (error) {
                emptySelect(this.province, "Không tải được danh sách", true);
                if (typeof window.showToast === "function") {
                    window.showToast(error.message || "Không tải được danh sách địa chỉ.", "error");
                }
            }
            return this;
        }

        selectedProvince() {
            return findUnit(this.data ? this.data.provinces : [], this.province ? this.province.value : "");
        }

        selectedDistrict() {
            const province = this.selectedProvince();
            return findUnit(province ? province.districts : [], this.district ? this.district.value : "");
        }

        onProvinceChange() {
            const province = this.selectedProvince();
            if (this.schema === "legacy") {
                fillSelect(this.district, province ? province.districts : [], province ? "-- Chọn Quận/Huyện --" : "Chọn Tỉnh/Thành phố trước");
                if (this.district) this.district.disabled = !province;
                emptySelect(this.commune, "Chọn Quận/Huyện trước", true);
            } else {
                fillSelect(this.commune, province ? province.communes : [], province ? "-- Chọn Phường/Xã --" : "Chọn Tỉnh/Thành phố trước");
                this.commune.disabled = !province;
            }
        }

        onDistrictChange() {
            const district = this.selectedDistrict();
            fillSelect(this.commune, district ? district.wards : [], district ? "-- Chọn Phường/Xã --" : "Chọn Quận/Huyện trước");
            this.commune.disabled = !district;
        }

        async setValues(values = {}) {
            await this.initialize();
            const province = findUnit(this.data ? this.data.provinces : [], values.province);
            this.province.value = province ? province.name : "";
            this.onProvinceChange();

            if (this.schema === "legacy" && this.district) {
                const district = findUnit(province ? province.districts : [], values.district);
                this.district.value = district ? district.name : "";
                this.onDistrictChange();
                const commune = findUnit(district ? district.wards : [], values.commune);
                this.commune.value = commune ? commune.name : "";
            } else {
                const commune = findUnit(province ? province.communes : [], values.commune);
                this.commune.value = commune ? commune.name : "";
            }
        }

        reset() {
            if (this.province) this.province.value = "";
            if (this.district) emptySelect(this.district, "Chọn Tỉnh/Thành phố trước", true);
            if (this.commune) emptySelect(this.commune, this.schema === "legacy" ? "Chọn Quận/Huyện trước" : "Chọn Tỉnh/Thành phố trước", true);
        }

        codes() {
            const code = (select) => select && select.selectedOptions[0] ? (select.selectedOptions[0].dataset.code || null) : null;
            return {
                province_code: code(this.province),
                district_code: code(this.district),
                commune_code: code(this.commune),
            };
        }
    }

    function getGroup(name) {
        if (!groups.has(name)) groups.set(name, new AddressGroup(name));
        return groups.get(name);
    }

    window.VietnamAddressPicker = {
        initGroup(name) {
            return getGroup(name).initialize();
        },
        setValues(name, values) {
            return getGroup(name).setValues(values);
        },
        reset(name) {
            return getGroup(name).reset();
        },
        getCodes(name) {
            return getGroup(name).codes();
        },
        clearCache() {
            cache.clear();
        }
    };

    document.addEventListener("DOMContentLoaded", () => {
        const names = new Set();
        document.querySelectorAll("[data-vn-address-autoload][data-vn-address-group]").forEach((element) => {
            names.add(element.dataset.vnAddressGroup);
        });
        names.forEach((name) => window.VietnamAddressPicker.initGroup(name));
    });
})(window, document);
