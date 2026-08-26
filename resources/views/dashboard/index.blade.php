@extends('layouts.app')

@section('title', 'الصفحة الشخصية — منصة المعرفة السعودية')

@php
    $user = auth()->user();
    $postsTotal = $posts->total();
    $commentsTotal = \App\Models\Comment::whereIn('post_id', $user->posts()->select('id'))->count();
@endphp

@section('content')
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <x-breadcrumbs class="mb-4" :items="[
            ['label' => 'الرئيسية', 'href' => route('home')],
            ['label' => 'الصفحة الشخصية'],
        ]" />

        <div class="mb-8">
            <h1 class="text-2xl sm:text-3xl font-bold text-ink-900">الصفحة الشخصية</h1>
            <p class="text-sm text-ink-500 mt-1.5">حياك الله، {{ $user->name }} — أدر مقالاتك وتعليقاتك على المنصة.</p>
        </div>

        @include('dashboard._tabs')

        {{-- Overview --}}
        <div class="grid gap-4 sm:grid-cols-3 mb-8">
            <div class="rounded-2xl border border-ink-100 bg-white p-5">
                <p class="text-sm text-ink-500">مقالاتك</p>
                <p class="text-3xl font-extrabold text-ink-900 mt-1">{{ $postsTotal }}</p>
            </div>
            <div class="rounded-2xl border border-ink-100 bg-white p-5">
                <p class="text-sm text-ink-500">التعليقات على مقالاتك</p>
                <p class="text-3xl font-extrabold text-ink-900 mt-1">{{ $commentsTotal }}</p>
            </div>

            {{-- The WHOLE card is the link to the writing page, not just the
                 wording inside it — hover, pointer and a focus ring make that
                 obvious, and it stays reachable by keyboard. --}}
            <a href="{{ route('posts.create') }}"
               class="group cursor-pointer rounded-2xl border border-brand-100 bg-brand-50/50 p-5 flex items-center justify-between gap-3
                      transition-all duration-200 hover:-translate-y-0.5 hover:border-brand-300 hover:bg-brand-50 hover:shadow-md hover:shadow-ink-900/5
                      focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-400 focus-visible:ring-offset-2">
                <div>
                    <p class="text-sm font-semibold text-brand-700">ابدأ الكتابة</p>
                    <p class="text-xs text-ink-500 mt-1">شارك مقالاً جديداً مع القرّاء.</p>
                </div>
                <span class="shrink-0 inline-flex h-10 w-10 items-center justify-center rounded-xl bg-white text-brand-600 ring-1 ring-brand-100 transition-colors group-hover:bg-brand-600 group-hover:text-white">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                </span>
            </a>
        </div>

        @if ($posts->isEmpty())
            <x-empty-state title="لم تنشر أي مقال بعد.">
                <a href="{{ route('posts.create') }}" class="text-brand-600 font-semibold hover:text-brand-700">ابدأ بنشر أول مقال ←</a>
            </x-empty-state>
        @else
            <div class="overflow-x-auto rounded-2xl border border-ink-100 bg-white">
                <table class="w-full text-sm text-start min-w-[720px]">
                    <thead>
                        <tr class="border-b border-ink-100 bg-ink-25 text-ink-400 text-xs">
                            <th class="px-5 py-3 font-medium text-start">العنوان</th>
                            <th class="px-5 py-3 font-medium text-start">الحالة</th>
                            <th class="px-5 py-3 font-medium text-start">المشاهدات</th>
                            <th class="px-5 py-3 font-medium text-start">التصنيفات</th>
                            <th class="px-5 py-3 font-medium text-start">التعليقات</th>
                            <th class="px-5 py-3 font-medium text-start">تاريخ النشر</th>
                            <th class="px-5 py-3 font-medium text-start">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($posts as $post)
                            <tr class="border-b border-ink-50 last:border-0 hover:bg-ink-25 transition-colors">
                                <td class="px-5 py-4">
                                    <a href="{{ route('posts.show', $post) }}" class="font-medium text-ink-800 hover:text-brand-600">{{ \Illuminate\Support\Str::limit($post->title, 50) }}</a>
                                </td>
                                {{-- Real status straight from the database: draft / published / scheduled. --}}
                                <td class="px-5 py-4">
                                    <x-status-badge :status="$post->status" :label="$post->statusLabel()" />
                                </td>
                                <td class="px-5 py-4 text-ink-500 tabular-nums">{{ number_format($post->views_count) }}</td>
                                <td class="px-5 py-4 text-ink-500">{{ $post->tags_count }}</td>
                                <td class="px-5 py-4 text-ink-500">{{ $post->comments_count }}</td>
                                <td class="px-5 py-4 text-ink-500 whitespace-nowrap">
                                    @if ($post->isScheduled())
                                        <span class="text-indigo-600">{{ optional($post->published_at)->format('Y/m/d H:i') }}</span>
                                    @else
                                        {{ optional($post->published_at)->format('Y/m/d') ?? '—' }}
                                    @endif
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <a href="{{ route('posts.edit', $post) }}" class="text-brand-600 hover:text-brand-700 font-medium">تعديل</a>
                                        @unless ($post->isPublished())
                                            {{-- Drafts and scheduled articles can go live in one click. --}}
                                            <form method="POST" action="{{ route('posts.publish', $post) }}">
                                                @csrf
                                                <button type="submit" class="text-brand-700 hover:text-brand-800 font-medium">نشر</button>
                                            </form>
                                        @endunless
                                        <form method="POST" action="{{ route('posts.destroy', $post) }}" onsubmit="return confirm('هل أنت متأكد من حذف هذا المقال؟');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-500 hover:text-red-700 font-medium">حذف</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-6">{{ $posts->links() }}</div>
        @endif
    </section>
@endsection
