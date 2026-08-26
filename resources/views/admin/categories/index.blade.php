@extends('layouts.admin')

@section('title', 'التصنيفات')

@php
    // The add dialog re-opens by itself when the server rejected the name, so
    // the moderator sees the message ("هذا التصنيف موجود بالفعل.") in place.
    $addHasErrors = $errors->has('name');
    $restorable = $removed->count() + count($missingDefaults);
@endphp

@section('content')
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-5">
        <div>
            <h1 class="text-xl font-bold text-ink-900">التصنيفات</h1>
            <p class="text-sm text-ink-500 mt-0.5">{{ $tags->count() }} تصنيفاً</p>
        </div>

        <div class="flex flex-wrap items-center gap-2 shrink-0">
            @if ($restorable > 0)
                <button type="button" data-modal-open="restore-categories"
                        class="inline-flex items-center gap-1.5 h-11 rounded-xl border border-ink-200 bg-white px-4 text-sm font-semibold text-ink-700 hover:border-brand-300 hover:text-brand-700 transition-colors">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992V4.356m-4.993 4.992-3.181-3.183a8.25 8.25 0 0 0-13.803 3.7M4.031 9.865v4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7" /></svg>
                    استرداد التصنيفات المحذوفة ({{ $restorable }})
                </button>
            @endif

            <button type="button" data-modal-open="add-category"
                    class="inline-flex items-center gap-1.5 h-11 rounded-xl bg-brand-600 text-white px-5 text-sm font-semibold hover:bg-brand-700 transition-colors">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                إضافة تصنيف
            </button>
        </div>
    </div>

    {{-- Existing categories --}}
    <div class="rounded-2xl border border-ink-100 bg-white overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm min-w-[640px]">
                <thead>
                    <tr class="bg-ink-25 text-ink-400 text-xs border-b border-ink-100">
                        <th class="px-4 py-3 font-medium text-start">التصنيف</th>
                        <th class="px-4 py-3 font-medium text-start">المقالات</th>
                        <th class="px-4 py-3 font-medium text-start">المنشورة</th>
                        <th class="px-4 py-3 font-medium text-start">إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($tags as $tag)
                        <tr class="border-b border-ink-50 last:border-0 hover:bg-ink-25 transition-colors">
                            <td class="px-4 py-3">
                                {{-- Inline rename. The slug stays fixed so existing filter links keep working. --}}
                                <form method="POST" action="{{ route('admin.categories.update', $tag) }}" class="flex items-center gap-2">
                                    @csrf
                                    @method('PUT')
                                    <input type="text" name="name" value="{{ $tag->name }}" maxlength="50" required
                                           class="h-9 w-full max-w-[260px] rounded-lg border border-transparent bg-transparent px-2 text-sm font-medium text-ink-800 hover:border-ink-200 focus:border-brand-300 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-100 transition-colors">
                                    <button type="submit" class="shrink-0 text-xs font-semibold text-brand-600 hover:text-brand-700">حفظ</button>
                                </form>
                            </td>
                            <td class="px-4 py-3 text-ink-500 tabular-nums">{{ number_format($tag->posts_count) }}</td>
                            <td class="px-4 py-3 text-ink-500 tabular-nums">{{ number_format($tag->published_posts_count) }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3 whitespace-nowrap">
                                    @if ($tag->posts_count > 0)
                                        <a href="{{ route('admin.posts.index', ['tag' => $tag->slug]) }}" class="text-xs text-ink-500 hover:text-brand-700">المقالات</a>
                                        <span class="text-xs text-ink-300" title="لا يمكن الحذف لوجود مقالات مرتبطة">حذف</span>
                                    @else
                                        <form method="POST" action="{{ route('admin.categories.destroy', $tag) }}"
                                              data-confirm="سيتم حذف التصنيف «{{ $tag->name }}». يمكنك استرداده لاحقاً."
                                              data-confirm-title="حذف التصنيف" data-confirm-ok="حذف">
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
                            <td colspan="4" class="px-4 py-14 text-center">
                                <p class="text-ink-600 font-medium">لا توجد تصنيفات بعد</p>
                                <p class="text-sm text-ink-400 mt-1">أضف تصنيفاً من زر «إضافة تصنيف».</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <p class="text-xs text-ink-400 mt-3">
        لا يمكن حذف تصنيف مرتبط بمقالات، حتى لا تفقد تلك المقالات تصنيفها. أزِل التصنيف من مقالاته أولاً ثم احذفه.
    </p>

    {{-- ─────────────────── Add a category ─────────────────── --}}
    <div id="modal-add-category" data-modal
         class="{{ $addHasErrors ? '' : 'hidden' }} fixed inset-0 z-50 flex items-center justify-center p-4"
         role="dialog" aria-modal="true" aria-labelledby="add-category-title">
        <div class="absolute inset-0 bg-ink-900/40" data-modal-close></div>

        <div class="relative w-full max-w-md rounded-2xl bg-white shadow-xl">
            <div class="flex items-center justify-between px-5 py-4 border-b border-ink-100">
                <h2 id="add-category-title" class="font-bold text-ink-900">إضافة تصنيف</h2>
                <button type="button" data-modal-close aria-label="إغلاق"
                        class="p-1 text-ink-400 hover:text-ink-700 transition-colors">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                </button>
            </div>

            {{-- Name only. The id and the slug are generated by the backend. --}}
            <form method="POST" action="{{ route('admin.categories.store') }}" class="p-5 space-y-4">
                @csrf

                <div>
                    <label for="category-name" class="block text-sm font-medium text-ink-700 mb-1.5">
                        اسم التصنيف <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="category-name" name="name" value="{{ old('name') }}" required maxlength="50"
                           placeholder="مثال: الابتكار والبحث العلمي" autocomplete="off"
                           class="h-11 w-full rounded-xl border border-ink-200 px-4 text-sm focus:outline-none focus:ring-2 focus:ring-brand-300 focus:border-brand-300 @error('name') border-red-300 @enderror">
                    @error('name')
                        <p class="text-xs text-red-600 mt-1.5">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center gap-2 pt-1">
                    <button type="submit"
                            class="h-11 flex-1 rounded-xl bg-brand-600 text-white text-sm font-semibold hover:bg-brand-700 transition-colors">
                        إضافة التصنيف
                    </button>
                    <button type="button" data-modal-close
                            class="h-11 rounded-xl border border-ink-200 px-5 text-sm font-medium text-ink-600 hover:border-ink-300 transition-colors">
                        إلغاء
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ───────────── Restore removed categories ───────────── --}}
    @if ($restorable > 0)
        <div id="modal-restore-categories" data-modal
             class="hidden fixed inset-0 z-50 flex items-center justify-center p-4"
             role="dialog" aria-modal="true" aria-labelledby="restore-categories-title">
            <div class="absolute inset-0 bg-ink-900/40" data-modal-close></div>

            <div class="relative w-full max-w-lg rounded-2xl bg-white shadow-xl flex flex-col max-h-[85vh]">
                <div class="flex items-center justify-between px-5 py-4 border-b border-ink-100">
                    <h2 id="restore-categories-title" class="font-bold text-ink-900">استرداد التصنيفات المحذوفة</h2>
                    <button type="button" data-modal-close aria-label="إغلاق"
                            class="p-1 text-ink-400 hover:text-ink-700 transition-colors">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                {{-- Only the ticked entries are restored; the rest stay removed. --}}
                <form method="POST" action="{{ route('admin.categories.restore') }}" class="flex flex-col min-h-0" data-restore-form>
                    @csrf

                    <div class="px-5 py-3 overflow-y-auto">
                        <p class="text-xs text-ink-400 mb-3">اختر التصنيفات التي تريد إعادتها. لن يُسترد أي تصنيف لم تحدّده.</p>

                        @foreach ($removed as $tag)
                            <label class="flex items-center gap-3 py-2.5 border-b border-ink-50 last:border-0 cursor-pointer">
                                <input type="checkbox" name="ids[]" value="{{ $tag->id }}"
                                       class="h-4 w-4 shrink-0 rounded border-ink-300 text-brand-600 focus:ring-brand-300">
                                <span class="flex-1 min-w-0 text-sm text-ink-800 truncate">{{ $tag->name }}</span>
                                @if ($tag->posts_count > 0)
                                    <span class="shrink-0 text-[11px] text-ink-400">{{ number_format($tag->posts_count) }} مقال</span>
                                @endif
                            </label>
                        @endforeach

                        @foreach ($missingDefaults as $slug => $name)
                            <label class="flex items-center gap-3 py-2.5 border-b border-ink-50 last:border-0 cursor-pointer">
                                <input type="checkbox" name="slugs[]" value="{{ $slug }}"
                                       class="h-4 w-4 shrink-0 rounded border-ink-300 text-brand-600 focus:ring-brand-300">
                                <span class="flex-1 min-w-0 text-sm text-ink-800 truncate">{{ $name }}</span>
                                <span class="shrink-0 text-[11px] text-ink-400">افتراضي</span>
                            </label>
                        @endforeach
                    </div>

                    <div class="px-5 py-4 border-t border-ink-100 bg-ink-25 rounded-b-2xl">
                        <p data-restore-error class="hidden text-xs text-red-600 mb-2">يرجى اختيار تصنيف واحد على الأقل.</p>
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
    @endif

    <script>
        // Small, dependency-free dialog behaviour, matching the confirm dialog
        // already used in the admin layout.
        (function () {
            function setOpen(el, open) {
                if (!el) return;
                el.classList.toggle('hidden', !open);
                document.body.classList.toggle('overflow-hidden', open);
                if (open) {
                    var field = el.querySelector('input[type="text"]');
                    if (field) field.focus();
                }
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

            // Client-side courtesy only — the server refuses an empty selection too.
            var restoreForm = document.querySelector('[data-restore-form]');
            if (restoreForm) {
                restoreForm.addEventListener('submit', function (e) {
                    var chosen = restoreForm.querySelectorAll('input[type="checkbox"]:checked').length;
                    if (chosen === 0) {
                        e.preventDefault();
                        var msg = restoreForm.querySelector('[data-restore-error]');
                        if (msg) msg.classList.remove('hidden');
                    }
                });
            }

            // Keep the add dialog open on the page it came back to when the
            // server rejected the name.
            @if ($addHasErrors)
                document.body.classList.add('overflow-hidden');
            @endif
        })();
    </script>
@endsection
