<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * PRD Data Spec: LeaveRequest (user_id, leave_type_id, start_date,
     * end_date, days_requested, reason, status).
     *
     * Delete rules (Data Spec 11.2):
     * - User -> LeaveRequest: restrict (history must be preserved).
     * - LeaveType -> LeaveRequest: restrict (deactivate the type instead).
     */
    public function up(): void
    {
        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('leave_type_id')->constrained()->restrictOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            $table->unsignedInteger('days_requested');
            $table->text('reason');
            $table->enum('status', [
                'pending_supervisor',
                'pending_hr',
                'approved',
                'rejected_by_supervisor',
                'rejected_by_hr',
                'cancelled',
            ])->default('pending_supervisor');
            $table->timestamps();

            // Approval queues filter by status; histories by employee + date.
            $table->index(['status', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_requests');
    }
};
