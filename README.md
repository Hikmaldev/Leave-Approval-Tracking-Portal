<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://laravel.com/docs/13.x"><img src="https://img.shields.io/badge/Laravel-13.x-FF2D20?style=flat-square&logo=laravel&logoColor=white" alt="Laravel 13"></a>
<a href="https://www.php.net/releases/8.3/en.php"><img src="https://img.shields.io/badge/PHP-8.3-777BB4?style=flat-square&logo=php&logoColor=white" alt="PHP 8.3"></a>
<a href="https://tailwindcss.com/"><img src="https://img.shields.io/badge/Tailwind_CSS-v4-06B6D4?style=flat-square&logo=tailwindcss&logoColor=white" alt="Tailwind CSS v4"></a>
<a href="https://www.mysql.com/"><img src="https://img.shields.io/badge/MySQL-5.7-4479A1?style=flat-square&logo=mysql&logoColor=white" alt="MySQL 5.7"></a>
<a href="https://opensource.org/licenses/MIT"><img src="https://img.shields.io/badge/License-MIT-green.svg" alt="MIT License"></a>
</p>

# Leave Approval Tracking Portal

A Laravel-based employee leave and permission approval portal. The application replaces paper forms and informal chat messages with a trackable workflow where employees submit requests, supervisors review them, and HR gives the final decision.

The project follows the product requirements in [`PRD-Leave-Approval-Tracking-Portal.md`](PRD-Leave-Approval-Tracking-Portal.md) and the visual rules in [`design-system-leave-portal.md`](design-system-leave-portal.md).

## About the application

The portal provides:

- Session-based authentication with employee, supervisor, and HR/admin roles.
- Leave and permission request submission with date calculation, balance checks, reasons, and attachments.
- A strict two-step approval workflow: supervisor approval followed by HR approval.
- Leave balance tracking with deduction only after final HR approval.
- Request history, approval audit actions, rejection comments, and cancellation handling.
- Email notification service integration with notification delivery logging.
- HR leave-type configuration and employee balance adjustments with append-only reasons.
- Server-rendered Blade pages and REST-style API endpoints backed by the same service and policy layers.
- Real empty and error states; the UI does not fall back to demo data.

## Technology stack

- **Backend:** Laravel 13, PHP 8.3
- **Database:** MySQL 5.7
- **Frontend:** Blade, Tailwind CSS v4, Vite
- **Authentication:** Laravel session authentication
- **Storage:** Laravel filesystem for restricted attachments
- **Testing:** PHPUnit through Laravel's `php artisan test`

## Requirements

Before installing, ensure the local environment provides:

- PHP 8.3 or newer supported by the project
- Composer
- Node.js and npm
- MySQL 5.7 or compatible MySQL server
- Laragon on Windows is the currently verified development environment

## Installation

### 1. Install dependencies

```bash
composer install
npm install
```

### 2. Create the environment file

```bash
copy .env.example .env
php artisan key:generate
```

On non-Windows systems, use `cp .env.example .env` instead of `copy`.

### 3. Create and configure the database

Create a MySQL database named `leave_portal`, then verify the database values in `.env`:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=leave_portal
DB_USERNAME=root
DB_PASSWORD=
```

Use the credentials for the local MySQL installation when they differ from the example values.

### 4. Run migrations and development seeders

```bash
php artisan migrate --seed
```

The seeder creates development-only login accounts. It does not seed leave requests, leave types, balances, or other demo business data, so empty database states remain visible during development.

## Running the application

Build the frontend assets:

```bash
npm run build
```

Start the Laravel server:

```bash
php artisan serve
```

The application is then available at `http://localhost:8000` unless `APP_URL` or the server port is changed.

For the full local development stack—Laravel server, queue listener, log viewer, and Vite hot-reload server—run:

```bash
composer run dev
```

To use Vite separately during frontend work:

```bash
npm run dev
```

## Development accounts

These accounts are created by `database/seeders/DatabaseSeeder.php` for local development only. All use the password `password`.

| Role | Email |
|---|---|
| HR admin | `hr@leave-portal.test` |
| Supervisor | `supervisor@leave-portal.test` |
| Employee | `employee@leave-portal.test` |

Do not ship these credentials or rely on them in production.

## Testing

Run the complete test suite with:

```bash
php artisan test
```

The verified suite covers authentication, request submission, the supervisor-to-HR approval workflow, balance behavior, authorization, and the API action inventory.

For a focused feature test:

```bash
php artisan test tests/Feature/LeaveWorkflowTest.php
```

For a single test method, use PHPUnit's filter:

```bash
php artisan test --filter=test_name_here
```

## Application structure

The main request flow is:

```text
Route
  -> Controller
    -> FormRequest / Policy
      -> Service
        -> Eloquent Model / Database
          -> Blade view or API Resource
```

Important locations:

- `app/Http/Controllers` — server-rendered page controllers.
- `app/Http/Controllers/Api` — REST-style API controllers.
- `app/Http/Requests` — validation and authorization-aware form requests.
- `app/Http/Resources` — API response resources.
- `app/Models` — Eloquent models and relationships.
- `app/Policies` — server-side ownership and role authorization.
- `app/Services` — request workflow, approval, balance, notification, attachment, and leave-type logic.
- `database/migrations` — database schema changes.
- `database/seeders` — development-only seed data.
- `resources/views` — Blade UI and reusable components.
- `resources/css` and `resources/js` — Tailwind/Vite assets.
- `routes/web.php` — session-based Blade routes.
- `routes/api.php` — API equivalents of the PRD action inventory.

## API notes

API routes are mounted under `/api` and currently use the `web` middleware group so the session-authenticated Blade UI can use the same server-side authorization and CSRF behavior. Sanctum/token authentication is not required for the current setup and remains a future step.

Examples of available API areas include:

- `POST /api/login`
- `GET|POST /api/leave-requests`
- `GET /api/leave-requests/{leaveRequest}`
- `PATCH /api/leave-requests/{leaveRequest}/supervisor-decision`
- `PATCH /api/leave-requests/{leaveRequest}/hr-decision`
- `GET /api/users/{user}/leave-balances`
- `POST|PUT /api/leave-types`

Authorization must remain enforced on the server. Queries must be scoped to the authenticated user, direct reports, or HR visibility rules rather than relying on UI filtering.

## Core business rules

- A request moves from `pending_supervisor` to `pending_hr` and then to `approved`.
- Rejection at either approval step is terminal; cancellation is a separate terminal state.
- Leave balance is deducted only when HR gives final approval, in the same database transaction as the approval action.
- Rejection always requires a comment and approval actions retain the actor, step, decision, comment, and timestamp.
- Historical requests and approval actions are preserved. Leave types and users should be deactivated rather than removed when history depends on them.
- User-facing status labels must be plain language, such as “Waiting for Supervisor” and “Waiting for HR,” rather than internal enum values.
- Attachments are validated before storage and are accessible only to the employee, assigned supervisor, and HR.

## Documentation

- [Product Requirements Document](PRD-Leave-Approval-Tracking-Portal.md)
- [Design System](design-system-leave-portal.md)
- [Laravel Documentation](https://laravel.com/docs)
- [Tailwind CSS Documentation](https://tailwindcss.com/docs)

## Agentic development

Repository-specific instructions for OpenCode and other coding agents are maintained in [`AGENTS.md`](AGENTS.md). Read that file before making implementation changes. In particular, do not hardcode demo data in UI components, do not use demo fallbacks for empty/error states, and report any incomplete or unconnected UI at the end of each task.

## Contributing

Before submitting changes:

1. Run the relevant focused test(s).
2. Run the complete suite with `php artisan test`.
3. Build frontend assets with `npm run build` when frontend files changed.
4. Confirm migrations, authorization policies, and UI actions remain connected to real persistence and server handlers.

## Security vulnerabilities

If you discover a security vulnerability, do not open a public issue with exploit details. Contact the project maintainers privately so the issue can be investigated and fixed responsibly.

## License

Copyright © 2026 Hikmaldev. All rights reserved.

This application is open-sourced software licensed under the [MIT License](https://opensource.org/licenses/MIT).
