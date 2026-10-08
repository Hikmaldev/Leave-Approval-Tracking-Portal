# Repository Instructions

## Current state

- Laravel 13 application wired to MySQL (database `leave_portal`), with Blade + Tailwind CSS v4 via Vite. `PRD-Leave-Approval-Tracking-Portal.md` and `design-system-leave-portal.md` remain the product/design source of truth.
- Verified commands (Laragon on Windows, PHP 8.3, MySQL 5.7): `composer install`, `npm install`, `npm run build` (or `npm run dev`), `php artisan migrate --seed`, `php artisan serve` (or the full stack via `composer run dev`), and `php artisan test` (35 tests passing).
- The full feature set is implemented database-to-UI using route → controller → service → model + policy: services in `app/Services`, policies in `app/Policies` (registered in `app/Providers/AppServiceProvider.php`), FormRequests in `app/Http/Requests`, API Resources in `app/Http/Resources`, and page/API controllers in `app/Http/Controllers` and `app/Http/Controllers/Api`.
- Blade views in `resources/views` are wired to real data and server actions. Pages render real empty states when the database is empty; no demo data is hardcoded.
- Development login accounts are seeded by `database/seeders/DatabaseSeeder.php` (development only, password `password`): do not ship or rely on them in production.
- `routes/api.php` (PRD Action Inventory) is registered under `/api` via the `web` middleware group in `bootstrap/app.php` (session auth + CSRF). Sanctum/token auth is a future step; `php artisan install:api` is not required for the current setup.
- `leave_balance_adjustments` (migration + `LeaveBalanceAdjustment` model) is an addition beyond the Data Spec to persist the mandatory FR-BAL-05 adjustment reason as append-only audit history.
- The root-level static HTML/CSS files are kept only as design reference; the implemented UI is the Blade/Tailwind version.
- Update this file when implementation/configuration establishes executable sources of truth.

## Domain constraints to preserve when implementing

- The approval workflow is strictly `pending_supervisor` → `pending_hr` → `approved`; rejection at either step terminates the request, and cancellation is a separate terminal state.
- Deduct leave balance only on final HR approval, inside the same database transaction as the approval; rejected or cancelled requests must not leave a deduction behind.
- Enforce ownership and role checks server-side: supervisors may only act on requests from their assigned direct reports, while HR performs the final decision.
- Rejection requires a comment; approval actions must retain the actor, step, decision, comment, and timestamp as audit history.
- Preserve historical requests and approval actions; deactivate leave types or users rather than deleting records needed by history.
- User-facing statuses must use plain labels such as “Waiting for Supervisor” and “Waiting for HR,” never raw enum values.
- Attachments requiring restricted access must be validated before storage and accessible only to the employee, assigned supervisor, and HR.

## UI/design reference

- Follow `design-system-leave-portal.md` for status colors, approval-chain visibility, rejection-comment visibility, balance previews, and dense approval queues; do not collapse the two pending states into one generic status.

## Mandatory implementation rules

1. Do not hardcode example data in components, pages, or initial state. All data must come from the database through an API or server action.
2. Do not fall back to demo data when data is empty or an error occurs; show an empty state or error state instead.
3. Keep development seed data only in Laravel seeders under `database/seeders/`; define schema changes in Laravel migrations under `database/migrations/` and represent persisted entities with Eloquent models under `app/Models/`.
4. Every button and form must have a handler connected to the API. If it is not connected yet, remove it or mark it disabled with the reason.
5. Detail and list pages must read data by the ID in the URL or the user session, never from global variables or constants.
6. Filter every query by the current user or data owner as required by the authorization rules.
7. After every create, update, or delete mutation, revalidate the related data.
8. End every task with a report stating what was completed, what remains incomplete, and which UI elements are not connected yet.

These rules apply to future implementation work; this project’s planned backend is Laravel with MySQL, migrations, Eloquent models, API/server-side actions, and Laravel seeders—not Prisma or a Next.js backend.
