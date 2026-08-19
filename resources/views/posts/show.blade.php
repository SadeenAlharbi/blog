@extends('layouts.app')

@section('title', $post->title.' — منصة المعرفة السعودية')
@section('description', \Illuminate\Support\Str::limit(strip_tags($post->content), 150))

@php
    $related = \App\Models\Post::where('id', '!=', $post->id)
        ->whereHas('tags', fn ($q) => $q->whereIn('tags.id', $post->tags->pluck('id')))
        ->with(['user', 'tags'])
        ->latest('published_at')
        ->take(3)
        ->get();
@endphp

@section('content')
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <x-breadcrumbs class="mb-6" :items="[
            ['label' => 'الرئيسية', 'href' => route('home')],
            ['label' => 'المقالات', 'href' => route('posts.index')],
            ['label' => \Illuminate\Support\Str::limit($post->title, 40)],
        ]" />

        <article>
            @if ($post->tags->isNotEmpty())
                <div class="flex flex-wrap gap-1.5 mb-4">
                    @foreach ($post->tags as $tag)
                        <x-chip :href="route('posts.index', ['tag' => $tag->slug])" variant="brand" size="sm">{{ $tag->name }}</x-chip>
                    @endforeach
                </div>
            @endif

            <h1 class="text-3xl sm:text-4xl font-extrabold text-ink-900 leading-tight tracking-tight">{{ $post->title }}</h1>

            <div class="flex items-center gap-3 mt-5 pb-6 border-b border-ink-100">
                <x-avatar :name="$post->user->name" :size="40" />
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-ink-800">{{ $post->user->name }}</p>
                    <p class="text-xs text-ink-400">{{ optional($post->published_at)->format('Y/m/d') }}</p>
                </div>

                @auth
                    @can('update', $post)
                        <div class="ms-auto flex items-center gap-3 shrink-0">
                            <a href="{{ route('posts.edit', $post) }}" class="text-sm text-brand-600 hover:text-brand-700 font-medium">تعديل</a>
                            <form method="POST" action="{{ route('posts.destroy', $post) }}" onsubmit="return confirm('هل أنت متأكد من حذف هذا المقال؟');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-sm text-red-500 hover:text-red-700 font-medium">حذف</button>
                            </form>
                        </div>
                    @endcan
                @endauth
            </div>

            @if ($post->image)
                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($post->image) }}"
                     alt="{{ $post->title }}" class="w-full rounded-2xl mt-8 aspect-video object-cover">
            @endif

            <div class="article-content mt-8 whitespace-pre-line">{{ $post->content }}</div>
        </article>

        {{-- Comments --}}
        <section class="mt-12 pt-10 border-t border-ink-100">
            <h2 class="text-lg font-bold text-ink-900 mb-6">التعليقات ({{ $post->comments->count() }})</h2>

            @auth
                <form method="POST" action="{{ route('comments.store', $post) }}" class="mb-8">
                    @csrf
                    <label for="content" class="sr-only">أضف تعليقاً</label>
                    <textarea id="content" name="content" rows="3"
                        placeholder="شاركنا رأيك حول هذا المقال..."
                        class="w-full rounded-xl border border-ink-200 bg-white px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand-300 focus:border-brand-300">{{ old('content') }}</textarea>
                    @error('content')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                    <button type="submit" class="mt-3 inline-flex items-center rounded-xl bg-brand-600 text-white px-5 py-2.5 text-sm font-semibold hover:bg-brand-700 transition-colors">نشر التعليق</button>
                </form>
            @else
                <div class="mb-8 rounded-xl bg-ink-50 border border-ink-100 px-4 py-3 text-sm text-ink-600">
                    <a href="{{ route('login') }}" class="text-brand-600 font-medium hover:text-brand-700">سجّل الدخول</a>
                    لإضافة تعليق.
                </div>
            @endauth

            <div class="space-y-5">
                @forelse ($post->comments as $comment)
                    <div class="flex gap-3">
                        <x-avatar :name="$comment->user->name" :size="36" />
                        <div class="flex-1 min-w-0 rounded-xl bg-ink-50 px-4 py-3">
                            <div class="flex items-center justify-between gap-3 mb-1">
                                <p class="text-sm font-semibold text-ink-800 truncate">{{ $comment->user->name }}</p>
                                <p class="text-xs text-ink-400 shrink-0">{{ $comment->created_at->format('Y/m/d H:i') }}</p>
                            </div>
                            <p class="text-sm text-ink-600 leading-relaxed">{{ $comment->content }}</p>
                            @can('delete', $comment)
                                <form method="POST" action="{{ route('comments.destroy', $comment) }}" class="mt-2"
                                      onsubmit="return confirm('هل أنت متأكد أنك تريد حذف هذا التعليق؟');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-xs font-medium text-red-500 hover:text-red-700 transition-colors">حذف تعليقي</button>
                                </form>
                            @endcan
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-ink-400">لا توجد تعليقات بعد. كن أول من يعلّق.</p>
                @endforelse
            </div>
        </section>
    </div>

    {{-- Related --}}
    @if ($related->isNotEmpty())
        <section class="border-t border-ink-100 bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-14">
                <h2 class="text-xl font-bold text-ink-900 mb-6">مقالات ذات صلة</h2>
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($related as $rel)
                        @include('partials.post-card', ['post' => $rel])
                    @endforeach
                </div>
            </div>
        </section>
    @endif
@endsection
