# Product Requirements Document (PRD)

## Employee Leave and Permission Approval & Tracking Portal

| Item | Detail |
|---|---|
| Document version | 1.0 (Draft) |
| Date | October 6, 2026 |
| Status | Draft for review |
| Tech stack | Laravel, MySQL, Tailwind CSS, RESTful API |

---

## 1. Product Summary

This portal replaces paper forms and WhatsApp messages for employee leave and permission requests. An employee submits a request with an optional attachment, it routes through a two-step approval chain (direct supervisor, then HR), and both the employee and HR can see the status and leave balance at any time without asking around.

## 2. Background and Problem

Small companies often run leave requests informally:

- An employee messages a supervisor on WhatsApp, and the message gets buried in an unrelated group chat.
- A paper form gets signed, then sits on someone's desk before it reaches HR.
- Nobody has a single, current view of who is on leave this week.
- Leave balance is tracked in a personal spreadsheet HR updates by hand, often days late.
- There is no record of when a request was approved, by whom, or why it was rejected.
- Sick leave attachments (doctor's notes) get lost or never make it to a permanent file.

## 3. Goals and Success Metrics

### 3.1 Goals

1. Give every leave or permission request one trackable record, from submission to final decision.
2. Enforce a clear two-step approval chain: direct supervisor, then HR.
3. Keep leave balance accurate and visible to the employee in real time.
4. Notify employees by email whenever their request status changes.
5. Remove reliance on paper and informal chat messages for leave requests.

### 3.2 Success Metrics

| Metric | Target | How to measure |
|---|---|---|
| Requests submitted through the portal vs informal channels | 90%+ within 2 months of rollout | Compare portal submissions to reported offline requests |
| Average time from submission to final decision | Reduced compared to the paper/WhatsApp process | Compare average turnaround before and after |
| Leave balance discrepancies reported by employees | Near zero | Count of balance-related complaints or corrections |
| Requests missing a required attachment (e.g. sick leave) | 0 | Count of requests submitted without a required attachment |
| Email notification delivery success rate | 95%+ | Notification log success rate |

## 4. Scope

### 4.1 In Scope (MVP)

- Employee submits a leave or permission request with type, date range, reason, and optional file attachment.
- Two-step approval chain: direct supervisor reviews first, then HR gives the final decision.
- Each employee has a leave balance per leave type, automatically deducted on final approval and automatically restored on rejection or cancellation.
- Employee can view their leave history and current balance at any time.
- Email notification sent to the employee whenever their request's status changes (approved by supervisor, approved by HR, rejected, or cancelled).
- Supervisor and HR each have a queue of requests waiting for their decision.
- Admin (HR) can configure leave types and the annual quota per leave type.

### 4.2 Out of Scope for MVP

- Integration with payroll systems.
- Mobile native app (web responsive is sufficient).
- Shift scheduling or attendance/clock-in tracking.
- Multi-level approval beyond two steps (e.g. department head, then division head, then HR).
- Leave request on behalf of another employee (HR manually entering a leave for someone).
- Public holiday calendar management (dates are assumed to be configured elsewhere or added manually by HR for MVP).

## 5. Target Users

### 5.1 Persona

**Employee**
- Submits leave or permission requests.
- Wants to know their current leave balance before requesting.
- Wants to see the status of a pending request without asking HR directly.

**Direct Supervisor**
- Reviews requests from their direct reports first.
- Needs a simple approve/reject action with an optional comment.
- Should only see requests from people who report to them.

**HR Staff/Admin**
- Gives the final decision after supervisor approval.
- Manages leave types, quotas, and employee-to-supervisor assignments.
- Needs a company-wide view of who is on leave and when.

## 6. Roles and Access

| Feature | Employee | Supervisor | HR/Admin |
|---|---|---|---|
| Submit a leave/permission request | Yes (own requests) | Yes (own requests) | Yes (own requests) |
| View own request history and balance | Yes | Yes | Yes |
| Approve/reject requests from direct reports | No | Yes | No (unless also a supervisor) |
| Give final approval/rejection (HR step) | No | No | Yes |
| View company-wide leave calendar | No | Limited (own team) | Yes |
| Configure leave types and quotas | No | No | Yes |
| Assign employee-to-supervisor relationships | No | No | Yes |
| Cancel a pending request | Yes (own, before final decision) | No | Yes (any request) |

## 7. Functional Requirements

### 7.1 Authentication and Authorization

| ID | Requirement | Priority |
|---|---|---|
| FR-AUTH-01 | User logs in with email and password. | Must |
| FR-AUTH-02 | System restricts actions and visible data by role (employee, supervisor, HR/admin). | Must |
| FR-AUTH-03 | A supervisor can only view and act on requests from employees assigned to them. | Must |
| FR-AUTH-04 | System supports password reset via email. | Should |

### 7.2 Leave/Permission Request Submission

| ID | Requirement | Priority |
|---|---|---|
| FR-REQ-01 | Employee can create a new request by selecting leave type, start date, end date, and reason. | Must |
| FR-REQ-02 | System calculates the number of days requested automatically from the date range. | Must |
| FR-REQ-03 | Employee can attach a file (e.g. doctor's note) to the request. | Must |
| FR-REQ-04 | System requires an attachment for leave types configured as requiring one (e.g. sick leave). | Must |
| FR-REQ-05 | System checks remaining balance before allowing submission and warns the employee if the request exceeds their available balance. | Must |
| FR-REQ-06 | Employee can cancel their own request while it is still pending (not yet given a final decision). | Should |
| FR-REQ-07 | System prevents submitting a request with an end date before its start date. | Must |

### 7.3 Approval Workflow

| ID | Requirement | Priority |
|---|---|---|
| FR-APR-01 | A new request first appears in the assigned supervisor's approval queue. | Must |
| FR-APR-02 | Supervisor can approve or reject, with an optional comment on approval and a required comment on rejection. | Must |
| FR-APR-03 | Once the supervisor approves, the request moves to HR's approval queue. | Must |
| FR-APR-04 | HR can approve or reject, with an optional comment on approval and a required comment on rejection. | Must |
| FR-APR-05 | A request is only considered fully approved after both supervisor and HR approve it. | Must |
| FR-APR-06 | If either the supervisor or HR rejects, the request is marked rejected and does not proceed further. | Must |
| FR-APR-07 | System logs every approval action with timestamp, actor, and decision. | Must |

### 7.4 Leave Balance

| ID | Requirement | Priority |
|---|---|---|
| FR-BAL-01 | Each employee has a leave balance per leave type, based on their annual quota. | Must |
| FR-BAL-02 | System deducts the requested days from the balance only after full approval (supervisor and HR). | Must |
| FR-BAL-03 | System restores the days to the balance if a fully approved request is later cancelled, or if a pending request is rejected or cancelled before deduction. | Must |
| FR-BAL-04 | Employee can view their current balance per leave type at any time. | Must |
| FR-BAL-05 | HR can view and manually adjust an employee's balance with a required reason. | Should |

### 7.5 Notifications

| ID | Requirement | Priority |
|---|---|---|
| FR-NOT-01 | System sends an email to the employee when their request is approved by the supervisor. | Must |
| FR-NOT-02 | System sends an email to the employee when their request is fully approved by HR. | Must |
| FR-NOT-03 | System sends an email to the employee when their request is rejected, including the comment. | Must |
| FR-NOT-04 | System sends an email to the supervisor when a new request needs their decision. | Should |
| FR-NOT-05 | System sends an email to HR when a request needs their decision. | Should |

### 7.6 History and Reporting

| ID | Requirement | Priority |
|---|---|---|
| FR-HIS-01 | Employee can view a list of all their past and current requests, with status. | Must |
| FR-HIS-02 | Supervisor can view the history of requests from their direct reports. | Should |
| FR-HIS-03 | HR can view and filter all requests company-wide by status, employee, department, or date range. | Must |
| FR-HIS-04 | HR can export a leave report (e.g. CSV) for a selected period. | Could |

## 8. Main User Flows

### 8.1 Submit and Track a Request (Employee)

1. Employee logs in and opens "New Request."
2. Employee selects leave type, date range, and reason, attaches a file if required.
3. System shows the calculated number of days and the remaining balance after this request.
4. Employee submits. The request appears in their history as "Pending Supervisor Approval."
5. Employee receives an email once the supervisor decides, and again once HR gives the final decision.
6. Employee can see the current status at any time on their request history page, without needing to ask.

### 8.2 Two-Step Approval (Supervisor, then HR)

1. Supervisor receives an email and sees the new request in their approval queue.
2. Supervisor reviews the request and attachment, then approves or rejects with a comment.
3. If approved, the request moves to HR's queue and HR is notified.
4. HR reviews and gives the final decision.
5. If HR approves, the balance is deducted and the employee is notified the request is fully approved.
6. If either step rejects, the request stops there, the employee is notified with the comment, and the balance is not touched.

### 8.3 HR Configures Leave Types and Quotas

1. HR opens the leave type settings.
2. HR creates or edits a leave type (name, whether an attachment is required, default annual quota).
3. HR assigns or adjusts an employee's quota if it differs from the default.
4. Changes apply to new requests going forward; already-approved requests are not recalculated retroactively.

## 9. Non-Functional Requirements

### 9.1 Performance

- Approval queue and request history pages load in under 2 seconds for a typical company size (up to a few hundred employees).
- File attachment upload completes in a reasonable time for files up to the configured size limit.

### 9.2 Security

- Passwords are hashed, never stored in plain text.
- All traffic runs over HTTPS.
- Role and ownership checks happen on the server (Laravel policies/middleware), not only in the UI.
- Uploaded attachments are validated by file type and size before storage, and are only accessible to the employee, their supervisor, and HR.

### 9.3 Data Integrity

- A request's status only moves forward through the defined sequence (pending supervisor, pending HR, approved, or rejected at either step). It never skips a step.
- Balance deduction and restoration happen inside a database transaction tied to the approval action, so a request is never left in a state where it's "approved" but the balance wasn't updated, or vice versa.
- Historical requests and their approval actions are never deleted, only ever added to.

### 9.4 Usability

- Non-technical employees can submit a request without training.
- Status is always visible in plain language ("Waiting for Supervisor," "Waiting for HR," "Approved," "Rejected"), not just an internal code.
- Rejection always shows the comment explaining why.

### 9.5 Availability

- Target uptime suitable for an internal business tool used during working hours.
- Daily database backup.

### 9.6 Maintainability

- Clear separation between request handling, approval workflow, balance management, and notifications as distinct Laravel modules/services.
- Automated tests cover the approval sequence and balance calculation logic, since these are the core business rules.

## 10. Technical Architecture

### 10.1 Stack

| Layer | Technology |
|---|---|
| Backend framework | Laravel (PHP) |
| Database | MySQL |
| Frontend styling | Tailwind CSS |
| API | RESTful API (Laravel API routes, used by the frontend views and available for future integrations) |
| Authentication | Laravel's built-in authentication (session-based for the web UI; token-based for API consumers if needed later) |
| Email | Laravel Notifications with a mail driver (e.g. SMTP) |
| File storage | Laravel Storage (local disk for MVP, swappable to S3-compatible storage later) |

### 10.2 Component Overview

```
[Browser: Blade + Tailwind views]
            |
      Laravel Routes/Controllers
            |
   +--------+---------+
   |                   |
[Eloquent Models]  [Notification Layer]
   |                   |
[MySQL Database]   [Mail Driver / SMTP]

[File Attachments] --> [Laravel Storage: local disk, swappable to S3]
```

### 10.3 Core Business Rules

1. A request always has exactly one active status at a time, moving only forward through: `pending_supervisor` → `pending_hr` → `approved`, or terminating early at `rejected_by_supervisor` / `rejected_by_hr` / `cancelled`.
2. Balance is only deducted when a request reaches `approved` (both steps complete), never earlier.
3. A supervisor can only act on requests from employees explicitly assigned to them.
4. Every status change triggers exactly one notification to the employee, and optionally one to the next approver.
5. Leave type configuration changes do not retroactively affect already-submitted requests.

## 11. Data Spec

### 11.1 Entities, Fields, and Types

**User**
| Field | Type | Required |
|---|---|---|
| id | UUID/bigint, primary key | Yes |
| name | string | Yes |
| email | string, unique | Yes |
| password_hash | string | Yes |
| role | enum (`employee`, `supervisor`, `hr_admin`) | Yes |
| supervisor_id | foreign key to User (nullable) | No |
| department | string | No |
| created_at | timestamp | Yes |

**LeaveType**
| Field | Type | Required |
|---|---|---|
| id | bigint, primary key | Yes |
| name | string (e.g. "Annual Leave", "Sick Leave") | Yes |
| requires_attachment | boolean | Yes |
| default_annual_quota | integer (days) | Yes |
| is_active | boolean | Yes |

**LeaveBalance**
| Field | Type | Required |
|---|---|---|
| id | bigint, primary key | Yes |
| user_id | foreign key to User | Yes |
| leave_type_id | foreign key to LeaveType | Yes |
| year | integer | Yes |
| quota | integer (days) | Yes |
| used | integer (days) | Yes |

**LeaveRequest**
| Field | Type | Required |
|---|---|---|
| id | bigint, primary key | Yes |
| user_id | foreign key to User | Yes |
| leave_type_id | foreign key to LeaveType | Yes |
| start_date | date | Yes |
| end_date | date | Yes |
| days_requested | integer | Yes |
| reason | text | Yes |
| status | enum (`pending_supervisor`, `pending_hr`, `approved`, `rejected_by_supervisor`, `rejected_by_hr`, `cancelled`) | Yes |
| created_at | timestamp | Yes |

**Attachment**
| Field | Type | Required |
|---|---|---|
| id | bigint, primary key | Yes |
| leave_request_id | foreign key to LeaveRequest | Yes |
| file_path | string | Yes |
| file_name | string | Yes |
| uploaded_at | timestamp | Yes |

**ApprovalAction**
| Field | Type | Required |
|---|---|---|
| id | bigint, primary key | Yes |
| leave_request_id | foreign key to LeaveRequest | Yes |
| actor_id | foreign key to User | Yes |
| step | enum (`supervisor`, `hr`) | Yes |
| decision | enum (`approved`, `rejected`) | Yes |
| comment | text | No (required by the application layer when decision is `rejected`) |
| decided_at | timestamp | Yes |

**Notification Log** (optional, for tracking delivery per FR-NOT)
| Field | Type | Required |
|---|---|---|
| id | bigint, primary key | Yes |
| leave_request_id | foreign key to LeaveRequest | Yes |
| recipient_id | foreign key to User | Yes |
| type | string (e.g. `supervisor_approved`, `hr_approved`, `rejected`) | Yes |
| sent_at | timestamp | Yes |
| status | enum (`sent`, `failed`) | Yes |

### 11.2 Relationships

| Relationship | Type | Delete Rule |
|---|---|---|
| User → User (supervisor_id) | 1-N (one supervisor has many direct reports) | Restrict (cannot delete a user who is still assigned as someone's supervisor; reassign first) |
| User → LeaveRequest | 1-N (one user has many requests) | Restrict (leave request history must be preserved; deactivate the user instead of deleting) |
| User → LeaveBalance | 1-N (one user has many balance records, one per leave type per year) | Cascade (if a user is permanently removed, their balance records are no longer meaningful on their own) |
| LeaveType → LeaveRequest | 1-N | Restrict (cannot delete a leave type that has existing requests; deactivate with `is_active = false` instead) |
| LeaveType → LeaveBalance | 1-N | Restrict (same reasoning as above) |
| LeaveRequest → Attachment | 1-N (a request can have more than one attachment, though MVP typically uses one) | Cascade (attachments only make sense in the context of their request) |
| LeaveRequest → ApprovalAction | 1-N (one request accumulates one action per step, up to two for MVP) | Cascade (approval actions are meaningless without their parent request, but the request itself is never deleted, so this cascade rarely triggers in practice) |
| LeaveRequest → NotificationLog | 1-N | Cascade |

There are no N-N relationships in this MVP data model. Every relationship is 1-N. If multi-level approval is added later (Phase 2+), the approval chain would move from two fixed steps on `LeaveRequest` to a more flexible N-N style `ApprovalStep` configuration per department, but that is explicitly out of scope for MVP.

### 11.3 Data Ownership

| Data | Owned by | Who can view |
|---|---|---|
| A leave request | The employee who submitted it | The employee, their assigned supervisor, HR |
| Approval actions on a request | The company (audit trail, not personal data of the actor alone) | The employee (their own request's actions), the supervisor involved, HR |
| Leave balance | The employee | The employee (own balance), HR (all balances), supervisor (their direct reports' balances, read-only) |
| Leave type configuration | The company, managed by HR | All roles can view active leave types when submitting a request; only HR can edit |
| Attachments | The employee who uploaded them, tied to their request | The employee, their assigned supervisor, HR |

## 12. Action Inventory

| ID | Page | UI Element | Endpoint | Entity | Success Result | Failure Result |
|---|---|---|---|---|---|---|
| ACT-01 | Login | "Log In" button | `POST /api/login` | User | Session created, redirect to dashboard | Error message "Invalid email or password" |
| ACT-02 | New Request | "Submit Request" button | `POST /api/leave-requests` | LeaveRequest | Request created with status `pending_supervisor`, appears in employee's history | Validation error shown inline (e.g. missing attachment, invalid date range, insufficient balance warning) |
| ACT-03 | New Request | File upload field | `POST /api/leave-requests/{id}/attachments` | Attachment | File attached and listed under the request | Error shown if file type/size invalid |
| ACT-04 | My Requests | "Cancel" button (on a pending request) | `PATCH /api/leave-requests/{id}/cancel` | LeaveRequest | Status changes to `cancelled`, balance restored if previously deducted | Error if request is no longer cancellable (already decided) |
| ACT-05 | Supervisor Approval Queue | "Approve" button | `PATCH /api/leave-requests/{id}/supervisor-decision` (decision=approved) | LeaveRequest, ApprovalAction | Status changes to `pending_hr`, HR notified, request removed from supervisor's queue | Error if request is not in `pending_supervisor` status (already acted on) |
| ACT-06 | Supervisor Approval Queue | "Reject" button | `PATCH /api/leave-requests/{id}/supervisor-decision` (decision=rejected) | LeaveRequest, ApprovalAction | Status changes to `rejected_by_supervisor`, employee notified with comment | Validation error if comment is missing |
| ACT-07 | HR Approval Queue | "Approve" button | `PATCH /api/leave-requests/{id}/hr-decision` (decision=approved) | LeaveRequest, ApprovalAction, LeaveBalance | Status changes to `approved`, balance deducted, employee notified | Error if request is not in `pending_hr` status |
| ACT-08 | HR Approval Queue | "Reject" button | `PATCH /api/leave-requests/{id}/hr-decision` (decision=rejected) | LeaveRequest, ApprovalAction | Status changes to `rejected_by_hr`, employee notified with comment | Validation error if comment is missing |
| ACT-09 | Leave Type Settings | "Save Leave Type" button | `POST /api/leave-types` or `PUT /api/leave-types/{id}` | LeaveType | Leave type created or updated, visible in request form | Validation error if name is empty or quota is invalid |
| ACT-10 | Leave Type Settings | "Deactivate" toggle | `PATCH /api/leave-types/{id}` (is_active=false) | LeaveType | Leave type hidden from new request form, existing requests unaffected | Error if the toggle action fails to save |
| ACT-11 | Employee Balance Page | (page load) | `GET /api/users/{id}/leave-balances` | LeaveBalance | Balance per leave type displayed | Error state shown if the balance cannot be loaded |
| ACT-12 | HR Balance Adjustment | "Adjust Balance" button | `PATCH /api/users/{id}/leave-balances/{leave_type_id}` | LeaveBalance | Balance updated, reason logged | Validation error if reason is missing or value is invalid |
| ACT-13 | HR All Requests | Filter controls (status, employee, date range) | `GET /api/leave-requests?filters...` | LeaveRequest | Filtered list displayed | Empty state shown if no results match |
| ACT-14 | HR All Requests | "Export CSV" button | `GET /api/leave-requests/export` | LeaveRequest | CSV file downloaded | Error message if export fails |
| ACT-15 | Supervisor/HR Assignment | "Assign Supervisor" dropdown | `PATCH /api/users/{id}` (supervisor_id) | User | Supervisor assignment saved, reflected in both users' relationships | Validation error if the assignment would create a circular reporting relationship |

## 13. Acceptance Criteria per Feature

### 13.1 General (Applies Across Features)

- A newly created account with no leave requests yet shows an empty state on "My Requests" (e.g. "You haven't submitted any requests yet" with a clear "New Request" call to action), not a blank page or an error.
- Adding a new leave request appears in the employee's request list and in the supervisor's approval queue immediately, without a manual page refresh.
- An approval decision (by supervisor or HR) immediately removes the request from that approver's queue and updates its status, without a manual page refresh.
- All data (requests, balances, approval history) persists correctly after a page refresh and after navigating to a different menu and back.

### 13.2 Leave/Permission Request Submission

- A new account with no prior requests sees an empty "My Requests" state with a prompt to submit their first request.
- Submitting a valid request adds it to "My Requests" with status "Waiting for Supervisor," visible without refreshing.
- Submitting a request for a leave type that requires an attachment, without attaching a file, is blocked with a clear inline error before submission succeeds.
- The calculated number of days updates automatically and correctly when the employee changes the start or end date, before submission.
- After refreshing the page or navigating away and back, the submitted request and its status are still visible and unchanged.

### 13.3 Two-Step Approval Workflow

- A supervisor with no pending requests sees an empty state on their approval queue (e.g. "No requests waiting for your approval").
- A new request from a direct report appears in the supervisor's queue without a manual refresh.
- Approving as supervisor moves the request out of the supervisor's queue and into HR's queue immediately.
- Rejecting (at either step) without entering a comment is blocked with a validation message; the action does not proceed until a comment is provided.
- After HR gives the final approval, the request's status updates to "Approved" everywhere it's displayed (employee's history, HR's request list) without requiring a refresh.
- Refreshing the page or switching menus and returning still shows the correct, latest status and the full approval history for the request.

### 13.4 Leave Balance

- A new employee account shows their configured starting balance per leave type, even with zero requests submitted yet. No empty or broken balance display.
- Submitting a request does not change the balance until the request is fully approved by both supervisor and HR.
- Once fully approved, the balance's "used" days update immediately and are reflected on the employee's balance view without a manual refresh.
- Cancelling a fully approved request restores the balance immediately and visibly.
- Rejecting a request at either step does not change the balance at all, since deduction only ever happens on full approval.
- Balance figures remain accurate and consistent after a page refresh and after navigating between menus.

### 13.5 Notifications

- An employee with no status changes yet has no notification history to show (if a notification log view exists, it shows an appropriate empty state).
- An email is sent to the employee immediately after the supervisor's decision, and again after HR's decision, with content matching the actual decision and comment.
- A failed email send is logged (per the Notification Log entity) rather than silently disappearing, so HR can confirm whether a notification went out.

### 13.6 Leave Type and Quota Configuration

- A freshly set up company account with no leave types configured shows an empty state on the Leave Type Settings page, prompting HR to create the first leave type.
- Adding a new leave type makes it immediately available in the employee's "New Request" leave type dropdown, without a manual refresh.
- Deactivating a leave type removes it from the dropdown for new requests but does not alter or hide any existing requests that already used it.
- Changes to leave type settings persist correctly after a page refresh and after navigating away and back.

## 14. UI Requirements

### 14.1 Main Screens

| Screen | Main Content |
|---|---|
| Login | Email and password form |
| Employee Dashboard | Current balance summary, recent request status, "New Request" button |
| New Request Form | Leave type, date range, reason, attachment upload, calculated days, balance preview |
| My Requests | List of all requests with status badges |
| Supervisor Approval Queue | List of pending requests from direct reports, approve/reject actions |
| HR Approval Queue | List of requests pending HR decision, approve/reject actions |
| HR All Requests | Company-wide filterable list, export option |
| Leave Type Settings | List of leave types, create/edit form, active/inactive toggle |
| Balance Management (HR) | Per-employee balance view and manual adjustment |

### 14.2 Design Principles

- Status is shown in plain language everywhere a request appears, not just an internal code.
- The approval queue shows only what the approver needs to decide: requester, type, dates, reason, attachment link. No unrelated company-wide noise.
- Balance is visible to the employee before they submit, not only after, so they don't find out they're over quota after the fact.

## 15. Release Plan

| Phase | Scope | Estimated Duration |
|---|---|---|
| Phase 0 | Data model design, auth setup, project scaffolding | 1 to 2 weeks |
| Phase 1 (MVP) | Request submission, two-step approval, balance tracking, email notifications, history views | 4 to 5 weeks |
| Phase 2 | CSV export, HR manual balance adjustment, supervisor team history view | 1 to 2 weeks |
| Phase 3 (optional) | Multi-level approval chains, payroll integration, holiday calendar | To be scoped later |

## 16. Testing Strategy

- **Unit tests:** balance calculation, approval status transitions, date range and days-requested calculation.
- **Integration tests:** full flow from request submission through supervisor approval, HR approval, and balance deduction.
- **Access control tests:** verify a supervisor cannot act on or view requests outside their assigned direct reports, verified at the API level, not just hidden in the UI.
- **UAT:** an employee, a supervisor, and an HR user run through a full request cycle, including a rejection path and a cancellation path.

## 17. Risks and Mitigation

| Risk | Impact | Mitigation |
|---|---|---|
| Supervisor-employee assignments are wrong or outdated | Requests go to the wrong approver or nobody | HR has a clear screen to manage and audit assignments; validate on assignment that no employee is left without a supervisor |
| Employees distrust the system and keep using WhatsApp as backup | Low adoption, defeats the purpose | Clear internal rollout communication; make the portal visibly faster than the old process |
| Email notifications fail silently (spam filters, wrong address) | Employees miss status updates | Log every notification attempt; let HR check delivery status |
| Balance miscalculation due to a bug in the deduction/restoration logic | Employee trust in the system breaks quickly | Heavy test coverage specifically on balance logic, since this is the most consequential calculation in the app |

## 18. Assumptions and Dependencies

**Assumptions**
- Each employee has exactly one direct supervisor at a time for MVP.
- Leave quotas reset annually; the exact reset mechanism (automatic on a fixed date vs HR-triggered) will be confirmed during Phase 0.
- Company size is small enough that a single HR approval step (not multiple HR reviewers in parallel) is sufficient.

**Dependencies**
- HR needs to provide the initial list of employees, their supervisors, and department structure before rollout.
- An SMTP provider or transactional email service needs to be set up for Laravel Notifications to send real emails.

## 19. Future Enhancements (Backlog)

- Multi-level approval chains for specific departments or leave types.
- Mobile app or PWA for on-the-go request submission and approval.
- Integration with a payroll or HRIS system.
- Company-wide leave calendar view (who's out, when).
- In-app notifications in addition to email.
- Delegate approval (a supervisor assigns a backup approver while on leave themselves).

## 20. Open Questions

1. Does annual quota reset automatically on a fixed date, or does HR trigger it manually each year?
2. Can an employee have more than one supervisor (e.g. matrix reporting), or is it strictly one-to-one for MVP?
3. What file types and size limit should be enforced for attachments?
4. Should a supervisor who is also an HR admin see requests from their own direct reports differently, or does the two-step flow still apply even when the same person holds both roles?

## 21. Glossary

| Term | Meaning |
|---|---|
| Leave type | A category of leave or permission (e.g. Annual Leave, Sick Leave, Unpaid Leave) |
| Quota | The number of days an employee is allotted per leave type per year |
| Pending supervisor | A request waiting for the direct supervisor's decision |
| Pending HR | A request that passed supervisor approval and is waiting for HR's final decision |
| Approval chain | The sequence of approvers a request must pass through before being fully approved |
