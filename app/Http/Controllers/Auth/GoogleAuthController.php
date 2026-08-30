<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Middleware\EnsureAccountIsActive;
use App\Models\User;
use App\Support\LoginDestination;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

/**
 * "Continue with Google" — one implementation serving both the sign-in and the
 * sign-up page. Starting from either page runs exactly this flow; the pages
 * differ only in where the visitor came from.
 *
 * What this deliberately does NOT do:
 *   • replace email + password sign-in, which is untouched;
 *   • touch Sanctum or the REST API, which keep their own token auth;
 *   • grant any role beyond the ordinary `user`.
 */
class GoogleAuthController extends Controller
{
    /** Shown when Google is not configured, or when it hands back nothing usable. */
    private const UNAVAILABLE = 'تعذّر تسجيل الدخول عبر Google حالياً. يمكنك الدخول بالبريد وكلمة المرور.';

    /** Send the visitor to Google to pick an account. */
    public function redirect(): RedirectResponse
    {
        if (! self::isConfigured()) {
            return redirect()->route('login')->with('error', self::UNAVAILABLE);
        }

        try {
            /*
             * prompt=select_account makes Google show its account chooser every
             * time. Without it Google silently reuses whichever account the
             * browser is already signed into, so anyone with a second address —
             * or simply sharing a computer — has no way to reach it and lands
             * in the wrong account.
             */
            return Socialite::driver('google')
                ->with(['prompt' => 'select_account'])
                ->redirect();
        } catch (Throwable $e) {
            // A bad client id or a malformed redirect URI surfaces here.
            return $this->fail($e, 'redirect');
        }
    }

    /** Google sends the visitor back here. Never behind `auth` — nobody is signed in yet. */
    public function callback(Request $request): RedirectResponse
    {
        if (! self::isConfigured()) {
            return redirect()->route('login')->with('error', self::UNAVAILABLE);
        }

        // The visitor pressed "Cancel" on Google's screen, or Google refused.
        if ($request->filled('error')) {
            return redirect()->route('login')->with('error', 'تم إلغاء تسجيل الدخول عبر Google.');
        }

        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (Throwable $e) {
            // Mismatched redirect URI, a stale/replayed state token, a network
            // failure — all land here, and none of them may reach the visitor
            // as a stack trace.
            return $this->fail($e, 'callback');
        }

        $email = filter_var($googleUser->getEmail(), FILTER_VALIDATE_EMAIL) ?: null;
        $providerId = (string) $googleUser->getId();

        // No usable address means no account: a half-built user with no way to
        // sign in or reset a password is worse than a clear refusal.
        if (! $email || $providerId === '') {
            return redirect()->route('login')
                ->with('error', 'لم يزوّدنا Google ببريد إلكتروني صالح، فلم يتم إنشاء حساب. جرّب الدخول بالبريد وكلمة المرور.');
        }

        $user = $this->resolveUser($googleUser, $email, $providerId);

        /*
         * The disabled check happens BEFORE any session is created, so Google
         * cannot become a way around the moderators' decision. Note this runs
         * for an account found by email too — a disabled member does not get a
         * fresh account by arriving through Google instead.
         */
        if (! $user->is_active) {
            return redirect()->route('login')->with('error', EnsureAccountIsActive::MESSAGE);
        }

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        // The same destination rules as email + password sign-in.
        return redirect()
            ->to(LoginDestination::for($request, $user))
            ->with('success', 'تم تسجيل الدخول عبر Google بنجاح.');
    }

    /**
     * Find the account this Google identity belongs to, or make one.
     *
     * Order matters, and it is what keeps one address to one account:
     *   1. the Google id we already stored — survives the person renaming
     *      their Google account or changing its display name;
     *   2. the email address — an account that signed up with a password gets
     *      Google LINKED to it rather than a second, duplicate account;
     *   3. otherwise, a brand new ordinary member.
     */
    private function resolveUser(object $googleUser, string $email, string $providerId): User
    {
        $existing = User::where('provider', User::PROVIDER_GOOGLE)
            ->where('provider_id', $providerId)
            ->first();

        if ($existing) {
            return $this->refresh($existing, $googleUser);
        }

        $byEmail = User::where('email', $email)->first();

        if ($byEmail) {
            // Linking only. The role, the password, the articles and every
            // other field stay exactly as they were.
            $byEmail->provider = User::PROVIDER_GOOGLE;
            $byEmail->provider_id = $providerId;
            $this->markVerified($byEmail, $googleUser);
            $byEmail->save();

            return $byEmail;
        }

        $user = new User();
        $user->name = Str::limit(trim((string) $googleUser->getName()) ?: Str::before($email, '@'), 100, '');
        $user->email = $email;
        /*
         * `users.password` is NOT NULL and the traditional sign-in path hashes
         * against it, so the column gets a long random value that is hashed and
         * never shown, sent or logged. It cannot be guessed, and it cannot
         * authenticate anyone. Someone who later wants a password of their own
         * uses "نسيت كلمة المرور" like any other member.
         */
        $user->password = Hash::make(Str::random(64));
        $user->provider = User::PROVIDER_GOOGLE;
        $user->provider_id = $providerId;
        // Explicit, never mass-assigned: `role` is not fillable on User, and a
        // Google sign-in must never mint an administrator.
        $user->role = User::ROLE_USER;
        $user->is_active = true;
        $user->is_super_admin = false;
        $this->markVerified($user, $googleUser);
        $user->save();

        return $user;
    }

    /** Keep a linked account's Google id current without touching anything else. */
    private function refresh(User $user, object $googleUser): User
    {
        if ($user->email_verified_at === null) {
            $this->markVerified($user, $googleUser);
            $user->save();
        }

        return $user;
    }

    /**
     * Google has already proven the person controls this mailbox, so the
     * platform's own verification step is satisfied — otherwise a Google member
     * would be blocked from publishing by a link that is never coming.
     *
     * If Google explicitly says the address is NOT verified, we believe it.
     */
    private function markVerified(User $user, object $googleUser): void
    {
        $claim = $googleUser->user['email_verified'] ?? true;

        if ($claim !== false && $user->email_verified_at === null) {
            $user->email_verified_at = now();
        }
    }

    /**
     * Is Google sign-in switched on for this installation?
     *
     * The class check matters as much as the credentials: if someone fills in
     * .env before running `composer require laravel/socialite`, this keeps the
     * button hidden and the routes polite instead of letting the page die on a
     * missing class.
     */
    public static function isConfigured(): bool
    {
        return class_exists(Socialite::class)
            && filled(config('services.google.client_id'))
            && filled(config('services.google.client_secret'));
    }

    /** Log the real reason for us; show the visitor a plain sentence. */
    private function fail(Throwable $e, string $stage): RedirectResponse
    {
        Log::warning("Google OAuth {$stage} failed: ".$e->getMessage());

        return redirect()->route('login')->with('error', self::UNAVAILABLE);
    }
}
