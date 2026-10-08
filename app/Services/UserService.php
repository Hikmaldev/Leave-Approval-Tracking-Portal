<?php

namespace App\Services;

use App\Models\User;

class UserService
{
    /**
     * ACT-15: assign an employee's direct supervisor. Cycle and self checks
     * live in AssignSupervisorRequest so the error is anchored to the field.
     */
    public function assignSupervisor(User $user, int $supervisorId): User
    {
        $user->supervisor_id = $supervisorId;
        $user->save();

        return $user;
    }
}
