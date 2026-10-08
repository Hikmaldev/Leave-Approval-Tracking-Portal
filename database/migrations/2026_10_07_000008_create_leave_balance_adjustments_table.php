<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * FR-BAL-05: HR adjusts an employee balance with a mandatory reason, and
     * the reason must be "logged". The PRD Data Spec has no field for it, so
     * this append-only audit table records every adjustment (previous/new
     * quota and used, actor, reason). Same "never delete history" principle
     * as approval_actions.
     */
    public function up(): void
    {
        Schema::create('leave_balance_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('leave_balance_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('actor_id')
                ->constrained('users')
                ->restrictOnDelete();
            $table->unsignedInteger('previous_quota');
            $table->unsignedInteger('previous_used');
            $table->unsignedInteger('new_quota');
            $table->unsignedInteger('new_used');
            $table->text('reason');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['leave_balance_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_balance_adjustments');
    }
};
