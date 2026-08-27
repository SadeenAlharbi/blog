<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Middleware\EnsureAccountIsActive;
use App\Http\Requests\LoginRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthenticatedSessionController extends Controller
{
    public function create()
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request)
    {
        if (! Auth::attempt($request->validated(), $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => 'البريد الإلكتروني أو كلمة المرور غير صحيحة.',
            ]);
        }

        // Credentials are valid, but the account may have been disabled by a
        // moderator. Sign it straight back out and say so plainly — a generic
        // "wrong password" would leave the member guessing.
        if (! $request->user()->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'email' => EnsureAccountIsActive::MESSAGE,
            ]);
        }

        $request->session()->regenerate();

        return redirect()
            ->to($this->destinationAfterLogin($request, $request->user()))
            ->with('success', 'تم تسجيل الدخول بنجاح.');
    }

    /**
     * Where signing in should land.
     *
     * A reader goes to the site itself; an administrator goes to the dashboard
     * they actually work in.
     *
     * The remembered destination ("url.intended") is honoured only when the
     * account that just signed in can really open it. Without that check the
     * session carries a trap: a guest who lands on an /admin URL has it stored
     * by EnsureUserIsAdmin, and the NEXT person to sign in on that browser —
     * an ordinary member — was sent straight there and met a 403 that had
     * nothing to do with them. The page was never theirs to open, so it is
     * dropped rather than followed.
     */
    private function destinationAfterLogin(Request $request, User $user): string
    {
        $home = $user->isAdmin() ? route('admin.dashboard') : route('home');

        // pull() reads and clears, so a discarded destination cannot linger
        // and surprise the next sign-in on this browser either.
        $intended = $request->session()->pull('url.intended');

        if (! $intended) {
            return $home;
        }

        if (! $user->isAdmin() && $this->pointsAtAdminArea($intended)) {
            return $home;
        }

        return $intended;
    }

    /** Does this URL lead into the admin area? ("/administration" does not.) */
    private function pointsAtAdminArea(string $url): bool
    {
        $path = trim((string) parse_url($url, PHP_URL_PATH), '/');

        return $path === 'admin' || str_starts_with($path, 'admin/');
    }

    public function destroy(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('success', 'تم تسجيل الخروج بنجاح.');
    }
}
