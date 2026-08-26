@extends('layouts.admin')

@section('title', 'تفاصيل المقال')

@section('content')
    {{-- Way back into the dashboard: a moderator who opened this from the
         article list must never be stranded on the public site. --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-5">
        <a href="{{ route('admin.posts.index') }}"
           class="inline-flex items-center gap-1.5 self-start rounded-xl border border-ink-200 bg-white px-4 py-2 text-sm font-semibold text-ink-700 hover:border-brand-300 hover:text-brand-700 transition-colors">
            <svg class="h-4 w-4 rtl:rotate-180" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" /></svg>
            العودة للوحة التحكم
        </a>

        <div class="flex flex-wrap items-center gap-2">
            @if ($post->trashed())
                <x-status-badge status="hidden" label="محذوف" />
                <form method="POST" action="{{ route('admin.posts.restore', $post->id) }}">
                    @csrf
                    <button type="submit" class="rounded-xl bg-brand-600 text-white px-4 py-2 text-sm font-semibold hover:bg-brand-700 transition-colors">استعادة المقال</button>
                </form>
            @else
                <x-status-badge :status="$post->status" :label="$post->statusLabel()" />
                <a href="{{ route('admin.posts.edit', $post) }}" class="rounded-xl bg-brand-600 text-white px-4 py-2 text-sm font-semibold hover:bg-brand-700 transition-colors">تعديل</a>
                <a href="{{ route('posts.show', $post) }}" target="_blank" rel="noopener"
                   class="rounded-xl border border-ink-200 px-4 py-2 text-sm font-medium text-ink-600 hover:border-brand-300 hover:text-brand-700 transition-colors">
                    عرض في الموقع ↗
                </a>
                <form method="POST" action="{{ route('admin.posts.destroy', $post) }}"
                      data-confirm="سيُخفى المقال عن الموقع ويمكن استعادته لاحقاً، وسيُشعَر كاتبه بذلك."
                      data-confirm-title="حذف المقال" data-confirm-ok="حذف">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="rounded-xl border border-ink-200 px-4 py-2 text-sm font-medium text-red-500 hover:border-red-300 hover:bg-red-50 transition-colors">حذف</button>
                </form>
            @endif
        </div>
    </div>

    <div class="grid gap-5 lg:grid-cols-3">
        {{-- Article --}}
        <div class="lg:col-span-2 space-y-5">
            <article class="rounded-2xl border border-ink-100 bg-white overflow-hidden">
                @if ($post->image)
                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($post->image) }}"
                         alt="{{ $post->title }}" class="w-full aspect-video object-cover">
                @endif

                <div class="p-5 sm:p-6">
                    @if ($post->tags->isNotEmpty())
                        <div class="flex flex-wrap gap-1.5 mb-3">
                            @foreach ($post->tags as $tag)
                                <x-chip variant="brand" size="sm">{{ $tag->name }}</x-chip>
                            @endforeach
                        </div>
                    @endif

                    <h1 class="text-2xl font-bold text-ink-900 leading-snug mb-3">{{ $post->title }}</h1>

                    <div class="flex flex-wrap items-center gap-4 text-xs text-ink-400 pb-4 mb-4 border-b border-ink-100">
                        <span>الكاتب: <span class="text-ink-600 font-medium">{{ $post->user->name ?? '—' }}</span></span>
                        <span>النشر: {{ optional($post->published_at)->format('Y/m/d H:i') ?? '—' }}</span>
                        <span>الإنشاء: {{ $post->created_at->format('Y/m/d') }}</span>
                        <span class="mono" dir="ltr">{{ $post->slug }}</span>
                    </div>

                    <div class="article-content whitespace-pre-line">{{ $post->content }}</div>
                </div>
            </article>

            {{-- Comments --}}
            <div class="rounded-2xl border border-ink-100 bg-white overflow-hidden">
                <div class="flex items-center justify-between gap-3 px-5 py-4 border-b border-ink-100">
                    <h2 class="font-bold text-ink-900">التعليقات ({{ $post->comments->count() }})</h2>
                    <a href="{{ route('admin.comments.index') }}" class="text-xs font-semibold text-brand-600 hover:text-brand-700">إدارة التعليقات</a>
                </div>

                @forelse ($post->comments as $comment)
                    <div class="flex gap-3 px-5 py-4 border-b border-ink-50 last:border-0">
                        <x-avatar :name="$comment->user->name ?? 'م'" :size="34" />
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2 mb-1">
                                <p class="text-sm font-semibold text-ink-800">{{ $comment->user->name ?? 'مستخدم محذوف' }}</p>
                                <x-status-badge :status="$comment->status" :label="$comment->statusLabel()" />
                                <span class="text-xs text-ink-300">{{ $comment->created_at->format('Y/m/d H:i') }}</span>
                            </div>
                            <p class="text-sm text-ink-600 leading-relaxed">{{ $comment->content }}</p>
                        </div>

                        <div class="flex items-center gap-2 shrink-0">
                            @if ($comment->status !== 'hidden')
                                <form method="POST" action="{{ route('admin.comments.hide', $comment) }}"
                                      data-confirm="سيتم إخفاء التعليق عن زوار الموقع وإشعار كاتبه."
                                      data-confirm-title="إخفاء التعليق" data-confirm-ok="إخفاء">
                                    @csrf
                                    <button type="submit" class="text-xs text-ink-500 hover:text-amber-700">إخفاء</button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('admin.comments.approve', $comment) }}">
                                    @csrf
                                    <button type="submit" class="text-xs text-brand-600 hover:text-brand-700">إظهار</button>
                                </form>
                            @endif
                            <form method="POST" action="{{ route('admin.comments.destroy', $comment) }}"
                                  data-confirm="سيتم حذف التعليق وإشعار كاتبه."
                                  data-confirm-title="حذف التعليق" data-confirm-ok="حذف">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-xs text-red-500 hover:text-red-700">حذف</button>
                            </form>
                        </div>
                    </div>
                @empty
                    <p class="px-5 py-10 text-center text-sm text-ink-400">لا توجد تعليقات على هذا المقال.</p>
                @endforelse
            </div>
        </div>

        {{-- Side panel --}}
        <div class="space-y-4">
            <div class="rounded-2xl border border-ink-100 bg-white p-5">
                <h2 class="text-sm font-bold text-ink-900 mb-4">إحصاءات المقال</h2>
                <div class="grid grid-cols-2 gap-3">
                    <div class="rounded-xl bg-ink-25 p-3 text-center">
                        <p class="text-2xl font-extrabold text-ink-900 tabular-nums">{{ number_format($post->views_count) }}</p>
                        <p class="text-xs text-ink-500 mt-0.5">مشاهدة</p>
                    </div>
                    <div class="rounded-xl bg-ink-25 p-3 text-center">
                        <p class="text-2xl font-extrabold text-ink-900 tabular-nums">{{ number_format($post->comments->count()) }}</p>
                        <p class="text-xs text-ink-500 mt-0.5">تعليق</p>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-ink-100 bg-white p-5">
                <h2 class="text-sm font-bold text-ink-900 mb-3">الكاتب</h2>
                <div class="flex items-center gap-3">
                    <x-avatar :name="$post->user->name ?? 'م'" :size="40" />
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-ink-800 truncate">{{ $post->user->name ?? '—' }}</p>
                        <p class="text-xs text-ink-400">{{ $post->user?->roleLabel() ?? '' }}</p>
                    </div>
                </div>
                @if ($post->user)
                    <a href="{{ route('admin.users.show', $post->user) }}"
                       class="mt-3 block text-center rounded-xl border border-ink-200 px-4 py-2 text-xs font-semibold text-ink-600 hover:border-brand-300 hover:text-brand-700 transition-colors">
                        عرض ملف الكاتب
                    </a>
                @endif
            </div>
        </div>
    </div>
@endsection
