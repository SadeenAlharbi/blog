@extends('layouts.app')

@section('title', 'الصفحة غير موجودة — منصة المعرفة السعودية')

@section('content')
    <div class="max-w-lg mx-auto px-4 py-20 text-center">
        <div class="mx-auto mb-6 flex h-16 w-16 items-center justify-center rounded-2xl bg-brand-50 text-brand-600 ring-1 ring-brand-100">
            <svg class="h-8 w-8" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
            </svg>
        </div>

        <h1 class="text-2xl font-bold text-ink-900 mb-2">الصفحة غير موجودة</h1>
        <p class="text-ink-500 leading-relaxed mb-6">
            الرابط الذي فتحته غير صحيح، أو أن المحتوى لم يعد متاحاً.
        </p>

        <div class="flex items-center justify-center gap-2">
            <a href="{{ route('home') }}" class="inline-flex items-center rounded-xl bg-brand-600 text-white px-5 py-2.5 text-sm font-semibold hover:bg-brand-700 transition-colors">العودة للرئيسية</a>
            <a href="{{ route('posts.index') }}" class="inline-flex items-center rounded-xl border border-ink-200 px-5 py-2.5 text-sm font-medium text-ink-600 hover:bg-ink-50 transition-colors">تصفّح المقالات</a>
        </div>
    </div>
@endsection
