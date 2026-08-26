@extends('layouts.app')

@section('title', 'انتهت صلاحية الجلسة — منصة المعرفة السعودية')

@section('content')
    <div class="max-w-lg mx-auto px-4 py-20 text-center">
        <div class="mx-auto mb-6 flex h-16 w-16 items-center justify-center rounded-2xl bg-amber-50 text-amber-600 ring-1 ring-amber-100">
            <svg class="h-8 w-8" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
        </div>

        <h1 class="text-2xl font-bold text-ink-900 mb-2">انتهت صلاحية الجلسة</h1>
        <p class="text-ink-500 leading-relaxed mb-6">
            بقيت الصفحة مفتوحة مدة طويلة فانتهت صلاحية النموذج. يرجى تحديث الصفحة والمحاولة مرة أخرى.
        </p>

        <div class="flex items-center justify-center gap-2">
            <a href="{{ url()->previous() }}" class="inline-flex items-center rounded-xl bg-brand-600 text-white px-5 py-2.5 text-sm font-semibold hover:bg-brand-700 transition-colors">إعادة المحاولة</a>
            <a href="{{ route('home') }}" class="inline-flex items-center rounded-xl border border-ink-200 px-5 py-2.5 text-sm font-medium text-ink-600 hover:bg-ink-50 transition-colors">العودة للرئيسية</a>
        </div>
    </div>
@endsection
