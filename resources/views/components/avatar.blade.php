@props([
    'name' => '',
    'size' => 40,
])

@php
    $initial = mb_strtoupper(mb_substr(trim($name), 0, 1));
    $px = (int) $size;
    $fontPx = max(11, (int) round($px * 0.42));
@endphp

<span
    {{ $attributes->merge(['class' => 'inline-flex shrink-0 items-center justify-center rounded-full bg-brand-100 text-brand-700 font-bold select-none']) }}
    style="width: {{ $px }}px; height: {{ $px }}px; font-size: {{ $fontPx }}px;"
    aria-hidden="true"
>{{ $initial ?: 'م' }}</span>
