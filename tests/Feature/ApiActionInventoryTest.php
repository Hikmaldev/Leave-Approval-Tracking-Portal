<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ApiActionInventoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
    }

    /**
     * @return array{employee: User, supervisor: User, hr: User}
     */
    private function actors(): array
    {
        $hr = User::factory()->create(['role' => UserRole::HrAdmin]);
        $supervisor = User::factory()->create(['role' => UserRole::Supervisor]);
        $employee = User::factory()->create([
            'role' => UserRole::Employee,
            'supervisor_id' => $supervisor->id,
        ]);

        return compact('employee', 'supervisor', 'hr');
    }

    public function test_act_01_login_returns_the_authenticated_user(): void
    {
        $user = User::factory()->create(['password' => 'password']);

        $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertOk()->assertJsonPath('data.id', $user->id);
    }

    public function test_act_02_submit_creates_a_pending_supervisor_request(): void
    {
        ['employee' => $employee] = $this->actors();
        $type = LeaveType::factory()->create();
        $monday = Carbon::parse(Carbon::now()->year.'-06-01')->startOfWeek();

        $this->actingAs($employee)->postJson('/api/leave-requests', [
            'leave_type_id' => $type->id,
            'start_date' => $monday->toDateString(),
            'end_date' => $monday->copy()->addDays(2)->toDateString(),
            'reason' => 'API submission',
        ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'pending_supervisor')
            ->assertJsonPath('data.status_label', 'Waiting for Supervisor');
    }

    public function test_act_13_list_is_scoped_by_role(): void
    {
        ['employee' => $employee, 'supervisor' => $supervisor, 'hr' => $hr] = $this->actors();
        $outsider = User::factory()->create(['role' => UserRole::Employee]);

        LeaveRequest::factory()->create(['user_id' => $employee->id]);
        LeaveRequest::factory()->create(['user_id' => $outsider->id]);

        $this->actingAs($employee)->getJson('/api/leave-requests')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->actingAs($supervisor)->getJson('/api/leave-requests')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->actingAs($hr)->getJson('/api/leave-requests')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_act_14_export_is_restricted_to_hr(): void
    {
        ['employee' => $employee, 'supervisor' => $supervisor, 'hr' => $hr] = $this->actors();

        LeaveRequest::factory()->create(['user_id' => $employee->id]);

        // Employees and supervisors must never reach the company-wide export:
        // it is not scoped by role and would leak every employee's records.
        $this->actingAs($employee)
            ->get('/api/leave-requests/export')
            ->assertForbidden();

        $this->actingAs($supervisor)
            ->get('/api/leave-requests/export')
            ->assertForbidden();

        // HR receives the CSV stream.
        $response = $this->actingAs($hr)->get('/api/leave-requests/export');
        $response->assertOk();
        $this->assertStringContainsString(
            'text/csv',
            (string) $response->headers->get('content-type'),
        );
    }

    public function test_act_05_to_08_full_two_step_approval_through_the_api(): void
    {
        ['employee' => $employee, 'supervisor' => $supervisor, 'hr' => $hr] = $this->actors();
        $type = LeaveType::factory()->create();
        $request = LeaveRequest::factory()->create([
            'user_id' => $employee->id,
            'leave_type_id' => $type->id,
            'days_requested' => 2,
            'start_date' => Carbon::now()->addDay()->toDateString(),
            'end_date' => Carbon::now()->addDays(2)->toDateString(),
        ]);
        $balance = LeaveBalance::factory()->create([
            'user_id' => $employee->id,
            'leave_type_id' => $type->id,
            'year' => Carbon::now()->year,
            'quota' => 12,
            'used' => 0,
        ]);

        $this->actingAs($supervisor)->patchJson("/api/leave-requests/{$request->id}/supervisor-decision", [
            'decision' => 'approved',
        ])->assertOk()->assertJsonPath('data.status', 'pending_hr');

        $this->actingAs($hr)->patchJson("/api/leave-requests/{$request->id}/hr-decision", [
            'decision' => 'approved',
        ])->assertOk()->assertJsonPath('data.status', 'approved');

        $this->assertSame(2, $balance->fresh()->used);
    }

    public function test_act_04_cancel_via_api(): void
    {
        ['employee' => $employee] = $this->actors();
        $request = LeaveRequest::factory()->create(['user_id' => $employee->id]);

        $this->actingAs($employee)
            ->patchJson("/api/leave-requests/{$request->id}/cancel")
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled');
    }

    public function test_act_11_balances_are_scoped_and_returned(): void
    {
        ['employee' => $employee] = $this->actors();
        LeaveType::factory()->create(['default_annual_quota' => 10]);

        $this->actingAs($employee)
            ->getJson("/api/users/{$employee->id}/leave-balances")
            ->assertOk()
            ->assertJsonPath('data.0.remaining_days', 10);
    }

    public function test_act_12_balance_adjustment_requires_a_reason(): void
    {
        ['employee' => $employee, 'hr' => $hr] = $this->actors();
        $type = LeaveType::factory()->create();
        LeaveBalance::factory()->create([
            'user_id' => $employee->id,
            'leave_type_id' => $type->id,
        ]);

        $this->actingAs($hr)->patchJson("/api/users/{$employee->id}/leave-balances/{$type->id}", [
            'quota' => 20,
        ])->assertStatus(422)->assertJsonValidationErrors('reason');

        $this->actingAs($hr)->patchJson("/api/users/{$employee->id}/leave-balances/{$type->id}", [
            'quota' => 20,
            'reason' => 'Carry over',
        ])->assertOk()->assertJsonPath('data.quota', 20);
    }

    public function test_act_09_and_10_leave_type_management_via_api(): void
    {
        ['hr' => $hr] = $this->actors();

        $created = $this->actingAs($hr)->postJson('/api/leave-types', [
            'name' => 'Maternity Leave',
            'requires_attachment' => true,
            'default_annual_quota' => 90,
        ])->assertCreated()->json('data');

        $this->actingAs($hr)->patchJson("/api/leave-types/{$created['id']}", [
            'is_active' => false,
        ])->assertOk()->assertJsonPath('data.is_active', false);
    }

    public function test_act_15_assign_supervisor_and_reject_a_cycle(): void
    {
        ['supervisor' => $supervisor, 'hr' => $hr] = $this->actors();
        $employee = User::factory()->create(['role' => UserRole::Employee]);

        $this->actingAs($hr)->patchJson("/api/users/{$employee->id}", [
            'supervisor_id' => $supervisor->id,
        ])->assertOk()->assertJsonPath('data.id', $employee->id);

        $this->assertSame($supervisor->id, $employee->fresh()->supervisor_id);

        // A user cannot be their own supervisor.
        $this->actingAs($hr)->patchJson("/api/users/{$employee->id}", [
            'supervisor_id' => $employee->id,
        ])->assertStatus(422)->assertJsonValidationErrors('supervisor_id');
    }

    public function test_non_hr_cannot_adjust_a_balance_through_the_api(): void
    {
        ['employee' => $employee] = $this->actors();
        $type = LeaveType::factory()->create();

        $this->actingAs($employee)->patchJson("/api/users/{$employee->id}/leave-balances/{$type->id}", [
            'quota' => 99,
            'reason' => 'Nope',
        ])->assertForbidden();
    }
}
