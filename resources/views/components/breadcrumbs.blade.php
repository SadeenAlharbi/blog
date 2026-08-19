@props([
    'items' => [],
])

{{-- Kept as a real <nav> (block by default → Platforms Code core.css reset is harmless here). --}}
<nav aria-label="مسار التنقل" {{ $attributes->merge(['class' => 'text-xs text-ink-400']) }}>
    <ol class="flex flex-wrap items-center gap-x-2 gap-y-1">
        @foreach ($items as $item)
            <li class="flex items-center gap-x-2">
                @if (!empty($item['href']) && !$loop->last)
                    <a href="{{ $item['href'] }}" class="hover:text-brand-600 transition-colors">{{ $item['label'] }}</a>
                @else
                    <span class="text-ink-600 font-medium" @if ($loop->last) aria-current="page" @endif>{{ $item['label'] }}</span>
                @endif

                @unless ($loop->last)
                    <span class="text-ink-300" aria-hidden="true">/</span>
                @endunless
            </li>
        @endforeach
    </ol>
</nav>
