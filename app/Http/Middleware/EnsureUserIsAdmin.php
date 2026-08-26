<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gate for the whole /admin area (and the admin API group).
 *
 * This is server-side enforcement, not a hidden menu item: a signed-in
 * non-admin who types an /admin URL by hand gets a 403, and a guest is sent to
 * the login screen. A deactivated account is refused even if it holds the
 * admin role.
 */
class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json(['message' => 'يجب تسجيل الدخول.'], 401);
            }

            return redirect()->guest(route('login'));
        }

        if (! $user->isAdmin() || ! $user->is_active) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json(['message' => 'لا تملك صلاحية الوصول.'], 403);
            }

            abort(403, 'هذه الصفحة مخصّصة لمشرفي المنصة.');
        }

        return $next($request);
    }
}
