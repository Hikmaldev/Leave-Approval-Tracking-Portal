<?php

namespace App\Policies;

use App\Models\LeaveBalance;
use App\Models\User;

class LeaveBalancePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * ACT-12 / FR-BAL-05: the company-wide balance management screen and its
     * provisioning are HR-only. Kept separate from viewAny so an employee's
     * own-balance page can still pass viewAny.
     */
    public function manageAny(User $user): bool
    {
        return $user->isHrAdmin();
    }

    /**
     * PRD 11.3: own balance, HR (all balances), and a supervisor for their
     * direct reports (read-only).
     */
    public function view(User $user, LeaveBalance $balance): bool
    {
        return $balance->user_id === $user->id
            || $user->isHrAdmin()
            || ($user->isSupervisor() && $balance->user?->supervisor_id === $user->id);
    }

    /**
     * ACT-12 / FR-BAL-05: only HR may adjust a balance.
     */
    public function update(User $user, LeaveBalance $balance): bool
    {
        return $user->isHrAdmin();
    }
}
