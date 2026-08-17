@extends('layouts.app')

@section('title', 'تسجيل الدخول — منصة المعرفة السعودية')

@section('content')
    <section class="max-w-md mx-auto px-4 sm:px-6 py-16">
        <h1 class="text-2xl font-bold text-ink-900 text-center mb-1">تسجيل الدخول</h1>
        <p class="text-sm text-ink-500 text-center mb-8">سجّل الدخول للمشاركة والتعليق ونشر المقالات.</p>

        <form method="POST" action="{{ route('login') }}" class="space-y-5 bg-white border border-ink-100 rounded-2xl p-6">
            @csrf

            <div>
                <label for="email" class="block text-sm font-medium text-ink-700 mb-1.5">البريد الإلكتروني</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus
                    class="w-full rounded-lg border border-ink-200 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-300">
                @error('email')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-ink-700 mb-1.5">كلمة المرور</label>
                <input type="password" id="password" name="password" required
                    class="w-full rounded-lg border border-ink-200 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-300">
                @error('password')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <label class="flex items-center gap-2 text-sm text-ink-600">
                <input type="checkbox" name="remember" class="rounded border-ink-300">
                تذكرني
            </label>

            <button type="submit" class="w-full inline-flex justify-center items-center rounded-lg bg-brand-600 text-white px-5 py-2.5 text-sm font-semibold hover:bg-brand-700 transition-colors">
                تسجيل الدخول
            </button>
        </form>

        <p class="text-center text-sm text-ink-500 mt-6">
            ليس لديك حساب؟
            <a href="{{ route('register') }}" class="text-brand-600 font-medium hover:text-brand-700">إنشاء حساب جديد</a>
        </p>
    </section>
@endsection
