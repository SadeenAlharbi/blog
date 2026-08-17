@extends('layouts.app')

@section('title', $post->title.' — منصة المعرفة السعودية')
@section('description', \Illuminate\Support\Str::limit(strip_tags($post->content), 150))

@section('content')
    <article class="max-w-3xl mx-auto px-4 sm:px-6 py-10">
        <nav class="text-xs text-ink-400 mb-6">
            <a href="{{ route('home') }}" class="hover:text-brand-600">الرئيسية</a>
            <span class="mx-1">/</span>
            <a href="{{ route('posts.index') }}" class="hover:text-brand-600">المقالات</a>
        </nav>

        @if ($post->tags->isNotEmpty())
            <div class="flex flex-wrap gap-1.5 mb-4">
                @foreach ($post->tags as $tag)
                    <a href="{{ route('posts.index', ['tag' => $tag->slug]) }}">
                        <dga-tag label="{{ $tag->name }}" variant="success" size="sm" rounded="true"></dga-tag>
                    </a>
                @endforeach
            </div>
        @endif

        <h1 class="text-2xl sm:text-3xl font-extrabold text-ink-900 leading-tight">{{ $post->title }}</h1>

        <div class="flex items-center gap-3 mt-4 text-sm text-ink-500">
            <dga-avatar type="initials" text="{{ \Illuminate\Support\Str::substr($post->user->name, 0, 1) }}" size="36"></dga-avatar>
            <div>
                <p class="font-medium text-ink-700">{{ $post->user->name }}</p>
                <p class="text-xs text-ink-400">{{ optional($post->published_at)->format('Y/m/d') }}</p>
            </div>
            @auth
                @can('update', $post)
                    <div class="ms-auto flex items-center gap-3">
                        <a href="{{ route('posts.edit', $post) }}" class="text-brand-600 hover:text-brand-700 font-medium">تعديل</a>
                        <form method="POST" action="{{ route('posts.destroy', $post) }}" onsubmit="return confirm('هل أنت متأكد من حذف هذا المقال؟');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-500 hover:text-red-700 font-medium">حذف</button>
                        </form>
                    </div>
                @endcan
            @endauth
        </div>

        @if ($post->image)
            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($post->image) }}"
                 alt="{{ $post->title }}" class="w-full rounded-2xl mt-8 aspect-video object-cover">
        @endif

        <div class="prose prose-ink max-w-none mt-8 leading-loose text-ink-700 whitespace-pre-line">
            {{ $post->content }}
        </div>

        <hr class="my-10 border-ink-100">

        <section>
            <h2 class="text-lg font-bold text-ink-900 mb-5">
                التعليقات ({{ $post->comments->count() }})
            </h2>

            @auth
                <form method="POST" action="{{ route('comments.store', $post) }}" class="mb-8">
                    @csrf
                    <label for="content" class="sr-only">أضف تعليقاً</label>
                    <textarea
                        id="content"
                        name="content"
                        rows="3"
                        placeholder="شاركنا رأيك حول هذا المقال..."
                        class="w-full rounded-lg border border-ink-200 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand-300"
                    >{{ old('content') }}</textarea>
                    @error('content')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                    <button type="submit" class="mt-3 inline-flex items-center rounded-lg bg-brand-600 text-white px-5 py-2.5 text-sm font-semibold hover:bg-brand-700 transition-colors">
                        نشر التعليق
                    </button>
                </form>
            @else
                <p class="text-sm text-ink-500 mb-8">
                    <a href="{{ route('login') }}" class="text-brand-600 font-medium hover:text-brand-700">سجّل الدخول</a>
                    لإضافة تعليق.
                </p>
            @endauth

            <div class="space-y-5">
                @forelse ($post->comments as $comment)
                    <div class="flex gap-3">
                        <dga-avatar type="initials" text="{{ \Illuminate\Support\Str::substr($comment->user->name, 0, 1) }}" size="32"></dga-avatar>
                        <div class="flex-1 rounded-xl bg-ink-50 px-4 py-3">
                            <div class="flex items-center justify-between mb-1">
                                <p class="text-sm font-semibold text-ink-800">{{ $comment->user->name }}</p>
                                <p class="text-xs text-ink-400">{{ $comment->created_at->format('Y/m/d H:i') }}</p>
                            </div>
                            <p class="text-sm text-ink-600 leading-relaxed">{{ $comment->content }}</p>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-ink-400">لا توجد تعليقات بعد. كن أول من يعلّق.</p>
                @endforelse
            </div>
        </section>
    </article>
@endsection
