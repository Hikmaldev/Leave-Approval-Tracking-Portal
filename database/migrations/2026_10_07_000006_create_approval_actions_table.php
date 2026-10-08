<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * PRD Data Spec: ApprovalAction (leave_request_id, actor_id, step,
     * decision, comment, decided_at). This is the audit trail (FR-APR-07).
     *
     * Delete rules (Data Spec 11.2):
     * - LeaveRequest -> ApprovalAction: cascade.
     * - User (actor) -> ApprovalAction: restrict. The PRD does not list this
     *   relationship explicitly, but the audit trail must never be deleted,
     *   so a user with recorded actions cannot be hard-deleted (deactivate
     *   the user instead, per Data Spec 11.2).
     */
    public function up(): void
    {
        Schema::create('approval_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('leave_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $table->enum('step', ['supervisor', 'hr']);
            $table->enum('decision', ['approved', 'rejected']);
            $table->text('comment')->nullable();
            $table->timestamp('decided_at')->useCurrent();

            // MVP integrity guard: one recorded action per step per request
            // ("one request accumulates one action per step, up to two").
            $table->unique(['leave_request_id', 'step']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_actions');
    }
};
