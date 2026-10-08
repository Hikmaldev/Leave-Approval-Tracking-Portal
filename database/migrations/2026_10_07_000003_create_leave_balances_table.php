<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * PRD Data Spec: LeaveBalance (user_id, leave_type_id, year, quota, used).
     *
     * Delete rules (Data Spec 11.2):
     * - User -> LeaveBalance: cascade.
     * - LeaveType -> LeaveBalance: restrict (deactivate the type instead).
     */
    public function up(): void
    {
        Schema::create('leave_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('leave_type_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('year');
            $table->unsignedInteger('quota');
            $table->unsignedInteger('used')->default(0);
            $table->timestamps();

            // One balance record per employee, per leave type, per year.
            $table->unique(['user_id', 'leave_type_id', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_balances');
    }
};
