<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Development-only accounts so the team can log in and review the UI.
     *
     * Business data (leave types, balances, requests) is intentionally not
     * seeded: controllers currently send empty collections and every page
     * must show its real empty state (AGENTS.md rules 1-3).
     *
     * These accounts are for local development only; do not run in production.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'hr@leave-portal.test'],
            [
                'name' => 'HR Admin',
                'password' => 'password',
                'role' => UserRole::HrAdmin,
                'department' => 'HR',
                'is_active' => true,
                'is_demo_account' => true,
            ],
        );

        $supervisor = User::updateOrCreate(
            ['email' => 'supervisor@leave-portal.test'],
            [
                'name' => 'Supervisor One',
                'password' => 'password',
                'role' => UserRole::Supervisor,
                'department' => 'Operations',
                'is_active' => true,
                'is_demo_account' => true,
            ],
        );

        User::updateOrCreate(
            ['email' => 'employee@leave-portal.test'],
            [
                'name' => 'Employee One',
                'password' => 'password',
                'role' => UserRole::Employee,
                'department' => 'Operations',
                'supervisor_id' => $supervisor->id,
                'is_active' => true,
                'is_demo_account' => true,
            ],
        );
    }
}
