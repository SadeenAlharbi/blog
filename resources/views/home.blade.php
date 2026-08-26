@extends('layouts.app')

@section('title', 'السعودية تُروى من زواياها — منصة المعرفة السعودية')

@php
    $latest = $latestPosts->take(3);

    // Curated homepage highlight cards (navigation into the articles page).
    // Concepts without a single canonical tag link via content search so they
    // always surface real, relevant articles.
    // Each card carries its own barely-perceptible diagonal tint (spec colors),
    // applied via inline style so the exact hex gradients render without a rebuild.
    $categoryCards = [
        ['label' => 'التاريخ', 'desc' => 'جذور ممتدة وقصة وطن.', 'icon' => 'library', 'href' => route('posts.index', ['tag' => 'history']), 'tint' => 'linear-gradient(135deg,#ffffff 0%,#faf7f1 100%)'],
        ['label' => 'رؤية 2030', 'desc' => 'مستقبل نصنعه معًا.', 'icon' => 'eye', 'href' => route('posts.index', ['tag' => 'vision-2030']), 'tint' => 'linear-gradient(135deg,#ffffff 0%,#f4f7f6 100%)'],
        ['label' => 'الاقتصاد', 'desc' => 'تحولات اقتصادية تصنع مستقبلًا مزدهرًا.', 'icon' => 'chart', 'href' => route('posts.index', ['tag' => 'economy']), 'tint' => 'linear-gradient(135deg,#ffffff 0%,#f1f8f5 100%)'],
        ['label' => 'التقنية والذكاء الاصطناعي', 'desc' => 'ابتكار يقود المستقبل.', 'icon' => 'cpu', 'href' => route('posts.index', ['search' => 'الذكاء الاصطناعي']), 'tint' => 'linear-gradient(135deg,#ffffff 0%,#eef8f5 100%)'],
        ['label' => 'الثقافة والتراث', 'desc' => 'هوية أصيلة تتجدد.', 'icon' => 'sparkles', 'href' => route('posts.index', ['tag' => 'culture']), 'tint' => 'linear-gradient(135deg,#ffffff 0%,#fbf5f2 100%)'],
        ['label' => 'السياحة والمجتمع', 'desc' => 'وجهات وتجارب ومجتمع يعكس تنوع المملكة.', 'icon' => 'map', 'href' => route('posts.index', ['search' => 'السياحة']), 'tint' => 'linear-gradient(135deg,#ffffff 0%,#f2f7f8 100%)'],
    ];

    $icons = [
        'library' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332A48.36 48.36 0 0 0 12 9.75c-2.551 0-5.056.2-7.5.582V21M3 21h18M12 6.75h.008v.008H12V6.75Z"/>',
        'eye' => '<path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>',
        'chart' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z"/>',
        'cpu' => '<path stroke-linecap="round" stroke-linejoin="round" d="M8.25 3v1.5M4.5 8.25H3m18 0h-1.5M4.5 12H3m18 0h-1.5m-15 3.75H3m18 0h-1.5M8.25 19.5V21M12 3v1.5m0 15V21m3.75-18v1.5m0 15V21m-9-1.5h10.5a2.25 2.25 0 0 0 2.25-2.25V6.75a2.25 2.25 0 0 0-2.25-2.25H6.75A2.25 2.25 0 0 0 4.5 6.75v10.5a2.25 2.25 0 0 0 2.25 2.25Zm.75-12h9v9h-9v-9Z"/>',
        'sparkles' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456Z"/>',
        'map' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 6.75V15m6-6v8.25m.503 3.498 4.875-2.437c.381-.19.622-.58.622-1.006V4.82c0-.836-.88-1.38-1.628-1.006l-3.869 1.934c-.317.159-.69.159-1.006 0L9.503 3.252a1.125 1.125 0 0 0-1.006 0L3.622 5.689C3.24 5.88 3 6.27 3 6.695V19.18c0 .836.88 1.38 1.628 1.006l3.869-1.934c.317-.159.69-.159 1.006 0l4.994 2.497c.317.158.69.158 1.006 0Z"/>',
    ];

    // Very faint decorative geometric (diamond-grid) pattern, fully percent-encoded
    // so it drops straight into an unquoted CSS url(). Used at ~0.03 opacity only.
    $pattern = "data:image/svg+xml,%3Csvg%20xmlns=%27http://www.w3.org/2000/svg%27%20width=%2748%27%20height=%2748%27%20viewBox=%270%200%2048%2048%27%3E%3Cpath%20d=%27M24%202L46%2024L24%2046L2%2024Z%27%20fill=%27none%27%20stroke=%27%23145e46%27%20stroke-width=%271%27/%3E%3Cpath%20d=%27M24%2014L34%2024L24%2034L14%2024Z%27%20fill=%27none%27%20stroke=%27%23145e46%27%20stroke-width=%271%27/%3E%3C/svg%3E";

    $postsTotal = \App\Models\Post::count();
    $writersTotal = \App\Models\User::count();
    // Live category count — includes anything an administrator added.
    $categoriesTotal = count(\App\Models\Tag::options());
@endphp

@section('content')
    {{-- ============================= HERO ============================= --}}
    {{-- ⚠️ Do not modify the Hero. Background = hero-saudi.jpg + soft white overlay. --}}
    <section class="relative overflow-hidden border-b border-ink-100 bg-ink-25">
        {{-- Real Saudi hero photograph (hero-saudi.jpg) used ONLY here as the
             section background. A soft white overlay keeps the artwork visible
             but the text as the primary element. Purely decorative (aria-hidden). --}}
        <div aria-hidden="true" class="pointer-events-none absolute inset-0">
            <div class="absolute inset-0 bg-center bg-cover"
                 style="background-image:url('{{ asset('images/hero-saudi.jpg') }}')"></div>
            {{-- Soft white overlay: image stays clear, text stays primary. --}}
            <div class="absolute inset-0 bg-gradient-to-b from-white/85 via-white/75 to-white/90"></div>
        </div>

        <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 sm:py-28">
            <div class="max-w-3xl mx-auto text-center">
                <h1 class="text-4xl sm:text-6xl font-bold leading-[1.25] text-ink-900">
                    <span class="text-brand-600">السعودية</span> تُروى من زواياها
                </h1>

                <p class="mt-6 text-ink-500 text-base sm:text-lg leading-loose max-w-2xl mx-auto">
                    من تاريخها العريق إلى تحولاتها المتسارعة، نأخذك في رحلة معرفية لاكتشاف السعودية من زوايا مختلفة؛
                    مقالات موثّقة تستكشف قصتها، إنجازاتها، ثقافتها، اقتصادها، وتقنيتها، وما تصنعه اليوم لمستقبل الغد.
                </p>

                <form method="GET" action="{{ route('posts.index') }}" class="mt-9 flex items-center gap-2 max-w-2xl mx-auto">
                    <div class="relative flex-1">
                        <span class="pointer-events-none absolute inset-y-0 start-4 flex items-center text-ink-400">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                            </svg>
                        </span>
                        <input type="search" name="search" value="{{ request('search') }}"
                               placeholder="ابحث عن مقال بالعنوان أو المحتوى…"
                               class="w-full rounded-2xl border border-ink-200 bg-white ps-12 pe-4 py-3.5 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-brand-300 focus:border-brand-300">
                    </div>
                    <button type="submit" class="shrink-0 inline-flex items-center rounded-2xl bg-brand-600 text-white px-6 py-3.5 text-sm font-semibold hover:bg-brand-700 transition-colors">
                        بحث
                    </button>
                </form>

                <div class="mt-5 flex items-center justify-center gap-3 text-ink-400">
                    <span class="hidden sm:block h-px w-10 bg-ink-200"></span>
                    <p class="text-xs sm:text-sm">معرفة توثّق الماضي، وتقرأ الحاضر، وتستشرف المستقبل</p>
                    <span class="hidden sm:block h-px w-10 bg-ink-200"></span>
                </div>
            </div>
        </div>
    </section>

    {{-- ===================== EXPLORE / CATEGORIES ===================== --}}
    {{-- Near-white section, centered container, compact spacing, 6 tinted cards. --}}
    <section class="border-b border-ink-100" style="background:#fbfdfc">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-14 sm:py-16">
            <div class="text-center max-w-2xl mx-auto mb-9">
                <h2 class="text-2xl sm:text-3xl font-bold text-ink-900">استكشف السعودية من زواياها</h2>
                <p class="text-sm sm:text-base text-ink-500 mt-2">مواضيع معرفية تغطي كل ما يصنع قصة المملكة</p>
            </div>

            <div class="grid gap-4 sm:gap-5 grid-cols-1 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($categoryCards as $card)
                    <a href="{{ $card['href'] }}"
                       class="group relative overflow-hidden flex items-start gap-4 rounded-2xl border border-ink-100 p-5 transition-all duration-200 hover:-translate-y-0.5 hover:border-brand-200 hover:shadow-lg hover:shadow-ink-900/5"
                       style="background:{{ $card['tint'] }}">
                        {{-- Barely-there hover wash so the background shifts a touch. --}}
                        <span aria-hidden="true" class="pointer-events-none absolute inset-0 bg-brand-50 opacity-0 group-hover:opacity-30 transition-opacity duration-200"></span>

                        <span class="relative z-10 inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white/70 text-brand-600 ring-1 ring-brand-100 transition-colors group-hover:bg-brand-600 group-hover:text-white">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">{!! $icons[$card['icon']] !!}</svg>
                        </span>
                        <div class="relative z-10 min-w-0 flex-1">
                            <h3 class="font-bold text-ink-900 group-hover:text-brand-700 transition-colors">{{ $card['label'] }}</h3>
                            <p class="text-sm text-ink-500 mt-1 leading-relaxed line-clamp-2">{{ $card['desc'] }}</p>
                        </div>
                        <span class="relative z-10 shrink-0 text-ink-300 group-hover:text-brand-600 transition-colors mt-0.5">
                            <svg class="h-5 w-5 rtl:rotate-180" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" /></svg>
                        </span>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ======================= LATEST ARTICLES ======================= --}}
    {{-- Premium light background: soft radial gradients + faint pattern + floating cards.
         All article data is REAL — pulled from $latestPosts (DB), never hard-coded. --}}
    <section class="relative border-b border-ink-100"
             style="background:radial-gradient(circle at 85% 15%,rgba(19,111,82,0.045),transparent 32%),radial-gradient(circle at 10% 80%,rgba(215,191,155,0.035),transparent 30%),#f7faf8">
        <div aria-hidden="true" class="pointer-events-none absolute inset-0" style="background-image:url({{ $pattern }});background-size:48px 48px;opacity:.03"></div>

        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-14 sm:py-16">
            <div class="flex items-end justify-between gap-4 mb-8">
                <div>
                    <h2 class="text-2xl sm:text-3xl font-bold text-ink-900">أحدث المقالات</h2>
                    <p class="text-sm text-ink-500 mt-1.5">اكتشف آخر ما نرويه عن السعودية</p>
                </div>
                <a href="{{ route('posts.index') }}" class="shrink-0 inline-flex items-center gap-1 rounded-xl border border-ink-200 bg-white px-4 py-2 text-sm font-semibold text-ink-700 hover:border-brand-300 hover:text-brand-700 transition-colors">
                    عرض جميع المقالات
                    <svg class="h-4 w-4 rtl:rotate-180" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" /></svg>
                </a>
            </div>

            @if ($latest->isEmpty())
                <x-empty-state title="لا توجد مقالات منشورة بعد." />
            @else
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($latest as $post)
                        @php
                            $imageUrl = $post->image ? \Illuminate\Support\Facades\Storage::disk('public')->url($post->image) : null;
                            $words = count(array_filter(preg_split('/\s+/u', trim(strip_tags($post->content))) ?: []));
                            $readingTime = max(1, (int) ceil($words / 180));
                            $badge = $post->tags->first();
                        @endphp
                        <article class="group relative flex flex-col rounded-2xl overflow-hidden transition-all duration-200 hover:-translate-y-0.5"
                                 style="background:rgba(255,255,255,0.92);border:1px solid rgba(25,70,55,0.07);box-shadow:0 8px 28px rgba(20,55,45,0.055)">
                            <div class="relative aspect-[16/9] bg-gradient-to-br from-brand-100 to-sand-100">
                                @if ($imageUrl)
                                    <img src="{{ $imageUrl }}" alt="{{ $post->title }}" loading="lazy" class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105">
                                @else
                                    <div class="w-full h-full flex items-center justify-center"><x-logo :size="48" /></div>
                                @endif
                                {{-- Very light hover overlay only. --}}
                                <div aria-hidden="true" class="absolute inset-0 bg-ink-900/0 group-hover:bg-ink-900/5 transition-colors duration-200"></div>
                            </div>
                            <div class="flex flex-col flex-1 p-5">
                                @if ($badge)
                                    <div class="mb-2.5"><x-chip variant="brand" size="sm">{{ $badge->name }}</x-chip></div>
                                @endif
                                <h3 class="font-bold text-ink-900 leading-snug mb-2 line-clamp-2 group-hover:text-brand-600 transition-colors">{{ $post->title }}</h3>
                                <p class="text-sm text-ink-500 leading-relaxed mb-4 line-clamp-2">{{ \Illuminate\Support\Str::limit(strip_tags($post->content), 110) }}</p>
                                <div class="mt-auto flex items-center gap-3 pt-3 border-t border-ink-50 text-xs text-ink-400">
                                    <span class="inline-flex items-center gap-1">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" /></svg>
                                        {{ optional($post->published_at)->format('Y/m/d') }}
                                    </span>
                                    <span class="inline-flex items-center gap-1">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                                        {{ $readingTime }} دقائق قراءة
                                    </span>
                                    <span class="ms-auto font-semibold text-brand-600 group-hover:text-brand-700">اقرأ المقال ←</span>
                                </div>
                            </div>
                            <a href="{{ route('posts.show', $post) }}" class="absolute inset-0 z-10" aria-label="{{ $post->title }}"></a>
                        </article>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    {{-- =================== STATS (light, compact bar) =================== --}}
    <section class="bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 sm:py-12">
            <div class="mx-auto rounded-2xl border border-ink-100"
                 style="max-width:1200px;padding-block:22px;background:radial-gradient(circle at 15% 50%,rgba(11,127,91,0.06),transparent 35%),radial-gradient(circle at 85% 50%,rgba(91,135,116,0.04),transparent 35%),linear-gradient(135deg,#f3f9f6 0%,#edf6f2 50%,#f6faf8 100%)">
                <div class="grid grid-cols-2 lg:grid-cols-4 px-4 sm:px-6">
                    <div class="flex flex-col items-center text-center px-4 py-3">
                        <span class="mb-2 inline-flex h-9 w-9 items-center justify-center rounded-lg bg-white/70 text-brand-600 ring-1 ring-brand-100">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .32-.988l5.519-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z" /></svg>
                        </span>
                        <p class="text-2xl sm:text-3xl font-extrabold text-brand-700">100%</p>
                        <p class="text-xs sm:text-sm text-ink-500 mt-1">محتوى موثّق</p>
                    </div>
                    <div class="flex flex-col items-center text-center px-4 py-3 border-s border-ink-200/70 lg:border-s">
                        <span class="mb-2 inline-flex h-9 w-9 items-center justify-center rounded-lg bg-white/70 text-brand-600 ring-1 ring-brand-100">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6Z" /></svg>
                        </span>
                        <p class="text-2xl sm:text-3xl font-extrabold text-brand-700">{{ $categoriesTotal }}</p>
                        <p class="text-xs sm:text-sm text-ink-500 mt-1">تصنيف معرفي</p>
                    </div>
                    <div class="flex flex-col items-center text-center px-4 py-3 lg:border-s border-ink-200/70">
                        <span class="mb-2 inline-flex h-9 w-9 items-center justify-center rounded-lg bg-white/70 text-brand-600 ring-1 ring-brand-100">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" /></svg>
                        </span>
                        <p class="text-2xl sm:text-3xl font-extrabold text-brand-700">{{ $writersTotal }}</p>
                        <p class="text-xs sm:text-sm text-ink-500 mt-1">كاتب ومساهم</p>
                    </div>
                    <div class="flex flex-col items-center text-center px-4 py-3 border-s border-ink-200/70">
                        <span class="mb-2 inline-flex h-9 w-9 items-center justify-center rounded-lg bg-white/70 text-brand-600 ring-1 ring-brand-100">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18c-2.305 0-4.408.867-6 2.292m0-14.25v14.25" /></svg>
                        </span>
                        <p class="text-2xl sm:text-3xl font-extrabold text-brand-700">{{ $postsTotal }}</p>
                        <p class="text-xs sm:text-sm text-ink-500 mt-1">مقال معرفي</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ======================== ABOUT (compact) ======================= --}}
    {{-- Short image + text band. RTL: text right, real Saudi image left. --}}
    <section class="relative overflow-hidden"
             style="background:radial-gradient(circle at 20% 50%,rgba(10,120,85,0.035),transparent 35%),#ffffff">
        <div aria-hidden="true" class="pointer-events-none absolute inset-y-0 start-0 w-1/3" style="background-image:url({{ $pattern }});background-size:48px 48px;opacity:.035"></div>

        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8" style="padding-block:45px">
            <div class="grid gap-8 lg:grid-cols-12 items-center">
                {{-- Text (right in RTL) --}}
                <div class="lg:col-span-7">
                    <span class="inline-flex items-center gap-2 text-xs font-semibold text-brand-700 mb-3">
                        <span class="h-px w-8 bg-brand-300"></span> عن المنصة
                    </span>
                    <h2 class="font-bold text-ink-900 mb-3" style="font-size:26px;line-height:1.35">عن المنصة</h2>
                    <p class="text-ink-500 max-w-xl" style="font-size:15px;line-height:1.9">
                        منصة معرفية سعودية توثّق قصة المملكة وتحولاتها، وتقدّم محتوى موثوقًا يعرّفك بتاريخها، رؤيتها،
                        اقتصادها، ثقافتها، وتقنيتها، ويواكب ما تصنعه السعودية اليوم نحو المستقبل.
                    </p>
                    <div class="mt-5">
                        <a href="{{ route('posts.index') }}" class="inline-flex items-center gap-1.5 rounded-xl bg-brand-600 text-white px-5 py-2.5 text-sm font-semibold hover:bg-brand-700 transition-colors">
                            تصفّح المقالات
                            <svg class="h-4 w-4 rtl:rotate-180" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" /></svg>
                        </a>
                    </div>
                </div>

                {{-- Image (left in RTL) — no card frame; edges are masked so the
                     photo melts into the section background (soft toward the text
                     side and the top), matching the reference. --}}
                <div class="lg:col-span-5 order-first lg:order-last">
                    <img src="{{ asset('images/about-saudi.jpg') }}" alt="علم المملكة العربية السعودية" loading="lazy"
                         class="w-full h-64 sm:h-80 object-cover select-none pointer-events-none"
                         style="-webkit-mask-image:linear-gradient(to right,#000 65%,transparent 100%),linear-gradient(to top,#000 82%,transparent 100%);-webkit-mask-composite:source-in;mask-image:linear-gradient(to right,#000 65%,transparent 100%),linear-gradient(to top,#000 82%,transparent 100%);mask-composite:intersect;">
                </div>
            </div>
        </div>
    </section>
@endsection
