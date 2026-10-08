<?php

namespace App\Http\Controllers;

use App\Models\LeaveBalance;
use App\Services\LeaveBalanceService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeaveBalanceController extends Controller
{
    public function __construct(private readonly LeaveBalanceService $balances) {}

    /**
     * Screen: Employee Balance page (FR-BAL-04). Balances are scoped to the
     * authenticated user and initialised from the active leave types.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', LeaveBalance::class);

        $user = $request->user();
        $year = (int) $request->integer('year', now()->year);

        return view('balances.index', [
            'balances' => $this->balances->forUserYear($user, $year),
            'year' => $year,
        ]);
    }
}
