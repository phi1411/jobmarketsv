<?php

use JobMarket\Http\Controllers\AdminController;
use JobMarket\Http\Controllers\ApplicationController;
use JobMarket\Http\Controllers\AssistantController;
use JobMarket\Http\Controllers\AuthenticationController;
use JobMarket\Http\Controllers\CategoryController;
use JobMarket\Http\Controllers\ChatController;
use JobMarket\Http\Controllers\CompanyController;
use JobMarket\Http\Controllers\CvProfileExtractionController;
use JobMarket\Http\Controllers\DashboardController;
use JobMarket\Http\Controllers\DeveloperController;
use JobMarket\Http\Controllers\FavoriteController;
use JobMarket\Http\Controllers\HomeController;
use JobMarket\Http\Controllers\JobController;
use JobMarket\Http\Controllers\JobLocationController;
use JobMarket\Http\Controllers\JobRecommendationController;
use JobMarket\Http\Controllers\JobReportController;
use JobMarket\Http\Controllers\LocationController;
use JobMarket\Http\Controllers\MapController;
use JobMarket\Http\Controllers\MatchAnalysisController;
use JobMarket\Http\Controllers\NotificationController;
use JobMarket\Http\Controllers\OnlineCvController;
use JobMarket\Http\Controllers\ProfileController;
use JobMarket\Http\Controllers\ProfileJobAlertController;
use JobMarket\Http\Controllers\ReportController;
use JobMarket\Http\Controllers\ReviewController;
use JobMarket\Http\Controllers\SearchController;
use JobMarket\Http\Controllers\SkillController;
use JobMarket\Http\Controllers\StatisticController;
use JobMarket\Http\Controllers\StudentCvController;
use JobMarket\Http\Controllers\SubscriptionController;

return [
    // testing
    ["GET", "/", [HomeController::class, "index"]], //

    // Online CV Builder / Student CV Templates
    ["GET", "/cv/templates", [OnlineCvController::class, "templates"]],
    ["GET", "/student/cvs", [OnlineCvController::class, "index"]],
    ["POST", "/student/cvs", [OnlineCvController::class, "store"]],
    ["GET", "/student/cvs/{id:[0-9a-zA-Z\-_]+}", [OnlineCvController::class, "show"]],
    ["PATCH", "/student/cvs/{id:[0-9a-zA-Z\-_]+}", [OnlineCvController::class, "update"]],
    ["PUT", "/student/cvs/{id:[0-9a-zA-Z\-_]+}", [OnlineCvController::class, "update"]],
    ["DELETE", "/student/cvs/{id:[0-9a-zA-Z\-_]+}", [OnlineCvController::class, "destroy"]],
    ["POST", "/student/cvs/{id:[0-9a-zA-Z\-_]+}/duplicate", [OnlineCvController::class, "duplicate"]],
    ["POST", "/student/cvs/{id:[0-9a-zA-Z\-_]+}/primary", [OnlineCvController::class, "setPrimary"]],
    ["POST", "/student/cvs/{id:[0-9a-zA-Z\-_]+}/activate", [OnlineCvController::class, "activate"]],
    ["PATCH", "/student/cvs/{id:[0-9a-zA-Z\-_]+}/visibility", [OnlineCvController::class, "visibility"]],
    ["GET", "/student/cvs/{id:[0-9a-zA-Z\-_]+}/preview", [OnlineCvController::class, "preview"]],
    ["GET", "/student/cvs/{id:[0-9a-zA-Z\-_]+}/export.pdf", [OnlineCvController::class, "export"]],
    ["GET", "/cv/{slug:[0-9a-zA-Z\-_]+}", [OnlineCvController::class, "publicShow"]],
    ["GET", "/cv/{slug:[0-9a-zA-Z\-_]+}/export.pdf", [OnlineCvController::class, "publicExport"]],

    // Retrieve a list of all companies
    ["GET", "/companies", [CompanyController::class, "index"]],

    // Create a new company
    ["POST", "/companies", [CompanyController::class, "store"]],

    // Retrieve a specific company by ID
    ["GET", "/companies/{id:[0-9a-zA-Z\-_]+}", [CompanyController::class, "show"]],

    // Update a specific company by ID
    ["PUT", "/companies/{id:[0-9a-zA-Z\-_]+}", [CompanyController::class, "update"]],

    // Delete a specific company by ID
    ["DELETE", "/companies/{id:[0-9a-zA-Z\-_]+}", [CompanyController::class, "destroy"]],

    // Retrieve the company's own private profile
    ["GET", "/company/profile", [CompanyController::class, "myProfile"]],
    ["PUT", "/company/profile", [CompanyController::class, "updateMyProfile"]],
    ["PATCH", "/company/profile", [CompanyController::class, "updateMyProfile"]],

    // Retrieve a list of all jobs
    ["GET", "/jobs", [JobController::class, "index"]],

    // Geospatial job discovery (browser coordinates are processed per request and never persisted)
    ["POST", "/jobs/nearby-search", [JobLocationController::class, "nearby"]],

    // Create a new job
    ["POST", "/jobs", [JobController::class, "store"]],

    // Retrieve a specific job by ID
    ["GET", "/jobs/{id:[0-9a-zA-Z\-_]+}", [JobController::class, "show"]],

    ["GET", "/jobs/{id:[0-9a-zA-Z\-_]+}/locations", [JobLocationController::class, "index"]],
    ["POST", "/jobs/{id:[0-9a-zA-Z\-_]+}/commute-check", [JobLocationController::class, "commuteCheck"]],
    ["POST", "/company/jobs/{id:[0-9a-zA-Z\-_]+}/locations", [JobLocationController::class, "store"]],
    ["PATCH", "/company/jobs/{jobId:[0-9a-zA-Z\-_]+}/locations/{locationId:[0-9a-zA-Z\-_]+}", [JobLocationController::class, "update"]],
    ["PUT", "/company/jobs/{jobId:[0-9a-zA-Z\-_]+}/locations/{locationId:[0-9a-zA-Z\-_]+}", [JobLocationController::class, "update"]],
    ["DELETE", "/company/jobs/{jobId:[0-9a-zA-Z\-_]+}/locations/{locationId:[0-9a-zA-Z\-_]+}", [JobLocationController::class, "destroy"]],

    // Goong REST proxy: the API key stays server-side
    ["GET", "/map/places/autocomplete", [MapController::class, "autocomplete"]],
    ["POST", "/map/places/detail", [MapController::class, "detail"]],
    ["POST", "/map/geocode", [MapController::class, "geocode"]],
    ["POST", "/map/reverse-geocode", [MapController::class, "reverseGeocode"]],
    ["POST", "/map/resolve-location", [MapController::class, "resolveLocation"]],

    ["GET", "/student/preferred-locations", [JobLocationController::class, "preferences"]],
    ["PUT", "/student/preferred-locations", [JobLocationController::class, "savePreferences"]],

    // Update a specific job by ID
    ["PUT", "/jobs/{id:[0-9a-zA-Z\-_]+}", [JobController::class, "update"]],

    // Delete a specific job by ID (soft-delete)
    ["DELETE", "/jobs/{id:[0-9a-zA-Z\-_]+}", [JobController::class, "destroy"]],

    // Update job via PATCH
    ["PATCH", "/jobs/{id:[0-9a-zA-Z\-_]+}", [JobController::class, "update"]],

    // Close a job posting
    ["POST", "/jobs/{id:[0-9a-zA-Z\-_]+}/close", [JobController::class, "close"]],
    ["PUT", "/jobs/{id:[0-9a-zA-Z\-_]+}/close", [JobController::class, "close"]],

    // List jobs for the currently authenticated company
    ["GET", "/company/jobs", [JobController::class, "myJobs"]],

    // List public jobs for a specific company
    ["GET", "/companies/{id:[0-9a-zA-Z\-_]+}/jobs", [JobController::class, "companyJobs"]],

    // Student applies for a job
    ["POST", "/jobs/{id:[0-9a-zA-Z\-_]+}/applications", [ApplicationController::class, "store"]],
    ["POST", "/jobs/{id:[0-9a-zA-Z\-_]+}/reports", [JobReportController::class, "store"]],

    // Company views applications for a specific job
    ["GET", "/jobs/{id:[0-9a-zA-Z\-_]+}/applications", [ApplicationController::class, "index"]],

    // Student views their own applications list
    ["GET", "/student/applications", [ApplicationController::class, "myApplications"]],
    ["GET", "/applications/me", [ApplicationController::class, "myApplications"]],

    // Company views all applications across all jobs owned by their company
    ["GET", "/company/applications", [ApplicationController::class, "companyApplications"]],

    // Retrieve a specific application by ID
    ["GET", "/applications/{id:[0-9a-zA-Z\-_]+}", [ApplicationController::class, "show"]],

    // Protected CV document delivery (CV-P0-02)
    ["GET", "/applications/{id:[0-9a-zA-Z\-_]+}/cv", [ApplicationController::class, "downloadCv"]],

    // Update application status (by employer)
    ["PATCH", "/applications/{id:[0-9a-zA-Z\-_]+}/status", [ApplicationController::class, "updateStatus"]],
    ["PUT", "/applications/{id:[0-9a-zA-Z\-_]+}/status", [ApplicationController::class, "updateStatus"]],
    ["PUT", "/applications/{id:[0-9a-zA-Z\-_]+}", [ApplicationController::class, "updateStatus"]],

    // Withdraw an application (by student)
    ["POST", "/applications/{id:[0-9a-zA-Z\-_]+}/withdraw", [ApplicationController::class, "withdraw"]],
    ["PATCH", "/applications/{id:[0-9a-zA-Z\-_]+}/withdraw", [ApplicationController::class, "withdraw"]],
    ["DELETE", "/applications/{id:[0-9a-zA-Z\-_]+}", [ApplicationController::class, "withdraw"]],

    // CV / Profile Match Analysis (CV-AI-P1-03)
    ["POST", "/applications/{id:[0-9a-zA-Z\-_]+}/match-analysis", [MatchAnalysisController::class, "analyze"]],
    ["GET", "/applications/{id:[0-9a-zA-Z\-_]+}/match-analysis", [MatchAnalysisController::class, "show"]],
    ["PATCH", "/applications/{id:[0-9a-zA-Z\-_]+}/match-consent", [MatchAnalysisController::class, "updateConsent"]],

    // Retrieve a list of all developers
    ["GET", "/developers", [DeveloperController::class, "index"]],

    // Create a new developer
    ["POST", "/developers", [DeveloperController::class, "store"]],

    // Retrieve a specific developer by ID
    ["GET", "/developers/{id:[0-9a-zA-Z\-_]+}", [DeveloperController::class, "show"]],

    // Update a specific developer by ID
    ["PUT", "/developers/{id:[0-9a-zA-Z\-_]+}", [DeveloperController::class, "update"]],

    // Delete a specific developer by ID
    ["DELETE", "/developers/{id:[0-9a-zA-Z\-_]+}", [DeveloperController::class, "destroy"]],

    // Register a new user (developer or company)
    ["POST", "/register", [AuthenticationController::class, "register"]],

    // Authenticate a user and obtain an access token
    ["POST", "/login", [AuthenticationController::class, "login"]],

    // Logout the user and invalidate the access token
    ["POST", "/logout", [AuthenticationController::class, "logout"]],

    // Retrieve the user's profile information
    ["GET", "/profile", [ProfileController::class, "index"]],

    // Retrieve the student's own private profile information
    ["GET", "/student/profile", [ProfileController::class, "index"]],

    // Update the student's own private profile information
    ["PUT", "/student/profile", [ProfileController::class, "update"]],
    ["PATCH", "/student/profile", [ProfileController::class, "update"]],

    // Student Active CV Management (CV-P0-01)
    ["GET", "/student/cv", [StudentCvController::class, "show"]],
    ["POST", "/student/cv", [StudentCvController::class, "upload"]],
    ["DELETE", "/student/cv", [StudentCvController::class, "destroy"]],
    ["POST", "/student/cv/analyze", [CvProfileExtractionController::class, "analyze"]],

    // Update the user's password
    ["PUT", "/profile/password", [ProfileController::class, "passUpdate"]],

    // Retrieve a list of all available skills
    ["GET", "/skills", [SkillController::class, "index"]],

    // Create a new skill
    ["POST", "/skills", [SkillController::class, "store"]],

    // Retrieve a specific skill by ID
    ["GET", "/skills/{id:[0-9a-zA-Z\-_]+}", [SkillController::class, "show"]],

    // Update a specific skill by ID
    ["PUT", "/skills/{id:[0-9a-zA-Z\-_]+}", [SkillController::class, "update"]],

    // Delete a specific skill by ID
    ["DELETE", "/skills/{id:[0-9a-zA-Z\-_]+}", [SkillController::class, "destroy"]],

    // Retrieve a list of favorited jobs by the user
    ["GET", "/favorites/jobs", [FavoriteController::class, "index"]],

    // Add a job to the user's favorites
    ["POST", "/favorites/jobs/{id:[0-9a-zA-Z\-_]+}", [FavoriteController::class, "store"]],

    // Remove a job from the user's favorites
    ["DELETE", "/favorites/jobs/{id:[0-9a-zA-Z\-_]+}", [FavoriteController::class, "destroy"]],

    // Retrieve a list of saved searches by the user
    ["GET", "/saved-searches", [SearchController::class, "index"]],

    // Save a search with specified criteria
    ["POST", "/saved-searches", [SearchController::class, "store"]],

    // Update a saved search by ID
    ["PATCH", "/saved-searches/{id:[0-9a-zA-Z\-_]+}", [SearchController::class, "update"]],
    ["PUT", "/saved-searches/{id:[0-9a-zA-Z\-_]+}", [SearchController::class, "update"]],

    // Delete a saved search by ID
    ["DELETE", "/saved-searches/{id:[0-9a-zA-Z\-_]+}", [SearchController::class, "destroy"]],

    // Retrieve a list of all job categories
    ["GET", "/categories", [CategoryController::class, "index"]],

    // Create a new job category
    ["POST", "/categories", [CategoryController::class, "store"]],

    // Retrieve a specific job category by ID
    ["GET", "/categories/{id:[0-9a-zA-Z\-_]+}", [CategoryController::class, "show"]],

    // Update a specific job category by ID
    ["PUT", "/categories/{id:[0-9a-zA-Z\-_]+}", [CategoryController::class, "update"]],

    // Delete a specific job category by ID
    ["DELETE", "/categories/{id:[0-9a-zA-Z\-_]+}", [CategoryController::class, "destroy"]],

    // Retrieve a list of all available locations
    ["GET", "/locations", [LocationController::class, "index"]],

    // Retrieve locations grouped for the large province/area picker
    ["GET", "/locations/hierarchy", [LocationController::class, "hierarchy"]],

    // Create a new location
    ["POST", "/locations", [LocationController::class, "store"]],

    // Retrieve a specific location by ID
    ["GET", "/locations/{id:[0-9a-zA-Z\-_]+}", [LocationController::class, "show"]],

    // Update a specific location by ID
    ["PUT", "/locations/{id:[0-9a-zA-Z\-_]+}", [LocationController::class, "update"]],

    // Delete a specific location by ID
    ["DELETE", "/locations/{id:[0-9a-zA-Z\-_]+}", [LocationController::class, "destroy"]],

    // Retrieve all reviews for a specific company
    ["GET", "/companies/{id:[0-9a-zA-Z\-_]+}/reviews", [ReviewController::class, "index"]],

    // Create a new review for a specific company
    ["POST", "/companies/{id:[0-9a-zA-Z\-_]+}/reviews", [ReviewController::class, "store"]],

    // Retrieve a specific review by ID
    ["GET", "/reviews/{id:[0-9a-zA-Z\-_]+}", [ReviewController::class, "show"]],

    // Update a specific review by ID
    ["PUT", "/reviews/{id:[0-9a-zA-Z\-_]+}", [ReviewController::class, "update"]],

    // Delete a specific review by ID
    ["DELETE", "/reviews/{id:[0-9a-zA-Z\-_]+}", [ReviewController::class, "destroy"]],

    // Retrieve statistical data related to jobs (e.g., total jobs, top categories)
    ["GET", "/statistics/jobs", [StatisticController::class, "jobs"]],

    // Retrieve statistical data related to companies (e.g., total companies, top
    ["GET", "/statistics/companies", [StatisticController::class, "companies"]],

    // Retrieve a list of notifications for the authenticated user
    ["GET", "/notifications", [NotificationController::class, "index"]],

    // Retrieve unread notification count
    ["GET", "/notifications/unread-count", [NotificationController::class, "unreadCount"]],

    // Mark a specific notification as read
    ["PATCH", "/notifications/{id:[0-9a-zA-Z\-_]+}/read", [NotificationController::class, "markAsRead"]],
    ["PUT", "/notifications/{id:[0-9a-zA-Z\-_]+}/read", [NotificationController::class, "markAsRead"]],

    // Mark all notifications as read
    ["PATCH", "/notifications/read-all", [NotificationController::class, "markAllAsRead"]],
    ["PUT", "/notifications/read-all", [NotificationController::class, "markAllAsRead"]],

    // Dashboard APIs
    ["GET", "/student/dashboard", [DashboardController::class, "studentDashboard"]],
    ["GET", "/student/job-recommendations", [JobRecommendationController::class, "index"]],
    ["GET", "/student/recommendation-alert-settings", [ProfileJobAlertController::class, "show"]],
    ["PATCH", "/student/recommendation-alert-settings", [ProfileJobAlertController::class, "update"]],
    ["GET", "/company/dashboard", [DashboardController::class, "companyDashboard"]],
    ["GET", "/admin/dashboard", [DashboardController::class, "adminDashboard"]],

    // Subscribe to receive notifications for new job postings based on specific criteria
    ["POST", "/subscriptions/jobs", [SubscriptionController::class, "store"]],

    // Unsubscribe from notifications for a specific job subscription
    ["DELETE", "/subscriptions/jobs/{id:[0-9a-zA-Z\-_]+}", [SubscriptionController::class, "destroy"]],

    // Generate a report of job postings based on various criteria (e.g., date range, category)
    ["GET", "/reports/jobs", [ReportController::class, "jobs"]],

    // Generate a report of companies based on various criteria (e.g., location, industry)
    ["GET", "/reports/companies", [ReportController::class, "companies"]],

    // Admin User Management
    ["GET", "/admin/users", [AdminController::class, "users"]],
    ["GET", "/admin/users/{id:[0-9a-zA-Z\-_]+}", [AdminController::class, "showUser"]],
    ["PATCH", "/admin/users/{id:[0-9a-zA-Z\-_]+}/status", [AdminController::class, "updateUserStatus"]],
    ["PUT", "/admin/users/{id:[0-9a-zA-Z\-_]+}/status", [AdminController::class, "updateUserStatus"]],

    // Admin Company Verification
    ["GET", "/admin/companies", [AdminController::class, "companies"]],
    ["PATCH", "/admin/companies/{id:[0-9a-zA-Z\-_]+}/verification", [AdminController::class, "verifyCompany"]],
    ["PUT", "/admin/companies/{id:[0-9a-zA-Z\-_]+}/verification", [AdminController::class, "verifyCompany"]],

    // Admin Job Moderation
    ["GET", "/admin/jobs", [AdminController::class, "jobs"]],
    ["PATCH", "/admin/jobs/{id:[0-9a-zA-Z\-_]+}/moderation", [AdminController::class, "moderateJob"]],
    ["PUT", "/admin/jobs/{id:[0-9a-zA-Z\-_]+}/moderation", [AdminController::class, "moderateJob"]],

    // Admin Job Report Moderation Queue
    ["GET", "/admin/job-reports", [JobReportController::class, "index"]],
    ["PATCH", "/admin/job-reports/{id:[0-9a-zA-Z\-_]+}", [JobReportController::class, "update"]],

    // Admin Audit Logs
    ["GET", "/admin/audit-logs", [AdminController::class, "auditLogs"]],

    // Read-only Gemini AI Assistant (CHAT-P0-01, CHAT-P0-02, CHAT-P2-01)
    ["POST", "/assistant/chat", [AssistantController::class, "chat"]],
    ["POST", "/assistant/feedback", [AssistantController::class, "feedback"]],

    // Direct Real-time Support Chat (Student/Company <-> Admin)
    ["GET", "/support/conversation", [ChatController::class, "conversation"]],
    ["GET", "/support/messages", [ChatController::class, "messages"]],
    ["POST", "/support/messages", [ChatController::class, "sendMessage"]],
    ["POST", "/support/read", [ChatController::class, "markRead"]],
    ["GET", "/support/unread-count", [ChatController::class, "unreadCount"]],
    ["GET", "/admin/support/conversations", [ChatController::class, "adminConversations"]]
];
