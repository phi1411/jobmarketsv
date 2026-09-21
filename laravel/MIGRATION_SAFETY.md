# JobMarketSV Laravel Migration Safety

This Laravel application is a parallel migration target. The legacy PHP application remains the production source of truth.

## Non-negotiable rules

- Never point local development or automated tests at the production database.
- Never run `migrate:fresh`, `migrate:refresh`, `migrate:reset`, `db:wipe`, rollback commands, or destructive SQL against legacy data.
- The initial Laravel connection uses a MySQL account with `SELECT` permission only.
- Schema changes must be additive, reviewed, backed up, and tested against a cloned database first.
- Production cutover is forbidden until account, OAuth, job, application, CV, upload, and location integrity checks pass.

## Current phase

- Laravel 12 is installed under `laravel/`.
- `/viec-lam` and `/migration-status` read from the cloned legacy database.
- No write workflow has been migrated.
- The legacy application and production deployment remain unchanged.
