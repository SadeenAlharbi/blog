<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class NewPasswordController extends Controller
{
    public function create(Request $request)
    {
        return view('auth.reset-password', ['request' => $request]);
    }

    public function store(Request $request)
    {
        $request->validate(
            [
                'token' => ['required'],
                'email' => ['required', 'email'],
                'password' => ['required', 'confirmed', 'min:8'],
            ],
            [
                'email.required' => 'البريد الإلكتروني مطلوب.',
                'email.email' => 'صيغة البريد الإلكتروني غير صحيحة.',
                'password.required' => 'كلمة المرور مطلوبة.',
                'password.confirmed' => 'تأكيد كلمة المرور غير مطابق.',
                'password.min' => 'كلمة المرور يجب ألا تقل عن 8 أحرف.',
            ]
        );

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('success', 'تم تحديث كلمة المرور بنجاح. يمكنك تسجيل الدخول الآن.')
            : back()->withInput($request->only('email'))
                ->withErrors(['email' => 'رابط إعادة التعيين غير صالح أو منتهي الصلاحية.']);
    }
}
