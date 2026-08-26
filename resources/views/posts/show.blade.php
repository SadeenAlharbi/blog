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

            {{-- Moderator-published articles carry no personal byline
                 (see Post::showsAuthor()); the publish date still shows. --}}
            <div class="flex items-center gap-3 mt-5 pb-6 border-b border-ink-100">
                @if ($post->showsAuthor())
                    <x-avatar :name="$post->user->name" :size="40" />
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-ink-800">{{ $post->user->name }}</p>
                        <p class="text-xs text-ink-400">{{ optional($post->published_at)->format('Y/m/d') }}</p>
                    </div>
                @else
                    <div class="min-w-0">
                        <p class="text-xs text-ink-400">{{ optional($post->published_at)->format('Y/m/d') }}</p>
                    </div>
                @endif

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
                <form method="POST" action="{{ route('comments.store', $post) }}" class="mb-8" data-comment-form>
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
                    <div id="comment-{{ $comment->id }}" class="flex gap-3 scroll-mt-24">
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

    {{--
        Liveness poll.

        If a moderator removes this article — or one of its comments — while
        somebody is reading it, the page says so in place rather than the reader
        finding out through a broken refresh. Plain fetch plus the shared toast
        helper: no new dependency, and it pauses while the tab is hidden.
    --}}
    <script>
        (function () {
            var url = @json(route('posts.availability', $post->slug));
            var INTERVAL = 25000;
            var timer = null;

            function stop() { if (timer) { clearInterval(timer); timer = null; } }

            function markArticleRemoved() {
                stop();
                if (window.pushToast) {
                    window.pushToast('تم حذف هذا المقال من قبل إدارة المنصة.', 'warning');
                }
                document.querySelectorAll('[data-comment-form]').forEach(function (form) {
                    form.querySelectorAll('textarea, button').forEach(function (c) { c.disabled = true; });
                });
            }

            function removeComment(id) {
                var el = document.getElementById('comment-' + id);
                if (!el) return;
                el.style.transition = 'opacity .3s';
                el.style.opacity = '0';
                setTimeout(function () { el.remove(); }, 300);
                if (window.pushToast) {
                    window.pushToast('تم حذف هذا التعليق من قبل إدارة المنصة.', 'warning');
                }
            }

            function check() {
                fetch(url, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
                    .then(function (r) { return r.ok ? r.json() : null; })
                    .then(function (data) {
                        if (!data) return;

                        if (!data.available) { markArticleRemoved(); return; }

                        var live = {};
                        (data.comments || []).forEach(function (id) { live[String(id)] = true; });

                        document.querySelectorAll('[id^="comment-"]').forEach(function (el) {
                            var id = el.id.replace('comment-', '');
                            if (id && !live[id]) removeComment(id);
                        });
                    })
                    .catch(function () { /* transient network issue — retry next tick */ });
            }

            document.addEventListener('visibilitychange', function () {
                if (document.hidden) { stop(); }
                else if (!timer) { check(); timer = setInterval(check, INTERVAL); }
            });

            timer = setInterval(check, INTERVAL);
        })();
    </script>
@endsection
