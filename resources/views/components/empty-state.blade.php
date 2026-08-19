@props([
    'title' => 'لا يوجد شيء لعرضه بعد.',
    'icon' => 'document',
])

<div {{ $attributes->merge(['class' => 'rounded-2xl border border-dashed border-ink-200 bg-ink-25 py-16 px-6 text-center']) }}>
    <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-brand-50 text-brand-500">
        <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
            @if ($icon === 'search')
                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
            @else
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
            @endif
        </svg>
    </div>
    <p class="text-ink-500 text-sm mb-4">{{ $title }}</p>
    {{ $slot }}
</div>
