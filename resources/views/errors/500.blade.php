@extends('layouts.app')

@section('title', 'خطأ في الخادم — منصة المعرفة السعودية')

@section('content')
    <div class="max-w-lg mx-auto px-4 py-20 text-center">
        <div class="mx-auto mb-6 flex h-16 w-16 items-center justify-center rounded-2xl bg-red-50 text-red-600 ring-1 ring-red-100">
            <svg class="h-8 w-8" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
            </svg>
        </div>

        <h1 class="text-2xl font-bold text-ink-900 mb-2">حدث خطأ غير متوقع</h1>
        <p class="text-ink-500 leading-relaxed mb-6">
            نعتذر — واجه الخادم مشكلة أثناء تنفيذ طلبك. تم تسجيل الخطأ وسيتم النظر فيه.
        </p>

        <a href="{{ route('home') }}" class="inline-flex items-center rounded-xl bg-brand-600 text-white px-5 py-2.5 text-sm font-semibold hover:bg-brand-700 transition-colors">العودة للرئيسية</a>
    </div>
@endsection
