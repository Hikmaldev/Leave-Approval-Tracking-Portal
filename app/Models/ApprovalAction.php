<?php

namespace App\Models;

use App\Enums\ApprovalDecision;
use App\Enums\ApprovalStep;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovalAction extends Model
{
    use HasFactory;

    /**
     * Audit record: append-only, no updated_at (FR-APR-07).
     */
    public const UPDATED_AT = null;

    public const CREATED_AT = 'decided_at';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'leave_request_id',
        'actor_id',
        'step',
        'decision',
        'comment',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'step' => ApprovalStep::class,
            'decision' => ApprovalDecision::class,
            'decided_at' => 'datetime',
        ];
    }

    public function leaveRequest(): BelongsTo
    {
        return $this->belongsTo(LeaveRequest::class);
    }

    /**
     * The user who made the decision (supervisor or HR).
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
