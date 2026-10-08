<?php

namespace App\Services;

use App\Enums\LeaveRequestStatus;
use App\Exceptions\WorkflowException;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class LeaveRequestService
{
    public function __construct(
        private readonly AttachmentService $attachments,
        private readonly LeaveBalanceService $balances,
        private readonly NotificationService $notifications,
    ) {}

    /**
     * FR-REQ-02: inclusive calendar day count. Kept in one place so the
     * form preview, the stored value, and the balance deduction agree.
     */
    public function calculateDays(Carbon $start, Carbon $end): int
    {
        return (int) $start->startOfDay()->diffInDays($end->startOfDay()) + 1;
    }

    /**
     * ACT-02 / FR-REQ-01..05: create the request (status pending_supervisor),
     * store the optional attachment, and notify the supervisor. Balance is
     * not touched until final HR approval (FR-BAL-02).
     *
     * @param  array<string, mixed>  $data
     */
    public function submit(User $user, array $data, ?UploadedFile $attachment = null): LeaveRequest
    {
        $start = Carbon::parse($data['start_date']);
        $end = Carbon::parse($data['end_date']);

        $leaveRequest = DB::transaction(function () use ($user, $data, $start, $end, $attachment): LeaveRequest {
            $leaveRequest = LeaveRequest::query()->create([
                'user_id' => $user->id,
                'leave_type_id' => (int) $data['leave_type_id'],
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
                'days_requested' => $this->calculateDays($start, $end),
                'reason' => $data['reason'],
                'status' => LeaveRequestStatus::PendingSupervisor,
            ]);

            if ($attachment !== null) {
                $this->attachments->store($leaveRequest, $attachment);
            }

            return $leaveRequest;
        });

        // FR-NOT-04: notify the assigned supervisor (best effort, logged).
        $user->loadMissing('supervisor');
        if ($user->supervisor !== null) {
            $this->notifications->sendStatusChange($user->supervisor, $leaveRequest, 'new_request');
        }

        return $leaveRequest;
    }

    /**
     * Add an attachment to an existing pending request (ACT-03, standalone
     * upload after submission).
     */
    public function addAttachment(LeaveRequest $leaveRequest, UploadedFile $file): LeaveRequest
    {
        $this->attachments->store($leaveRequest, $file);

        return $leaveRequest->load('attachments');
    }

    /**
     * ACT-04 / FR-REQ-06 / FR-BAL-03: cancel. The owner may cancel while
     * pending; HR may cancel any cancellable request, restoring the balance
     * if it had already been deducted on full approval.
     */
    public function cancel(LeaveRequest $leaveRequest, User $actor): LeaveRequest
    {
        $cancelled = DB::transaction(function () use ($leaveRequest, $actor): LeaveRequest {
            $locked = LeaveRequest::query()->whereKey($leaveRequest->id)->lockForUpdate()->firstOrFail();

            $allowed = $actor->isHrAdmin()
                ? $locked->status->isCancellable()
                : ($locked->user_id === $actor->id && $locked->status->isPending());

            if (! $allowed) {
                throw new WorkflowException(
                    "This request can no longer be cancelled (current status: {$locked->status->label()}).",
                );
            }

            if ($locked->status === LeaveRequestStatus::Approved) {
                $this->balances->restore(
                    $locked->user_id,
                    $locked->leave_type_id,
                    (int) $locked->start_date->year,
                    $locked->days_requested,
                );
            }

            $locked->status = LeaveRequestStatus::Cancelled;
            $locked->save();

            return $locked;
        });

        $cancelled->loadMissing('user');
        $this->notifications->sendStatusChange($cancelled->user, $cancelled, 'cancelled');

        return $cancelled;
    }

    /**
     * FR-HIS-01: the authenticated user's own history.
     *
     * @return Collection<int, LeaveRequest>
     */
    public function forUser(User $user): Collection
    {
        return LeaveRequest::query()
            ->where('user_id', $user->id)
            ->with(['user', 'leaveType', 'attachments', 'approvalActions.actor'])
            ->latest()
            ->get();
    }

    /**
     * FR-APR-01 / FR-AUTH-03: pending requests from the supervisor's direct
     * reports only.
     *
     * @return Collection<int, LeaveRequest>
     */
    public function pendingForSupervisor(User $supervisor): Collection
    {
        return LeaveRequest::query()
            ->where('status', LeaveRequestStatus::PendingSupervisor)
            ->whereIn('user_id', $supervisor->directReports()->pluck('id'))
            ->with(['user', 'leaveType', 'attachments'])
            ->oldest()
            ->get();
    }

    /**
     * FR-APR-03: company-wide queue waiting for the HR final decision.
     *
     * @return Collection<int, LeaveRequest>
     */
    public function pendingForHr(): Collection
    {
        return LeaveRequest::query()
            ->where('status', LeaveRequestStatus::PendingHr)
            ->with(['user', 'leaveType', 'attachments'])
            ->oldest()
            ->get();
    }

    /**
     * FR-HIS-03 / ACT-13: filterable company-wide query for HR.
     *
     * @param  array<string, mixed>  $filters
     * @return Builder<LeaveRequest>
     */
    public function filtered(array $filters): Builder
    {
        return LeaveRequest::query()
            ->with(['user', 'leaveType', 'attachments', 'approvalActions.actor'])
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['employee_id'] ?? null, fn (Builder $query, int $id) => $query->where('user_id', $id))
            ->when($filters['employee'] ?? null, function (Builder $query, string $search): void {
                $query->whereHas('user', function (Builder $userQuery) use ($search): void {
                    $userQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($filters['department'] ?? null, fn (Builder $query, string $department) => $query->whereHas(
                'user',
                fn (Builder $userQuery) => $userQuery->where('department', $department),
            ))
            ->when($filters['from'] ?? null, fn (Builder $query, string $from) => $query->whereDate('start_date', '>=', $from))
            ->when($filters['to'] ?? null, fn (Builder $query, string $to) => $query->whereDate('end_date', '<=', $to))
            ->latest();
    }
}
