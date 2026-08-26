@extends('layouts.admin')

@section('title', 'الإشعارات')

@section('content')
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-5">
        <div>
            <h1 class="text-xl font-bold text-ink-900">الإشعارات</h1>
            <p class="text-sm text-ink-500 mt-0.5">
                {{ number_format($notifications->total()) }} إشعار ·
                {{ number_format($unreadCount) }} غير مقروء
            </p>
        </div>
        @if ($unreadCount > 0)
            <form method="POST" action="{{ route('admin.notifications.readAll') }}" class="shrink-0">
                @csrf
                <button type="submit" class="inline-flex items-center rounded-xl border border-ink-200 bg-white px-4 py-2.5 text-sm font-semibold text-ink-700 hover:border-brand-300 hover:text-brand-700 transition-colors">
                    تعليم الكل كمقروء
                </button>
            </form>
        @endif
    </div>

    <div class="space-y-3">
        @forelse ($notifications as $note)
            <x-notification-item :note="$note" :action="route('admin.notifications.read', $note->id)" />
        @empty
            <div class="rounded-2xl border border-ink-100 bg-white px-6 py-16 text-center">
                <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-ink-50 text-ink-300">
                    <svg class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" /></svg>
                </div>
                <p class="text-ink-600 font-medium">لا توجد إشعارات بعد</p>
                <p class="text-sm text-ink-400 mt-1">ستظهر هنا تنبيهات المنصة والإجراءات على المحتوى.</p>
            </div>
        @endforelse
    </div>

    @if ($notifications->hasPages())
        <div class="mt-5">{{ $notifications->links() }}</div>
    @endif
@endsection
