@extends('layouts.app')

@section('title', 'المقالات — منصة المعرفة السعودية')

@section('content')
    <section class="max-w-6xl mx-auto px-4 sm:px-6 py-10">
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-ink-900">جميع المقالات</h1>
            <p class="text-sm text-ink-500 mt-1">ابحث، صفِّ حسب التصنيف، وتصفّح المقالات المنشورة على المنصة.</p>
        </div>

        <div
            id="posts-explorer-root"
            data-api-url="{{ url('/api/v1/posts') }}"
            data-tags-api-url="{{ url('/api/v1/tags') }}"
            data-initial-search="{{ request('search') }}"
            data-initial-tag="{{ request('tag') }}"
        >
            {{-- Server-rendered fallback (visible until React hydrates, and for no-JS/SEO crawlers) --}}
            <noscript>
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($posts as $post)
                        @include('partials.post-card', ['post' => $post])
                    @endforeach
                </div>
                <div class="mt-8">{{ $posts->links() }}</div>
            </noscript>
        </div>
    </section>
@endsection
