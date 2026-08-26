@extends('layouts.admin')

@section('title', 'لوحة الإدارة')

@php
    $kpis = [
        ['label' => 'إجمالي المقالات', 'value' => $stats['posts_total'], 'change' => $stats['posts_change'], 'icon' => 'document', 'href' => route('admin.posts.index')],
        ['label' => 'المقالات المنشورة', 'value' => $stats['posts_published'], 'change' => null, 'icon' => 'check', 'href' => route('admin.posts.index', ['status' => 'published'])],
        ['label' => 'المسودات', 'value' => $stats['posts_draft'], 'change' => null, 'icon' => 'pencil', 'href' => route('admin.posts.index', ['status' => 'draft'])],
        ['label' => 'المجدولة', 'value' => $stats['posts_scheduled'], 'change' => null, 'icon' => 'clock', 'href' => route('admin.posts.index', ['status' => 'scheduled'])],
        ['label' => 'إجمالي المشاهدات', 'value' => $stats['views_total'], 'change' => $stats['views_change'], 'icon' => 'eye', 'href' => route('admin.analytics.index')],
        ['label' => 'المستخدمون', 'value' => $stats['users_total'], 'change' => $stats['users_change'], 'icon' => 'users', 'href' => route('admin.users.index')],
        ['label' => 'التعليقات', 'value' => $stats['comments_total'], 'change' => $stats['comments_change'], 'icon' => 'chat', 'href' => route('admin.comments.index')],
        ['label' => 'تعليقات مخفية', 'value' => $stats['comments_hidden'], 'change' => null, 'icon' => 'flag', 'href' => route('admin.comments.index', ['status' => 'hidden'])],
    ];

    $kpiIcons = [
        'document' => '<path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/>',
        'check' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>',
        'pencil' => '<path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Z"/>',
        'clock' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>',
        'eye' => '<path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>',
        'users' => '<path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"/>',
        'chat' => '<path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.501 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z"/>',
        'flag' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 3v1.5M3 21v-6m0 0 2.77-.693a9 9 0 0 1 6.208.682l.108.054a9 9 0 0 0 6.086.71l3.114-.732a48.524 48.524 0 0 1-.005-10.499l-3.11.732a9 9 0 0 1-6.085-.711l-.108-.054a9 9 0 0 0-6.208-.682L3 4.5M3 15V4.5"/>',
    ];

    $attention = collect($needsAttention)->filter(fn ($i) => $i['count'] > 0);
@endphp

@section('content')
    {{-- ============================ WELCOME ============================ --}}
    <section class="relative overflow-hidden rounded-2xl border border-ink-100 mb-6"
             style="background:radial-gradient(circle at 88% 20%,rgba(11,127,91,0.08),transparent 42%),linear-gradient(135deg,#f6faf8 0%,#f2f7f4 100%)">
        <div class="p-5 sm:p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <p class="text-xs font-semibold text-brand-700 mb-1">لوحة الإدارة</p>
                <h1 class="text-xl sm:text-2xl font-bold text-ink-900">حياك الله</h1>
                <p class="text-sm text-ink-500 mt-1">هذه نظرة عامة على حالة المنصة اليوم.</p>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <a href="{{ route('admin.posts.index') }}" class="inline-flex items-center rounded-xl bg-brand-600 text-white px-4 py-2.5 text-sm font-semibold hover:bg-brand-700 transition-colors">إدارة المقالات</a>
                <a href="{{ route('admin.analytics.index') }}" class="inline-flex items-center rounded-xl border border-ink-200 bg-white px-4 py-2.5 text-sm font-semibold text-ink-700 hover:border-brand-300 hover:text-brand-700 transition-colors">التحليلات</a>
            </div>
        </div>
    </section>

    {{-- ============================== KPIs ============================== --}}
    <section class="grid gap-4 grid-cols-2 lg:grid-cols-4 mb-6">
        @foreach ($kpis as $kpi)
            <a href="{{ $kpi['href'] }}" class="group rounded-2xl border border-ink-100 bg-white p-4 sm:p-5 transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md hover:shadow-ink-900/5 hover:border-brand-200">
                <div class="flex items-start justify-between gap-2 mb-3">
                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-brand-50 text-brand-600 ring-1 ring-brand-100">
                        <svg class="h-[18px] w-[18px]" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24" aria-hidden="true">{!! $kpiIcons[$kpi['icon']] !!}</svg>
                    </span>
                    @if (! is_null($kpi['change']))
                        <span class="text-[11px] font-bold {{ $kpi['change'] >= 0 ? 'text-brand-600' : 'text-red-500' }}">
                            {{ $kpi['change'] >= 0 ? '▲' : '▼' }} {{ abs($kpi['change']) }}%
                        </span>
                    @endif
                </div>
                <p class="text-2xl sm:text-3xl font-extrabold text-ink-900 tabular-nums">{{ number_format($kpi['value']) }}</p>
                <p class="text-xs sm:text-sm text-ink-500 mt-1">{{ $kpi['label'] }}</p>
            </a>
        @endforeach
    </section>

    {{-- ================= GROWTH CHART + TOP CATEGORIES ================= --}}
    <section class="grid gap-4 lg:grid-cols-3 mb-6">
        <div class="lg:col-span-2 rounded-2xl border border-ink-100 bg-white p-5">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
                <div>
                    <h2 class="font-bold text-ink-900">نمو المحتوى</h2>
                    <p class="text-xs text-ink-400 mt-0.5">{{ $periods[$period]['label'] }}</p>
                </div>
                <div class="flex flex-wrap items-center gap-1">
                    @foreach ($periods as $key => $meta)
                        <a href="{{ route('admin.dashboard', ['period' => $key]) }}"
                           class="rounded-lg px-2.5 py-1 text-xs font-medium transition-colors {{ $period === $key ? 'bg-brand-600 text-white' : 'text-ink-500 hover:bg-ink-50' }}">
                            {{ $meta['label'] }}
                        </a>
                    @endforeach
                </div>
            </div>

            <x-line-chart :series="[
                ['name' => 'المشاهدات', 'color' => '#0b6b45', 'points' => $growth['views']],
                ['name' => 'المقالات', 'color' => '#b5893c', 'points' => $growth['posts']],
                ['name' => 'التعليقات', 'color' => '#5b8774', 'points' => $growth['comments']],
            ]" />
        </div>

        <div class="rounded-2xl border border-ink-100 bg-white p-5">
            <h2 class="font-bold text-ink-900 mb-1">أكثر المواضيع نشرًا</h2>
            <p class="text-xs text-ink-400 mb-4">عدد المقالات المنشورة في كل تصنيف</p>

            <x-bar-chart :items="$topCategories->map(fn ($t) => [
                'label' => $t->name,
                'value' => $t->posts_count,
                'href' => route('admin.posts.index', ['tag' => $t->slug]),
            ])->all()" empty-text="لا توجد مقالات مصنّفة بعد." />
        </div>
    </section>

    {{-- ================ MOST VIEWED + RECENT ACTIVITY ================ --}}
    <section class="grid gap-4 lg:grid-cols-3 mb-6">
        <div class="lg:col-span-2 rounded-2xl border border-ink-100 bg-white overflow-hidden">
            <div class="flex items-center justify-between gap-3 px-5 py-4 border-b border-ink-100">
                <h2 class="font-bold text-ink-900">أكثر المقالات مشاهدة</h2>
                <a href="{{ route('admin.analytics.index') }}" class="text-xs font-semibold text-brand-600 hover:text-brand-700">عرض الكل</a>
            </div>

            @forelse ($mostViewed as $post)
                <div class="flex items-center gap-3 px-5 py-3 border-b border-ink-50 last:border-0 hover:bg-ink-25 transition-colors">
                    <div class="h-12 w-16 shrink-0 rounded-lg overflow-hidden bg-gradient-to-br from-brand-100 to-sand-100">
                        @if ($post->image)
                            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($post->image) }}" alt="" class="w-full h-full object-cover" loading="lazy">
                        @else
                            <div class="w-full h-full flex items-center justify-center text-brand-600/40 text-xs font-bold">م</div>
                        @endif
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-semibold text-ink-800 truncate">{{ $post->title }}</p>
                        <p class="text-xs text-ink-400 mt-0.5">
                            {{ $post->tags->first()->name ?? 'بدون تصنيف' }}
                            · {{ optional($post->published_at)->format('Y/m/d') ?? '—' }}
                        </p>
                    </div>
                    <div class="shrink-0 text-center">
                        <p class="text-sm font-extrabold text-ink-900 tabular-nums">{{ number_format($post->views_count) }}</p>
                        <p class="text-[10px] text-ink-400">مشاهدة</p>
                    </div>
                    <x-status-badge :status="$post->status" :label="$post->statusLabel()" class="hidden sm:inline-flex shrink-0" />
                    <div class="hidden sm:flex items-center gap-2 shrink-0">
                        <a href="{{ route('admin.posts.show', $post) }}" class="text-xs font-medium text-ink-500 hover:text-brand-700">عرض</a>
                        <a href="{{ route('admin.posts.edit', $post) }}" class="text-xs font-medium text-brand-600 hover:text-brand-700">تعديل</a>
                    </div>
                </div>
            @empty
                <p class="px-5 py-10 text-center text-sm text-ink-400">لا توجد مشاهدات مسجّلة بعد.</p>
            @endforelse
        </div>

        <div class="rounded-2xl border border-ink-100 bg-white p-5">
            <h2 class="font-bold text-ink-900 mb-4">آخر النشاطات</h2>
            @forelse ($activity as $row)
                <div class="flex gap-3 pb-3 mb-3 border-b border-ink-50 last:border-0 last:pb-0 last:mb-0">
                    <span class="mt-0.5 shrink-0 flex h-7 w-7 items-center justify-center rounded-full bg-brand-50 text-brand-600">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                            @if ($row['icon'] === 'chat')
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.501 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z" />
                            @elseif ($row['icon'] === 'user')
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                            @else
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                            @endif
                        </svg>
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs text-ink-700 leading-relaxed line-clamp-2">{{ $row['text'] }}</p>
                        <p class="text-[11px] text-ink-400 mt-0.5">
                            {{ $row['actor'] ?? 'مستخدم' }} · {{ $row['at']->diffForHumans() }}
                        </p>
                    </div>
                </div>
            @empty
                <p class="py-8 text-center text-sm text-ink-400">لا توجد نشاطات بعد.</p>
            @endforelse
        </div>
    </section>

    {{-- ========= LATEST POSTS + NEEDS ATTENTION + TOP AUTHORS ========= --}}
    <section class="grid gap-4 lg:grid-cols-3 mb-6">
        <div class="lg:col-span-2 rounded-2xl border border-ink-100 bg-white overflow-hidden">
            <div class="flex items-center justify-between gap-3 px-5 py-4 border-b border-ink-100">
                <h2 class="font-bold text-ink-900">آخر المقالات</h2>
                <a href="{{ route('admin.posts.index') }}" class="text-xs font-semibold text-brand-600 hover:text-brand-700">إدارة المقالات</a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm min-w-[620px]">
                    <thead>
                        <tr class="bg-ink-25 text-ink-400 text-xs">
                            <th class="px-4 py-2.5 font-medium text-start">المقال</th>
                            <th class="px-4 py-2.5 font-medium text-start">الكاتب</th>
                            <th class="px-4 py-2.5 font-medium text-start">الحالة</th>
                            <th class="px-4 py-2.5 font-medium text-start">المشاهدات</th>
                            <th class="px-4 py-2.5 font-medium text-start">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($latestPosts as $post)
                            <tr class="border-b border-ink-50 last:border-0 hover:bg-ink-25 transition-colors">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2.5">
                                        <div class="h-9 w-12 shrink-0 rounded-md overflow-hidden bg-gradient-to-br from-brand-100 to-sand-100">
                                            @if ($post->image)
                                                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($post->image) }}" alt="" class="w-full h-full object-cover" loading="lazy">
                                            @endif
                                        </div>
                                        <div class="min-w-0">
                                            <p class="font-medium text-ink-800 truncate max-w-[220px]">{{ $post->title }}</p>
                                            <p class="text-xs text-ink-400">{{ $post->tags->first()->name ?? 'بدون تصنيف' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-ink-500 whitespace-nowrap">{{ $post->user->name ?? '—' }}</td>
                                <td class="px-4 py-3"><x-status-badge :status="$post->status" :label="$post->statusLabel()" /></td>
                                <td class="px-4 py-3 text-ink-500 tabular-nums">{{ number_format($post->views_count) }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2.5 whitespace-nowrap">
                                        <a href="{{ route('admin.posts.show', $post) }}" class="text-xs text-ink-500 hover:text-brand-700">عرض</a>
                                        <a href="{{ route('admin.posts.edit', $post) }}" class="text-xs text-brand-600 hover:text-brand-700 font-medium">تعديل</a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-10 text-center text-sm text-ink-400">لا توجد مقالات بعد.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="space-y-4">
            {{-- Needs attention --}}
            <div class="rounded-2xl border border-ink-100 bg-white p-5">
                <h2 class="font-bold text-ink-900 mb-4">تحتاج انتباهك</h2>
                @forelse ($attention as $item)
                    <div class="flex items-center justify-between gap-3 py-2.5 border-b border-ink-50 last:border-0">
                        <div class="min-w-0">
                            <p class="text-sm text-ink-700 truncate">{{ $item['label'] }}</p>
                            <p class="text-xs text-ink-400 tabular-nums">{{ number_format($item['count']) }} عنصر</p>
                        </div>
                        <a href="{{ $item['url'] }}" class="shrink-0 rounded-lg border border-ink-200 px-3 py-1.5 text-xs font-semibold text-ink-600 hover:border-brand-300 hover:text-brand-700 transition-colors">مراجعة</a>
                    </div>
                @empty
                    <div class="py-8 text-center">
                        <div class="mx-auto mb-2 flex h-10 w-10 items-center justify-center rounded-full bg-brand-50 text-brand-600">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                        </div>
                        <p class="text-sm text-ink-500">كل شيء على ما يرام.</p>
                    </div>
                @endforelse
            </div>

            {{-- Top authors --}}
            <div class="rounded-2xl border border-ink-100 bg-white p-5">
                <h2 class="font-bold text-ink-900 mb-4">أكثر الكُتّاب نشاطًا</h2>
                @forelse ($topAuthors as $author)
                    <div class="flex items-center gap-3 py-2 border-b border-ink-50 last:border-0">
                        <x-avatar :name="$author->name" :size="32" />
                        <p class="flex-1 min-w-0 text-sm text-ink-700 truncate">{{ $author->name }}</p>
                        <span class="shrink-0 text-xs font-bold text-brand-700 tabular-nums">{{ $author->published_posts_count }}</span>
                    </div>
                @empty
                    <p class="py-8 text-center text-sm text-ink-400">لا يوجد كُتّاب بعد.</p>
                @endforelse
            </div>
        </div>
    </section>

    {{-- ========================= LATEST COMMENTS ========================= --}}
    <section class="rounded-2xl border border-ink-100 bg-white overflow-hidden">
        <div class="flex items-center justify-between gap-3 px-5 py-4 border-b border-ink-100">
            <h2 class="font-bold text-ink-900">آخر التعليقات</h2>
            <a href="{{ route('admin.comments.index') }}" class="text-xs font-semibold text-brand-600 hover:text-brand-700">إدارة التعليقات</a>
        </div>

        @forelse ($latestComments as $comment)
            <div class="flex gap-3 px-5 py-3 border-b border-ink-50 last:border-0">
                <x-avatar :name="$comment->user->name ?? 'م'" :size="32" />
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2 mb-0.5">
                        <p class="text-sm font-semibold text-ink-800 truncate">{{ $comment->user->name ?? 'مستخدم محذوف' }}</p>
                        <x-status-badge :status="$comment->status" :label="$comment->statusLabel()" />
                    </div>
                    <p class="text-sm text-ink-500 line-clamp-1">{{ $comment->content }}</p>
                    <p class="text-xs text-ink-300 mt-0.5">على «{{ \Illuminate\Support\Str::limit($comment->post->title ?? 'مقال محذوف', 40) }}» · {{ $comment->created_at->diffForHumans() }}</p>
                </div>
            </div>
        @empty
            <p class="px-5 py-10 text-center text-sm text-ink-400">لا توجد تعليقات بعد.</p>
        @endforelse
    </section>
@endsection
