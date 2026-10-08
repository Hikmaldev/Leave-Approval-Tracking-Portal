<?php

namespace App\Models;

use App\Enums\LeaveRequestStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaveRequest extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'leave_type_id',
        'start_date',
        'end_date',
        'days_requested',
        'reason',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'days_requested' => 'integer',
            'status' => LeaveRequestStatus::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    /**
     * Cascades on request deletion, but requests are never deleted
     * (Data Spec 11.2, historical records are preserved).
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }

    public function approvalActions(): HasMany
    {
        return $this->hasMany(ApprovalAction::class);
    }

    public function notificationLogs(): HasMany
    {
        return $this->hasMany(NotificationLog::class);
    }

    public function isPending(): bool
    {
        return $this->status->isPending();
    }

    public function isCancellable(): bool
    {
        return $this->status->isCancellable();
    }
}
