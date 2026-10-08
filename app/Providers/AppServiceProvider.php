<?php

namespace App\Providers;

use App\Models\Attachment;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use App\Policies\AttachmentPolicy;
use App\Policies\LeaveBalancePolicy;
use App\Policies\LeaveRequestPolicy;
use App\Policies\LeaveTypePolicy;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Explicit policy mapping (PRD 9.2: authorization lives on the server).
     * Laravel would auto-discover these, but registering them documents the
     * authorization surface in one place.
     */
    public function boot(): void
    {
        Gate::policy(LeaveRequest::class, LeaveRequestPolicy::class);
        Gate::policy(LeaveBalance::class, LeaveBalancePolicy::class);
        Gate::policy(LeaveType::class, LeaveTypePolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Attachment::class, AttachmentPolicy::class);
    }
}
