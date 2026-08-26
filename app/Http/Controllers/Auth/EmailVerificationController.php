<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Laravel's own email verification flow — nothing custom.
 *
 * `EmailVerificationRequest` (framework class) validates the signed URL and
 * checks that the id/hash in the link belong to the signed-in user, so a link
 * cannot be replayed for another account. The three actions below are the
 * standard notice / verify / resend trio.
 */
class EmailVerificationController extends Controller
{
    /** "Check your inbox" — where `verified` middleware sends an unverified user. */
    public function notice(Request $request)
    {
        return $request->user()->hasVerifiedEmail()
            ? redirect()->route('dashboard')
            : view('auth.verify-email');
    }

    /** The signed link itself. Marks the address verified, once. */
    public function verify(EmailVerificationRequest $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('dashboard')->with('success', 'بريدك الإلكتروني مُفعّل بالفعل.');
        }

        if ($request->user()->markEmailAsVerified()) {
            event(new Verified($request->user()));
        }

        return redirect()->route('dashboard')->with('success', 'تم تفعيل بريدك الإلكتروني بنجاح.');
    }

    /** Send the link again. Rate limited on the route. */
    public function send(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('dashboard');
        }

        $request->user()->sendEmailVerificationNotification();

        return back()->with('success', 'تم إرسال رابط التفعيل إلى بريدك الإلكتروني.');
    }
}
