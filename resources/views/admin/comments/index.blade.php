@extends('layouts.admin')

@section('title', 'إدارة التعليقات')

@section('content')
    <div class="mb-5">
        <h1 class="text-xl font-bold text-ink-900">التعليقات</h1>
        <p class="text-sm text-ink-500 mt-0.5">{{ number_format($comments->total()) }} تعليق</p>
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
        @endphp
        @foreach ($tabs as $value => $tab)
            <a href="{{ route('admin.comments.index', array_filter(['status' => $value, 'search' => $filters['search'] ?? null])) }}"
               class="inline-flex items-center gap-1.5 rounded-xl px-3.5 py-2 text-sm font-medium transition-colors {{ $activeStatus === $value ? 'bg-brand-600 text-white' : 'bg-white border border-ink-200 text-ink-600 hover:border-brand-300' }}">
                {{ $tab['label'] }}
                <span class="text-xs tabular-nums {{ $activeStatus === $value ? 'text-white/80' : 'text-ink-400' }}">{{ number_format($tab['count']) }}</span>
            </a>
        @endforeach

        <form method="GET" action="{{ route('admin.comments.index') }}" class="flex-1 min-w-[200px] flex gap-2">
            @if ($activeStatus)
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
                          data-confirm="سيتم حذف التعليق نهائياً. لا يمكن التراجع عن هذا الإجراء."
                          data-confirm-title="حذف التعليق" data-confirm-ok="حذف نهائي">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="rounded-lg border border-ink-200 px-3 py-1.5 text-xs font-medium text-red-500 hover:border-red-300 hover:bg-red-50 transition-colors">حذف</button>
                    </form>
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
@endsection
