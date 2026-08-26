@extends('layouts.admin')

@section('title', 'إدارة المستخدمين')

@section('content')
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-5">
        <div>
            <h1 class="text-xl font-bold text-ink-900">المستخدمون</h1>
            <p class="text-sm text-ink-500 mt-0.5">
                {{ number_format($counts['all']) }} مستخدم ·
                {{ number_format($counts['admins']) }} مشرف ·
                {{ number_format($counts['active']) }} نشط
            </p>
        </div>

        <form method="GET" action="{{ route('admin.users.index') }}" class="flex gap-2 shrink-0">
            <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="الاسم أو البريد…"
                   class="h-11 rounded-xl border border-ink-200 px-4 text-sm focus:outline-none focus:ring-2 focus:ring-brand-300">
            <button type="submit" class="h-11 rounded-xl bg-brand-600 text-white px-5 text-sm font-semibold hover:bg-brand-700 transition-colors">بحث</button>
        </form>
    </div>

    {{-- المشرفون أولاً --}}
    <section class="mb-8">
        <div class="flex items-center gap-2.5 mb-3">
            <h2 class="text-base font-bold text-ink-900">المشرفون</h2>
            <span class="inline-flex items-center justify-center rounded-full bg-brand-50 text-brand-700 ring-1 ring-brand-200 px-2.5 py-0.5 text-xs font-semibold tabular-nums">{{ number_format($admins->count()) }}</span>
        </div>

        @include('admin.users._table', ['rows' => $admins, 'emptyText' => 'لا يوجد مشرفون مطابقون.'])
    </section>

    {{-- ثم المستخدمون العاديون --}}
    <section>
        <div class="flex items-center gap-2.5 mb-3">
            <h2 class="text-base font-bold text-ink-900">المستخدمون</h2>
            <span class="inline-flex items-center justify-center rounded-full bg-ink-50 text-ink-600 ring-1 ring-ink-200 px-2.5 py-0.5 text-xs font-semibold tabular-nums">{{ number_format($members->total()) }}</span>
        </div>

        @include('admin.users._table', ['rows' => $members, 'emptyText' => 'لا يوجد مستخدمون مطابقون.'])

        @if ($members->hasPages())
            <div class="mt-5">{{ $members->links() }}</div>
        @endif
    </section>
@endsection
