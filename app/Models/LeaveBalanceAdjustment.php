<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveBalanceAdjustment extends Model
{
    use HasFactory;

    /**
     * Audit record: append-only, no updated_at.
     */
    public const UPDATED_AT = null;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'leave_balance_id',
        'actor_id',
        'previous_quota',
        'previous_used',
        'new_quota',
        'new_used',
        'reason',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'previous_quota' => 'integer',
            'previous_used' => 'integer',
            'new_quota' => 'integer',
            'new_used' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function leaveBalance(): BelongsTo
    {
        return $this->belongsTo(LeaveBalance::class);
    }

    /**
     * The HR user who recorded the adjustment.
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
