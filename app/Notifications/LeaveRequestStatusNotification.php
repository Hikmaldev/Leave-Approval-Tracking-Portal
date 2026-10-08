<?php

namespace App\Notifications;

use App\Models\LeaveRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LeaveRequestStatusNotification extends Notification
{
    use Queueable;

    /**
     * @param  string  $type  One of: new_request, supervisor_approved,
     *                        pending_hr, hr_approved, rejected, cancelled.
     */
    public function __construct(
        public LeaveRequest $leaveRequest,
        public string $type,
        public ?string $comment = null,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $request = $this->leaveRequest;
        $employee = $request->user?->name ?? 'An employee';
        $type = $request->leaveType?->name ?? 'leave';
        $dates = $request->start_date?->format('M j, Y').' – '.$request->end_date?->format('M j, Y');

        $message = (new MailMessage)->greeting('Leave Portal');

        return match ($this->type) {
            'new_request' => $message
                ->subject('A new leave request needs your review')
                ->line("{$employee} submitted a {$type} request for {$dates} ({$request->days_requested} day(s)).")
                ->line('Open the supervisor approval queue to review it.'),

            'supervisor_approved' => $message
                ->subject('Your request was approved by your supervisor')
                ->line("Your {$type} request for {$dates} was approved by your supervisor and is now waiting for HR.")
                ->line('No balance has been deducted yet.'),

            'pending_hr' => $message
                ->subject('A leave request is waiting for HR approval')
                ->line("{$employee}'s {$type} request for {$dates} was approved by the supervisor and needs your final decision."),

            'hr_approved' => $message
                ->subject('Your request is fully approved')
                ->line("Your {$type} request for {$dates} has been fully approved.")
                ->line("{$request->days_requested} day(s) were deducted from your balance."),

            'rejected' => $message
                ->subject('Your request was rejected')
                ->line("Your {$type} request for {$dates} was rejected.")
                ->when($this->comment, fn (MailMessage $mail) => $mail->line("Reason: {$this->comment}"))
                ->line('No balance was deducted.'),

            'cancelled' => $message
                ->subject('Your request was cancelled')
                ->line("Your {$type} request for {$dates} was cancelled.")
                ->line('Any deducted days were restored to your balance.'),

            default => $message
                ->subject('Your leave request was updated')
                ->line("Your {$type} request for {$dates} was updated."),
        };
    }
}
