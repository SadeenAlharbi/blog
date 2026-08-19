@props([
    'type' => 'info',
])

@php
    $variants = [
        'success' => ['wrap' => 'bg-brand-50 border-brand-200 text-brand-800', 'icon' => 'text-brand-600'],
        'error' => ['wrap' => 'bg-red-50 border-red-200 text-red-800', 'icon' => 'text-red-600'],
        'info' => ['wrap' => 'bg-ink-50 border-ink-200 text-ink-700', 'icon' => 'text-ink-500'],
    ];
    $v = $variants[$type] ?? $variants['info'];
@endphp

<div role="alert" {{ $attributes->merge(['class' => 'flex items-start gap-2.5 rounded-xl border px-4 py-3 text-sm ' . $v['wrap']]) }}>
    <svg class="h-5 w-5 shrink-0 mt-px {{ $v['icon'] }}" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
        @if ($type === 'success')
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
        @elseif ($type === 'error')
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.008M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
        @else
            <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25 12 12v4.5m-.008 3h.008M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
        @endif
    </svg>
    <div class="min-w-0">{{ $slot }}</div>
</div>
