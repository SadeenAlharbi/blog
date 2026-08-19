@extends('layouts.auth')

@section('title', 'إعادة تعيين كلمة المرور — منصة المعرفة السعودية')

@section('content')
    <div class="text-center mb-8">
        <a href="{{ url('/') }}" class="inline-flex mb-4"><x-logo :size="56" /></a>
        <h1 class="text-2xl font-bold text-ink-900">تعيين كلمة مرور جديدة</h1>
        <p class="text-sm text-ink-500 mt-1.5">اختر كلمة مرور جديدة لحسابك.</p>
    </div>

    <form method="POST" action="{{ route('password.update') }}" class="space-y-5 bg-white border border-ink-100 rounded-2xl p-6 shadow-sm">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div>
            <label for="email" class="block text-sm font-medium text-ink-700 mb-1.5">البريد الإلكتروني</label>
            <input type="email" id="email" name="email" value="{{ old('email', $request->email) }}" required autofocus autocomplete="email"
                class="w-full rounded-xl border border-ink-200 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-300 focus:border-brand-300 @error('email') border-red-300 @enderror">
            @error('email')
                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-ink-700 mb-1.5">كلمة المرور الجديدة</label>
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
            حفظ كلمة المرور
        </button>
    </form>
@endsection
