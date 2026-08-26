@extends('layouts.admin')

@section('title', 'إدارة التعليقات')

@section('content')
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-5">
        <div>
            <h1 class="text-xl font-bold text-ink-900">التعليقات</h1>
            <p class="text-sm text-ink-500 mt-0.5">{{ number_format($comments->total()) }} تعليق</p>
        </div>

        @if ($deletedTotal > 0)
            <button type="button" data-modal-open="restore-comments"
                    class="inline-flex items-center gap-1.5 h-11 shrink-0 rounded-xl border border-ink-200 bg-white px-4 text-sm font-semibold text-ink-700 hover:border-brand-300 hover:text-brand-700 transition-colors">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992V4.356m-4.993 4.992-3.181-3.183a8.25 8.25 0 0 0-13.803 3.7M4.031 9.865v4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7" /></svg>
                استرداد التعليقات المحذوفة ({{ number_format($deletedTotal) }})
            </button>
        @endif
    </div>

    {{-- Status tabs (real counts) --}}
    <div class="flex flex-wrap items-center gap-2 mb-5">
        @php
            $tabs = [
                '' => ['label' => 'الكل', 'count' => $counts['all']],
                'approved' => ['label' => 'ظاهر', 'count' => $counts['approved']],
                'hidden' => ['label' => 'مخفي', 'count' => $counts['hidden']],
            ];
            $activeStatus = $filters['status'] ?? '';
            // The removed list is a separate view, not a status: it reads the
            // ?trashed=1 branch the controller already supported.
            $viewingTrashed = (bool) ($filters['trashed'] ?? false);
        @endphp
        @foreach ($tabs as $value => $tab)
            <a href="{{ route('admin.comments.index', array_filter(['status' => $value, 'search' => $filters['search'] ?? null])) }}"
               class="inline-flex items-center gap-1.5 rounded-xl px-3.5 py-2 text-sm font-medium transition-colors {{ ! $viewingTrashed && $activeStatus === $value ? 'bg-brand-600 text-white' : 'bg-white border border-ink-200 text-ink-600 hover:border-brand-300' }}">
                {{ $tab['label'] }}
                <span class="text-xs tabular-nums {{ ! $viewingTrashed && $activeStatus === $value ? 'text-white/80' : 'text-ink-400' }}">{{ number_format($tab['count']) }}</span>
            </a>
        @endforeach

        @if ($counts['trashed'] > 0)
            <a href="{{ route('admin.comments.index', array_filter(['trashed' => 1, 'search' => $filters['search'] ?? null])) }}"
               class="inline-flex items-center gap-1.5 rounded-xl px-3.5 py-2 text-sm font-medium transition-colors {{ $viewingTrashed ? 'bg-brand-600 text-white' : 'bg-white border border-ink-200 text-ink-600 hover:border-brand-300' }}">
                محذوف
                <span class="text-xs tabular-nums {{ $viewingTrashed ? 'text-white/80' : 'text-ink-400' }}">{{ number_format($counts['trashed']) }}</span>
            </a>
        @endif

        <form method="GET" action="{{ route('admin.comments.index') }}" class="flex-1 min-w-[200px] flex gap-2">
            @if ($viewingTrashed)
                <input type="hidden" name="trashed" value="1">
            @elseif ($activeStatus)
                <input type="hidden" name="status" value="{{ $activeStatus }}">
            @endif
            <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="ابحث في التعليقات…"
                   class="flex-1 min-w-0 rounded-xl border border-ink-200 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-300">
            <button type="submit" class="shrink-0 rounded-xl bg-brand-600 text-white px-4 text-sm font-semibold hover:bg-brand-700 transition-colors">بحث</button>
        </form>
    </div>

    <div class="rounded-2xl border border-ink-100 bg-white overflow-hidden">
        @forelse ($comments as $comment)
            <div class="flex flex-col sm:flex-row sm:items-start gap-3 px-5 py-4 border-b border-ink-50 last:border-0 hover:bg-ink-25 transition-colors">
                <x-avatar :name="$comment->user->name ?? 'م'" :size="36" class="shrink-0" />

                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2 mb-1">
                        <p class="text-sm font-semibold text-ink-800">{{ $comment->user->name ?? 'مستخدم محذوف' }}</p>
                        <x-status-badge :status="$comment->status" :label="$comment->statusLabel()" />
                        <span class="text-xs text-ink-300">{{ $comment->created_at->format('Y/m/d H:i') }}</span>
                    </div>

                    <p class="text-sm text-ink-600 leading-relaxed mb-1.5">{{ $comment->content }}</p>

                    @if ($comment->post)
                        <a href="{{ route('posts.show', $comment->post) }}" class="text-xs text-ink-400 hover:text-brand-700">
                            على مقال: {{ \Illuminate\Support\Str::limit($comment->post->title, 60) }}
                        </a>
                    @else
                        <span class="text-xs text-ink-300">المقال محذوف</span>
                    @endif
                </div>

                <div class="flex items-center gap-2 shrink-0">
                    @if ($comment->trashed())
                        {{-- Removed comment: the only action is putting it back. --}}
                        <form method="POST" action="{{ route('admin.comments.restore', $comment->id) }}">
                            @csrf
                            <button type="submit" class="rounded-lg border border-brand-200 bg-brand-50 px-3 py-1.5 text-xs font-semibold text-brand-700 hover:bg-brand-100 transition-colors">استرداد</button>
                        </form>
                    @else
                        @if ($comment->status !== 'approved')
                            <form method="POST" action="{{ route('admin.comments.approve', $comment) }}">
                                @csrf
                                <button type="submit" class="rounded-lg border border-brand-200 bg-brand-50 px-3 py-1.5 text-xs font-semibold text-brand-700 hover:bg-brand-100 transition-colors">موافقة</button>
                            </form>
                        @endif

                        @if ($comment->status !== 'hidden')
                            <form method="POST" action="{{ route('admin.comments.hide', $comment) }}"
                                  data-confirm="سيتم إخفاء التعليق عن زوار الموقع (دون حذفه)."
                                  data-confirm-title="إخفاء التعليق" data-confirm-ok="إخفاء">
                                @csrf
                                <button type="submit" class="rounded-lg border border-ink-200 px-3 py-1.5 text-xs font-medium text-ink-600 hover:border-amber-300 hover:text-amber-700 transition-colors">إخفاء</button>
                            </form>
                        @endif

                        <form method="POST" action="{{ route('admin.comments.destroy', $comment) }}"
                              data-confirm="سيتم حذف التعليق. يمكنك استرداده لاحقاً من «استرداد التعليقات المحذوفة»."
                              data-confirm-title="حذف التعليق" data-confirm-ok="حذف">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="rounded-lg border border-ink-200 px-3 py-1.5 text-xs font-medium text-red-500 hover:border-red-300 hover:bg-red-50 transition-colors">حذف</button>
                        </form>
                    @endif
                </div>
            </div>
        @empty
            <div class="px-5 py-16 text-center">
                <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-ink-50 text-ink-300">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.501 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z" /></svg>
                </div>
                <p class="text-ink-600 font-medium">لا توجد تعليقات</p>
                <p class="text-sm text-ink-400 mt-1">لم يُعثر على تعليقات مطابقة لهذا التصفية.</p>
            </div>
        @endforelse
    </div>

    @if ($comments->hasPages())
        <div class="mt-5">{{ $comments->links() }}</div>
    @endif

    {{-- ───────────── Restore removed comments ───────────── --}}
    @if ($deletedTotal > 0)
        <div id="modal-restore-comments" data-modal
             class="hidden fixed inset-0 z-50 flex items-center justify-center p-4"
             role="dialog" aria-modal="true" aria-labelledby="restore-comments-title">
            <div class="absolute inset-0 bg-ink-900/40" data-modal-close></div>

            {{-- 85vh, matching the categories dialog: the value is already in
                 the compiled stylesheet, so this needs no Tailwind rebuild. --}}
            <div class="relative w-full max-w-2xl rounded-2xl bg-white shadow-xl flex flex-col max-h-[85vh]">
                <div class="flex items-center justify-between px-5 py-4 border-b border-ink-100">
                    <h2 id="restore-comments-title" class="font-bold text-ink-900">استرداد التعليقات المحذوفة</h2>
                    <button type="button" data-modal-close aria-label="إغلاق"
                            class="p-1 text-ink-400 hover:text-ink-700 transition-colors">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                {{-- Only ticked comments are restored; the rest stay removed. --}}
                <form method="POST" action="{{ route('admin.comments.restoreSelected') }}"
                      class="flex flex-col min-h-0" data-restore-form>
                    @csrf

                    {{-- Filters. Applied in place so the dialog never reloads. --}}
                    <div class="px-5 py-3 border-b border-ink-100 bg-ink-25 grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <div>
                            <label for="restore-filter-user" class="block text-xs font-medium text-ink-500 mb-1">المستخدم</label>
                            <select id="restore-filter-user" data-filter="user"
                                    class="h-10 w-full rounded-xl border border-ink-200 bg-white px-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand-300">
                                <option value="">جميع المستخدمين</option>
                                @foreach ($deletedAuthors as $author)
                                    <option value="{{ $author->id }}">{{ $author->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="restore-filter-post" class="block text-xs font-medium text-ink-500 mb-1">المقال</label>
                            <select id="restore-filter-post" data-filter="post"
                                    class="h-10 w-full rounded-xl border border-ink-200 bg-white px-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand-300">
                                <option value="">جميع المقالات</option>
                                @foreach ($deletedPosts as $post)
                                    <option value="{{ $post->id }}">{{ \Illuminate\Support\Str::limit($post->title, 70) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- Select-all applies to what the filters currently show. --}}
                    <div class="px-5 py-2.5 border-b border-ink-50 flex items-center justify-between gap-3">
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" data-select-all
                                   class="h-4 w-4 rounded border-ink-300 text-brand-600 focus:ring-brand-300">
                            <span class="text-xs font-medium text-ink-600">تحديد الكل الظاهر</span>
                        </label>
                        <span data-visible-count class="text-xs text-ink-400"></span>
                    </div>

                    <div class="px-5 py-1 overflow-y-auto" data-restore-list>
                        @foreach ($deleted as $comment)
                            <label data-row
                                   data-user="{{ $comment->user->id ?? '' }}"
                                   data-post="{{ $comment->post->id ?? '' }}"
                                   class="flex items-start gap-3 py-3 border-b border-ink-50 last:border-0 cursor-pointer">
                                <input type="checkbox" name="ids[]" value="{{ $comment->id }}"
                                       class="mt-1 h-4 w-4 shrink-0 rounded border-ink-300 text-brand-600 focus:ring-brand-300">

                                <span class="min-w-0 flex-1">
                                    <span class="flex flex-wrap items-center gap-2 mb-1">
                                        <span class="text-sm font-semibold text-ink-800">{{ $comment->user->name ?? 'مستخدم محذوف' }}</span>
                                        <span class="text-xs text-ink-300">{{ $comment->created_at?->format('Y/m/d H:i') }}</span>
                                        @if ($comment->deleted_at)
                                            <span class="text-[11px] text-red-500">حُذف {{ $comment->deleted_at->diffForHumans() }}</span>
                                        @endif
                                    </span>

                                    <span class="block text-sm text-ink-600 leading-relaxed line-clamp-3">{{ $comment->content }}</span>

                                    <span class="block text-xs text-ink-400 mt-1">
                                        @if ($comment->post)
                                            على مقال: {{ \Illuminate\Support\Str::limit($comment->post->title, 60) }}
                                        @else
                                            المقال محذوف
                                        @endif
                                    </span>
                                </span>
                            </label>
                        @endforeach

                        <p data-empty-filter class="hidden py-10 text-center text-sm text-ink-400">
                            لا توجد تعليقات محذوفة مطابقة لهذه التصفية.
                        </p>
                    </div>

                    <div class="px-5 py-4 border-t border-ink-100 bg-ink-25 rounded-b-2xl">
                        @if ($deletedTotal > $deletedLimit)
                            <p class="text-xs text-ink-400 mb-2">
                                يُعرض أحدث {{ number_format($deletedLimit) }} تعليقاً من أصل {{ number_format($deletedTotal) }}.
                            </p>
                        @endif

                        <p data-restore-error class="hidden text-xs text-red-600 mb-2">يرجى تحديد تعليق واحد على الأقل.</p>

                        <div class="flex items-center gap-2">
                            <button type="submit"
                                    class="h-11 flex-1 rounded-xl bg-brand-600 text-white text-sm font-semibold hover:bg-brand-700 transition-colors">
                                استرداد المحدد
                            </button>
                            <button type="button" data-modal-close
                                    class="h-11 rounded-xl border border-ink-200 bg-white px-5 text-sm font-medium text-ink-600 hover:border-ink-300 transition-colors">
                                إلغاء
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <script>
            // Dependency-free dialog + in-place filtering, matching the pattern
            // already used on the categories page.
            (function () {
                function setOpen(el, open) {
                    if (!el) return;
                    el.classList.toggle('hidden', !open);
                    document.body.classList.toggle('overflow-hidden', open);
                }

                document.querySelectorAll('[data-modal-open]').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        setOpen(document.getElementById('modal-' + btn.dataset.modalOpen), true);
                    });
                });

                document.querySelectorAll('[data-modal-close]').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        setOpen(btn.closest('[data-modal]'), false);
                    });
                });

                document.addEventListener('keydown', function (e) {
                    if (e.key !== 'Escape') return;
                    document.querySelectorAll('[data-modal]:not(.hidden)').forEach(function (m) { setOpen(m, false); });
                });

                var form = document.querySelector('[data-restore-form]');
                if (!form) return;

                var rows = Array.prototype.slice.call(form.querySelectorAll('[data-row]'));
                var userFilter = form.querySelector('[data-filter="user"]');
                var postFilter = form.querySelector('[data-filter="post"]');
                var selectAll = form.querySelector('[data-select-all]');
                var countLabel = form.querySelector('[data-visible-count]');
                var emptyNote = form.querySelector('[data-empty-filter]');
                var errorNote = form.querySelector('[data-restore-error]');

                function visibleRows() {
                    return rows.filter(function (r) { return !r.classList.contains('hidden'); });
                }

                function applyFilters() {
                    var u = userFilter.value;
                    var p = postFilter.value;

                    rows.forEach(function (row) {
                        var show = (!u || row.dataset.user === u) && (!p || row.dataset.post === p);
                        row.classList.toggle('hidden', !show);
                        // A hidden row must never be submitted, even if it was
                        // ticked before the filter changed.
                        if (!show) row.querySelector('input[type="checkbox"]').checked = false;
                    });

                    var shown = visibleRows().length;
                    emptyNote.classList.toggle('hidden', shown > 0);
                    countLabel.textContent = shown + ' من ' + rows.length;
                    selectAll.checked = false;
                    errorNote.classList.add('hidden');
                }

                userFilter.addEventListener('change', applyFilters);
                postFilter.addEventListener('change', applyFilters);

                selectAll.addEventListener('change', function () {
                    visibleRows().forEach(function (row) {
                        row.querySelector('input[type="checkbox"]').checked = selectAll.checked;
                    });
                    errorNote.classList.add('hidden');
                });

                // Client-side courtesy only — the server refuses an empty
                // selection too, so this can never be the only guard.
                form.addEventListener('submit', function (e) {
                    if (form.querySelectorAll('input[name="ids[]"]:checked').length === 0) {
                        e.preventDefault();
                        errorNote.classList.remove('hidden');
                    }
                });

                applyFilters();
            })();
        </script>
    @endif
@endsection
