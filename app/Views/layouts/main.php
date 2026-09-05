<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? "Job Marketplace - Việc Làm Part-Time Sinh Viên") ?></title>
    <meta name="description" content="Nền tảng kết nối việc làm bán thời gian, linh hoạt theo ca cho sinh viên tại Việt Nam.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css?v=<?= defined('BASE_PATH') && file_exists(BASE_PATH . '/public/assets/css/style.css') ? filemtime(BASE_PATH . '/public/assets/css/style.css') : time() ?>">
    <script src="/assets/js/api.js?v=<?= defined('BASE_PATH') && file_exists(BASE_PATH . '/public/assets/js/api.js') ? filemtime(BASE_PATH . '/public/assets/js/api.js') : time() ?>"></script>
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
                </ul>

                <div class="nav-actions">
                    <a href="/login" class="btn btn-outline btn-sm">Đăng nhập</a>
                    <a href="/register" class="btn btn-primary btn-sm">Đăng ký</a>
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
                        <li><a href="#" class="footer-link">Hà Nội & TP. Hồ Chí Minh</a></li>
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
</body>
</html>
