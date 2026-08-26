@props([
    'series' => [],       // [['name' => 'المشاهدات', 'color' => '#0b6b45', 'points' => [['date'=>..,'value'=>..], ...]], ...]
    'height' => 220,
])

@php
    /**
     * Server-rendered SVG line chart. Every point comes from the database via
     * AnalyticsService — there is no client-side data and no chart library.
     * Rendered with a viewBox so it scales fluidly on any screen width.
     */
    $W = 720; $H = (int) $height;
    $padL = 38; $padR = 12; $padT = 12; $padB = 26;
    $plotW = $W - $padL - $padR;
    $plotH = $H - $padT - $padB;

    $series = collect($series)->filter(fn ($s) => ! empty($s['points']))->values();
    $count = $series->first() ? count($series->first()['points']) : 0;

    $max = 0;
    foreach ($series as $s) {
        foreach ($s['points'] as $p) { $max = max($max, (int) $p['value']); }
    }
    $niceMax = $max > 0 ? (int) (ceil($max / 4) * 4) : 4;

    $x = fn ($i) => $count > 1 ? $padL + ($i * ($plotW / ($count - 1))) : $padL + $plotW / 2;
    $y = fn ($v) => $padT + $plotH - (($v / $niceMax) * $plotH);
@endphp

@if ($count === 0)
    <div class="flex items-center justify-center h-40 text-sm text-ink-400">لا توجد بيانات لعرضها في هذه الفترة.</div>
@else
    <div class="w-full overflow-x-auto">
        <svg viewBox="0 0 {{ $W }} {{ $H }}" class="w-full" style="min-width:320px;height:{{ $H }}px" role="img" aria-label="رسم بياني زمني">
            {{-- horizontal grid + y labels --}}
            @for ($g = 0; $g <= 4; $g++)
                @php $gv = $niceMax - ($g * ($niceMax / 4)); $gy = $y($gv); @endphp
                <line x1="{{ $padL }}" y1="{{ round($gy, 1) }}" x2="{{ $W - $padR }}" y2="{{ round($gy, 1) }}"
                      stroke="#e7ecea" stroke-width="1" />
                <text x="{{ $padL - 6 }}" y="{{ round($gy + 3.5, 1) }}" text-anchor="end"
                      font-size="9" fill="#93a49b">{{ (int) $gv }}</text>
            @endfor

            @foreach ($series as $s)
                @php
                    $pts = [];
                    foreach ($s['points'] as $i => $p) {
                        $pts[] = round($x($i), 1).','.round($y((int) $p['value']), 1);
                    }
                    $line = implode(' ', $pts);
                    $areaId = 'grad-'.md5($s['name']);
                    $first = explode(',', $pts[0]);
                    $last = explode(',', $pts[count($pts) - 1]);
                    $area = $line.' '.$last[0].','.($padT + $plotH).' '.$first[0].','.($padT + $plotH);
                @endphp
                <defs>
                    <linearGradient id="{{ $areaId }}" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stop-color="{{ $s['color'] }}" stop-opacity="0.18" />
                        <stop offset="100%" stop-color="{{ $s['color'] }}" stop-opacity="0" />
                    </linearGradient>
                </defs>
                <polygon points="{{ $area }}" fill="url(#{{ $areaId }})" />
                <polyline points="{{ $line }}" fill="none" stroke="{{ $s['color'] }}" stroke-width="2"
                          stroke-linejoin="round" stroke-linecap="round" />
                {{-- emphasise the final point --}}
                <circle cx="{{ $last[0] }}" cy="{{ $last[1] }}" r="3" fill="{{ $s['color'] }}" />
            @endforeach

            {{-- x labels: first, middle, last --}}
            @php $ticks = $count > 2 ? [0, intdiv($count - 1, 2), $count - 1] : range(0, $count - 1); @endphp
            @foreach ($ticks as $t)
                <text x="{{ round($x($t), 1) }}" y="{{ $H - 8 }}" text-anchor="middle" font-size="9" fill="#93a49b">
                    {{ \Illuminate\Support\Carbon::parse($series->first()['points'][$t]['date'])->format('m/d') }}
                </text>
            @endforeach
        </svg>
    </div>

    <div class="flex flex-wrap items-center gap-4 mt-3">
        @foreach ($series as $s)
            <span class="inline-flex items-center gap-1.5 text-xs text-ink-500">
                <span class="h-2 w-2 rounded-full" style="background:{{ $s['color'] }}"></span>
                {{ $s['name'] }}
                <span class="font-semibold text-ink-700">{{ number_format(collect($s['points'])->sum('value')) }}</span>
            </span>
        @endforeach
    </div>
@endif
