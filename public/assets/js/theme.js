/**
 * JobMarketplace Theme Engine (Vanilla JS)
 * Supports 3 Modes: 'system' (Hệ thống), 'dark' (Tối), 'light' (Sáng)
 * Instant apply, zero flicker, auto system preference detection
 */

const ThemeEngine = (function () {
    const STORAGE_KEY = "jobmarket_theme";

    const ICONS = {
        light: `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>`,
        dark: `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>`,
        system: `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>`
    };

    const LABELS = {
        light: "Sáng",
        dark: "Tối",
        system: "Hệ thống"
    };

    function getSetting() {
        return localStorage.getItem(STORAGE_KEY) || "system";
    }

    function isSystemDark() {
        return window.matchMedia && window.matchMedia("(prefers-color-scheme: dark)").matches;
    }

    function isEffectiveDark(setting) {
        if (setting === "dark") return true;
        if (setting === "light") return false;
        return isSystemDark();
    }

    function apply(setting, save = true) {
        if (!["light", "dark", "system"].includes(setting)) {
            setting = "system";
        }

        if (save) {
            localStorage.setItem(STORAGE_KEY, setting);
        }

        const effectiveDark = isEffectiveDark(setting);
        document.documentElement.setAttribute("data-theme", effectiveDark ? "dark" : "light");
        document.documentElement.setAttribute("data-theme-setting", setting);

        updateAllSwitchers(setting);

        window.dispatchEvent(new CustomEvent("themeChanged", {
            detail: { setting, isDark: effectiveDark }
        }));
    }

    function updateAllSwitchers(setting) {
        document.querySelectorAll(".theme-switcher-dropdown").forEach(dropdown => {
            const iconSlot = dropdown.querySelector(".theme-icon-slot");
            const labelSlot = dropdown.querySelector(".theme-btn-label");

            if (iconSlot) {
                iconSlot.innerHTML = ICONS[setting] || ICONS.system;
            }
            if (labelSlot) {
                labelSlot.innerText = LABELS[setting] || LABELS.system;
            }

            dropdown.querySelectorAll(".theme-menu-item").forEach(item => {
                const val = item.getAttribute("data-theme-val");
                if (val === setting) {
                    item.classList.add("active");
                } else {
                    item.classList.remove("active");
                }
            });
        });
    }

    function createSwitcherHTML(idPrefix = "hdr") {
        const currentSetting = getSetting();
        return `
            <div class="theme-switcher-dropdown" id="${idPrefix}-theme-dropdown">
                <button type="button" class="theme-switcher-btn" aria-label="Đổi giao diện: Sáng, Tối hoặc Hệ thống" title="Đổi giao diện">
                    <span class="theme-icon-slot">${ICONS[currentSetting] || ICONS.system}</span>
                    <span class="theme-btn-label">${LABELS[currentSetting] || LABELS.system}</span>
                    <svg class="theme-chevron" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                </button>
                <div class="theme-menu">
                    <button type="button" class="theme-menu-item ${currentSetting === 'light' ? 'active' : ''}" data-theme-val="light">
                        ${ICONS.light}
                        <span>Sáng</span>
                        <span class="theme-check-icon">✓</span>
                    </button>
                    <button type="button" class="theme-menu-item ${currentSetting === 'dark' ? 'active' : ''}" data-theme-val="dark">
                        ${ICONS.dark}
                        <span>Tối</span>
                        <span class="theme-check-icon">✓</span>
                    </button>
                    <button type="button" class="theme-menu-item ${currentSetting === 'system' ? 'active' : ''}" data-theme-val="system">
                        ${ICONS.system}
                        <span>Hệ thống</span>
                        <span class="theme-check-icon">✓</span>
                    </button>
                </div>
            </div>
        `;
    }

    function setupEventListeners() {
        // Dropdown toggle click
        document.addEventListener("click", (e) => {
            const btn = e.target.closest(".theme-switcher-btn");
            if (btn) {
                const dropdown = btn.closest(".theme-switcher-dropdown");
                if (dropdown) {
                    const isOpen = dropdown.classList.contains("open");
                    // Close any other open menus
                    document.querySelectorAll(".theme-switcher-dropdown.open").forEach(d => d.classList.remove("open"));
                    if (!isOpen) {
                        dropdown.classList.add("open");
                    }
                }
                return;
            }

            // Menu item click
            const menuItem = e.target.closest(".theme-menu-item");
            if (menuItem) {
                const val = menuItem.getAttribute("data-theme-val");
                if (val) {
                    apply(val, true);
                }
                const dropdown = menuItem.closest(".theme-switcher-dropdown");
                if (dropdown) {
                    dropdown.classList.remove("open");
                }
                return;
            }

            // Click outside closes all
            if (!e.target.closest(".theme-switcher-dropdown")) {
                document.querySelectorAll(".theme-switcher-dropdown.open").forEach(d => d.classList.remove("open"));
            }
        });

        // Close on Escape key
        document.addEventListener("keydown", (e) => {
            if (e.key === "Escape") {
                document.querySelectorAll(".theme-switcher-dropdown.open").forEach(d => d.classList.remove("open"));
            }
        });

        // Listen to OS dark mode changes when set to 'system'
        if (window.matchMedia) {
            window.matchMedia("(prefers-color-scheme: dark)").addEventListener("change", () => {
                if (getSetting() === "system") {
                    apply("system", false);
                }
            });
        }
    }

    // Auto initialize on script execution and DOM ready
    function init() {
        apply(getSetting(), false);
        if (document.readyState === "loading") {
            document.addEventListener("DOMContentLoaded", setupEventListeners);
        } else {
            setupEventListeners();
        }
    }

    init();

    return {
        getSetting,
        apply,
        createSwitcherHTML,
        updateAllSwitchers
    };
})();
