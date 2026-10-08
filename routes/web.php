<?php

use App\Http\Controllers\ApprovalQueueController;
use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BalanceManagementController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HrLeaveRequestController;
use App\Http\Controllers\LeaveBalanceController;
use App\Http\Controllers\LeaveRequestController;
use App\Http\Controllers\LeaveTypeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web routes (session-based Blade UI)
|--------------------------------------------------------------------------
|
| Pages are server-rendered; create/update/delete actions post to the routes
| below, which call the service layer and redirect with flash feedback. The
| RESTful equivalents live in routes/api.php (PRD Action Inventory).
|
| Authorization is enforced server-side in the controllers (policies) and in
| the FormRequests, on top of the `role` middleware (PRD 9.2).
|
*/

Route::middleware('guest')->group(function () {
    // Screen: Login (FR-AUTH-01)
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->name('login.store');
});

Route::post('/logout', [AuthController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::middleware(['auth', 'role:employee,supervisor,hr_admin'])->group(function () {
    // Screen: Employee Dashboard
    Route::get('/', DashboardController::class)->name('dashboard');

    // Screen: My Requests (FR-HIS-01)
    Route::get('/requests', [LeaveRequestController::class, 'index'])->name('requests.index');

    // Screen: New Request form (FR-REQ-01..05)
    Route::get('/requests/create', [LeaveRequestController::class, 'create'])->name('requests.create');

    // ACT-02: submit a request
    Route::post('/requests', [LeaveRequestController::class, 'store'])->name('requests.store');

    // Screen: Request Detail — reads the ID from the URL, never global state.
    Route::get('/requests/{leaveRequest}', [LeaveRequestController::class, 'show'])->name('requests.show');

    // ACT-03: attach a file to a pending request
    Route::post('/requests/{leaveRequest}/attachments', [LeaveRequestController::class, 'addAttachment'])
        ->name('requests.attachments.store');

    // ACT-04: cancel a pending request (owner) / any cancellable request (HR)
    Route::patch('/requests/{leaveRequest}/cancel', [LeaveRequestController::class, 'cancel'])
        ->name('requests.cancel');

    // Screen: Employee Balance page (FR-BAL-04)
    Route::get('/balances', [LeaveBalanceController::class, 'index'])->name('balances.index');

    // Secured attachment download (PRD 9.2)
    Route::get('/attachments/{attachment}', [AttachmentController::class, 'download'])
        ->name('attachments.download');
});

Route::middleware(['auth', 'role:supervisor,hr_admin'])->group(function () {
    // Screen: Supervisor Approval Queue (FR-APR-01)
    Route::get('/approvals/supervisor', [ApprovalQueueController::class, 'supervisor'])
        ->name('approvals.supervisor');

    // ACT-05 / ACT-06: supervisor decision
    Route::patch('/approvals/{leaveRequest}/supervisor', [ApprovalQueueController::class, 'supervisorDecision'])
        ->name('approvals.supervisor.decide');
});

Route::middleware(['auth', 'role:hr_admin'])->prefix('hr')->group(function () {
    // Screen: HR Approval Queue (FR-APR-03/04)
    Route::get('/approvals', [ApprovalQueueController::class, 'hr'])->name('approvals.hr');

    // ACT-07 / ACT-08: HR final decision
    Route::patch('/approvals/{leaveRequest}/hr', [ApprovalQueueController::class, 'hrDecision'])
        ->name('approvals.hr.decide');

    // Screen: HR All Requests — filters + CSV export (FR-HIS-03/04)
    Route::get('/requests', [HrLeaveRequestController::class, 'index'])->name('hr.requests.index');

    // ACT-14: CSV export for the filtered period
    Route::get('/requests/export', [HrLeaveRequestController::class, 'export'])->name('hr.requests.export');

    // Screen: Leave Type Settings (PRD 4.1 admin scope)
    Route::get('/leave-types', [LeaveTypeController::class, 'index'])->name('hr.leave-types.index');

    // ACT-09: create / update a leave type
    Route::post('/leave-types', [LeaveTypeController::class, 'store'])->name('hr.leave-types.store');
    Route::put('/leave-types/{leaveType}', [LeaveTypeController::class, 'update'])->name('hr.leave-types.update');

    // ACT-10: activate / deactivate
    Route::patch('/leave-types/{leaveType}/status', [LeaveTypeController::class, 'updateStatus'])
        ->name('hr.leave-types.status');

    // Screen: Balance Management (FR-BAL-05)
    Route::get('/balances', [BalanceManagementController::class, 'index'])->name('hr.balances.index');

    // ACT-12: manual balance adjustment (reason required)
    Route::patch('/balances/{user}/{leaveType}', [BalanceManagementController::class, 'update'])
        ->name('hr.balances.update');
});
