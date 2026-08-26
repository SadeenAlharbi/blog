@extends('layouts.auth')

@section('title', 'تفعيل البريد الإلكتروني — منصة المعرفة السعودية')

@section('content')
    <div class="text-center mb-8">
        <a href="{{ url('/') }}" class="inline-flex mb-4"><x-logo :size="56" /></a>
        <h1 class="text-2xl font-bold text-ink-900">فعّل بريدك الإلكتروني</h1>
        <p class="text-sm text-ink-500 mt-1.5 leading-relaxed">
            أرسلنا رابط تفعيل إلى <span class="font-semibold text-ink-700">{{ auth()->user()->email }}</span>.
            افتح الرابط لتتمكن من نشر المقالات والتعليق.
        </p>
    </div>

    <div class="bg-white border border-ink-100 rounded-2xl p-6 shadow-sm space-y-5">
        <div class="flex items-start gap-3 rounded-xl bg-brand-50 border border-brand-100 p-4">
            <svg class="h-5 w-5 shrink-0 text-brand-600 mt-0.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
            </svg>
            <p class="text-sm text-ink-700 leading-relaxed">
                لم يصلك البريد؟ تحقّق من مجلد الرسائل غير المرغوب فيها، أو اطلب إرسال الرابط مرة أخرى.
            </p>
        </div>

        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit"
                    class="w-full inline-flex justify-center items-center rounded-xl bg-brand-600 text-white px-5 py-2.5 text-sm font-semibold hover:bg-brand-700 transition-colors">
                إعادة إرسال رابط التفعيل
            </button>
        </form>

        <div class="flex items-center gap-3 pt-1">
            <a href="{{ route('dashboard') }}"
               class="flex-1 inline-flex justify-center items-center rounded-xl border border-ink-200 px-5 py-2.5 text-sm font-medium text-ink-600 hover:border-ink-300 transition-colors">
                الذهاب إلى لوحتي
            </a>

            <form method="POST" action="{{ route('logout') }}" class="flex-1">
                @csrf
                <button type="submit"
                        class="w-full inline-flex justify-center items-center rounded-xl border border-ink-200 px-5 py-2.5 text-sm font-medium text-ink-600 hover:border-ink-300 transition-colors">
                    تسجيل خروج
                </button>
            </form>
        </div>
    </div>

    <p class="text-center text-xs text-ink-400 mt-6 leading-relaxed">
        يمكنك تصفّح المنصة وقراءة المقالات دون تفعيل. التفعيل مطلوب لنشر المقالات وكتابة التعليقات فقط.
    </p>
@endsection
