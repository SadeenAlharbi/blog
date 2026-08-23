@extends('layouts.app')

@section('title', 'الإشعارات — منصة المعرفة السعودية')

@section('content')
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div class="flex items-center justify-between gap-4 mb-8">
            <div>
                <h1 class="text-2xl font-bold text-ink-900">الإشعارات</h1>
                <p class="text-sm text-ink-500 mt-1">تنبيهات التعليقات على مقالاتك</p>
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
            @php $d = $note->data; $unread = is_null($note->read_at); @endphp
            <form method="POST" action="{{ route('notifications.read', $note->id) }}" class="mb-3">
                @csrf
                <button type="submit"
                        class="w-full text-start flex gap-4 rounded-2xl border p-4 transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md hover:shadow-ink-900/5 {{ $unread ? 'bg-brand-50/50 border-brand-100' : 'bg-white border-ink-100' }}">
                    <span class="shrink-0">
                        <span class="flex h-10 w-10 items-center justify-center rounded-full bg-brand-100 text-brand-600">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.501 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z" /></svg>
                        </span>
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="block text-sm text-ink-700 leading-relaxed">
                            <span class="font-semibold text-ink-900">{{ $d['commenter_name'] ?? 'مستخدم' }}</span>
                            علّق على مقالك
                            <span class="font-semibold text-brand-700">«{{ \Illuminate\Support\Str::limit($d['post_title'] ?? '', 60) }}»</span>
                        </span>
                        @if (!empty($d['excerpt']))
                            <span class="block text-sm text-ink-500 mt-1 line-clamp-2">{{ $d['excerpt'] }}</span>
                        @endif
                        <span class="block text-xs text-ink-300 mt-1.5">{{ $note->created_at->diffForHumans() }}</span>
                    </span>
                    @if ($unread)
                        <span class="mt-1.5 shrink-0 h-2.5 w-2.5 rounded-full bg-brand-500" aria-label="غير مقروء"></span>
                    @endif
                </button>
            </form>
        @empty
            <div class="rounded-2xl border border-ink-100 bg-white px-6 py-16 text-center">
                <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-ink-50 text-ink-300">
                    <svg class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" /></svg>
                </div>
                <p class="text-ink-600 font-medium">لا توجد إشعارات بعد</p>
                <p class="text-sm text-ink-400 mt-1">ستظهر هنا تنبيهات التعليقات على مقالاتك.</p>
            </div>
        @endforelse

        @if ($notifications->hasPages())
            <div class="mt-8">
                {{ $notifications->links() }}
            </div>
        @endif
    </div>
@endsection
