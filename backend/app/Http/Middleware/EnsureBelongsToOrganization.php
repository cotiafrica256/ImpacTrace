<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Guards every org-scoped route (projects, forms, submissions, attendance,
 * reports, org-level user management). A super_admin belongs to no
 * organisation and has no business reading or writing another
 * organisation's project data, so it's blocked here rather than trusted to
 * pass an organization_id of its own choosing.
 */
class EnsureBelongsToOrganization
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'message' => 'Unauthorized.',
            ], 403);
        }

        if ($user->role === 'super_admin') {
            return $next($request);
        }

        if (! $user->organization_id) {
            return response()->json([
                'message' => 'This action is only available to users of a client organisation.',
            ], 403);
        }

        return $next($request);
    }
}
