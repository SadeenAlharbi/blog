<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\Request;

/**
 * Where signing in should land — for EVERY way of signing in.
 *
 * This was private to AuthenticatedSessionController until Google sign-in
 * needed the same answer. It lives here so the two entry points cannot drift
 * apart: whatever rule applies to email+password applies to Google as well.
 *
 * A reader goes to the site itself; an administrator goes to the dashboard
 * they actually work in.
 *
 * The remembered destination ("url.intended") is honoured only when the account
 * that just signed in can really open it. Without that check the session
 * carries a trap: a guest who lands on an /admin URL has it stored by
 * EnsureUserIsAdmin, and the next person to sign in on that browser — an
 * ordinary member — was sent straight there and met a 403 that had nothing to
 * do with them.
 */
class LoginDestination
{
    public static function for(Request $request, User $user): string
    {
        $home = $user->isAdmin() ? route('admin.dashboard') : route('home');

        // pull() reads and clears, so a discarded destination cannot linger and
        // surprise the next sign-in on this browser either.
        $intended = $request->session()->pull('url.intended');

        if (! $intended) {
            return $home;
        }

        if (! $user->isAdmin() && self::pointsAtAdminArea($intended)) {
            return $home;
        }

        return $intended;
    }

    /** Does this URL lead into the admin area? ("/administration" does not.) */
    private static function pointsAtAdminArea(string $url): bool
    {
        $path = trim((string) parse_url($url, PHP_URL_PATH), '/');

        return $path === 'admin' || str_starts_with($path, 'admin/');
    }
}
