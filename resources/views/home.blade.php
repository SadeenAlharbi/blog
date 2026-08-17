@extends('layouts.app')

@section('title', 'منصة المعرفة السعودية — معرفة، تطور، رؤية 2030')

@section('content')
    <section class="relative overflow-hidden border-b border-ink-100 bg-gradient-to-b from-brand-50 to-white">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 py-16 sm:py-24 text-center">
            <span class="inline-block rounded-full bg-brand-100 text-brand-700 text-xs font-semibold px-3 py-1 mb-5">
                رؤية 2030 · تقنية · مجتمع · تراث
            </span>
            <h1 class="text-3xl sm:text-5xl font-extrabold text-ink-900 leading-tight max-w-3xl mx-auto">
                منصة معرفية سعودية لفهم التحوّل الوطني
            </h1>
            <p class="mt-5 text-ink-500 text-base sm:text-lg max-w-2xl mx-auto leading-relaxed">
                مقالات موثّقة عن تاريخ المملكة، الاقتصاد، التقنية والذكاء الاصطناعي، الثقافة والسياحة،
                والمشاريع الوطنية التي تشكّل مستقبل السعودية.
            </p>
            <div class="mt-8 flex items-center justify-center gap-3">
                <a href="{{ route('posts.index') }}" class="inline-flex items-center rounded-lg bg-brand-600 text-white px-6 py-3 text-sm font-semibold hover:bg-brand-700 transition-colors">
                    تصفح المقالات
                </a>
                @guest
                    <a href="{{ route('register') }}" class="inline-flex items-center rounded-lg border border-ink-200 text-ink-700 px-6 py-3 text-sm font-semibold hover:border-brand-300 hover:text-brand-700 transition-colors">
                        انضم إلينا
                    </a>
                @endguest
            </div>
        </div>
    </section>

    @if ($popularTags->isNotEmpty())
        <section class="max-w-6xl mx-auto px-4 sm:px-6 py-8">
            <div class="flex flex-wrap items-center justify-center gap-2">
                @foreach ($popularTags as $tag)
                    <a href="{{ route('posts.index', ['tag' => $tag->slug]) }}">
                        <dga-tag label="{{ $tag->name }}" variant="neutral" size="md" rounded="true"></dga-tag>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    <section class="max-w-6xl mx-auto px-4 sm:px-6 py-10">
        <div class="flex items-end justify-between mb-6">
            <div>
                <h2 class="text-xl font-bold text-ink-900">أحدث المقالات</h2>
                <p class="text-sm text-ink-500 mt-1">آخر ما نُشر على المنصة</p>
            </div>
            <a href="{{ route('posts.index') }}" class="text-sm font-semibold text-brand-600 hover:text-brand-700">
                عرض الكل ←
            </a>
        </div>

        @if ($latestPosts->isEmpty())
            <p class="text-ink-400 text-sm">لا توجد مقالات منشورة بعد.</p>
        @else
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($latestPosts as $post)
                    @include('partials.post-card', ['post' => $post])
                @endforeach
            </div>
        @endif
    </section>

    <section class="bg-ink-900 text-white">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 py-14 grid gap-8 sm:grid-cols-3 text-center">
            <div>
                <p class="text-3xl font-extrabold text-sand-300">2030</p>
                <p class="text-sm text-ink-300 mt-1">رؤية التحوّل الوطني</p>
            </div>
            <div>
                <p class="text-3xl font-extrabold text-sand-300">{{ \App\Models\Post::count() }}+</p>
                <p class="text-sm text-ink-300 mt-1">مقال منشور</p>
            </div>
            <div>
                <p class="text-3xl font-extrabold text-sand-300">{{ \App\Models\Tag::count() }}+</p>
                <p class="text-sm text-ink-300 mt-1">تصنيف معرفي</p>
            </div>
        </div>
    </section>
@endsection
