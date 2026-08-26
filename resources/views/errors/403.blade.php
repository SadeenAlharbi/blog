@extends('layouts.app')

@section('title', 'لا تملك صلاحية الوصول — منصة المعرفة السعودية')

@section('content')
    <div class="max-w-lg mx-auto px-4 py-20 text-center">
        <div class="mx-auto mb-6 flex h-16 w-16 items-center justify-center rounded-2xl bg-amber-50 text-amber-600 ring-1 ring-amber-100">
            <svg class="h-8 w-8" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
            </svg>
        </div>

        <h1 class="text-2xl font-bold text-ink-900 mb-2">لا تملك صلاحية الوصول</h1>
        <p class="text-ink-500 leading-relaxed mb-6">
            {{ $exception->getMessage() ?: 'هذه الصفحة تتطلّب صلاحيات لا يملكها حسابك الحالي.' }}
        </p>

        <div class="flex items-center justify-center gap-2">
            <a href="{{ route('home') }}" class="inline-flex items-center rounded-xl bg-brand-600 text-white px-5 py-2.5 text-sm font-semibold hover:bg-brand-700 transition-colors">العودة للرئيسية</a>
            <a href="{{ route('posts.index') }}" class="inline-flex items-center rounded-xl border border-ink-200 px-5 py-2.5 text-sm font-medium text-ink-600 hover:bg-ink-50 transition-colors">تصفّح المقالات</a>
        </div>
    </div>
@endsection
