@props([
    'variant' => 'neutral',
    'size' => 'sm',
    'href' => null,
])

@php
    $base = 'inline-flex items-center rounded-full font-medium leading-none transition-colors max-w-full';
    $sizes = [
        'sm' => 'text-xs px-2.5 py-1',
        'md' => 'text-sm px-3 py-1.5',
    ];
    $variants = [
        'neutral' => 'bg-ink-100 text-ink-600 hover:bg-ink-200',
        'brand' => 'bg-brand-50 text-brand-700 hover:bg-brand-100',
        'solid' => 'bg-brand-600 text-white hover:bg-brand-700',
        'onDark' => 'bg-white/10 text-white ring-1 ring-white/20 hover:bg-white/20',
    ];
    $classes = $base . ' ' . ($sizes[$size] ?? $sizes['sm']) . ' ' . ($variants[$variant] ?? $variants['neutral']);
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        <span class="truncate">{{ $slot }}</span>
    </a>
@else
    <span {{ $attributes->merge(['class' => $classes]) }}>
        <span class="truncate">{{ $slot }}</span>
    </span>
@endif
