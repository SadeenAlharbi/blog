@extends('layouts.admin')

@section('title', $user->name)

@section('content')
    <div class="mb-5">
        <a href="{{ route('admin.users.index') }}" class="text-xs text-ink-400 hover:text-brand-700">← العودة إلى المستخدمين</a>
    </div>

    <div class="rounded-2xl border border-ink-100 bg-white p-5 mb-5 flex flex-col sm:flex-row sm:items-center gap-4">
        <x-avatar :name="$user->name" :size="56" />
        <div class="min-w-0 flex-1">
            <h1 class="text-lg font-bold text-ink-900">{{ $user->name }}</h1>
            <p class="text-sm text-ink-500" dir="ltr">{{ $user->email }}</p>
            <p class="text-xs text-ink-400 mt-1">انضم في {{ $user->created_at->format('Y/m/d') }}</p>
        </div>
        <div class="flex items-center gap-2 shrink-0">
            @if ($user->isAdmin())
                <span class="inline-flex rounded-full bg-brand-50 text-brand-700 ring-1 ring-brand-200 px-3 py-1 text-xs font-semibold">مشرف</span>
            @else
                <span class="inline-flex rounded-full bg-ink-50 text-ink-600 ring-1 ring-ink-200 px-3 py-1 text-xs font-semibold">كاتب</span>
            @endif
            @if ($user->is_active)
                <x-status-badge status="published" label="نشط" />
            @else
                <x-status-badge status="hidden" label="معطّل" />
            @endif
        </div>
    </div>

    <div class="grid gap-4 grid-cols-2 sm:grid-cols-3 mb-5">
        <div class="rounded-2xl border border-ink-100 bg-white p-4">
            <p class="text-2xl font-extrabold text-ink-900 tabular-nums">{{ number_format($user->posts_count) }}</p>
            <p class="text-xs text-ink-500 mt-1">مقال</p>
        </div>
        <div class="rounded-2xl border border-ink-100 bg-white p-4">
            <p class="text-2xl font-extrabold text-ink-900 tabular-nums">{{ number_format($user->comments_count) }}</p>
            <p class="text-xs text-ink-500 mt-1">تعليق</p>
        </div>
        <div class="rounded-2xl border border-ink-100 bg-white p-4">
            <p class="text-2xl font-extrabold text-ink-900 tabular-nums">{{ number_format($posts->total()) }}</p>
            <p class="text-xs text-ink-500 mt-1">مقالات في القائمة</p>
        </div>
    </div>

    <div class="rounded-2xl border border-ink-100 bg-white overflow-hidden">
        <div class="px-5 py-4 border-b border-ink-100">
            <h2 class="font-bold text-ink-900">مقالات {{ $user->name }}</h2>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm min-w-[600px]">
                <thead>
                    <tr class="bg-ink-25 text-ink-400 text-xs">
                        <th class="px-4 py-2.5 font-medium text-start">العنوان</th>
                        <th class="px-4 py-2.5 font-medium text-start">الحالة</th>
                        <th class="px-4 py-2.5 font-medium text-start">المشاهدات</th>
                        <th class="px-4 py-2.5 font-medium text-start">التعليقات</th>
                        <th class="px-4 py-2.5 font-medium text-start"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($posts as $post)
                        <tr class="border-b border-ink-50 last:border-0 hover:bg-ink-25 transition-colors">
                            <td class="px-4 py-3 font-medium text-ink-800 truncate max-w-[280px]">{{ $post->title }}</td>
                            <td class="px-4 py-3"><x-status-badge :status="$post->status" :label="$post->statusLabel()" /></td>
                            <td class="px-4 py-3 text-ink-500 tabular-nums">{{ number_format($post->views_count) }}</td>
                            <td class="px-4 py-3 text-ink-500 tabular-nums">{{ number_format($post->comments_count) }}</td>
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.posts.edit', $post) }}" class="text-xs font-medium text-brand-600 hover:text-brand-700">تعديل</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-12 text-center text-sm text-ink-400">لم ينشر هذا المستخدم أي مقال بعد.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($posts->hasPages())
        <div class="mt-5">{{ $posts->links() }}</div>
    @endif
@endsection
