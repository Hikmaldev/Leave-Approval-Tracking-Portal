# Design System

## Employee Leave and Permission Approval & Tracking Portal

| Item | Detail |
|---|---|
| Document version | 1.0 (Draft) |
| Date | October 6, 2026 |
| Companion document | PRD-Leave-Approval-Tracking-Portal.md |
| Built for | Laravel (Blade views), Tailwind CSS |

---

## 1. Overview

Three different people open this app with three different questions in mind. An employee asks "where is my request right now, and how many days do I have left." A supervisor asks "what's waiting for me to decide." HR asks "is everything moving the way it should, company-wide." The design system exists to answer each of those questions in under five seconds, without a tutorial.

Guiding principles:

1. **Status language is plain, never a code.** "Waiting for Supervisor," never `pending_supervisor` shown raw in the UI.
2. **The two-step approval chain is always visible as a chain**, not just a single status word. A user should see both steps and know exactly where a request sits between them.
3. **Balance is a number people trust.** No ambiguity about whether a pending request already counts against it.
4. **Rejection always shows its reason.** A red badge with no comment next to it is treated as an incomplete screen, not a finished one.

This is an internal HR tool. It needs to feel calm and procedural, like something from a well-run office, not a consumer app competing for attention.

## 2. Colors

### 2.1 Core Palette

| Token | Hex | Tailwind class | Usage |
|---|---|---|---|
| `brand-700` | `#0F4C3A` | `bg-brand-700` | Primary buttons, active nav, header |
| `brand-600` | `#166A50` | `bg-brand-600` | Hover state for primary actions |
| `brand-100` | `#DCEFE6` | `bg-brand-100` | Selected rows, subtle highlights |
| `neutral-900` | `#1C1F24` | `text-neutral-900` | Primary text |
| `neutral-600` | `#5C6370` | `text-neutral-600` | Secondary text, labels |
| `neutral-300` | `#D6D9DE` | `border-neutral-300` | Borders, dividers |
| `neutral-100` | `#F5F6F8` | `bg-neutral-100` | Page background |
| `neutral-0` | `#FFFFFF` | `bg-white` | Card and surface background |

A dark green was chosen for brand color deliberately, distinct from the amber/red/green status set below, so the primary action color never gets mistaken for a status signal.

### 2.2 Status Colors (Request Lifecycle)

These map directly to the `LeaveRequest.status` values in the PRD's Data Spec. Each status gets exactly one color, used nowhere else in the app.

| Status (internal) | Label shown to user | Token | Tailwind class |
|---|---|---|---|
| `pending_supervisor` | "Waiting for Supervisor" | `status-pending-1` | `bg-amber-100 text-amber-800` |
| `pending_hr` | "Waiting for HR" | `status-pending-2` | `bg-blue-100 text-blue-800` |
| `approved` | "Approved" | `status-approved` | `bg-green-100 text-green-800` |
| `rejected_by_supervisor` / `rejected_by_hr` | "Rejected" | `status-rejected` | `bg-red-100 text-red-800` |
| `cancelled` | "Cancelled" | `status-cancelled` | `bg-gray-100 text-gray-600` |

Note the two different "waiting" colors (amber for supervisor step, blue for HR step). This is deliberate: a single amber for "any pending state" would hide which of the two approval steps a request is actually sitting at, which is exactly the information this app exists to surface.

### 2.3 Semantic Colors (Non-Status)

| Token | Hex | Tailwind class | Usage |
|---|---|---|---|
| `success` | `#166A50` | `text-green-700` | Form save confirmation, non-status success |
| `error` | `#B3261E` | `text-red-700` | Form validation errors |
| `warning` | `#B8860B` | `text-amber-700` | Balance warnings, non-status cautions |
| `info` | `#1D4ED8` | `text-blue-700` | Informational banners, help text |

**Rule:** a balance warning ("This request exceeds your remaining balance") uses `warning` amber, same hue family as `pending_supervisor`'s amber but applied only to banners and inline text, never as a badge shape, so it's never confused with a status badge at a glance.

### 2.4 Dark Mode

Not required for MVP. HR and approval work happens during business hours on standard office displays. If added later, keep the five status hues recognizable, just shifted to darker backgrounds with lighter text, never redesigned into different colors per mode.

## 3. Typography

### 3.1 Typeface

**Inter** across the entire app, with system fallback (`Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif`). No secondary display font. This is a forms-and-tables application; legibility at small sizes in dense tables matters more than visual flair.

### 3.2 Type Scale

| Token | Size | Weight | Tailwind class | Usage |
|---|---|---|---|---|
| `heading-xl` | 24px / 1.3 | 700 | `text-2xl font-bold` | Page titles ("My Requests", "Approval Queue") |
| `heading-md` | 18px / 1.4 | 600 | `text-lg font-semibold` | Card titles, section headers |
| `body` | 14px / 1.5 | 400 | `text-sm` | Table rows, form fields, general content |
| `body-lg` | 16px / 1.5 | 400 | `text-base` | Request reason text, approval comments |
| `label` | 12px / 1.4 | 500 | `text-xs font-medium uppercase tracking-wide` | Field labels, badge text |
| `numeric` | 20px / 1.2 | 700 | `text-xl font-bold tabular-nums` | Balance numbers on the dashboard |

### 3.3 Rules

- Balance numbers always use `numeric` with `tabular-nums`, so digits align in columns when multiple leave types are shown side by side. A balance that visually jitters between "12" and "7" because of proportional digit widths undermines the trust this number needs to carry.
- Status badges always use `label` style regardless of which screen they appear on (dashboard card, table row, detail page). Consistency here is what makes status scannable across the whole app.
- Rejection comments and approval comments use `body-lg`, not the smaller `body` size. These are the words someone reads to understand why a decision was made, often under some frustration if it's a rejection. Give them room.

## 4. Elevation

Elevation stays minimal and purposeful, matching a forms-heavy, document-like tool.

| Level | Tailwind class | Usage |
|---|---|---|
| `elevation-0` | `shadow-none border border-neutral-300` | Tables, request cards, the main content panel |
| `elevation-1` | `shadow-sm` | Dropdown menus, date pickers |
| `elevation-2` | `shadow-md` | Modals (approve/reject confirmation dialog) |
| `elevation-3` | `shadow-lg` | Toast notifications |

**Rule:** the Approve and Reject confirmation dialog always uses `elevation-2`, never inline on the page. A decision that affects someone's leave balance and gets emailed to them deserves a deliberate, separate confirmation step, not a button that fires immediately on click.

## 5. Components

### 5.1 Status Badge

```
[ WAITING FOR SUPERVISOR ]   amber background, amber-800 text
[ WAITING FOR HR ]           blue background, blue-800 text
[ APPROVED ]                 green background, green-800 text
[ REJECTED ]                 red background, red-800 text
[ CANCELLED ]                gray background, gray-600 text
```

Shape: `rounded-full`, padding `px-3 py-1`, always `label` typography. Appears in the same position (top-right of a request card, rightmost column of a table row) on every screen that lists requests.

### 5.2 Approval Chain Stepper

A small horizontal indicator shown on every request detail view, making the two-step process visible as a literal chain rather than a single abstract status:

```
[●  Submitted] ──── [●  Supervisor] ──── [○  HR] ──── [○  Final]
     done              done              pending      pending
```

Filled circles (`bg-brand-700`) mark completed steps. Hollow circles (`border-2 border-neutral-300`) mark steps not yet reached. If a step was rejected, its circle turns red (`bg-red-600`) and the chain visually stops there, with no further hollow circles shown after it, since nothing comes next.

### 5.3 Buttons

| Variant | Usage | Tailwind |
|---|---|---|
| Primary | Submit Request, Approve | `bg-brand-700 text-white hover:bg-brand-600 rounded-md px-4 py-2 font-medium` |
| Secondary | Save Draft, Cancel action, Edit | `bg-white border border-neutral-300 text-neutral-900 hover:bg-neutral-100 rounded-md px-4 py-2` |
| Destructive-outline | Reject, Cancel Request | `bg-white border border-red-300 text-red-700 hover:bg-red-50 rounded-md px-4 py-2` |
| Ghost/Text | "View Details", "View History" links | `text-brand-700 hover:underline` |

**Rule:** identical to how Approve/Reject should never carry equal visual weight. "Approve" is Primary, filled. "Reject" is Destructive-outline, never a solid filled red button. This keeps approval as the visually expected path while still making rejection fully available and clear, not hidden or minimized.

### 5.4 Request Card (List View)

```
[Employee Name]                              [STATUS BADGE]
Leave Type · Jan 10–12, 2027 (3 days)         caption style
"Reason text, truncated to two lines..."       body style
📎 doctor-note.pdf (if attached)               caption, with icon
```

Hover state: `bg-neutral-100`. Entire card clickable to open detail view, not just a "View" link buried at the end.

### 5.5 Balance Summary Card (Employee Dashboard)

```
┌─────────────────────┐
│ Annual Leave         │
│                       │
│      8 / 12           ← numeric style, large
│      days remaining    body, neutral-600
│                       │
│ ▓▓▓▓▓▓▓░░░░           ← progress bar
└─────────────────────┘
```

One card per leave type, shown as a row of cards on the dashboard. Progress bar fill color: `brand-600` when remaining balance is above 25%, `amber-500` between 10% and 25%, `red-500` below 10%. This is the one place color communicates urgency rather than status, and it is visually distinct from status badges (a bar, not a pill), so the two systems don't get confused.

### 5.6 Approval Queue Row

```
[Employee Name]   [Leave Type]   [Dates]   [Days]   [Attachment icon]   [Approve] [Reject]
```

A dense table row, not a card, because supervisors and HR often triage several requests in one sitting and need to scan quickly. Approve and Reject buttons sit inline at the row's end, each opening the confirmation modal (section 4) rather than acting instantly.

### 5.7 Rejection Comment Display

Wherever a rejected request is shown (employee's history, HR's all-requests view), the rejection comment appears directly beneath the status badge, never behind a "view reason" click:

```
[ REJECTED ]
"Rejected by: Budi (Supervisor) — Insufficient notice given, please resubmit with at least 3 business days' notice."
```

**Rule:** a rejected request without its visible comment is treated as a broken screen, not an acceptable minimal state. The whole point of logging a required rejection comment (FR-APR-02/04) is defeated if the UI makes someone click to find it.

### 5.8 Empty States

Every list view (My Requests, Supervisor Queue, HR Queue, Leave Type Settings) has a defined empty state, matching the PRD's acceptance criteria. Structure:

```
       [simple icon, neutral-300]
       "You haven't submitted any requests yet."
       [Primary button: New Request]
```

**Rule:** an empty state always includes the one action that would resolve the emptiness (submit a request, wait for new approvals, add a leave type), not just a flat statement that nothing is there.

### 5.9 Forms and Inputs

- Input fields: `border border-neutral-300 rounded-md px-3 py-2`, focus state `ring-2 ring-brand-600 border-brand-600`.
- Date range picker: shows the calculated day count live, next to the fields, updating as either date changes, matching FR-REQ-02.
- Balance preview: shown inline below the leave type selector once both type and dates are chosen, written as plain text ("You have 8 days remaining. This request uses 3 days, leaving 5."), not just a number with no context.
- Required field indicator: red asterisk plus "(required)" text for screen readers, never color alone.
- Validation error: red `caption`-sized text directly under the field, plus a red border on the field. Field-level errors stay anchored to their field, never a page-top banner for a single-field problem.

## 6. Spacing

An 8px base grid, with 4px available only for icon-to-text gaps and badge internal padding.

| Token | Value | Tailwind class |
|---|---|---|
| `space-1` | 4px | `p-1`, `gap-1` |
| `space-2` | 8px | `p-2`, `gap-2` |
| `space-3` | 12px | `p-3`, `gap-3` |
| `space-4` | 16px | `p-4`, `gap-4` |
| `space-6` | 24px | `p-6`, `gap-6` |
| `space-8` | 32px | `p-8`, `gap-8` |
| `space-12` | 48px | `p-12`, `gap-12` |

**Rule:** table rows (approval queue, all-requests list) use `py-3` (12px vertical) for row height, not `py-4` or more. These tables carry a lot of rows during peak leave season (e.g. around holidays); keep them dense enough to scan many requests without excessive scrolling, while still leaving enough room for comfortable tap targets on the approve/reject buttons.

## 7. Border Radius

| Token | Value | Tailwind class | Usage |
|---|---|---|---|
| `radius-sm` | 4px | `rounded` | Inputs, small buttons |
| `radius-md` | 8px | `rounded-md` | Cards, buttons, modals |
| `radius-lg` | 12px | `rounded-lg` | Balance summary cards |
| `radius-full` | 9999px | `rounded-full` | Status badges only |

**Rule:** `radius-full` is reserved for status badges. Nothing else in the interface uses a fully rounded pill shape, so a badge is instantly recognizable by silhouette alone, even before its color or text registers.

## 8. Do's and Don'ts

### Status and the Approval Chain

✅ **Do** show the Approval Chain Stepper (section 5.2) on every request detail view, so the two-step process is always visible as a literal sequence.
❌ **Don't** reduce the two-step chain to a single status word without the stepper. "Pending" alone doesn't tell anyone whether it's the supervisor or HR who's holding up the request.

✅ **Do** use two distinct colors for the two "waiting" states (amber for supervisor, blue for HR).
❌ **Don't** collapse both pending states into one generic "yellow = pending" badge. That hides exactly the information an employee wants when they ask "who's sitting on my request right now."

### Rejection

✅ **Do** show the rejection comment immediately, visible without a click, wherever a rejected status appears.
❌ **Don't** let a rejected badge appear with no visible reason nearby. That's an incomplete screen, not a finished one.

✅ **Do** require a comment before the Reject action in the confirmation modal can be submitted.
❌ **Don't** allow a silent rejection. Every rejected employee deserves to know why, immediately, in the notification email and in the app.

### Balance

✅ **Do** show the balance impact of a request before submission ("You have 8 days, this uses 3, leaving 5").
❌ **Don't** let an employee find out they're over quota only after HR rejects their request for that reason. The warning belongs at submission time.

✅ **Do** keep balance numbers in `tabular-nums` so they align cleanly when several leave types are shown together.
❌ **Don't** treat the balance progress bar's color (green/amber/red for remaining days) as the same system as request status badges. They answer different questions and should never share a visual shape (bar vs pill).

### General

✅ **Do** keep approval queue tables dense and scannable, built for triaging several requests in one sitting.
❌ **Don't** turn the approval queue into a card grid that forces an approver to scroll through fewer requests per screen. Supervisors often review a backlog, not one request in isolation.

✅ **Do** write every status label in plain language a non-technical employee understands instantly.
❌ **Don't** ever leak an internal enum value (`pending_hr`, `rejected_by_supervisor`) into a user-facing label.
