<?php

namespace Tests\Feature;

use App\Enums\LeaveRequestStatus;
use App\Enums\UserRole;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use App\Notifications\LeaveRequestStatusNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class LeaveWorkflowTest extends TestCase
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

    public function test_employee_can_submit_a_request_and_it_appears_in_history_and_supervisor_queue(): void
    {
        ['employee' => $employee, 'supervisor' => $supervisor] = $this->actors();
        $type = LeaveType::factory()->create(['name' => 'Annual Leave']);

        $response = $this->actingAs($employee)->post('/requests', [
            'leave_type_id' => $type->id,
            'start_date' => Carbon::now()->addDays(3)->toDateString(),
            'end_date' => Carbon::now()->addDays(5)->toDateString(),
            'reason' => 'Family trip',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('leave_requests', [
            'user_id' => $employee->id,
            'days_requested' => 3,
            'status' => LeaveRequestStatus::PendingSupervisor->value,
        ]);

        $this->actingAs($employee)
            ->get('/requests')
            ->assertOk()
            ->assertSee('Family trip')
            ->assertSee('Waiting for Supervisor');

        $this->actingAs($supervisor)
            ->get('/approvals/supervisor')
            ->assertOk()
            ->assertSee('Family trip');

        Notification::assertSentTo($supervisor, LeaveRequestStatusNotification::class);
    }

    public function test_a_leave_type_that_requires_an_attachment_blocks_submission_without_one(): void
    {
        ['employee' => $employee] = $this->actors();
        $type = LeaveType::factory()->requiresAttachment()->create();

        $this->actingAs($employee)->post('/requests', [
            'leave_type_id' => $type->id,
            'start_date' => Carbon::now()->addDays(1)->toDateString(),
            'end_date' => Carbon::now()->addDays(1)->toDateString(),
            'reason' => 'Sick',
        ])->assertSessionHasErrors('attachment');

        $this->assertDatabaseCount('leave_requests', 0);
    }

    public function test_submission_is_blocked_when_the_request_exceeds_the_remaining_balance(): void
    {
        ['employee' => $employee] = $this->actors();
        $type = LeaveType::factory()->create();

        LeaveBalance::factory()->create([
            'user_id' => $employee->id,
            'leave_type_id' => $type->id,
            'quota' => 1,
            'used' => 0,
        ]);

        $this->actingAs($employee)->post('/requests', [
            'leave_type_id' => $type->id,
            'start_date' => Carbon::now()->addDays(1)->toDateString(),
            'end_date' => Carbon::now()->addDays(3)->toDateString(),
            'reason' => 'Too long',
        ])->assertSessionHasErrors('end_date');

        $this->assertDatabaseCount('leave_requests', 0);
    }

    public function test_supervisor_approval_moves_the_request_to_the_hr_queue(): void
    {
        ['employee' => $employee, 'supervisor' => $supervisor, 'hr' => $hr] = $this->actors();
        $request = LeaveRequest::factory()->create(['user_id' => $employee->id]);

        $this->actingAs($supervisor)
            ->patch("/approvals/{$request->id}/supervisor", ['decision' => 'approved'])
            ->assertRedirect();

        $this->assertSame(LeaveRequestStatus::PendingHr, $request->fresh()->status);
        $this->assertDatabaseHas('approval_actions', [
            'leave_request_id' => $request->id,
            'actor_id' => $supervisor->id,
            'step' => 'supervisor',
            'decision' => 'approved',
        ]);

        $this->actingAs($hr)
            ->get('/hr/approvals')
            ->assertOk()
            ->assertSee($employee->name);
    }

    public function test_hr_approval_fully_approves_and_deducts_the_balance(): void
    {
        ['employee' => $employee, 'hr' => $hr] = $this->actors();
        $type = LeaveType::factory()->create();
        $request = LeaveRequest::factory()->status(LeaveRequestStatus::PendingHr)->create([
            'user_id' => $employee->id,
            'leave_type_id' => $type->id,
            'days_requested' => 3,
            'start_date' => Carbon::now()->addDays(2)->toDateString(),
            'end_date' => Carbon::now()->addDays(4)->toDateString(),
        ]);
        $balance = LeaveBalance::factory()->create([
            'user_id' => $employee->id,
            'leave_type_id' => $type->id,
            'year' => Carbon::now()->year,
            'quota' => 12,
            'used' => 0,
        ]);

        $this->actingAs($hr)
            ->patch("/hr/approvals/{$request->id}/hr", ['decision' => 'approved'])
            ->assertRedirect();

        $this->assertSame(LeaveRequestStatus::Approved, $request->fresh()->status);
        $this->assertSame(3, $balance->fresh()->used);
    }

    public function test_rejection_requires_a_comment(): void
    {
        ['employee' => $employee, 'supervisor' => $supervisor] = $this->actors();
        $request = LeaveRequest::factory()->create(['user_id' => $employee->id]);

        $this->actingAs($supervisor)
            ->patch("/approvals/{$request->id}/supervisor", ['decision' => 'rejected'])
            ->assertSessionHasErrors('comment');

        $this->assertSame(LeaveRequestStatus::PendingSupervisor, $request->fresh()->status);
    }

    public function test_supervisor_rejection_does_not_touch_the_balance(): void
    {
        ['employee' => $employee, 'supervisor' => $supervisor] = $this->actors();
        $type = LeaveType::factory()->create();
        $request = LeaveRequest::factory()->create([
            'user_id' => $employee->id,
            'leave_type_id' => $type->id,
        ]);
        $balance = LeaveBalance::factory()->create([
            'user_id' => $employee->id,
            'leave_type_id' => $type->id,
            'quota' => 12,
            'used' => 0,
        ]);

        $this->actingAs($supervisor)->patch("/approvals/{$request->id}/supervisor", [
            'decision' => 'rejected',
            'comment' => 'Insufficient notice',
        ])->assertRedirect();

        $this->assertSame(LeaveRequestStatus::RejectedBySupervisor, $request->fresh()->status);
        $this->assertSame(0, $balance->fresh()->used);
    }

    public function test_an_employee_cannot_view_another_employees_request(): void
    {
        $owner = User::factory()->create(['role' => UserRole::Employee]);
        $other = User::factory()->create(['role' => UserRole::Employee]);
        $request = LeaveRequest::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($other)
            ->get("/requests/{$request->id}")
            ->assertForbidden();
    }

    public function test_a_non_assigned_supervisor_cannot_decide_a_request(): void
    {
        ['employee' => $employee] = $this->actors();
        $otherSupervisor = User::factory()->create(['role' => UserRole::Supervisor]);
        $request = LeaveRequest::factory()->create(['user_id' => $employee->id]);

        $this->actingAs($otherSupervisor)
            ->patch("/approvals/{$request->id}/supervisor", ['decision' => 'approved'])
            ->assertForbidden();
    }

    public function test_cancelling_a_pending_request_keeps_the_balance_unchanged(): void
    {
        ['employee' => $employee] = $this->actors();
        $type = LeaveType::factory()->create();
        $request = LeaveRequest::factory()->create([
            'user_id' => $employee->id,
            'leave_type_id' => $type->id,
        ]);
        $balance = LeaveBalance::factory()->create([
            'user_id' => $employee->id,
            'leave_type_id' => $type->id,
            'used' => 0,
        ]);

        $this->actingAs($employee)
            ->patch("/requests/{$request->id}/cancel")
            ->assertRedirect();

        $this->assertSame(LeaveRequestStatus::Cancelled, $request->fresh()->status);
        $this->assertSame(0, $balance->fresh()->used);
    }

    public function test_cancelling_a_fully_approved_request_restores_the_balance(): void
    {
        ['employee' => $employee, 'hr' => $hr] = $this->actors();
        $type = LeaveType::factory()->create();
        $request = LeaveRequest::factory()->status(LeaveRequestStatus::Approved)->create([
            'user_id' => $employee->id,
            'leave_type_id' => $type->id,
            'days_requested' => 4,
        ]);
        $balance = LeaveBalance::factory()->create([
            'user_id' => $employee->id,
            'leave_type_id' => $type->id,
            'used' => 4,
        ]);

        $this->actingAs($hr)
            ->patch("/requests/{$request->id}/cancel")
            ->assertRedirect();

        $this->assertSame(LeaveRequestStatus::Cancelled, $request->fresh()->status);
        $this->assertSame(0, $balance->fresh()->used);
    }

    public function test_balance_page_provisions_a_balance_from_active_leave_types(): void
    {
        ['employee' => $employee] = $this->actors();
        LeaveType::factory()->create(['name' => 'Annual Leave', 'default_annual_quota' => 14]);
        LeaveType::factory()->inactive()->create(['name' => 'Old Leave']);

        $this->actingAs($employee)
            ->get('/balances')
            ->assertOk()
            ->assertSee('Annual Leave')
            ->assertDontSee('Old Leave');

        $this->assertDatabaseHas('leave_balances', [
            'user_id' => $employee->id,
            'quota' => 14,
            'used' => 0,
        ]);
    }

    public function test_hr_can_create_a_leave_type(): void
    {
        ['hr' => $hr] = $this->actors();

        $this->actingAs($hr)->post('/hr/leave-types', [
            'name' => 'Sick Leave',
            'requires_attachment' => true,
            'default_annual_quota' => 10,
            'is_active' => true,
        ])->assertRedirect();

        $this->assertDatabaseHas('leave_types', [
            'name' => 'Sick Leave',
            'requires_attachment' => true,
            'default_annual_quota' => 10,
        ]);
    }

    public function test_employee_cannot_create_a_leave_type(): void
    {
        ['employee' => $employee] = $this->actors();

        $this->actingAs($employee)->post('/hr/leave-types', [
            'name' => 'Sneaky Leave',
            'requires_attachment' => false,
            'default_annual_quota' => 5,
        ])->assertForbidden();
    }

    public function test_balance_adjustment_requires_a_reason_and_is_audited(): void
    {
        ['employee' => $employee, 'hr' => $hr] = $this->actors();
        $type = LeaveType::factory()->create();
        $balance = LeaveBalance::factory()->create([
            'user_id' => $employee->id,
            'leave_type_id' => $type->id,
            'quota' => 12,
            'used' => 0,
        ]);

        $this->actingAs($hr)->patch("/hr/balances/{$employee->id}/{$type->id}", [
            'quota' => 15,
            'year' => $balance->year,
        ])->assertSessionHasErrors('reason');

        $this->assertSame(12, $balance->fresh()->quota);

        $this->actingAs($hr)->patch("/hr/balances/{$employee->id}/{$type->id}", [
            'quota' => 15,
            'reason' => 'Approved carry-over',
            'year' => $balance->year,
        ])->assertRedirect();

        $this->assertSame(15, $balance->fresh()->quota);
        $this->assertDatabaseHas('leave_balance_adjustments', [
            'leave_balance_id' => $balance->id,
            'actor_id' => $hr->id,
            'previous_quota' => 12,
            'new_quota' => 15,
            'reason' => 'Approved carry-over',
        ]);
    }

    public function test_employee_cannot_adjust_a_balance(): void
    {
        ['employee' => $employee] = $this->actors();
        $type = LeaveType::factory()->create();

        $this->actingAs($employee)->patch("/hr/balances/{$employee->id}/{$type->id}", [
            'quota' => 99,
            'reason' => 'Nope',
        ])->assertForbidden();
    }

    public function test_hr_can_export_filtered_requests_as_csv(): void
    {
        ['employee' => $employee, 'hr' => $hr] = $this->actors();
        $request = LeaveRequest::factory()->create(['user_id' => $employee->id]);

        $response = $this->actingAs($hr)->get('/hr/requests/export?employee='.$employee->email);

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $content = $response->streamedContent();
        $this->assertStringContainsString('Employee', $content);
        $this->assertStringContainsString($employee->name, $content);
        $this->assertStringContainsString((string) $request->id, $content);
    }

    public function test_request_detail_back_link_returns_to_the_originating_list(): void
    {
        ['employee' => $employee, 'hr' => $hr] = $this->actors();
        $request = LeaveRequest::factory()->create(['user_id' => $employee->id]);

        // The employee opens the detail from "My Requests": back goes there.
        $this->actingAs($employee)
            ->get("/requests/{$request->id}")
            ->assertOk()
            ->assertSee('Back to my requests')
            ->assertDontSee('Back to all requests');

        // HR's "All Requests" list links into the detail with the HR origin.
        $this->actingAs($hr)
            ->get('/hr/requests')
            ->assertOk()
            ->assertSee(route('requests.show', ['leaveRequest' => $request, 'from' => 'hr']), false);

        // Opened from there, the back link returns to "All Requests".
        $this->actingAs($hr)
            ->get("/requests/{$request->id}?from=hr")
            ->assertOk()
            ->assertSee('Back to all requests')
            ->assertDontSee('Back to my requests');

        // Cancelling from that context keeps the "All Requests" origin.
        $this->actingAs($hr)
            ->patch("/requests/{$request->id}/cancel", ['from' => 'hr'])
            ->assertRedirect(route('requests.show', ['leaveRequest' => $request, 'from' => 'hr']));
    }

    public function test_attachment_upload_stores_a_downloadable_file(): void
    {
        ['employee' => $employee, 'supervisor' => $supervisor, 'hr' => $hr] = $this->actors();
        $type = LeaveType::factory()->requiresAttachment()->create();
        $request = LeaveRequest::factory()->create([
            'user_id' => $employee->id,
            'leave_type_id' => $type->id,
        ]);

        $this->actingAs($employee)
            ->post("/requests/{$request->id}/attachments", [
                'attachment' => UploadedFile::fake()->create('note.pdf', 100, 'application/pdf'),
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('attachments', [
            'leave_request_id' => $request->id,
            'file_name' => 'note.pdf',
        ]);

        $attachment = $request->attachments()->first();

        // PRD 9.2: the owner, the assigned supervisor, and HR may download.
        foreach ([$employee, $supervisor, $hr] as $actor) {
            $this->actingAs($actor)
                ->get("/attachments/{$attachment->id}")
                ->assertOk();
        }

        // Anyone else is refused.
        $this->actingAs(User::factory()->create(['role' => UserRole::Employee]))
            ->get("/attachments/{$attachment->id}")
            ->assertForbidden();

        // The reviewer queues surface the attachment as a working download link.
        $downloadUrl = route('attachments.download', $attachment);

        $this->actingAs($supervisor)
            ->get('/approvals/supervisor')
            ->assertOk()
            ->assertSee($downloadUrl, false);

        $request->update(['status' => LeaveRequestStatus::PendingHr]);

        $this->actingAs($hr)
            ->get('/hr/approvals')
            ->assertOk()
            ->assertSee($downloadUrl, false);
    }
}
