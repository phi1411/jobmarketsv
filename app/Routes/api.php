<?php

use JobMarket\Http\Controllers\HomeController;

return [
    // testing
    ["GET", "/", [HomeController::class, "index"]],

    // Retrieve a list of all companies
    ["GET", "/companies", []],

    // Create a new company
    ["POST", "/companies", []],

    // Retrieve a specific company by ID
    ["GET", "/companies/{id:\d+}", []],

    // Update a specific company by ID
    ["PUT", "/companies/{id:\d+}", []],

    // Delete a specific company by ID
    ["DELETE", "/companies/{id:\d+}", []],

    // Retrieve a list of all jobs
    ["GET", "/jobs", []],

    // Create a new job
    ["POST", "/jobs", []],

    // Retrieve a specific job by ID
    ["GET", "/jobs/{id:\d+}", []],

    // Update a specific job by ID
    ["PUT", "/jobs/{id:\d+}", []],

    // Delete a specific job by ID
    ["DELETE", "/jobs/{id:\d+}", []],

    // Retrieve all applications for a specific job
    ["GET", "/jobs/{id:\d+}/applications", []],

    // Create a new application for a specific job
    ["POST", "/jobs/{id:\d+}/applications", []],

    // Retrieve a specific application by ID
    ["GET", "/applications/{id:\d+}", []],

    // Update a specific application by ID
    ["PUT", "/applications/{id:\d+}", []],

    // Delete a specific application by ID
    ["DELETE", "/applications/{id:\d+}", []],

    // Retrieve a list of all developers
    ["GET", "/developers", []],

    // Create a new developer
    ["POST", "/developers", []],

    // Retrieve a specific developer by ID
    ["GET", "/developers/{id:\d+}", []],

    // Update a specific developer by ID
    ["PUT", "/developers/{id:\d+}", []],

    // Delete a specific developer by ID
    ["DELETE", "/developers/{id:\d+}", []],

    // Register a new user (developer or company)
    ["POST", "/register", []],

    // Authenticate a user and obtain an access token
    ["POST", "/login", []],

    // Logout the user and invalidate the access token
    ["POST", "/logout", []],

    // Retrieve the user's profile information
    ["GET", "/profile", []],

    // Update the user's profile information
    ["PUT", "/profile", []],

    // Update the user's password
    ["PUT", "/profile/password", []],

    // Retrieve a list of all available skills
    ["GET", "/skills", []],

    // Create a new skill
    ["POST", "/skills", []],

    // Retrieve a specific skill by ID
    ["GET", "/skills/{id:\d+}", []],

    // Update a specific skill by ID
    ["PUT", "/skills/{id:\d+}", []],

    // Delete a specific skill by ID
    ["DELETE", "/skills/{id:\d+}", []],

    // Retrieve a list of favorited jobs by the user
    ["GET", "/favorites/jobs", []],

    // Add a job to the user's favorites
    ["POST", "/favorites/jobs/{id:\d+}", []],

    // Remove a job from the user's favorites
    ["DELETE", "/favorites/jobs/{id:\d+}", []],

    // Retrieve a list of saved searches by the user
    ["GET", "/saved-searches", []],

    // Save a search with specified criteria
    ["POST", "/saved-searches", []],

    // Delete a saved search by ID
    ["DELETE", "/saved-searches/{id:\d+}", []],

    // Retrieve a list of all job categories
    ["GET", "/categories", []],

    // Create a new job category
    ["POST", "/categories", []],

    // Retrieve a specific job category by ID
    ["GET", "/categories/{id:\d+}", []],

    // Update a specific job category by ID
    ["PUT", "/categories/{id:\d+}", []],

    // Delete a specific job category by ID
    ["DELETE", "/categories/{id:\d+}", []],

    // Retrieve a list of all available locations
    ["GET", "/locations", []],

    // Create a new location
    ["POST", "/locations", []],

    // Retrieve a specific location by ID
    ["GET", "/locations/{id:\d+}", []],

    // Update a specific location by ID
    ["PUT", "/locations/{id:\d+}", []],

    // Delete a specific location by ID
    ["DELETE", "/locations/{id:\d+}", []],

    // Retrieve all reviews for a specific company
    ["GET", "/companies/{id:\d+}/reviews", []],

    // Create a new review for a specific company
    ["POST", "/companies/{id:\d+}/reviews", []],

    // Retrieve a specific review by ID
    ["GET", "/reviews/{id:\d+}", []],

    // Update a specific review by ID
    ["PUT", "/reviews/{id:\d+}", []],

    // Delete a specific review by ID
    ["DELETE", "/reviews/{id:\d+}", []],

    // Retrieve statistical data related to jobs (e.g., total jobs, top categories)
    ["GET", "/statistics/jobs", []],

    // Retrieve statistical data related to companies (e.g., total companies, top
    ["GET", "/statistics/companies", []],

    // Retrieve a list of notifications for the authenticated user
    ["GET", "/notifications", []],

    // Create a new notification for the authenticated user
    ["POST", "/notifications", []],

    // Retrieve a specific notification by ID
    ["GET", "/notifications/{id:\d+}", []],

    // Update a specific notification by ID
    ["PUT", "/notifications/{id:\d+}", []],

    // Delete a specific notification by ID
    ["DELETE", "/notifications/{id:\d+}", []],

    // Subscribe to receive notifications for new job postings based on specific criteria
    ["POST", "/subscriptions/jobs", []],

    // Unsubscribe from notifications for a specific job subscription
    ["DELETE", "/subscriptions/jobs/{id:\d+}", []],

    // Generate a report of job postings based on various criteria (e.g., date range, category)
    ["GET", "/reports/jobs", []],

    // Generate a report of companies based on various criteria (e.g., location, industry)
    ["GET", "/reports/companies", []],

    // Retrieve a list of all job postings (admin access)
    ["GET", "/admin/jobs", []],

    // Retrieve a list of all companies (admin access)
    ["GET", "/admin/companies", []],

    // Retrieve a list of all users (admin access)
    ["GET", "/admin/users", []],

    // Update a user's profile or permissions (admin access)
    ["PUT", "/admin/users/{id:\d+}", []],

    // Delete a user's account (admin access)
    ["DELETE", "/admin/users/{id:\d+}", []]
];
