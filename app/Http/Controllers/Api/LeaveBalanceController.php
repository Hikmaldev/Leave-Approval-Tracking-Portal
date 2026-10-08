<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdjustLeaveBalanceRequest;
use App\Http\Resources\LeaveBalanceResource;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\User;
use App\Services\LeaveBalanceService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class LeaveBalanceController extends Controller
{
    /**
     * @return array<int, mixed>
     */
    public function __construct(private readonly LeaveBalanceService $balances) {}

    /**
     * ACT-11: current balances of an employee, scoped by the user policy
     * (own, assigned supervisor, HR).
     */
    public function index(Request $request, User $user): AnonymousResourceCollection
    {
        $this->authorize('viewBalances', $user);

        $year = (int) $request->integer('year', now()->year);

        return LeaveBalanceResource::collection($this->balances->forUserYear($user, $year));
    }

    /**
     * ACT-12: HR manual adjustment with a mandatory reason.
     */
    public function update(
        AdjustLeaveBalanceRequest $request,
        User $user,
        LeaveType $leaveType,
    ): LeaveBalanceResource {
        $this->authorize('adjustBalance', $user);

        $year = (int) $request->integer('year', now()->year);

        $balance = LeaveBalance::query()->firstOrCreate(
            [
                'user_id' => $user->id,
                'leave_type_id' => $leaveType->id,
                'year' => $year,
            ],
            [
                'quota' => $leaveType->default_annual_quota,
                'used' => 0,
            ],
        );

        $this->balances->adjust($balance, $request->user(), [
            'quota' => $request->validated('quota'),
            'used' => $request->validated('used'),
            'reason' => $request->validated('reason'),
        ]);

        return new LeaveBalanceResource($balance->fresh()->load(['user', 'leaveType']));
    }
}
