<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    /**
     * Screen: Login (FR-AUTH-01).
     *
     * The seeded demo accounts (DatabaseSeeder) can be offered as one-click
     * autofill buttons. This is always on in local environments and can be
     * enabled on a hosted demo with DEMO_LOGIN_AUTOFILL=true. The emails always
     * come from the database (AGENTS.md rule 1) and only accounts that still
     * use the seeded dev password are offered, so a changed password is never
     * autofilled.
     */
    public function create(): View
    {
        if (! $this->offersDemoAccounts()) {
            return view('auth.login');
        }

        $demoAccounts = User::query()
            ->where('is_demo_account', true)
            ->orderBy('id')
            ->get()
            ->filter(fn (User $user) => Hash::check('password', $user->password))
            ->map(fn (User $user) => [
                'label' => $user->role->label(),
                'email' => $user->email,
                'password' => 'password',
            ])
            ->values();

        return view('auth.login', ['demoAccounts' => $demoAccounts]);
    }

    /**
     * Whether the login screen may offer the seeded demo accounts. Local
     * environments always do; hosted environments opt in explicitly.
     */
    private function offersDemoAccounts(): bool
    {
        return app()->environment('local') || (bool) config('app.demo_login_autofill');
    }

    /**
     * FR-AUTH-01: log in with email and password. Only active accounts may
     * authenticate, since a user is deactivated instead of deleted
     * (Data Spec 11.2).
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $credentials = $request->validated();
        $credentials['is_active'] = true;

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => 'Invalid email or password',
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    /**
     * Log the user out of the application (session-based, PRD 10.1).
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
