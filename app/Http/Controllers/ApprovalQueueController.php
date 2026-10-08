<?php

namespace App\Http\Controllers;

use App\Enums\ApprovalDecision;
use App\Http\Requests\HrDecisionRequest;
use App\Http\Requests\SupervisorDecisionRequest;
use App\Models\LeaveRequest;
use App\Services\ApprovalService;
use App\Services\LeaveRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ApprovalQueueController extends Controller
{
    public function __construct(
        private readonly LeaveRequestService $requests,
        private readonly ApprovalService $approvals,
    ) {}

    /**
     * Screen: Supervisor Approval Queue (FR-APR-01 / FR-AUTH-03). Only
     * requests from the authenticated supervisor's direct reports.
     */
    public function supervisor(Request $request): View
    {
        $this->authorize('viewAny', LeaveRequest::class);

        return view('approvals.supervisor', [
            'leaveRequests' => $this->requests->pendingForSupervisor($request->user()),
        ]);
    }

    /**
     * Screen: HR Approval Queue (FR-APR-03/04), company-wide.
     */
    public function hr(): View
    {
        $this->authorize('viewAny', LeaveRequest::class);

        return view('approvals.hr', [
            'leaveRequests' => $this->requests->pendingForHr(),
        ]);
    }

    /**
     * ACT-05 / ACT-06: record the supervisor decision.
     */
    public function supervisorDecision(SupervisorDecisionRequest $request, LeaveRequest $leaveRequest): RedirectResponse
    {
        $this->authorize('decideAsSupervisor', $leaveRequest);

        $this->approvals->supervisorDecide(
            $leaveRequest,
            $request->user(),
            ApprovalDecision::from($request->validated('decision')),
            $request->validated('comment'),
        );

        return back()->with('status', 'The supervisor decision was recorded.');
    }

    /**
     * ACT-07 / ACT-08: record the HR final decision (deducts balance on
     * approval).
     */
    public function hrDecision(HrDecisionRequest $request, LeaveRequest $leaveRequest): RedirectResponse
    {
        $this->authorize('decideAsHr', $leaveRequest);

        $this->approvals->hrDecide(
            $leaveRequest,
            $request->user(),
            ApprovalDecision::from($request->validated('decision')),
            $request->validated('comment'),
        );

        return back()->with('status', 'The HR decision was recorded.');
    }
}
