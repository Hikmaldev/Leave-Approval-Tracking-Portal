<?php

namespace Database\Factories;

use App\Enums\ApprovalDecision;
use App\Enums\ApprovalStep;
use App\Models\ApprovalAction;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ApprovalAction>
 */
class ApprovalActionFactory extends Factory
{
    protected $model = ApprovalAction::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'leave_request_id' => LeaveRequest::factory(),
            'actor_id' => User::factory(),
            'step' => ApprovalStep::Supervisor,
            'decision' => ApprovalDecision::Approved,
            'comment' => null,
        ];
    }
}
