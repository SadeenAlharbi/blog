@extends('layouts.app')

@section('title', 'المقالات — منصة المعرفة السعودية')

@section('content')
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <x-breadcrumbs class="mb-4" :items="[
            ['label' => 'الرئيسية', 'href' => route('home')],
            ['label' => 'المقالات'],
        ]" />

        <div class="mb-8">
            <h1 class="text-2xl sm:text-3xl font-bold text-ink-900">جميع المقالات</h1>
            <p class="text-sm text-ink-500 mt-1.5">ابحث، صفِّ حسب التصنيف، وتصفّح المقالات المنشورة على المنصة.</p>
        </div>

        <div
            id="posts-explorer-root"
            data-api-url="{{ url('/api/v1/posts') }}"
            data-tags-api-url="{{ url('/api/v1/tags') }}"
            data-initial-search="{{ request('search') }}"
            data-initial-tag="{{ request('tag') }}"
        >
            {{-- Server-rendered fallback: visible until React hydrates, and for no-JS / SEO crawlers. --}}
            <noscript>
                @if ($posts->isEmpty())
                    <x-empty-state icon="search" title="لا توجد مقالات مطابقة." />
                @else
                    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($posts as $post)
                            @include('partials.post-card', ['post' => $post])
                        @endforeach
                    </div>
                    <div class="mt-8">{{ $posts->links() }}</div>
                @endif
            </noscript>
        </div>
    </section>

    {{-- Floating action button — shown ONLY on the articles list page (this view),
         and only to signed-in users (creating a post requires auth). --}}
    @auth
        <a href="{{ route('posts.create') }}" title="مقال جديد" aria-label="إنشاء مقال جديد"
           class="fixed bottom-6 left-6 z-40 inline-flex h-14 w-14 items-center justify-center rounded-full bg-brand-600 text-white shadow-lg shadow-brand-900/25 hover:bg-brand-700 hover:scale-105 focus:outline-none focus:ring-4 focus:ring-brand-300 transition-all">
            <svg class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
        </a>
    @endauth
@endsection
