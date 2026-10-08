<?php

namespace App\Services;

use App\Enums\ApprovalDecision;
use App\Enums\ApprovalStep;
use App\Enums\LeaveRequestStatus;
use App\Enums\UserRole;
use App\Exceptions\WorkflowException;
use App\Models\ApprovalAction;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ApprovalService
{
    public function __construct(
        private readonly LeaveBalanceService $balances,
        private readonly NotificationService $notifications,
    ) {}

    /**
     * ACT-05 / ACT-06 / FR-APR-01..02/06/07: record the supervisor decision.
     * Approving moves the request to pending_hr; rejecting terminates it.
     * Balance is untouched at this step (FR-BAL-02).
     */
    public function supervisorDecide(
        LeaveRequest $leaveRequest,
        User $actor,
        ApprovalDecision $decision,
        ?string $comment,
    ): LeaveRequest {
        $decided = DB::transaction(function () use ($leaveRequest, $actor, $decision, $comment): LeaveRequest {
            $locked = LeaveRequest::query()->whereKey($leaveRequest->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== LeaveRequestStatus::PendingSupervisor) {
                throw new WorkflowException('This request is no longer waiting for the supervisor decision.');
            }

            $locked->loadMissing('user');
            if ($locked->user?->supervisor_id !== $actor->id) {
                throw new WorkflowException("Only the employee's assigned supervisor can decide this request.");
            }

            ApprovalAction::query()->create([
                'leave_request_id' => $locked->id,
                'actor_id' => $actor->id,
                'step' => ApprovalStep::Supervisor,
                'decision' => $decision,
                'comment' => $comment,
            ]);

            $locked->status = $decision === ApprovalDecision::Approved
                ? LeaveRequestStatus::PendingHr
                : LeaveRequestStatus::RejectedBySupervisor;

            $locked->save();

            return $locked;
        });

        $this->notifyAfterDecision($decided, ApprovalStep::Supervisor, $decision, $comment);

        return $decided;
    }

    /**
     * ACT-07 / ACT-08 / FR-APR-03..05/07: record the HR final decision.
     * On approval the requested days are deducted inside the same
     * transaction as the status change, so the two can never disagree
     * (PRD 9.3, FR-BAL-02).
     */
    public function hrDecide(
        LeaveRequest $leaveRequest,
        User $actor,
        ApprovalDecision $decision,
        ?string $comment,
    ): LeaveRequest {
        $decided = DB::transaction(function () use ($leaveRequest, $actor, $decision, $comment): LeaveRequest {
            $locked = LeaveRequest::query()->whereKey($leaveRequest->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== LeaveRequestStatus::PendingHr) {
                throw new WorkflowException('This request is not waiting for an HR decision.');
            }

            ApprovalAction::query()->create([
                'leave_request_id' => $locked->id,
                'actor_id' => $actor->id,
                'step' => ApprovalStep::Hr,
                'decision' => $decision,
                'comment' => $comment,
            ]);

            if ($decision === ApprovalDecision::Approved) {
                $locked->status = LeaveRequestStatus::Approved;

                $this->balances->deduct(
                    $locked->user_id,
                    $locked->leave_type_id,
                    (int) $locked->start_date->year,
                    $locked->days_requested,
                );
            } else {
                $locked->status = LeaveRequestStatus::RejectedByHr;
            }

            $locked->save();

            return $locked;
        });

        $this->notifyAfterDecision($decided, ApprovalStep::Hr, $decision, $comment);

        return $decided;
    }

    /**
     * FR-NOT-01..03/05: one notification to the employee per status change,
     * and one to the next approver when the chain moves on. Notifications
     * run after commit and are always logged.
     */
    protected function notifyAfterDecision(
        LeaveRequest $leaveRequest,
        ApprovalStep $step,
        ApprovalDecision $decision,
        ?string $comment,
    ): void {
        $leaveRequest->loadMissing('user');

        if ($decision === ApprovalDecision::Rejected) {
            $this->notifications->sendStatusChange($leaveRequest->user, $leaveRequest, 'rejected', $comment);

            return;
        }

        if ($step === ApprovalStep::Supervisor) {
            $this->notifications->sendStatusChange($leaveRequest->user, $leaveRequest, 'supervisor_approved');

            foreach ($this->hrAdmins() as $hrAdmin) {
                $this->notifications->sendStatusChange($hrAdmin, $leaveRequest, 'pending_hr');
            }

            return;
        }

        $this->notifications->sendStatusChange($leaveRequest->user, $leaveRequest, 'hr_approved');
    }

    /**
     * @return Collection<int, User>
     */
    protected function hrAdmins(): Collection
    {
        return User::query()
            ->where('role', UserRole::HrAdmin->value)
            ->where('is_active', true)
            ->get();
    }
}
