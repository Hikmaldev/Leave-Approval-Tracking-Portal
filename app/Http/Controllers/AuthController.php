<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    /**
     * Screen: Login (FR-AUTH-01).
     */
    public function create(): View
    {
        return view('auth.login');
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
