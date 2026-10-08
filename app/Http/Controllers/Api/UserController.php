<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AssignSupervisorRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserService;

class UserController extends Controller
{
    public function __construct(private readonly UserService $users) {}

    /**
     * ACT-15: assign an employee's direct supervisor.
     */
    public function update(AssignSupervisorRequest $request, User $user): UserResource
    {
        $this->authorize('assignSupervisor', $user);

        $this->users->assignSupervisor($user, (int) $request->validated('supervisor_id'));

        return new UserResource($user->load('supervisor'));
    }
}
