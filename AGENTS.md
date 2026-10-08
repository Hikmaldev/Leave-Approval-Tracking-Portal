# Repository Instructions

## Current state

- Laravel 13 application wired to MySQL (database `leave_portal`), with Blade + Tailwind CSS v4 via Vite. `PRD-Leave-Approval-Tracking-Portal.md` and `design-system-leave-portal.md` remain the product/design source of truth.
- Verified commands (Laragon on Windows, PHP 8.3, MySQL 5.7): `composer install`, `npm install`, `npm run build` (or `npm run dev`), `php artisan migrate --seed`, `php artisan serve` (or the full stack via `composer run dev`), and `php artisan test` (35 tests passing).
- The full feature set is implemented database-to-UI using route → controller → service → model + policy: services in `app/Services`, policies in `app/Policies` (registered in `app/Providers/AppServiceProvider.php`), FormRequests in `app/Http/Requests`, API Resources in `app/Http/Resources`, and page/API controllers in `app/Http/Controllers` and `app/Http/Controllers/Api`.
- Blade views in `resources/views` are wired to real data and server actions. Pages render real empty states when the database is empty; no demo data is hardcoded.
- Development login accounts are seeded by `database/seeders/DatabaseSeeder.php` (development only, password `password`): do not ship or rely on them in production. The login screen's one-click demo autofill is on in `local` and gated by `DEMO_LOGIN_AUTOFILL=true` elsewhere; the hosted portfolio demo sets it to `true` until real accounts replace the demo ones.
- `routes/api.php` (PRD Action Inventory) is registered under `/api` via the `web` middleware group in `bootstrap/app.php` (session auth + CSRF). Sanctum/token auth is a future step; `php artisan install:api` is not required for the current setup.
- `leave_balance_adjustments` (migration + `LeaveBalanceAdjustment` model) is an addition beyond the Data Spec to persist the mandatory FR-BAL-05 adjustment reason as append-only audit history.
- TestSprite E2E suite exists under `tests/testsprite/plans/` (project `e48ff857-3eda-4c80-a7b1-bdd3d1146469`, local port 8123) with a dedicated dataset in `database/seeders/TestDatasetSeeder.php` (dev only). Re-run sequence and results are documented in `IMPLEMENTATION-REPORT.md` → "TestSprite E2E verification".
- Production deployment is containerized: the root `Dockerfile` targets `app` (PHP-FPM + migrations), `web` (Nginx static/pass), and `paas` (single-container Nginx + PHP-FPM listening on `$PORT`, the default final stage for Railway/Koyeb/Render/Northflank); `docker-compose.yml` runs app/web/worker/scheduler/db with `app-storage` + `db-data` volumes and configs live in `docker/`. Guides: `DEPLOY-PAAS.md` and `DEPLOY-DOCKER-ORACLE.md`. Images build for amd64/arm64.
- The implemented UI is the Blade/Tailwind version only; the static HTML/CSS design mockups have been removed from the repo (`design-system-leave-portal.md` and the PRD remain the design source of truth).
- Update this file when implementation/configuration establishes executable sources of truth.

## Domain constraints to preserve when implementing

- The approval workflow is strictly `pending_supervisor` → `pending_hr` → `approved`; rejection at either step terminates the request, and cancellation is a separate terminal state.
- Deduct leave balance only on final HR approval, inside the same database transaction as the approval; rejected or cancelled requests must not leave a deduction behind.
- Requested days count only working days (Monday–Friday); weekends are excluded. A range with no working days is rejected. Keep the shared calculation (`App\Support\WorkingDayCalculator`), the submission validation (`StoreLeaveRequestRequest`), and the request form's live preview (`resources/js/app.js`) in sync.
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

<!-- BEGIN TESTSPRITE AGENT SECTION (testsprite agent install codex) -->
<!-- testsprite-skill: testsprite-verify+testsprite-onboard v0.14.0 sha256:74bfa300e213 -->
# TestSprite Verification Loop

Run relevant tests and inspect failures before reporting. Skip docs/build-config
edits; missing credentials mean unverified. Honor the user's CLI/MCP choice.

## 1. Preflight and project

```bash
testsprite --version
testsprite auth status
```

If missing, advise CLI install or `testsprite setup`. Find the project via
`$TESTSPRITE_PROJECT_ID`, `.testsprite/config.json`, then `testsprite project list
--output json`; ask if several match.

For a local app without a project:

```bash
testsprite project create --type frontend --name "<repo name>" --local <port> --local-host <host>
testsprite test create --plan-from plan.json --project <projectId>
testsprite test run <id> <id> --local <port> --local-host <host> --output json
```

Local projects need V3 (V2-only: exit 7, `local-origin-requires-v3`). Exploration
and `test plan generate` exit 6 before charge. Create plans with `test create
--plan-from` without `--run`; then run several ids with `--local`. Portal runs
are blocked free until `project update <id> --url https://…` sets a public URL.
For deployed projects use `project create --url`; empty suites can use `test plan
generate --project <id>` then `test plan accept`.

## 2. Run against the change

The CLI does not host apps. Create a deployed environment once; select it with
`--env <name>` or omit for Default. Unknown names list valid names. Use `--local
<port>` for local FE runs.

```bash
# Deployed frontend: create + run, or run an existing test
testsprite project env create <id> --name staging --url https://staging.example.com
testsprite test create --plan-from plan.json --run --wait \
  --env staging --timeout 600 --output json
testsprite test run <test-id> --env staging \
  --wait --timeout 600 --output json
# Backend Python assertion
testsprite test create --type backend --name "Login rejects empty password" \
  --project <id> --code-file /tmp/test.py --run --wait --timeout 600
# Deployed replay (V3 FE: 0.5 credit)
testsprite test rerun <test-id> --env staging --wait --output json
# Dependency batch; optional --filter <substring>
testsprite test run --all --project <id> --env staging --wait --output json
```

- Supported backends grant existing keys `run:tunnel`; a narrowed key without it
  exits 3. Free-plan local runs work; V3 FE costs 0.5 credit. BE cannot use `--local`.
- `--local` implies `--wait` (1200 s per run; ordinary: 600). `--local-host`:
  `localhost`, `127.0.0.1` (default), `::1`. A dead port exits 5 before charge;
  `--skip-preflight` skips the probe. Environment `--url` rejects private hosts.
  `--local` selects/creates `local-<port>`; a named `--env` must be local.
  `--target-url` cannot combine with `--local`.
- Run local ids together (`test run <id> <id> … --local <port>`); `--all --project
<id> --local <port>` skips BE tests. One tunnel, 5 concurrent runs by default
  (`--max-concurrency` 1–10). Five live bindings per user; `tunnel_binding_limit`
  exits 11 without retry. Use `tunnel list` then `tunnel stop <id>` or `--all --confirm`.
- Keep early **stderr** `Run <runId>` receipts and any `Dashboard: <url>`.
  Stdout is the JSON channel.
- An **owned** local timeout, Ctrl-C, or poll failure requests cancellation and
  closes the tunnel. Before a terminal V3 FE verdict, the backend attempts a refund;
  check `refund.status`: cancellation does not guarantee a refund.
  `--no-cancel-on-interrupt` detaches, but the owned tunnel still closes.
- After an owned timeout, start a **new** `test run <test-id> --local <port>
--timeout 1800` (retain `--local-host`). `test wait` cannot reopen the tunnel;
  ordinary/adopted waits can resume with `test wait <run-id>` if reachable.
- Keep `tunnel start` alive; borrow with `--local <port> --tunnel-client <uuid>`.
  Adopted runs normally detach without cancellation or closing the owner's tunnel.
  If the owner has disappeared during a run, cancellation is requested by default;
  `--no-cancel-on-interrupt` skips it. A second connection with the same credential
  takes over; the first exits **10**.
- Loopback environments need `--local <port>`. Bare CLI runs selecting one by
  name or Default exit 6 (`tunnel-required`) before dispatch or charge. The Portal
  shows no Run; backend requests are refused free. Schedules and test lists get
  free BLOCKED results. A public `--env` or Default works regardless of earlier
  tunnel runs. V3 local runs use the agent path, never saved-code replay, and
  preserve saved code.
- `--wait` handles polling/backoff; do not wrap it in a retry loop.
- Backend Python runs top-to-bottom, not via pytest: call `test_*` functions.
  The sandbox has stdlib, `requests`, `pytest`, `numpy`, `scipy`; use HTTP,
  not project imports or uninstalled packages.
- Backend `--produces`/`--needs` are repeatable; `--category teardown` marks cleanup.
  Set dependencies with `test create` or edit with `test update`; do not delete
  and recreate. Use `test run --all` for producer → consumer → teardown ordering
  and variable passing. A BE `test rerun` includes that closure and its side effects;
  `--skip-dependencies` selects only the named test. Triage failed producers before
  blaming consumers starved of their token/fixture.

## 3. Inspect and report

```bash
testsprite test artifact get <run-id> --out ./.testsprite/runs/<run-id>/
testsprite test steps <test-id> --run-id <run-id> --output json
```

Read failing steps, screenshots and root cause. Bare `test steps <test-id>`
shows the latest run; pin the receipt's id. For empty latest steps, choose an
earlier run from `test result <test-id> --history`. Report verdict and dashboard.

Exits: 0 passed; 1 failed/blocked/cancelled; 3 auth/scope; 4 not found;
5 validation; 6 conflict/precondition; 7 timeout/unsupported; 10 unavailable;
11 rate limited; 12 insufficient credits.

## Dry-run and setup

`--dry-run` works without credentials:

```bash
testsprite test run <test-id> --dry-run --output json
testsprite test create --plan-from plan.json --dry-run --output json
```

Setup: `npm install -g @testsprite/testsprite-cli`; `testsprite setup`.

**First-time setup:** if this repo has no TestSprite tests yet, seed a *broad* first suite across its main user flows — not just one test — each with a concrete, observable assertion, before reporting setup as done.
<!-- END TESTSPRITE AGENT SECTION -->
