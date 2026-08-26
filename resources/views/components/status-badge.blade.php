@props(['status', 'label' => null])

@php
    // Status colours per the platform's convention:
    // published = green · draft = amber · scheduled = indigo · hidden = red.
    $styles = [
        'published' => 'bg-brand-50 text-brand-700 ring-brand-200',
        'draft' => 'bg-amber-50 text-amber-700 ring-amber-200',
        'scheduled' => 'bg-indigo-50 text-indigo-700 ring-indigo-200',
        'approved' => 'bg-brand-50 text-brand-700 ring-brand-200',
        'hidden' => 'bg-red-50 text-red-700 ring-red-200',
    ];
    $dots = [
        'published' => 'bg-brand-500',
        'draft' => 'bg-amber-500',
        'scheduled' => 'bg-indigo-500',
        'approved' => 'bg-brand-500',
        'hidden' => 'bg-red-500',
    ];
    $cls = $styles[$status] ?? 'bg-ink-50 text-ink-600 ring-ink-200';
    $dot = $dots[$status] ?? 'bg-ink-400';
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold ring-1 {$cls}"]) }}>
    <span class="h-1.5 w-1.5 rounded-full {{ $dot }}"></span>
    {{ $label ?? $slot }}
</span>
