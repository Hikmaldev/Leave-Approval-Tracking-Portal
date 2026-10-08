<?php

namespace App\Services;

use App\Enums\NotificationStatus;
use App\Models\LeaveRequest;
use App\Models\NotificationLog;
use App\Models\User;
use App\Notifications\LeaveRequestStatusNotification;

class NotificationService
{
    /**
     * Attempt to email a status change and always persist the delivery
     * outcome, so a failed email is never silent (FR-NOT acceptance
     * criteria, PRD risk "Email notifications fail silently").
     */
    public function sendStatusChange(
        User $recipient,
        LeaveRequest $leaveRequest,
        string $type,
        ?string $comment = null,
    ): NotificationLog {
        $status = NotificationStatus::Sent;

        try {
            $recipient->notify(new LeaveRequestStatusNotification($leaveRequest, $type, $comment));
        } catch (\Throwable $exception) {
            report($exception);
            $status = NotificationStatus::Failed;
        }

        return NotificationLog::create([
            'leave_request_id' => $leaveRequest->id,
            'recipient_id' => $recipient->id,
            'type' => $type,
            'status' => $status,
        ]);
    }
}
