<?php

namespace App\Http\Controllers;

use App\Services\LeaveBalanceService;
use App\Services\LeaveRequestService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly LeaveBalanceService $balances,
        private readonly LeaveRequestService $requests,
    ) {}

    /**
     * Screen: Employee Dashboard (PRD 14.1). Balance summary + recent
     * requests, both scoped to the authenticated user.
     */
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $year = now()->year;

        return view('dashboard', [
            'balances' => $this->balances->forUserYear($user, $year),
            'recentRequests' => $this->requests->forUser($user)->take(5),
            'year' => $year,
        ]);
    }
}
