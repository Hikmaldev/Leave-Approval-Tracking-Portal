<?php

namespace App\Enums;

enum LeaveRequestStatus: string
{
    case PendingSupervisor = 'pending_supervisor';
    case PendingHr = 'pending_hr';
    case Approved = 'approved';
    case RejectedBySupervisor = 'rejected_by_supervisor';
    case RejectedByHr = 'rejected_by_hr';
    case Cancelled = 'cancelled';

    /**
     * Plain-language label shown to users (design system: the two pending
     * states must never be collapsed into one generic "pending").
     */
    public function label(): string
    {
        return match ($this) {
            self::PendingSupervisor => 'Waiting for Supervisor',
            self::PendingHr => 'Waiting for HR',
            self::Approved => 'Approved',
            self::RejectedBySupervisor, self::RejectedByHr => 'Rejected',
            self::Cancelled => 'Cancelled',
        };
    }

    /**
     * True while the request is still inside the two-step approval chain.
     */
    public function isPending(): bool
    {
        return in_array($this, [self::PendingSupervisor, self::PendingHr], true);
    }

    /**
     * True when no further approval step will follow.
     */
    public function isFinal(): bool
    {
        return ! $this->isPending();
    }

    /**
     * A request can be cancelled while pending. HR may also cancel a fully
     * approved request, in which case the deducted balance is restored
     * (FR-BAL-03, PRD section 6).
     */
    public function isCancellable(): bool
    {
        return in_array($this, [self::PendingSupervisor, self::PendingHr, self::Approved], true);
    }
}
