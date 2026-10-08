<?php

namespace App\Http\Controllers\Api;

use App\Enums\ApprovalDecision;
use App\Http\Controllers\Controller;
use App\Http\Requests\HrDecisionRequest;
use App\Http\Requests\SupervisorDecisionRequest;
use App\Http\Resources\LeaveRequestResource;
use App\Models\LeaveRequest;
use App\Services\ApprovalService;

class LeaveRequestDecisionController extends Controller
{
    public function __construct(private readonly ApprovalService $approvals) {}

    /**
     * ACT-05 / ACT-06: supervisor decision.
     */
    public function supervisor(SupervisorDecisionRequest $request, LeaveRequest $leaveRequest): LeaveRequestResource
    {
        $this->authorize('decideAsSupervisor', $leaveRequest);

        $decided = $this->approvals->supervisorDecide(
            $leaveRequest,
            $request->user(),
            ApprovalDecision::from($request->validated('decision')),
            $request->validated('comment'),
        );

        return new LeaveRequestResource($decided->load(['user', 'leaveType', 'attachments', 'approvalActions.actor']));
    }

    /**
     * ACT-07 / ACT-08: HR final decision.
     */
    public function hr(HrDecisionRequest $request, LeaveRequest $leaveRequest): LeaveRequestResource
    {
        $this->authorize('decideAsHr', $leaveRequest);

        $decided = $this->approvals->hrDecide(
            $leaveRequest,
            $request->user(),
            ApprovalDecision::from($request->validated('decision')),
            $request->validated('comment'),
        );

        return new LeaveRequestResource($decided->load(['user', 'leaveType', 'attachments', 'approvalActions.actor']));
    }
}
