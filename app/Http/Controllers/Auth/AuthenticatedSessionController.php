<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Middleware\EnsureAccountIsActive;
use App\Http\Requests\LoginRequest;
use App\Support\LoginDestination;
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
            ->to(LoginDestination::for($request, $request->user()))
            ->with('success', 'تم تسجيل الدخول بنجاح.');
    }

    public function destroy(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('success', 'تم تسجيل الخروج بنجاح.');
    }
}
