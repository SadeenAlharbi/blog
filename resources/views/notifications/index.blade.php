@extends('layouts.app')

@section('title', 'الإشعارات — منصة المعرفة السعودية')

@section('content')
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div class="flex items-center justify-between gap-4 mb-8">
            <div>
                <h1 class="text-2xl font-bold text-ink-900">الإشعارات</h1>
                {{-- Generic on purpose: this page carries comments, article
                     notices and moderation notices alike. --}}
                <p class="text-sm text-ink-500 mt-1">آخر التنبيهات والتحديثات الخاصة بحسابك</p>
            </div>
            @if (auth()->user()->unreadNotifications()->count() > 0)
                <form method="POST" action="{{ route('notifications.readAll') }}" class="shrink-0">
                    @csrf
                    <button type="submit" class="inline-flex items-center rounded-xl border border-ink-200 bg-white px-4 py-2 text-sm font-semibold text-ink-700 hover:border-brand-300 hover:text-brand-700 transition-colors">
                        تعليم الكل كمقروء
                    </button>
                </form>
            @endif
        </div>

        @forelse ($notifications as $note)
            <x-notification-item :note="$note" :action="route('notifications.read', $note->id)" />
        @empty
            <div class="rounded-2xl border border-ink-100 bg-white px-6 py-16 text-center">
                <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-ink-50 text-ink-300">
                    <svg class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" /></svg>
                </div>
                <p class="text-ink-600 font-medium">لا توجد إشعارات بعد</p>
                <p class="text-sm text-ink-400 mt-1">ستظهر هنا التنبيهات الخاصة بحسابك ومقالاتك.</p>
            </div>
        @endforelse

        @if ($notifications->hasPages())
            <div class="mt-8">
                {{ $notifications->links() }}
            </div>
        @endif
    </div>
@endsection
