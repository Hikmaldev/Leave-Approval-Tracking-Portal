<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * PRD Data Spec: NotificationLog (leave_request_id, recipient_id, type,
     * sent_at, status). Optional entity, used so a failed email is never
     * silent (FR-NOT acceptance criteria).
     *
     * Delete rules (Data Spec 11.2):
     * - LeaveRequest -> NotificationLog: cascade.
     * - User (recipient) -> NotificationLog: restrict, for the same audit
     *   reason as the approval actor (deactivate the user instead).
     */
    public function up(): void
    {
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('leave_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('recipient_id')->constrained('users')->restrictOnDelete();
            $table->string('type');
            $table->enum('status', ['sent', 'failed']);
            $table->timestamp('sent_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
    }
};
