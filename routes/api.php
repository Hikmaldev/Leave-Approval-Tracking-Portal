<?php

use App\Http\Controllers\Api\AttachmentController;
use App\Http\Controllers\Api\LeaveBalanceController;
use App\Http\Controllers\Api\LeaveRequestController;
use App\Http\Controllers\Api\LeaveRequestDecisionController;
use App\Http\Controllers\Api\LeaveTypeController;
use App\Http\Controllers\Api\LoginController;
use App\Http\Controllers\Api\UserController;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API routes (PRD Action Inventory, section 12)
|--------------------------------------------------------------------------
|
| Every route below maps to an ACT-xx entry in the Action Inventory.
| The routes are mounted under /api with the web middleware group (see
| bootstrap/app.php) so session authentication and CSRF are available for
| the server-rendered UI's REST equivalents. Sanctum/token auth is a future
| step (PRD 10.1).
|
| Authorization lives in the FormRequests and policies, never only in the
| UI (PRD 9.2). Role scoping for list endpoints is enforced in the
| controller/policy.
|
*/

// ACT-01 — Log in. The Blade UI uses the web session route; this endpoint
// serves the same login for API consumers (session or future token).
Route::post('/login', [LoginController::class, 'store'])
    ->middleware('guest')
    ->name('api.login');

Route::middleware('auth')->group(function () {
    // Supporting endpoint: current session user for the UI shell.
    Route::get('/user', fn (Request $request) => new UserResource($request->user()))
        ->name('api.user');

    // ACT-02 — Submit a new request (status: pending_supervisor)
    // ACT-13 — Filtered list: own history / supervisor reports / HR all
    Route::get('/leave-requests', [LeaveRequestController::class, 'index'])
        ->name('api.leave-requests.index');
    Route::post('/leave-requests', [LeaveRequestController::class, 'store'])
        ->name('api.leave-requests.store');

    // ACT-14 — CSV export. Must stay declared BEFORE the {leaveRequest}
    // route below, or "export" would be captured as a model binding.
    Route::get('/leave-requests/export', [LeaveRequestController::class, 'export'])
        ->middleware('role:hr_admin')
        ->name('api.leave-requests.export');

    // Supporting endpoint: request detail page reads the ID from the URL.
    Route::get('/leave-requests/{leaveRequest}', [LeaveRequestController::class, 'show'])
        ->name('api.leave-requests.show');

    // ACT-03 — Attach a file to a request
    Route::post('/leave-requests/{leaveRequest}/attachments', [AttachmentController::class, 'store'])
        ->name('api.leave-requests.attachments.store');

    // Supporting endpoint: secured attachment download (employee, assigned
    // supervisor, and HR only, per PRD 9.2).
    Route::get('/attachments/{attachment}', [AttachmentController::class, 'download'])
        ->name('api.attachments.download');

    // ACT-04 — Cancel: owner while pending; HR may cancel any request
    Route::patch('/leave-requests/{leaveRequest}/cancel', [LeaveRequestController::class, 'cancel'])
        ->name('api.leave-requests.cancel');

    // ACT-05 / ACT-06 — Supervisor decision (approve or reject)
    Route::patch('/leave-requests/{leaveRequest}/supervisor-decision', [LeaveRequestDecisionController::class, 'supervisor'])
        ->name('api.leave-requests.supervisor-decision');

    // ACT-07 / ACT-08 — HR final decision (approve or reject)
    Route::patch('/leave-requests/{leaveRequest}/hr-decision', [LeaveRequestDecisionController::class, 'hr'])
        ->name('api.leave-requests.hr-decision');

    // ACT-11 — Current balances of an employee
    Route::get('/users/{user}/leave-balances', [LeaveBalanceController::class, 'index'])
        ->name('api.users.leave-balances.index');

    // ACT-12 — Manual balance adjustment (reason required). {leaveType}
    // represents the leave_type_id path segment from the Action Inventory.
    Route::patch('/users/{user}/leave-balances/{leaveType}', [LeaveBalanceController::class, 'update'])
        ->name('api.users.leave-balances.update');

    // ACT-15 — Assign a supervisor (supervisor_id only)
    Route::patch('/users/{user}', [UserController::class, 'update'])
        ->name('api.users.update');

    // ACT-09 — Create / update a leave type
    Route::post('/leave-types', [LeaveTypeController::class, 'store'])
        ->name('api.leave-types.store');
    Route::put('/leave-types/{leaveType}', [LeaveTypeController::class, 'update'])
        ->name('api.leave-types.update');

    // ACT-10 — Activate / deactivate toggle
    Route::patch('/leave-types/{leaveType}', [LeaveTypeController::class, 'updateStatus'])
        ->name('api.leave-types.update-status');
});
