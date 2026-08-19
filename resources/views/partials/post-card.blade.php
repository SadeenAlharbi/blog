@php
    $excerpt = \Illuminate\Support\Str::limit(strip_tags($post->content), 120);
    $imageUrl = $post->image ? \Illuminate\Support\Facades\Storage::disk('public')->url($post->image) : null;
@endphp

{{--
    Whole-card link via the "stretched link" pattern: one absolutely-positioned
    <a> covers the card, so clicking the image, title, excerpt or anywhere opens
    the article. All other elements here are spans (not links), so there are no
    nested/overlapping anchors.
--}}
<article class="group relative flex flex-col rounded-2xl border border-ink-100 bg-white overflow-hidden transition-all duration-200 hover:shadow-lg hover:shadow-ink-900/5 hover:border-ink-200 hover:-translate-y-0.5">
    <div class="aspect-[16/9] bg-gradient-to-br from-brand-100 to-sand-100">
        @if ($imageUrl)
            <img src="{{ $imageUrl }}" alt="{{ $post->title }}" loading="lazy"
                 class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105">
        @else
            <div class="w-full h-full flex items-center justify-center text-brand-600/30 font-bold text-4xl">م</div>
        @endif
    </div>

    <div class="flex flex-col flex-1 p-5">
        @if ($post->tags->isNotEmpty())
            <div class="flex flex-wrap gap-1.5 mb-2.5">
                @foreach ($post->tags->take(2) as $tag)
                    <x-chip variant="neutral" size="sm">{{ $tag->name }}</x-chip>
                @endforeach
            </div>
        @endif

        <h3 class="font-bold text-ink-900 leading-snug mb-2 line-clamp-2 group-hover:text-brand-600 transition-colors">{{ $post->title }}</h3>

        <p class="text-sm text-ink-500 leading-relaxed mb-4 line-clamp-3">{{ $excerpt }}</p>

        <div class="mt-auto flex items-center gap-2.5 pt-3 border-t border-ink-50">
            <x-avatar :name="$post->user->name" :size="28" />
            <span class="text-xs font-medium text-ink-600 truncate">{{ $post->user->name }}</span>
            <span class="text-xs text-ink-300 ms-auto shrink-0">{{ optional($post->published_at)->format('Y/m/d') }}</span>
        </div>
    </div>

    <a href="{{ route('posts.show', $post) }}" class="absolute inset-0 z-10" aria-label="{{ $post->title }}"></a>
</article>
