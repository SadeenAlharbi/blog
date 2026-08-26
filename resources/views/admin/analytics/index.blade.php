@extends('layouts.admin')

@section('title', 'التحليلات')

@section('content')
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-5">
        <div>
            <h1 class="text-xl font-bold text-ink-900">التحليلات</h1>
            <p class="text-sm text-ink-500 mt-0.5">{{ $periods[$period]['label'] }} — بيانات فعلية من قاعدة البيانات</p>
        </div>
        <div class="flex flex-wrap items-center gap-1 shrink-0">
            @foreach ($periods as $key => $meta)
                <a href="{{ route('admin.analytics.index', ['period' => $key]) }}"
                   class="inline-flex items-center h-9 rounded-lg px-3 text-xs font-medium transition-colors {{ $period === $key ? 'bg-brand-600 text-white' : 'bg-white border border-ink-200 text-ink-500 hover:border-brand-300' }}">
                    {{ $meta['label'] }}
                </a>
            @endforeach
        </div>
    </div>

    {{-- ① الإحصائيات العامة — كل رقم استعلام حقيقي --}}
    <section class="mb-6">
        <h2 class="text-base font-bold text-ink-900 mb-3">الإحصائيات العامة</h2>

        @php
            $cards = [
                ['label' => 'إجمالي المقالات', 'value' => $overview['posts_total']],
                ['label' => 'المنشورة', 'value' => $overview['posts_published']],
                ['label' => 'المسودات', 'value' => $overview['posts_draft']],
                ['label' => 'المجدولة', 'value' => $overview['posts_scheduled']],
                ['label' => 'إجمالي المشاهدات', 'value' => $overview['views_total']],
                ['label' => 'إجمالي التعليقات', 'value' => $overview['comments_total']],
                ['label' => 'إجمالي المستخدمين', 'value' => $overview['users_total']],
                ['label' => 'تعليقات مخفية', 'value' => $overview['comments_hidden']],
            ];
        @endphp

        <div class="grid gap-4 grid-cols-2 lg:grid-cols-4">
            @foreach ($cards as $card)
                <div class="rounded-2xl border border-ink-100 bg-white p-4">
                    <p class="text-2xl font-extrabold text-ink-900 tabular-nums">{{ number_format($card['value']) }}</p>
                    <p class="text-xs text-ink-500 mt-1">{{ $card['label'] }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- ② أكثر المقالات مشاهدة | أكثر التصنيفات نشرًا --}}
    <section class="grid gap-4 lg:grid-cols-2 items-stretch mb-6">
        <div class="h-full rounded-2xl border border-ink-100 bg-white overflow-hidden flex flex-col">
            <div class="px-5 py-4 border-b border-ink-100">
                <h2 class="font-bold text-ink-900">أكثر المقالات مشاهدة</h2>
                <p class="text-xs text-ink-400 mt-0.5">حسب عدد القراءات المسجّلة</p>
            </div>
            <div class="flex-1 overflow-x-auto">
                <table class="w-full text-sm min-w-[420px]">
                    <thead>
                        <tr class="bg-ink-25 text-ink-400 text-xs">
                            <th class="px-4 py-2.5 font-medium text-start">المقال</th>
                            <th class="px-4 py-2.5 font-medium text-start">الحالة</th>
                            <th class="px-4 py-2.5 font-medium text-start">المشاهدات</th>
                            <th class="px-4 py-2.5 font-medium text-start">التعليقات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($topPosts as $post)
                            <tr class="border-b border-ink-50 last:border-0 hover:bg-ink-25 transition-colors">
                                <td class="px-4 py-3">
                                    <a href="{{ route('admin.posts.show', $post->id) }}" class="font-medium text-ink-800 hover:text-brand-700 truncate block max-w-[220px]">{{ $post->title }}</a>
                                    <span class="text-xs text-ink-400">{{ $post->tags->first()->name ?? '—' }}</span>
                                </td>
                                <td class="px-4 py-3"><x-status-badge :status="$post->status" :label="$post->statusLabel()" /></td>
                                <td class="px-4 py-3 font-bold text-ink-900 tabular-nums">{{ number_format($post->views_count) }}</td>
                                <td class="px-4 py-3 text-ink-500 tabular-nums">{{ number_format($post->comments_count) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-12 text-center text-sm text-ink-400">لا توجد مشاهدات مسجّلة بعد.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="h-full rounded-2xl border border-ink-100 bg-white p-5 flex flex-col">
            <h2 class="font-bold text-ink-900 mb-1">أكثر التصنيفات نشرًا</h2>
            <p class="text-xs text-ink-400 mb-4">عدد المقالات المرتبطة بكل تصنيف</p>
            <div class="flex-1">
                <x-bar-chart :items="$topCategories->map(fn ($t) => [
                    'label' => $t->name,
                    'value' => $t->posts_count,
                    'href' => route('admin.posts.index', ['tag' => $t->slug]),
                ])->all()" empty-text="لا توجد مقالات مصنّفة بعد." />
            </div>
        </div>
    </section>

    {{-- ③ أكثر التصنيفات مشاهدة | المشاهدات عبر الزمن --}}
    <section class="grid gap-4 lg:grid-cols-2 items-stretch mb-6">
        <div class="h-full rounded-2xl border border-ink-100 bg-white p-5 flex flex-col">
            <h2 class="font-bold text-ink-900 mb-1">أكثر التصنيفات مشاهدة</h2>
            {{-- Derived from the article view records, not a second view system. --}}
            <p class="text-xs text-ink-400 mb-4">مجموع مشاهدات مقالات كل تصنيف</p>
            <div class="flex-1">
                <x-bar-chart color="#b5893c" :items="$topCategoriesByViews->map(fn ($t) => [
                    'label' => $t->name,
                    'value' => (int) $t->views_count,
                    'href' => route('admin.posts.index', ['tag' => $t->slug]),
                ])->all()" empty-text="لا توجد مشاهدات مسجّلة بعد." />
            </div>
        </div>

        <div class="h-full rounded-2xl border border-ink-100 bg-white p-5 flex flex-col">
            <h2 class="font-bold text-ink-900 mb-1">المشاهدات عبر الزمن</h2>
            <p class="text-xs text-ink-400 mb-4">عدد قراءات المقالات يومياً</p>
            <div class="flex-1">
                <x-line-chart :series="[['name' => 'المشاهدات', 'color' => '#0b6b45', 'points' => $views]]" :height="240" />
            </div>
        </div>
    </section>

    {{-- ④ المقالات المنشورة عبر الزمن | التعليقات عبر الزمن --}}
    <section class="grid gap-4 lg:grid-cols-2 items-stretch mb-6">
        <div class="h-full rounded-2xl border border-ink-100 bg-white p-5 flex flex-col">
            <h2 class="font-bold text-ink-900 mb-1">المقالات المنشورة عبر الزمن</h2>
            <p class="text-xs text-ink-400 mb-4">وتيرة النشر</p>
            <div class="flex-1">
                <x-line-chart :series="[['name' => 'المقالات', 'color' => '#5b8774', 'points' => $posts]]" :height="220" />
            </div>
        </div>

        <div class="h-full rounded-2xl border border-ink-100 bg-white p-5 flex flex-col">
            <h2 class="font-bold text-ink-900 mb-1">التعليقات عبر الزمن</h2>
            <p class="text-xs text-ink-400 mb-4">تفاعل القرّاء</p>
            <div class="flex-1">
                <x-line-chart :series="[['name' => 'التعليقات', 'color' => '#0b6b45', 'points' => $comments]]" :height="220" />
            </div>
        </div>
    </section>

    {{-- أكثر الكُتّاب نشاطًا — بيانات قائمة من قبل، محفوظة كما هي --}}
    <section>
        <div class="rounded-2xl border border-ink-100 bg-white p-5">
            <h2 class="font-bold text-ink-900 mb-4">أكثر الكُتّاب نشاطًا</h2>
            <div class="grid gap-x-8 sm:grid-cols-2">
                @forelse ($topAuthors as $author)
                    <div class="flex items-center gap-3 py-2 border-b border-ink-50">
                        <x-avatar :name="$author->name" :size="30" />
                        <a href="{{ route('admin.users.show', $author) }}" class="flex-1 min-w-0 text-sm text-ink-700 hover:text-brand-700 truncate">{{ $author->name }}</a>
                        <span class="shrink-0 text-xs font-bold text-brand-700 tabular-nums">{{ $author->published_posts_count }}</span>
                    </div>
                @empty
                    <p class="py-6 text-center text-sm text-ink-400 sm:col-span-2">لا يوجد كُتّاب بعد.</p>
                @endforelse
            </div>
        </div>
    </section>
@endsection
