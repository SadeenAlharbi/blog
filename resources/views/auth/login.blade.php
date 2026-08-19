@extends('layouts.auth')

@section('title', 'تسجيل الدخول — منصة المعرفة السعودية')

@section('content')
    <div class="text-center mb-8">
        <a href="{{ url('/') }}" class="inline-flex mb-4"><x-logo :size="56" /></a>
        <h1 class="text-2xl font-bold text-ink-900">تسجيل الدخول</h1>
        <p class="text-sm text-ink-500 mt-1.5">سجّل الدخول للمشاركة والتعليق ونشر المقالات.</p>
    </div>

    <form method="POST" action="{{ route('login') }}" class="space-y-5 bg-white border border-ink-100 rounded-2xl p-6 shadow-sm">
        @csrf

        <div>
            <label for="email" class="block text-sm font-medium text-ink-700 mb-1.5">البريد الإلكتروني</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email"
                class="w-full rounded-xl border border-ink-200 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-300 focus:border-brand-300 @error('email') border-red-300 @enderror">
            @error('email')
                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <div class="flex items-center justify-between mb-1.5">
                <label for="password" class="block text-sm font-medium text-ink-700">كلمة المرور</label>
                <a href="{{ route('password.request') }}" class="text-xs text-brand-600 hover:text-brand-700 font-medium">نسيت كلمة المرور؟</a>
            </div>
            <input type="password" id="password" name="password" required autocomplete="current-password"
                class="w-full rounded-xl border border-ink-200 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-300 focus:border-brand-300 @error('password') border-red-300 @enderror">
            @error('password')
                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
            @enderror
        </div>

        <label class="flex items-center gap-2 text-sm text-ink-600">
            <input type="checkbox" name="remember" class="rounded border-ink-300 text-brand-600 focus:ring-brand-300">
            تذكرني
        </label>

        <button type="submit" class="w-full inline-flex justify-center items-center rounded-xl bg-brand-600 text-white px-5 py-2.5 text-sm font-semibold hover:bg-brand-700 transition-colors">
            تسجيل الدخول
        </button>
    </form>

    <p class="text-center text-sm text-ink-500 mt-6">
        ليس لديك حساب؟
        <a href="{{ route('register') }}" class="text-brand-600 font-medium hover:text-brand-700">إنشاء حساب جديد</a>
    </p>
@endsection
