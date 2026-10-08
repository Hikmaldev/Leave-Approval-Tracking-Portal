<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaveBalance extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'leave_type_id',
        'year',
        'quota',
        'used',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'quota' => 'integer',
            'used' => 'integer',
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
     * Append-only audit of HR adjustments (FR-BAL-05).
     */
    public function adjustments(): HasMany
    {
        return $this->hasMany(LeaveBalanceAdjustment::class);
    }

    /**
     * Days still available ("8 / 12" style display in the design system).
     */
    public function remainingDays(): int
    {
        return $this->quota - $this->used;
    }
}
