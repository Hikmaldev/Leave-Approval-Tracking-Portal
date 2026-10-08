<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * ACT-15: only HR may manage the employee-to-supervisor relationship.
     */
    public function assignSupervisor(User $user, User $target): bool
    {
        return $user->isHrAdmin();
    }

    /**
     * ACT-11/ACT-12: an employee reads their own balances, HR reads/manages
     * everyone's, and a supervisor reads their direct reports' balances.
     */
    public function viewBalances(User $user, User $target): bool
    {
        return $user->id === $target->id
            || $user->isHrAdmin()
            || ($user->isSupervisor() && $target->supervisor_id === $user->id);
    }

    /**
     * FR-BAL-05: HR-only balance adjustment.
     */
    public function adjustBalance(User $user, User $target): bool
    {
        return $user->isHrAdmin();
    }

    /**
     * HR-only company-wide employee listing.
     */
    public function viewAny(User $user): bool
    {
        return $user->isHrAdmin();
    }
}
