<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    /**
     * ACT-01: session login for API/JS consumers. Mirrors the web controller
     * so both entry points share the same credential and is_active rules.
     */
    public function store(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();
        $credentials['is_active'] = true;

        if (! Auth::attempt($credentials)) {
            throw ValidationException::withMessages([
                'email' => 'Invalid email or password',
            ]);
        }

        $request->session()->regenerate();

        return (new UserResource($request->user()))->response();
    }
}
