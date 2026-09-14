<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? "Job Marketplace - Việc Làm Part-Time Sinh Viên") ?></title>
    <meta name="description" content="Nền tảng kết nối việc làm bán thời gian, linh hoạt theo ca cho sinh viên tại Việt Nam.">
    <script>
    (function() {
        try {
            var t = localStorage.getItem("jobmarket_theme") || "system";
            var isDark = t === "dark" || (t === "system" && window.matchMedia && window.matchMedia("(prefers-color-scheme: dark)").matches);
            document.documentElement.setAttribute("data-theme", isDark ? "dark" : "light");
            document.documentElement.setAttribute("data-theme-setting", t);
        } catch(e) {}
    })();
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css?v=<?= defined('BASE_PATH') && file_exists(BASE_PATH . '/public/assets/css/style.css') ? filemtime(BASE_PATH . '/public/assets/css/style.css') : time() ?>">
    <link rel="stylesheet" href="/assets/css/theme.css?v=<?= defined('BASE_PATH') && file_exists(BASE_PATH . '/public/assets/css/theme.css') ? filemtime(BASE_PATH . '/public/assets/css/theme.css') : time() ?>">
    <?php
    $isGlobalChatEnabled = class_exists(\JobMarket\Facades\Config::class) && \JobMarket\Facades\Config::isGeminiEnabled();
    $isCompanyChatEnabled = class_exists(\JobMarket\Facades\Config::class) && \JobMarket\Facades\Config::isGeminiCompanyEnabled();
    $isChatbotPage = $isGlobalChatEnabled && (
        in_array($currentPage ?? '', ['home', 'jobs', 'job_detail'], true)
        || str_starts_with($currentPage ?? '', 'student_')
        || (str_starts_with($currentPage ?? '', 'company_') && $isCompanyChatEnabled)
    );
    $isGoongMapPage = ($currentPage ?? '') === 'job_detail' && !empty($goongMaptilesKey);
    ?>
    <?php if ($isChatbotPage): ?>
    <link rel="stylesheet" href="/assets/css/assistant.css?v=<?= defined('BASE_PATH') && file_exists(BASE_PATH . '/public/assets/css/assistant.css') ? filemtime(BASE_PATH . '/public/assets/css/assistant.css') : time() ?>">
    <?php endif; ?>
    <link rel="stylesheet" href="/assets/css/support_comm.css?v=<?= defined('BASE_PATH') && file_exists(BASE_PATH . '/public/assets/css/support_comm.css') ? filemtime(BASE_PATH . '/public/assets/css/support_comm.css') : time() ?>">
    <link rel="stylesheet" href="/assets/css/cv_builder.css?v=<?= defined('BASE_PATH') && file_exists(BASE_PATH . '/public/assets/css/cv_builder.css') ? filemtime(BASE_PATH . '/public/assets/css/cv_builder.css') : time() ?>">
    <link rel="stylesheet" href="/assets/css/location.css?v=<?= defined('BASE_PATH') && file_exists(BASE_PATH . '/public/assets/css/location.css') ? filemtime(BASE_PATH . '/public/assets/css/location.css') : time() ?>">
    <link rel="stylesheet" href="/assets/css/password_security.css?v=<?= defined('BASE_PATH') && file_exists(BASE_PATH . '/public/assets/css/password_security.css') ? filemtime(BASE_PATH . '/public/assets/css/password_security.css') : time() ?>">
    <?php if ($isGoongMapPage): ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@goongmaps/goong-js@1.0.9/dist/goong-js.css">
    <script src="https://cdn.jsdelivr.net/npm/@goongmaps/goong-js@1.0.9/dist/goong-js.js"></script>
    <?php endif; ?>
    <script src="/assets/js/api.js?v=<?= defined('BASE_PATH') && file_exists(BASE_PATH . '/public/assets/js/api.js') ? filemtime(BASE_PATH . '/public/assets/js/api.js') : time() ?>"></script>
    <script src="/assets/js/address_autocomplete.js?v=<?= defined('BASE_PATH') && file_exists(BASE_PATH . '/public/assets/js/address_autocomplete.js') ? filemtime(BASE_PATH . '/public/assets/js/address_autocomplete.js') : time() ?>"></script>
    <script src="/assets/js/large_location_picker.js?v=<?= defined('BASE_PATH') && file_exists(BASE_PATH . '/public/assets/js/large_location_picker.js') ? filemtime(BASE_PATH . '/public/assets/js/large_location_picker.js') : time() ?>"></script>
    <script src="/assets/js/administrative_address_picker.js?v=<?= defined('BASE_PATH') && file_exists(BASE_PATH . '/public/assets/js/administrative_address_picker.js') ? filemtime(BASE_PATH . '/public/assets/js/administrative_address_picker.js') : time() ?>"></script>
    <script src="/assets/js/theme.js?v=<?= defined('BASE_PATH') && file_exists(BASE_PATH . '/public/assets/js/theme.js') ? filemtime(BASE_PATH . '/public/assets/js/theme.js') : time() ?>"></script>
</head>
<body>

    <!-- Header & Navigation -->
    <header class="site-header">
        <div class="container">
            <nav class="navbar">
                <a href="/" class="brand-logo">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    <span>JobMarket<span style="color:var(--secondary)">SV</span></span>
                    <span class="brand-badge">Part-time</span>
                </a>

                <ul class="nav-links">
                    <li><a href="/" class="nav-link <?= ($currentPage ?? "") === "home" ? "active" : "" ?>">Trang Chủ</a></li>
                    <li><a href="/viec-lam" class="nav-link <?= ($currentPage ?? "") === "jobs" ? "active" : "" ?>">Tìm Việc Làm</a></li>
                    <li><a href="/mau-cv-sinh-vien" class="nav-link <?= ($currentPage ?? "") === "cv_templates" ? "active" : "" ?>">Mẫu CV</a></li>
                </ul>

                <div class="nav-actions">
                    <a href="/login" class="btn btn-outline btn-sm">Đăng nhập</a>
                    <a href="/register" class="btn btn-primary btn-sm">Đăng ký</a>
                </div>

                <!-- Theme Switcher (3 Chế độ: Sáng, Tối, Hệ thống) -->
                <div class="theme-switcher-dropdown" id="nav-theme-dropdown">
                    <button type="button" class="theme-switcher-btn" aria-label="Đổi giao diện: Sáng, Tối hoặc Hệ thống" title="Đổi giao diện">
                        <span class="theme-icon-slot"></span>
                        <span class="theme-btn-label">Hệ thống</span>
                        <svg class="theme-chevron" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                    </button>
                    <div class="theme-menu">
                        <button type="button" class="theme-menu-item" data-theme-val="light">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
                            <span>Sáng</span>
                            <span class="theme-check-icon">✓</span>
                        </button>
                        <button type="button" class="theme-menu-item" data-theme-val="dark">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
                            <span>Tối</span>
                            <span class="theme-check-icon">✓</span>
                        </button>
                        <button type="button" class="theme-menu-item" data-theme-val="system">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                            <span>Hệ thống</span>
                            <span class="theme-check-icon">✓</span>
                        </button>
                    </div>
                </div>

                <button class="menu-toggle" aria-label="Mở menu điều hướng">
                    <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                </button>
            </nav>
        </div>
    </header>

    <!-- Main Content Area -->
    <main>
        <?= $content ?? "" ?>
    </main>

    <!-- Toast Notification Container -->
    <div id="toast-container" class="toast-container"></div>

    <!-- Footer -->
    <footer class="site-footer">
        <div class="container">
            <div class="footer-grid">
                <div>
                    <div class="brand-logo" style="color:#fff;margin-bottom:1rem;">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        <span>JobMarket<span style="color:var(--secondary)">SV</span></span>
                    </div>
                    <p style="color:#94a3b8;font-size:0.9rem;line-height:1.6;">Nền tảng kết nối việc làm part-time hàng đầu cho sinh viên Việt Nam. Tìm kiếm việc làm theo ca, theo giờ, gần trường học, phù hợp lịch học.</p>
                </div>
                <div>
                    <h4 class="footer-title">Dành Cho Sinh Viên</h4>
                    <ul class="footer-links">
                        <li><a href="/viec-lam?shift_type=morning" class="footer-link">Việc ca sáng</a></li>
                        <li><a href="/viec-lam?shift_type=evening" class="footer-link">Việc ca tối</a></li>
                        <li><a href="/viec-lam?shift_type=flexible" class="footer-link">Việc ca linh hoạt</a></li>
                        <li><a href="/register" class="footer-link">Đăng ký hồ sơ tìm việc</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="footer-title">Nhà Tuyển Dụng</h4>
                    <ul class="footer-links">
                        <li><a href="/register" class="footer-link">Đăng ký tài khoản Công ty</a></li>
                        <li><a href="/login" class="footer-link">Đăng tin tuyển dụng part-time</a></li>
                        <li><a href="/viec-lam" class="footer-link">Quy chế kiểm duyệt tin</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="footer-title">Hỗ Trợ & Liên Hệ</h4>
                    <ul class="footer-links">
                        <li><a href="#" class="footer-link">Email: support@jobmarket.vn</a></li>
                        <li><a href="#" class="footer-link">Hotline: 1900-1234-56</a></li>
                        <li><a href="/viec-lam" class="footer-link">Việc làm trên toàn quốc</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2026 JobMarketplace Platform. Phát triển với kiến trúc Clean Architecture / DDD trên nền tảng PHP & MySQL thuần.</p>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="/assets/js/main.js?v=<?= defined('BASE_PATH') && file_exists(BASE_PATH . '/public/assets/js/main.js') ? filemtime(BASE_PATH . '/public/assets/js/main.js') : time() ?>"></script>
    <script src="/assets/js/password_security.js?v=<?= defined('BASE_PATH') && file_exists(BASE_PATH . '/public/assets/js/password_security.js') ? filemtime(BASE_PATH . '/public/assets/js/password_security.js') : time() ?>"></script>
    <?php if ($isChatbotPage): ?>
    <script>window.__CHATBOT_CONFIG__ = { enabled: <?= $isGlobalChatEnabled ? 'true' : 'false' ?>, companyEnabled: <?= $isCompanyChatEnabled ? 'true' : 'false' ?> };</script>
    <script src="/assets/js/assistant.js?v=<?= defined('BASE_PATH') && file_exists(BASE_PATH . '/public/assets/js/assistant.js') ? filemtime(BASE_PATH . '/public/assets/js/assistant.js') : time() ?>"></script>
    <?php endif; ?>
    <script src="/assets/js/support_comm.js?v=<?= defined('BASE_PATH') && file_exists(BASE_PATH . '/public/assets/js/support_comm.js') ? filemtime(BASE_PATH . '/public/assets/js/support_comm.js') : time() ?>"></script>
</body>
</html>
