<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * E2E acceptance-test dataset (TestSprite) — development only.
 *
 * Extends the base development accounts with one dedicated account per UI
 * flow so TestSprite tests stay deterministic and never collide with each
 * other's data. Password is the same dev password (`password`) as the base
 * seeder. No business rows (leave types, balances, requests) are created
 * here; those are created by the tests themselves or during run setup.
 */
class TestDatasetSeeder extends Seeder
{
    public function run(): void
    {
        $supervisor = User::where('email', 'supervisor@leave-portal.test')->firstOrFail();

        // Fresh accounts used by the empty-state assertions.
        $this->account(
            'fresh.employee@leave-portal.test',
            'Fresh Employee',
            UserRole::Employee,
            $supervisor,
        );
        $this->account('empty.supervisor@leave-portal.test', 'Empty Supervisor', UserRole::Supervisor);

        // One employee per mutation flow, all reporting to the dev supervisor.
        $flows = [
            ['flow.daycalc@leave-portal.test', 'Flow Daycalc'],
            ['flow.submit@leave-portal.test', 'Flow Submit'],
            ['flow.attach@leave-portal.test', 'Flow Attach'],
            ['flow.reject@leave-portal.test', 'Flow Reject'],
            ['flow.full@leave-portal.test', 'Flow Full'],
            ['flow.cancel@leave-portal.test', 'Flow Cancel'],
            ['flow.cancelhr@leave-portal.test', 'Flow Cancel Hr'],
            ['flow.access@leave-portal.test', 'Flow Access'],
        ];

        foreach ($flows as [$email, $name]) {
            $this->account($email, $name, UserRole::Employee, $supervisor);
        }
    }

    private function account(string $email, string $name, UserRole $role, ?User $supervisor = null): void
    {
        User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => 'password',
                'role' => $role,
                'department' => $supervisor?->department ?? 'Operations',
                'supervisor_id' => $supervisor?->id,
                'is_active' => true,
            ],
        );
    }
}
