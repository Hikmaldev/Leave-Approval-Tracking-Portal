<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdjustLeaveBalanceRequest;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\User;
use App\Services\LeaveBalanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BalanceManagementController extends Controller
{
    public function __construct(private readonly LeaveBalanceService $balances) {}

    /**
     * Screen: Balance Management (FR-BAL-05). Company-wide for the selected
     * year, with an employee search. Balances are provisioned so the view is
     * never artificially empty (FR-BAL-01).
     */
    public function index(Request $request): View
    {
        $this->authorize('manageAny', LeaveBalance::class);

        $year = (int) $request->integer('year', now()->year);
        $this->balances->ensureForAllUsers($year);

        $employee = trim((string) $request->string('employee'));

        $balances = LeaveBalance::query()
            ->where('year', $year)
            ->with(['user', 'leaveType'])
            ->when($employee !== '', function ($query) use ($employee): void {
                $query->whereHas('user', function ($userQuery) use ($employee): void {
                    $userQuery->where('name', 'like', "%{$employee}%")
                        ->orWhere('email', 'like', "%{$employee}%");
                });
            })
            ->get()
            ->sortBy([
                fn (LeaveBalance $a, LeaveBalance $b) => ($a->user?->name ?? '') <=> ($b->user?->name ?? ''),
                fn (LeaveBalance $a, LeaveBalance $b) => ($a->leaveType?->name ?? '') <=> ($b->leaveType?->name ?? ''),
            ])
            ->values();

        return view('hr.balances', [
            'balances' => $balances,
            'year' => $year,
            'filters' => ['employee' => $employee],
        ]);
    }

    /**
     * ACT-12: manual adjustment with a mandatory reason, recorded in the
     * balance adjustment audit trail.
     */
    public function update(AdjustLeaveBalanceRequest $request, User $user, LeaveType $leaveType): RedirectResponse
    {
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

        return back()->with('status', 'The balance was adjusted.');
    }
}
