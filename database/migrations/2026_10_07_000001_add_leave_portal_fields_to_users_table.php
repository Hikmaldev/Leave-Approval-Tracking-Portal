<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adds the leave portal fields to the default Laravel users table.
     *
     * PRD Data Spec fields: role, supervisor_id, department. The password is
     * stored in Laravel's standard hashed "password" column (the PRD's
     * "password_hash" is the logical field; Laravel hashes it via the model
     * cast, per PRD 9.2 "passwords are hashed, never stored in plain text").
     *
     * is_active is added to support the PRD rule "deactivate the user instead
     * of deleting" (Data Spec 11.2, User -> LeaveRequest: restrict).
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['employee', 'supervisor', 'hr_admin'])
                ->default('employee')
                ->after('password');

            // Restrict: a user who is still assigned as someone's supervisor
            // cannot be deleted; reassign their reports first.
            $table->foreignId('supervisor_id')
                ->nullable()
                ->after('role')
                ->constrained('users')
                ->restrictOnDelete();

            $table->string('department')->nullable()->after('supervisor_id');
            $table->boolean('is_active')->default(true)->after('department');

            $table->index(['role', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role', 'is_active']);
            $table->dropConstrainedForeignId('supervisor_id');
            $table->dropColumn(['role', 'department', 'is_active']);
        });
    }
};
