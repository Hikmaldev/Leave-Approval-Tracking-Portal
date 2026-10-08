<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * FR-AUTH-02: restrict routes by role. Role checks happen on the server
     * (PRD 9.2), never only in the UI.
     *
     * Usage: ->middleware('role:supervisor,hr_admin')
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if ($user === null || ! in_array($user->role->value, $roles, true)) {
            abort(403);
        }

        return $next($request);
    }
}
