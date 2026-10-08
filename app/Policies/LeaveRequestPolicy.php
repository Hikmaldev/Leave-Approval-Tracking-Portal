<?php

namespace App\Policies;

use App\Enums\LeaveRequestStatus;
use App\Models\LeaveRequest;
use App\Models\User;

class LeaveRequestPolicy
{
    /**
     * The list is always scoped by the controller (own / direct reports /
     * company-wide), so any authenticated user may reach an index endpoint.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * ACT-14 / FR-HIS-04: only HR may export the company-wide filtered list.
     * The export path does not run list scoping, so it must not be reachable
     * by employees/supervisors (PRD 9.2 — authorization at the API layer).
     */
    public function export(User $user): bool
    {
        return $user->isHrAdmin();
    }

    /**
     * PRD 11.3: a request is visible to its owner, their assigned supervisor,
     * and HR.
     */
    public function view(User $user, LeaveRequest $leaveRequest): bool
    {
        return $this->isOwner($user, $leaveRequest)
            || $user->isHrAdmin()
            || $this->isAssignedSupervisor($user, $leaveRequest);
    }

    public function create(User $user): bool
    {
        return true;
    }

    /**
     * ACT-04: owner while pending; HR for any cancellable request.
     */
    public function cancel(User $user, LeaveRequest $leaveRequest): bool
    {
        if ($user->isHrAdmin()) {
            return $leaveRequest->status->isCancellable();
        }

        return $this->isOwner($user, $leaveRequest) && $leaveRequest->status->isPending();
    }

    /**
     * ACT-05/06: only the employee's assigned supervisor, and only while the
     * request is waiting for the supervisor step (FR-AUTH-03).
     */
    public function decideAsSupervisor(User $user, LeaveRequest $leaveRequest): bool
    {
        return $leaveRequest->status === LeaveRequestStatus::PendingSupervisor
            && $this->isAssignedSupervisor($user, $leaveRequest);
    }

    /**
     * ACT-07/08: only HR, and only after the supervisor approved (FR-APR-03).
     */
    public function decideAsHr(User $user, LeaveRequest $leaveRequest): bool
    {
        return $user->isHrAdmin()
            && $leaveRequest->status === LeaveRequestStatus::PendingHr;
    }

    /**
     * ACT-03: only the owner, and only while still pending.
     */
    public function addAttachment(User $user, LeaveRequest $leaveRequest): bool
    {
        return $this->isOwner($user, $leaveRequest) && $leaveRequest->status->isPending();
    }

    /**
     * PRD 9.2: attachments are downloadable by the owner, their assigned
     * supervisor, and HR.
     */
    public function downloadAttachment(User $user, LeaveRequest $leaveRequest): bool
    {
        return $this->view($user, $leaveRequest);
    }

    protected function isOwner(User $user, LeaveRequest $leaveRequest): bool
    {
        return $leaveRequest->user_id === $user->id;
    }

    protected function isAssignedSupervisor(User $user, LeaveRequest $leaveRequest): bool
    {
        return $leaveRequest->user?->supervisor_id === $user->id;
    }
}
