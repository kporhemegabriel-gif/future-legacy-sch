<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restricts a route (or route group) to one or more roles.
 *
 * Usage: Route::middleware('role:admin')->group(...)
 *        Route::middleware('role:admin,parent')->group(...)
 *
 * This is intentionally the *only* place role names are compared, so
 * adding a new role later (teacher, accountant, ...) never requires
 * touching individual controllers.
 */
class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401, 'Authentication required.');
        }

        if (! $user->isActive()) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            abort(403, 'This account has been deactivated. Contact the school administrator.');
        }

        if (! in_array($user->role, $roles, true)) {
            abort(403, 'You do not have access to this section.');
        }

        return $next($request);
    }
}
