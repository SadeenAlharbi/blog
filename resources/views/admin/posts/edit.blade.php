@extends('layouts.admin')

@section('title', 'تعديل مقال')

@section('content')
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-5">
        <div class="min-w-0">
            <a href="{{ route('admin.posts.index') }}" class="text-xs text-ink-400 hover:text-brand-700">← العودة إلى المقالات</a>
            <h1 class="text-xl font-bold text-ink-900 mt-1 truncate">{{ $post->title }}</h1>
        </div>
        <div class="flex items-center gap-2 shrink-0">
            <x-status-badge :status="$post->status" :label="$post->statusLabel()" />
            <a href="{{ route('posts.show', $post) }}" class="rounded-xl border border-ink-200 px-4 py-2 text-sm font-medium text-ink-600 hover:border-brand-300 hover:text-brand-700 transition-colors">عرض المقال</a>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.posts.update', $post) }}" enctype="multipart/form-data">
        @include('admin.posts._form', ['post' => $post])
    </form>
@endsection
