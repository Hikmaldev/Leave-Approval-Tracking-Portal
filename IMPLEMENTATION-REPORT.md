# Implementation Report — Leave Approval Tracking Portal

End-to-end implementation (route → controller → service → model + policy) of the
PRD's MVP feature set. Updated after the final verification run.

## Architecture

- **Services** (`app/Services`) hold the business rules:
  - `LeaveRequestService` — submit, attachment add, cancel, day calculation, role-scoped list queries, HR filters.
  - `ApprovalService` — supervisor/HR decisions in transactions, HR balance deduction inside the approval transaction.
  - `LeaveBalanceService` — balance provisioning, locked deduct/restore, HR adjustment with audit.
  - `AttachmentService` — private-disk storage and secured download.
  - `LeaveTypeService` — create/update/activate-deactivate.
  - `NotificationService` — status-change email with sent/failed logging.
  - `UserService` — supervisor assignment.
- **Policies** (`app/Policies`, registered in `AppServiceProvider`): `LeaveRequestPolicy`,
  `LeaveBalancePolicy`, `LeaveTypePolicy`, `UserPolicy`, `AttachmentPolicy`.
- **Web controllers** handle Blade pages and server-rendered form submissions (redirect + flash).
- **API controllers** (`app/Http/Controllers/Api`) implement the PRD Action Inventory, mounted under
  `/api` through the `web` middleware group in `bootstrap/app.php` (session auth + CSRF).

## Feature coverage (PRD Action Inventory)

| ACT | Feature | Web | API |
|---|---|---|---|
| 01 | Login | `/login` | `POST /api/login` |
| 02 | Submit request | `POST /requests` | `POST /api/leave-requests` |
| 03 | Attachment upload | `POST /requests/{id}/attachments` | `POST /api/leave-requests/{id}/attachments` |
| 04 | Cancel request | `PATCH /requests/{id}/cancel` | `PATCH /api/leave-requests/{id}/cancel` |
| 05/06 | Supervisor approve/reject | `PATCH /approvals/{id}/supervisor` | `.../supervisor-decision` |
| 07/08 | HR approve/reject | `PATCH /hr/approvals/{id}/hr` | `.../hr-decision` |
| 09/10 | Leave type create/update/toggle | `/hr/leave-types` | `/api/leave-types` |
| 11 | View balances | `/balances` | `GET /api/users/{id}/leave-balances` |
| 12 | Balance adjustment (reason required) | `PATCH /hr/balances/{user}/{type}` | same under `/api` |
| 13 | Filtered lists (role-scoped) | list pages | `GET /api/leave-requests` |
| 14 | CSV export | `GET /hr/requests/export` | `GET /api/leave-requests/export` |
| 15 | Assign supervisor | API only | `PATCH /api/users/{id}` |

Secured attachment download: web `GET /attachments/{id}`, API `GET /api/attachments/{id}`.

## Business rules enforced

- Workflow is strictly `pending_supervisor` → `pending_hr` → `approved`; rejection terminates;
  cancellation is a separate terminal state.
- Balance is deducted only on final HR approval, inside the same transaction as the status change,
  with `lockForUpdate` on the request and balance rows; restored on cancellation of an approved request.
- Rejection requires a comment (FormRequest + modal required field).
- Approval actions keep actor, step, decision, comment, and timestamp.
- Statuses render plain labels ("Waiting for Supervisor", "Waiting for HR").
- Ownership/role checks are server-side (policies + FormRequests), including "supervisor only acts on
  assigned direct reports" and "only owners see their requests".
- Attachments stored on the private `local` disk; access limited to owner, assigned supervisor, and HR.
- Balanced/notifications: every status change emails the employee; new requests notify the supervisor;
  supervisor approval notifies HR. Delivery outcome is written to `notification_logs`.

## Data / schema additions

- `leave_balance_adjustments` (migration `2026_10_07_000008` + `LeaveBalanceAdjustment` model): the Data
  Spec had no place for the mandatory FR-BAL-05 adjustment reason, so it is persisted as append-only
  audit history (previous/new quota and used, actor, reason).

## Verification

- `php artisan test` → **36 passed (114 assertions)** across `AuthenticationTest`,
  `LeaveWorkflowTest` (18 cases), and `ApiActionInventoryTest` (11 cases), plus the skeleton tests.
- `npm run build` → success (Vite 7, Tailwind v4).
- Live HTTP + MySQL end-to-end smoke test: HR created a leave type, employee submitted a request,
  supervisor approved, HR approved; balance moved `12 → used 3 → remaining 9`; 2 approval actions and
  4 notification logs recorded; balance page showed `9 / 12`; HR All Requests and CSV export rendered;
  HR adjustment persisted and wrote the audit row. Smoke-test data was removed afterwards.
- Database left in its seeded state (login accounts only), so every page shows its real empty state.

## Mandatory-rules audit (ATURAN WAJIB)

Re-audit of `resources/views`, `app/`, and `database/seeders` against the four mandatory rules.

| # | Check | Result | Action |
|---|---|---|---|
| 1 | Hardcoded / demo data in views, app, seeders | No demo/business data. Seeders contain dev login accounts only (documented, rule 3). Dashboard mini-chain is static workflow copy, not data. | None |
| 2 | Buttons, forms, links without route/controller | Every `<form>` has an `action()`, every link uses `route()`; no `href="#"`/`url('/...')`; no disabled stubs remain. | None |
| 3 | Eloquent queries not filtered by owner | **Real bug:** `GET /api/leave-requests/export` ran the unscoped company-wide query behind `viewAny` (always true), so any authenticated employee could export all records. | Fixed |
| 4 | Views without empty state | All 6 `@forelse` have `@empty`; all list `@if isEmpty()` branches render an empty state or a configuration prompt. | None |

Fixes applied:

- `LeaveRequestPolicy::export()` (HR only) and both export handlers now authorize `export`; the API export
  route also carries `role:hr_admin`. Regression test `test_act_14_export_is_restricted_to_hr` covers it.
- `LeaveBalancePolicy::manageAny()` and `LeaveTypePolicy::manageAny()` (HR only) now guard the company-wide
  balance screen and leave-type settings, instead of relying on the always-true `viewAny` plus route role.

## Not implemented / open decisions

- **Token auth (Sanctum)** is not installed; the API uses session auth under `/api`. `php artisan
  install:api` would be the step to add token auth for external consumers.
- **HR/Admin own requests**: HR users have no supervisor, so an HR-submitted request would sit at
  `pending_supervisor` with no approver. This is the unresolved product question from the PRD.
- **Balance-exceed policy**: submission is *blocked* when it exceeds the remaining balance (validation);
  the PRD lists "warn vs block" as an open decision.
- **Email delivery** is synchronous with the `log` mailer in development; queuing/SMTP is deployment work.
- **Pagination**: API list endpoints paginate; web list pages render full collections (small MVP volumes).
- **Supervisor assignment UI**: ACT-15 exists only as an API endpoint (no PRD screen defines a UI for it).
- **Supervisor team history view** (PRD Phase 2) is out of MVP scope.
