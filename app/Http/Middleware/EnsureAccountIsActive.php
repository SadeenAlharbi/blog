<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Stops a disabled account from continuing with a session or an API token that
 * was issued BEFORE it was disabled.
 *
 * Blocking the login screen alone is not enough: a member who was signed in (or
 * who holds a Sanctum token) would otherwise keep working until it expired.
 * On the API the offending token is revoked on the spot.
 */
class EnsureAccountIsActive
{
    public const MESSAGE = 'عذراً، تم تعطيل حسابك من قبل إدارة المنصة.';

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->is_active) {
            return $next($request);
        }

        if ($request->is('api/*') || $request->expectsJson()) {
            // Revoke the token being used so it cannot be replayed.
            $user->currentAccessToken()?->delete();

            return response()->json(['message' => self::MESSAGE], 403);
        }

        // Web session: sign the account out and explain why on the login screen.
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->withErrors(['email' => self::MESSAGE]);
    }
}
