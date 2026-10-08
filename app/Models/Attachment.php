<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attachment extends Model
{
    use HasFactory;

    /**
     * Attachments are immutable after upload: one timestamp, no updated_at.
     */
    public const UPDATED_AT = null;

    public const CREATED_AT = 'uploaded_at';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'leave_request_id',
        'file_path',
        'file_name',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'uploaded_at' => 'datetime',
        ];
    }

    public function leaveRequest(): BelongsTo
    {
        return $this->belongsTo(LeaveRequest::class);
    }
}
