<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureUserHasRole
{
    /**
     * Usage in routes: ->middleware('role:ed,meo')
     * Within an organisation, its ED is always allowed through (org admin).
     * The platform super-admin may also perform org-level actions on behalf of
     * the client organisation while staying above the tenant boundary.
     */
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = $request->user();

        if (! $user || ! $user->is_active) {
            return response()->json(['message' => 'Unauthorized or inactive account.'], 403);
        }

        if ($user->role === 'super_admin' || $user->role === 'ed' || in_array($user->role, $roles, true)) {
            return $next($request);
        }

        return response()->json(['message' => 'You do not have permission to perform this action.'], 403);
    }
}
