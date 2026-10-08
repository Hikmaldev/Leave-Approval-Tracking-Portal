<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Raised when a business rule is broken (invalid status transition, balance
 * conflict, ...). Rendered as a 422 for API clients and a redirect-with-error
 * for the Blade UI, so the same rule can be enforced from either entry point.
 */
class WorkflowException extends RuntimeException
{
    public function render(Request $request): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $this->getMessage()], 422);
        }

        return back()->withInput()->withErrors(['workflow' => $this->getMessage()]);
    }
}
