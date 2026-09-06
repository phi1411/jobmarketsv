<?php

/**
 * Test: CHAT-P1-01 View Rendering and Conditional Asset Inclusion
 * Verifies that chatbot.css and chatbot.js are included ONLY on:
 * - Public job pages (home, jobs, job_detail)
 * - Student portal pages (student_*)
 * And NOT on Company, Admin, or Auth pages.
 */

define("BASE_PATH", __DIR__);
require_once __DIR__ . "/vendor/autoload.php";

use JobMarket\Support\View;

$pass = 0;
$total = 0;

function assertTest(string $name, callable $fn) {
    global $pass, $total;
    $total++;
    echo "[TEST {$total}] {$name}... ";
    try {
        $fn();
        echo "PASS\n";
        $pass++;
    } catch (\Throwable $e) {
        echo "FAIL\n  " . $e->getMessage() . "\n";
    }
}

echo "=================================================================\n";
echo "   CHAT-P1-01 CHATBOT WIDGET VIEW & ASSET INCLUSION TEST SUITE   \n";
echo "=================================================================\n\n";

$_ENV["GEMINI_FEATURE_ENABLED"] = "true";
$_ENV["GEMINI_COMPANY_ENABLED"] = "false";

// 1. Public Job Pages: MUST include chatbot assets
assertTest("1. Home page ('home') includes chatbot assets", function () {
    $html = View::renderWithLayout("home", [
        "title" => "Trang chủ",
        "currentPage" => "home"
    ]);
    if (!str_contains($html, "/assets/css/chatbot.css")) {
        throw new RuntimeException("Missing chatbot.css on home page");
    }
    if (!str_contains($html, "/assets/js/chatbot.js")) {
        throw new RuntimeException("Missing chatbot.js on home page");
    }
});

assertTest("2. Job search page ('jobs') includes chatbot assets", function () {
    $html = View::renderWithLayout("jobs/index", [
        "title" => "Tìm việc",
        "currentPage" => "jobs"
    ]);
    if (!str_contains($html, "/assets/css/chatbot.css")) {
        throw new RuntimeException("Missing chatbot.css on jobs page");
    }
    if (!str_contains($html, "/assets/js/chatbot.js")) {
        throw new RuntimeException("Missing chatbot.js on jobs page");
    }
});

assertTest("3. Job detail page ('job_detail') includes chatbot assets", function () {
    $html = View::renderWithLayout("jobs/show", [
        "title" => "Chi tiết việc",
        "currentPage" => "job_detail",
        "jobId" => "job-001"
    ]);
    if (!str_contains($html, "/assets/css/chatbot.css")) {
        throw new RuntimeException("Missing chatbot.css on job_detail page");
    }
    if (!str_contains($html, "/assets/js/chatbot.js")) {
        throw new RuntimeException("Missing chatbot.js on job_detail page");
    }
});

// 2. Student Portal Pages: MUST include chatbot assets
assertTest("4. Student dashboard includes chatbot assets", function () {
    $html = View::renderWithLayout("student/dashboard", [
        "title" => "Student Dashboard",
        "currentPage" => "student_dashboard",
        "activeTab" => "dashboard"
    ]);
    if (!str_contains($html, "/assets/css/chatbot.css")) {
        throw new RuntimeException("Missing chatbot.css on student dashboard");
    }
    if (!str_contains($html, "/assets/js/chatbot.js")) {
        throw new RuntimeException("Missing chatbot.js on student dashboard");
    }
});

assertTest("5. Student profile includes chatbot assets", function () {
    $html = View::renderWithLayout("student/profile", [
        "title" => "Student Profile",
        "currentPage" => "student_profile",
        "activeTab" => "profile"
    ]);
    if (!str_contains($html, "/assets/css/chatbot.css")) {
        throw new RuntimeException("Missing chatbot.css on student profile");
    }
    if (!str_contains($html, "/assets/js/chatbot.js")) {
        throw new RuntimeException("Missing chatbot.js on student profile");
    }
});

assertTest("6. Student applications includes chatbot assets", function () {
    $html = View::renderWithLayout("student/applications", [
        "title" => "Student Applications",
        "currentPage" => "student_applications",
        "activeTab" => "applications"
    ]);
    if (!str_contains($html, "/assets/css/chatbot.css")) {
        throw new RuntimeException("Missing chatbot.css on student applications");
    }
    if (!str_contains($html, "/assets/js/chatbot.js")) {
        throw new RuntimeException("Missing chatbot.js on student applications");
    }
});

// 3. Company Portal Pages: MUST NOT include chatbot assets
assertTest("7. Company dashboard MUST NOT include chatbot assets", function () {
    $html = View::renderWithLayout("company/dashboard", [
        "title" => "Company Dashboard",
        "currentPage" => "company_dashboard",
        "activeTab" => "dashboard"
    ]);
    if (str_contains($html, "chatbot.css")) {
        throw new RuntimeException("chatbot.css MUST NOT be included on company dashboard");
    }
    if (str_contains($html, "chatbot.js")) {
        throw new RuntimeException("chatbot.js MUST NOT be included on company dashboard");
    }
});

assertTest("8. Company jobs list MUST NOT include chatbot assets", function () {
    $html = View::renderWithLayout("company/jobs", [
        "title" => "Company Jobs",
        "currentPage" => "company_jobs",
        "activeTab" => "jobs"
    ]);
    if (str_contains($html, "chatbot.css") || str_contains($html, "chatbot.js")) {
        throw new RuntimeException("chatbot assets MUST NOT be on company jobs");
    }
});

assertTest("9. Company applications list MUST NOT include chatbot assets", function () {
    $html = View::renderWithLayout("company/applications", [
        "title" => "Company Applications",
        "currentPage" => "company_applications",
        "activeTab" => "applications"
    ]);
    if (str_contains($html, "chatbot.css") || str_contains($html, "chatbot.js")) {
        throw new RuntimeException("chatbot assets MUST NOT be on company applications");
    }
});

// 4. Admin Portal Pages: MUST NOT include chatbot assets
assertTest("10. Admin dashboard MUST NOT include chatbot assets", function () {
    $html = View::renderWithLayout("admin/dashboard", [
        "title" => "Admin Dashboard",
        "currentPage" => "admin_dashboard",
        "activeTab" => "dashboard"
    ]);
    if (str_contains($html, "chatbot.css") || str_contains($html, "chatbot.js")) {
        throw new RuntimeException("chatbot assets MUST NOT be on admin dashboard");
    }
});

assertTest("11. Admin users list MUST NOT include chatbot assets", function () {
    $html = View::renderWithLayout("admin/users", [
        "title" => "Admin Users",
        "currentPage" => "admin_users",
        "activeTab" => "users"
    ]);
    if (str_contains($html, "chatbot.css") || str_contains($html, "chatbot.js")) {
        throw new RuntimeException("chatbot assets MUST NOT be on admin users");
    }
});

assertTest("12. Admin audit logs MUST NOT include chatbot assets", function () {
    $html = View::renderWithLayout("admin/audit_logs", [
        "title" => "Admin Audit Logs",
        "currentPage" => "admin_audit_logs",
        "activeTab" => "audit_logs"
    ]);
    if (str_contains($html, "chatbot.css") || str_contains($html, "chatbot.js")) {
        throw new RuntimeException("chatbot assets MUST NOT be on admin audit logs");
    }
});

// 5. Auth Pages: MUST NOT include chatbot assets
assertTest("13. Login page MUST NOT include chatbot assets", function () {
    $html = View::renderWithLayout("auth/login", [
        "title" => "Đăng nhập",
        "currentPage" => "login"
    ]);
    if (str_contains($html, "chatbot.css") || str_contains($html, "chatbot.js")) {
        throw new RuntimeException("chatbot assets MUST NOT be on login page");
    }
});

assertTest("14. Register page MUST NOT include chatbot assets", function () {
    $html = View::renderWithLayout("auth/register", [
        "title" => "Đăng ký",
        "currentPage" => "register"
    ]);
    if (str_contains($html, "chatbot.css") || str_contains($html, "chatbot.js")) {
        throw new RuntimeException("chatbot assets MUST NOT be on register page");
    }
});

// 6. Company Launch Control (CHAT-P1-02): When enabled, Company views DO include chatbot assets
assertTest("15. Company flag ON: Company dashboard includes chatbot assets", function () {
    $_ENV["GEMINI_FEATURE_ENABLED"] = "true";
    $_ENV["GEMINI_COMPANY_ENABLED"] = "true";

    $html = View::renderWithLayout("company/dashboard", [
        "title" => "Company Dashboard",
        "currentPage" => "company_dashboard",
        "activeTab" => "dashboard"
    ]);
    if (!str_contains($html, "/assets/css/chatbot.css")) {
        throw new RuntimeException("Missing chatbot.css on company dashboard when flag is on");
    }
    if (!str_contains($html, "/assets/js/chatbot.js")) {
        throw new RuntimeException("Missing chatbot.js on company dashboard when flag is on");
    }
    if (!str_contains($html, 'companyEnabled: true')) {
        throw new RuntimeException("Missing companyEnabled: true in client config");
    }
});

assertTest("16. Company flag ON: Admin dashboard STILL MUST NOT include chatbot assets", function () {
    $_ENV["GEMINI_FEATURE_ENABLED"] = "true";
    $_ENV["GEMINI_COMPANY_ENABLED"] = "true";

    $html = View::renderWithLayout("admin/dashboard", [
        "title" => "Admin Dashboard",
        "currentPage" => "admin_dashboard",
        "activeTab" => "dashboard"
    ]);
    if (str_contains($html, "chatbot.css") || str_contains($html, "chatbot.js")) {
        throw new RuntimeException("chatbot assets MUST NOT be on admin dashboard even if company flag is on");
    }
});

// 7. Global Feature Flag OFF Regression:
// When GEMINI_FEATURE_ENABLED is false, public job pages and student portal MUST NOT include chatbot assets
assertTest("17. Global flag OFF: Home page MUST NOT include chatbot assets", function () {
    $_ENV["GEMINI_FEATURE_ENABLED"] = "false";
    $_ENV["GEMINI_COMPANY_ENABLED"] = "false";

    $html = View::renderWithLayout("home", [
        "title" => "Trang chủ",
        "currentPage" => "home"
    ]);
    if (str_contains($html, "chatbot.css") || str_contains($html, "chatbot.js")) {
        throw new RuntimeException("chatbot assets MUST NOT be included on home page when global flag is off");
    }
});

assertTest("18. Global flag OFF: Job search page MUST NOT include chatbot assets", function () {
    $_ENV["GEMINI_FEATURE_ENABLED"] = "false";
    $_ENV["GEMINI_COMPANY_ENABLED"] = "false";

    $html = View::renderWithLayout("jobs/index", [
        "title" => "Tìm việc",
        "currentPage" => "jobs"
    ]);
    if (str_contains($html, "chatbot.css") || str_contains($html, "chatbot.js")) {
        throw new RuntimeException("chatbot assets MUST NOT be included on jobs page when global flag is off");
    }
});

assertTest("19. Global flag OFF: Job detail page MUST NOT include chatbot assets", function () {
    $_ENV["GEMINI_FEATURE_ENABLED"] = "false";
    $_ENV["GEMINI_COMPANY_ENABLED"] = "false";

    $html = View::renderWithLayout("jobs/show", [
        "title" => "Chi tiết việc",
        "currentPage" => "job_detail",
        "jobId" => "job-001"
    ]);
    if (str_contains($html, "chatbot.css") || str_contains($html, "chatbot.js")) {
        throw new RuntimeException("chatbot assets MUST NOT be included on job detail page when global flag is off");
    }
});

assertTest("20. Global flag OFF: Student dashboard MUST NOT include chatbot assets", function () {
    $_ENV["GEMINI_FEATURE_ENABLED"] = "false";
    $_ENV["GEMINI_COMPANY_ENABLED"] = "false";

    $html = View::renderWithLayout("student/dashboard", [
        "title" => "Student Dashboard",
        "currentPage" => "student_dashboard",
        "activeTab" => "dashboard"
    ]);
    if (str_contains($html, "chatbot.css") || str_contains($html, "chatbot.js")) {
        throw new RuntimeException("chatbot assets MUST NOT be included on student dashboard when global flag is off");
    }
});

assertTest("21. Global flag OFF: Company dashboard MUST NOT include chatbot assets even if company flag is ON", function () {
    $_ENV["GEMINI_FEATURE_ENABLED"] = "false";
    $_ENV["GEMINI_COMPANY_ENABLED"] = "true";

    $html = View::renderWithLayout("company/dashboard", [
        "title" => "Company Dashboard",
        "currentPage" => "company_dashboard",
        "activeTab" => "dashboard"
    ]);
    if (str_contains($html, "chatbot.css") || str_contains($html, "chatbot.js")) {
        throw new RuntimeException("chatbot assets MUST NOT be included on company dashboard when global flag is off");
    }
});

echo "\n=================================================================\n";
echo "   KẾT QUẢ: {$pass}/{$total} BÀI TEST VIEW & ASSET ĐẠT THÀNH CÔNG (100% PASS)\n";
echo "=================================================================\n";

exit($pass === $total ? 0 : 1);
