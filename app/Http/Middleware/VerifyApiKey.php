<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Identifies the CLIENT APPLICATION calling the REST API.
 *
 * This is a separate concern from Sanctum: Sanctum answers "which user is
 * this?", the API key answers "which app is this?". A protected endpoint
 * therefore expects BOTH headers:
 *
 *     X-API-KEY:      <the client key>
 *     Authorization:  Bearer <the user's Sanctum token>
 *
 * The key is read from config (never env() directly, so `config:cache` works)
 * and must live in .env — never in the repository and never in frontend JS.
 *
 * Enforcement is deliberately conditional on a key being CONFIGURED: on an
 * installation that has not set API_KEY yet the middleware steps aside instead
 * of locking the owner out of their own working API. Set API_KEY in .env to
 * turn the layer on.
 */
class VerifyApiKey
{
    public const HEADER = 'X-API-KEY';

    public function handle(Request $request, Closure $next): Response
    {
        $expected = config('services.api.key');

        // Not configured → the layer is inactive (documented in .env.example).
        if (blank($expected)) {
            return $next($request);
        }

        $provided = $request->header(self::HEADER);

        if (blank($provided) || ! hash_equals((string) $expected, (string) $provided)) {
            return response()->json([
                'message' => 'مفتاح الوصول (X-API-KEY) مفقود أو غير صالح.',
            ], 401);
        }

        return $next($request);
    }
}
