@php
    $excerpt = \Illuminate\Support\Str::limit(strip_tags($post->content), 130);
@endphp
<article class="group rounded-2xl border border-ink-100 bg-white overflow-hidden hover:shadow-lg hover:-translate-y-0.5 transition-all">
    <a href="{{ route('posts.show', $post) }}" class="block">
        <div class="aspect-[16/9] bg-gradient-to-br from-brand-100 to-sand-100 overflow-hidden">
            @if ($post->image)
                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($post->image) }}"
                     alt="{{ $post->title }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
            @else
                <div class="w-full h-full flex items-center justify-center text-brand-600/40 font-bold text-3xl">
                    م
                </div>
            @endif
        </div>
    </a>
    <div class="p-5">
        @if ($post->tags->isNotEmpty())
            <div class="flex flex-wrap gap-1.5 mb-2">
                @foreach ($post->tags->take(2) as $tag)
                    <dga-tag label="{{ $tag->name }}" variant="neutral" size="sm" rounded="true"></dga-tag>
                @endforeach
            </div>
        @endif
        <h3 class="font-bold text-ink-900 leading-snug mb-2">
            <a href="{{ route('posts.show', $post) }}" class="hover:text-brand-600 transition-colors">
                {{ $post->title }}
            </a>
        </h3>
        <p class="text-sm text-ink-500 leading-relaxed mb-4">{{ $excerpt }}</p>
        <div class="flex items-center justify-between text-xs text-ink-400">
            <span>{{ $post->user->name }}</span>
            <span>{{ optional($post->published_at)->format('Y/m/d') }}</span>
        </div>
    </div>
</article>
