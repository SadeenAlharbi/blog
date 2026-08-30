@extends('layouts.auth')

@section('title', 'إنشاء حساب — منصة المعرفة السعودية')

@section('content')
    <div class="text-center mb-8">
        <a href="{{ url('/') }}" class="inline-flex mb-4"><x-logo :size="56" /></a>
        <h1 class="text-2xl font-bold text-ink-900">إنشاء حساب جديد</h1>
        <p class="text-sm text-ink-500 mt-1.5">انضم إلى المنصة لمشاركة معرفتك عن المملكة.</p>
    </div>

    <form method="POST" action="{{ route('register') }}" class="space-y-5 bg-white border border-ink-100 rounded-2xl p-6 shadow-sm">
        @csrf

        <div>
            <label for="name" class="block text-sm font-medium text-ink-700 mb-1.5">الاسم الكامل</label>
            <input type="text" id="name" name="name" value="{{ old('name') }}" required autofocus autocomplete="name"
                class="w-full rounded-xl border border-ink-200 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-300 focus:border-brand-300 @error('name') border-red-300 @enderror">
            @error('name')
                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="email" class="block text-sm font-medium text-ink-700 mb-1.5">البريد الإلكتروني</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" required autocomplete="email"
                class="w-full rounded-xl border border-ink-200 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-300 focus:border-brand-300 @error('email') border-red-300 @enderror">
            @error('email')
                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-ink-700 mb-1.5">كلمة المرور</label>
            <input type="password" id="password" name="password" required autocomplete="new-password"
                class="w-full rounded-xl border border-ink-200 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-300 focus:border-brand-300 @error('password') border-red-300 @enderror">
            @error('password')
                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-ink-700 mb-1.5">تأكيد كلمة المرور</label>
            <input type="password" id="password_confirmation" name="password_confirmation" required autocomplete="new-password"
                class="w-full rounded-xl border border-ink-200 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-300 focus:border-brand-300">
        </div>

        <button type="submit" class="w-full inline-flex justify-center items-center rounded-xl bg-brand-600 text-white px-5 py-2.5 text-sm font-semibold hover:bg-brand-700 transition-colors">
            إنشاء الحساب
        </button>
    </form>

    <x-google-button label="إنشاء حساب باستخدام Google" />

    <p class="text-center text-sm text-ink-500 mt-6">
        لديك حساب بالفعل؟
        <a href="{{ route('login') }}" class="text-brand-600 font-medium hover:text-brand-700">تسجيل الدخول</a>
    </p>
@endsection
