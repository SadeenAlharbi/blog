@props(['items' => [], 'color' => '#0b6b45', 'emptyText' => 'لا توجد بيانات.'])

@php
    /**
     * Horizontal bar list (RTL-correct): label on the right, bar growing to the
     * left, value at the end. Data comes straight from the database.
     * $items: [['label' => '...', 'value' => 12, 'href' => null], ...]
     */
    $items = collect($items)->filter(fn ($i) => ($i['value'] ?? 0) > 0)->values();
    $max = $items->max('value') ?: 1;
@endphp

@if ($items->isEmpty())
    <p class="text-sm text-ink-400 py-8 text-center">{{ $emptyText }}</p>
@else
    <ul class="space-y-3">
        @foreach ($items as $item)
            @php $pct = max(2, round(($item['value'] / $max) * 100)); @endphp
            <li>
                <div class="flex items-center justify-between gap-3 mb-1.5">
                    @if (! empty($item['href']))
                        <a href="{{ $item['href'] }}" class="text-sm text-ink-700 hover:text-brand-700 font-medium truncate">{{ $item['label'] }}</a>
                    @else
                        <span class="text-sm text-ink-700 font-medium truncate">{{ $item['label'] }}</span>
                    @endif
                    <span class="text-xs font-bold text-ink-500 shrink-0 tabular-nums">{{ number_format($item['value']) }}</span>
                </div>
                <div class="h-2 rounded-full bg-ink-50 overflow-hidden">
                    <div class="h-full rounded-full" style="width:{{ $pct }}%;background:{{ $color }}"></div>
                </div>
            </li>
        @endforeach
    </ul>
@endif
