<?php

namespace App\Models;

use App\Enums\NotificationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationLog extends Model
{
    use HasFactory;

    /**
     * Delivery log: append-only, no updated_at.
     */
    public const UPDATED_AT = null;

    public const CREATED_AT = 'sent_at';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'leave_request_id',
        'recipient_id',
        'type',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => NotificationStatus::class,
            'sent_at' => 'datetime',
        ];
    }

    public function leaveRequest(): BelongsTo
    {
        return $this->belongsTo(LeaveRequest::class);
    }

    /**
     * The user the notification was sent to.
     */
    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }
}
