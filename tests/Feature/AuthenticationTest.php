<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_is_rendered(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('Log in to your account');
    }

    public function test_users_can_authenticate_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'password' => 'password',
            'role' => UserRole::Employee,
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_inactive_users_cannot_authenticate(): void
    {
        $user = User::factory()->create([
            'password' => 'password',
            'is_active' => false,
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_hr_admin_can_open_the_hr_approval_queue(): void
    {
        $user = User::factory()->create(['role' => UserRole::HrAdmin]);

        $this->actingAs($user)
            ->get('/hr/approvals')
            ->assertOk()
            ->assertSee('HR approval queue');
    }

    public function test_employee_cannot_open_the_hr_approval_queue(): void
    {
        $user = User::factory()->create(['role' => UserRole::Employee]);

        $this->actingAs($user)
            ->get('/hr/approvals')
            ->assertForbidden();
    }
}
