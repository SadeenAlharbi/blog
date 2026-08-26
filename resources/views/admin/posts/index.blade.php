@extends('layouts.admin')

@section('title', 'إدارة المقالات')

@php $viewingTrashed = ! empty($filters['trashed']); @endphp

@section('content')
    <div class="mb-5">
        <h1 class="text-xl font-bold text-ink-900">المقالات</h1>
        <p class="text-sm text-ink-500 mt-0.5">
            {{ number_format($posts->total()) }} مقال
            @if ($trashedCount > 0 && ! $viewingTrashed)
                · <a href="{{ route('admin.posts.index', ['trashed' => 1]) }}" class="text-brand-600 hover:text-brand-700 font-medium">{{ number_format($trashedCount) }} محذوف</a>
            @endif
        </p>
    </div>

    {{-- Filters: one aligned grid. Every control is the same height (h-11) and
         shares the same gap, so the row reads as a single strip on desktop and
         stacks cleanly on smaller screens. --}}
    <form method="GET" action="{{ route('admin.posts.index') }}"
          class="rounded-2xl border border-ink-100 bg-white p-4 mb-5">
        @if ($viewingTrashed)
            <input type="hidden" name="trashed" value="1">
        @endif

        <div class="grid gap-3 grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 items-end">
            {{-- Search --}}
            <div class="lg:col-span-4">
                <label for="search" class="block text-xs font-medium text-ink-500 mb-1.5">بحث</label>
                <input type="search" id="search" name="search" value="{{ $filters['search'] ?? '' }}"
                       placeholder="العنوان أو المحتوى…"
                       class="h-11 w-full rounded-xl border border-ink-200 px-4 text-sm focus:outline-none focus:ring-2 focus:ring-brand-300 focus:border-brand-300">
            </div>

            {{-- Status --}}
            <div class="lg:col-span-2">
                <label for="f-status" class="block text-xs font-medium text-ink-500 mb-1.5">الحالة</label>
                <select id="f-status" name="status"
                        class="h-11 w-full rounded-xl border border-ink-200 px-3 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-brand-300 focus:border-brand-300">
                    <option value="">كل الحالات</option>
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Category --}}
            <div class="lg:col-span-2">
                <label for="f-tag" class="block text-xs font-medium text-ink-500 mb-1.5">التصنيف</label>
                <select id="f-tag" name="tag"
                        class="h-11 w-full rounded-xl border border-ink-200 px-3 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-brand-300 focus:border-brand-300">
                    <option value="">كل التصنيفات</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->slug }}" @selected(($filters['tag'] ?? '') === $category->slug)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Author --}}
            <div class="lg:col-span-2">
                <label for="f-author" class="block text-xs font-medium text-ink-500 mb-1.5">الكاتب</label>
                <select id="f-author" name="author"
                        class="h-11 w-full rounded-xl border border-ink-200 px-3 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-brand-300 focus:border-brand-300">
                    <option value="">كل الكُتّاب</option>
                    @foreach ($authors as $author)
                        <option value="{{ $author->id }}" @selected((string) ($filters['author'] ?? '') === (string) $author->id)>{{ $author->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Actions --}}
            <div class="lg:col-span-2 flex gap-2">
                <button type="submit"
                        class="h-11 flex-1 rounded-xl bg-brand-600 text-white px-4 text-sm font-semibold hover:bg-brand-700 transition-colors">
                    تصفية
                </button>
                <a href="{{ route('admin.posts.index', $viewingTrashed ? ['trashed' => 1] : []) }}"
                   class="h-11 inline-flex items-center rounded-xl border border-ink-200 px-4 text-sm font-medium text-ink-600 hover:bg-ink-50 transition-colors">
                    إعادة
                </a>
            </div>
        </div>
    </form>

    @if ($viewingTrashed)
        <div class="mb-4 flex items-center justify-between gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3">
            <p class="text-sm text-amber-800">تعرض الآن المقالات المحذوفة. يمكن استعادتها في أي وقت.</p>
            <a href="{{ route('admin.posts.index') }}" class="shrink-0 text-xs font-semibold text-amber-800 hover:text-amber-900">عرض المقالات النشطة</a>
        </div>
    @endif

    {{-- Table --}}
    <div class="rounded-2xl border border-ink-100 bg-white overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm min-w-[900px]">
                <thead>
                    <tr class="bg-ink-25 text-ink-400 text-xs border-b border-ink-100">
                        <th class="px-4 py-3 font-medium text-start">المقال</th>
                        <th class="px-4 py-3 font-medium text-start">الكاتب</th>
                        <th class="px-4 py-3 font-medium text-start">التصنيف</th>
                        <th class="px-4 py-3 font-medium text-start">الحالة</th>
                        <th class="px-4 py-3 font-medium text-start">المشاهدات</th>
                        <th class="px-4 py-3 font-medium text-start">التعليقات</th>
                        <th class="px-4 py-3 font-medium text-start">تاريخ النشر</th>
                        <th class="px-4 py-3 font-medium text-start">إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($posts as $post)
                        <tr class="border-b border-ink-50 last:border-0 hover:bg-ink-25 transition-colors">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2.5">
                                    <div class="h-10 w-14 shrink-0 rounded-md overflow-hidden bg-gradient-to-br from-brand-100 to-sand-100">
                                        @if ($post->image)
                                            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($post->image) }}" alt="" class="w-full h-full object-cover" loading="lazy">
                                        @else
                                            <div class="w-full h-full flex items-center justify-center text-brand-600/40 text-[10px] font-bold">لا صورة</div>
                                        @endif
                                    </div>
                                    {{-- Opens the article INSIDE the dashboard. --}}
                                    <a href="{{ route('admin.posts.show', $post) }}"
                                       class="font-medium text-ink-800 hover:text-brand-700 truncate max-w-[220px]">{{ $post->title }}</a>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-ink-500 whitespace-nowrap">{{ $post->user->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-ink-500 whitespace-nowrap">{{ $post->tags->first()->name ?? '—' }}</td>
                            <td class="px-4 py-3">
                                @if ($post->trashed())
                                    <x-status-badge status="hidden" label="محذوف" />
                                @else
                                    <x-status-badge :status="$post->status" :label="$post->statusLabel()" />
                                @endif
                            </td>
                            <td class="px-4 py-3 text-ink-500 tabular-nums">{{ number_format($post->views_count) }}</td>
                            <td class="px-4 py-3 text-ink-500 tabular-nums">{{ number_format($post->comments_count) }}</td>
                            <td class="px-4 py-3 text-ink-500 whitespace-nowrap">{{ optional($post->published_at)->format('Y/m/d') ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2 whitespace-nowrap">
                                    @if ($post->trashed())
                                        <form method="POST" action="{{ route('admin.posts.restore', $post->id) }}">
                                            @csrf
                                            <button type="submit" class="text-xs font-medium text-brand-600 hover:text-brand-700">استعادة</button>
                                        </form>
                                    @else
                                        <a href="{{ route('admin.posts.show', $post) }}" class="text-xs text-ink-500 hover:text-brand-700">عرض</a>
                                        <a href="{{ route('admin.posts.edit', $post) }}" class="text-xs font-medium text-brand-600 hover:text-brand-700">تعديل</a>

                                        @if ($post->isPublished())
                                            <form method="POST" action="{{ route('admin.posts.unpublish', $post) }}"
                                                  data-confirm="سيتم تحويل المقال إلى مسودة وإخفاؤه عن الزوار."
                                                  data-confirm-title="إلغاء النشر" data-confirm-ok="إلغاء النشر">
                                                @csrf
                                                <button type="submit" class="text-xs text-amber-600 hover:text-amber-700">إلغاء النشر</button>
                                            </form>
                                        @else
                                            <form method="POST" action="{{ route('admin.posts.publish', $post) }}">
                                                @csrf
                                                <button type="submit" class="text-xs text-brand-600 hover:text-brand-700">نشر</button>
                                            </form>
                                        @endif

                                        <form method="POST" action="{{ route('admin.posts.destroy', $post) }}"
                                              data-confirm="سيُخفى المقال عن الموقع ويمكن استعادته لاحقاً، وسيُشعَر كاتبه بذلك."
                                              data-confirm-title="حذف المقال" data-confirm-ok="حذف">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-xs text-red-500 hover:text-red-700">حذف</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-14 text-center">
                                <p class="text-ink-600 font-medium">لا توجد مقالات مطابقة</p>
                                <p class="text-sm text-ink-400 mt-1">جرّب تعديل عوامل التصفية.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($posts->hasPages())
        <div class="mt-5">{{ $posts->links() }}</div>
    @endif
@endsection
