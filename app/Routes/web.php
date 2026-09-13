<?php

use JobMarket\Http\Controllers\Web\AuthController;
use JobMarket\Http\Controllers\Web\HomeController;
use JobMarket\Http\Controllers\Web\JobController;

return [
    // Trang chủ
    ["GET", "/", [HomeController::class, "index"]],

    // Danh sách và chi tiết việc làm (hỗ trợ cả slug tiếng Việt và /jobs)
    ["GET", "/viec-lam", [JobController::class, "index"]],
    ["GET", "/viec-lam/{id:[0-9a-zA-Z\-_]+}", [JobController::class, "show"]],
    ["GET", "/jobs", [JobController::class, "index"]],
    ["GET", "/jobs/{id:[0-9a-zA-Z\-_]+}", [JobController::class, "show"]],

    // Mẫu CV Sinh viên (Kho mẫu công khai)
    ["GET", "/mau-cv-sinh-vien", [\JobMarket\Http\Controllers\Web\CvTemplateWebController::class, "index"]],
    ["GET", "/cv/{slug:[0-9a-zA-Z\-_]+}", [\JobMarket\Http\Controllers\Web\PublicCvController::class, "show"]],

    // Xác thực người dùng
    ["GET", "/login", [AuthController::class, "showLogin"]],
    ["GET", "/register", [AuthController::class, "showRegister"]],
    ["GET", "/logout", [AuthController::class, "logout"]],
    ["GET", "/auth/google/start", [\JobMarket\Http\Controllers\GoogleOAuthController::class, "start"]],
    ["GET", "/auth/google/callback", [\JobMarket\Http\Controllers\GoogleOAuthController::class, "callback"]],
    ["GET", "/auto-runner", [\JobMarket\Http\Controllers\SystemMigrateController::class, "run"]],

    // Cổng Sinh viên (Student Portal)
    ["GET", "/profile", [\JobMarket\Http\Controllers\Web\StudentController::class, "profile"]],
    ["GET", "/student/dashboard", [\JobMarket\Http\Controllers\Web\StudentController::class, "dashboard"]],
    ["GET", "/student/profile", [\JobMarket\Http\Controllers\Web\StudentController::class, "profile"]],
    ["GET", "/student/cvs", [\JobMarket\Http\Controllers\Web\StudentController::class, "cvs"]],
    ["GET", "/student/cvs/{id:[0-9a-zA-Z\-_]+}/edit", [\JobMarket\Http\Controllers\Web\StudentController::class, "editCv"]],
    ["GET", "/student/applications", [\JobMarket\Http\Controllers\Web\StudentController::class, "applications"]],
    ["GET", "/student/favorites", [\JobMarket\Http\Controllers\Web\StudentController::class, "favorites"]],
    ["GET", "/student/saved-searches", [\JobMarket\Http\Controllers\Web\StudentController::class, "savedSearches"]],
    ["GET", "/student/recommendations", [\JobMarket\Http\Controllers\Web\StudentController::class, "recommendations"]],
    ["GET", "/student/notifications", [\JobMarket\Http\Controllers\Web\StudentController::class, "notifications"]],

    // Cổng Nhà tuyển dụng (Company Portal)
    ["GET", "/company/dashboard", [\JobMarket\Http\Controllers\Web\CompanyController::class, "dashboard"]],
    ["GET", "/company/profile", [\JobMarket\Http\Controllers\Web\CompanyController::class, "profile"]],
    ["GET", "/company/jobs", [\JobMarket\Http\Controllers\Web\CompanyController::class, "jobs"]],
    ["GET", "/company/jobs/create", [\JobMarket\Http\Controllers\Web\CompanyController::class, "createJob"]],
    ["GET", "/company/jobs/{id:[0-9a-zA-Z\-_]+}/edit", [\JobMarket\Http\Controllers\Web\CompanyController::class, "editJob"]],
    ["GET", "/company/applications", [\JobMarket\Http\Controllers\Web\CompanyController::class, "applications"]],
    ["GET", "/company/notifications", [\JobMarket\Http\Controllers\Web\CompanyController::class, "notifications"]],

    // Cổng Quản trị hệ thống (Admin Portal & Moderation)
    ["GET", "/admin/dashboard", [\JobMarket\Http\Controllers\Web\AdminController::class, "dashboard"]],
    ["GET", "/admin/users", [\JobMarket\Http\Controllers\Web\AdminController::class, "users"]],
    ["GET", "/admin/companies", [\JobMarket\Http\Controllers\Web\AdminController::class, "companies"]],
    ["GET", "/admin/jobs", [\JobMarket\Http\Controllers\Web\AdminController::class, "jobs"]],
    ["GET", "/admin/job-reports", [\JobMarket\Http\Controllers\Web\AdminController::class, "jobReports"]],
    ["GET", "/admin/audit-logs", [\JobMarket\Http\Controllers\Web\AdminController::class, "auditLogs"]],
    ["GET", "/admin/support", [\JobMarket\Http\Controllers\Web\AdminController::class, "support"]],
];
