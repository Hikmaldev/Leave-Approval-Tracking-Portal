<?php

namespace App\Services;

use App\Exceptions\WorkflowException;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class LeaveBalanceService
{
    /**
     * FR-BAL-01 / acceptance 13.4: every employee has a balance record per
     * active leave type for the year, initialised from the type's default
     * annual quota. Missing records are created on demand.
     */
    public function ensureForYear(User $user, int $year): void
    {
        $types = LeaveType::query()->active()->get();

        foreach ($types as $type) {
            LeaveBalance::query()->firstOrCreate(
                [
                    'user_id' => $user->id,
                    'leave_type_id' => $type->id,
                    'year' => $year,
                ],
                [
                    'quota' => $type->default_annual_quota,
                    'used' => 0,
                ],
            );
        }
    }

    /**
     * Ensure balances exist for every active user, used by HR balance
     * management so the company-wide view is never artificially empty.
     */
    public function ensureForAllUsers(int $year): void
    {
        User::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->each(fn (User $user) => $this->ensureForYear($user, $year));
    }

    /**
     * The user's balances for a year, with the leave type eager loaded.
     *
     * @return Collection<int, LeaveBalance>
     */
    public function forUserYear(User $user, int $year): Collection
    {
        $this->ensureForYear($user, $year);

        return LeaveBalance::query()
            ->where('user_id', $user->id)
            ->where('year', $year)
            ->with('leaveType')
            ->join('leave_types', 'leave_types.id', '=', 'leave_balances.leave_type_id')
            ->orderBy('leave_types.name')
            ->select('leave_balances.*')
            ->get();
    }

    /**
     * Deduct days on final HR approval (FR-BAL-02). Must run inside the
     * approval transaction; the row is locked so two concurrent approvals
     * cannot double-deduct.
     */
    public function deduct(int $userId, int $leaveTypeId, int $year, int $days): LeaveBalance
    {
        $balance = $this->lockOrCreate($userId, $leaveTypeId, $year);

        $balance->used += $days;
        $balance->save();

        return $balance;
    }

    /**
     * Restore days when a fully approved request is later cancelled
     * (FR-BAL-03). Never drops below zero.
     */
    public function restore(int $userId, int $leaveTypeId, int $year, int $days): LeaveBalance
    {
        $balance = $this->lockOrCreate($userId, $leaveTypeId, $year);

        $balance->used = max(0, $balance->used - $days);
        $balance->save();

        return $balance;
    }

    /**
     * FR-BAL-05 / ACT-12: HR manually adjusts quota and/or used and the
     * mandatory reason is written to the adjustment audit trail.
     *
     * @param  array{quota?: int|null, used?: int|null, reason: string}  $data
     */
    public function adjust(LeaveBalance $balance, User $actor, array $data): LeaveBalance
    {
        $previousQuota = $balance->quota;
        $previousUsed = $balance->used;

        if (array_key_exists('quota', $data) && $data['quota'] !== null) {
            $balance->quota = (int) $data['quota'];
        }

        if (array_key_exists('used', $data) && $data['used'] !== null) {
            $balance->used = (int) $data['used'];
        }

        if ($balance->quota < $balance->used) {
            throw new WorkflowException('The quota cannot be lower than the days already used.');
        }

        if ($previousQuota === $balance->quota && $previousUsed === $balance->used) {
            // Nothing changed: do not write a misleading audit entry.
            return $balance;
        }

        $balance->save();

        $balance->adjustments()->create([
            'actor_id' => $actor->id,
            'previous_quota' => $previousQuota,
            'previous_used' => $previousUsed,
            'new_quota' => $balance->quota,
            'new_used' => $balance->used,
            'reason' => $data['reason'],
        ]);

        return $balance;
    }

    /**
     * Lock an existing balance row, or create it (from the type default)
     * and lock it. Called only inside a transaction.
     */
    protected function lockOrCreate(int $userId, int $leaveTypeId, int $year): LeaveBalance
    {
        $balance = LeaveBalance::query()
            ->where('user_id', $userId)
            ->where('leave_type_id', $leaveTypeId)
            ->where('year', $year)
            ->lockForUpdate()
            ->first();

        if ($balance !== null) {
            return $balance;
        }

        $type = LeaveType::query()->findOrFail($leaveTypeId);

        $balance = LeaveBalance::query()->create([
            'user_id' => $userId,
            'leave_type_id' => $leaveTypeId,
            'year' => $year,
            'quota' => $type->default_annual_quota,
            'used' => 0,
        ]);

        return LeaveBalance::query()
            ->whereKey($balance->id)
            ->lockForUpdate()
            ->firstOrFail();
    }
}
