# JobMarketSV Laravel Migration

Laravel 12 migration target for JobMarketSV. This application is developed in parallel with the existing PHP system so the production website and its data remain recoverable throughout the migration.

## Current scope

- Read-only connection to a cloned legacy MySQL database.
- Legacy-compatible Eloquent models for users, companies, jobs, and job locations.
- Read-only `/viec-lam` listing with keyword, city, shift, sorting, and pagination.
- `/migration-status` health endpoint.
- Reused JobMarketSV Emerald UI assets.

## Local setup

1. Create a database clone named `jobmarket_laravel_test`.
2. Create a MySQL account with `SELECT` access to that database only.
3. Copy `.env.example` to `.env`, configure the read-only account, and run `php artisan key:generate`.
4. Run `composer install` and `php artisan serve --port=8001`.
5. Open `http://127.0.0.1:8001/viec-lam`.

Read [MIGRATION_SAFETY.md](MIGRATION_SAFETY.md) before changing the database connection or adding migrations.
