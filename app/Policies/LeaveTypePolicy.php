<?php

namespace App\Policies;

use App\Models\LeaveType;
use App\Models\User;

class LeaveTypePolicy
{
    /**
     * Every role reads active leave types when submitting a request; HR sees
     * all of them (including inactive) on the settings screen.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Leave Type Settings: HR sees every type (including inactive) on the
     * management screen. Separated from viewAny so employees can still read
     * the active list while submitting a request.
     */
    public function manageAny(User $user): bool
    {
        return $user->isHrAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isHrAdmin();
    }

    /**
     * ACT-09 / ACT-10: editing and toggling are HR-only. Existing requests
     * are never recalculated (business rule 5).
     */
    public function update(User $user, LeaveType $leaveType): bool
    {
        return $user->isHrAdmin();
    }
}
