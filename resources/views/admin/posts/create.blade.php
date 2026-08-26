@extends('layouts.admin')

@section('title', 'إضافة مقال')

@section('content')
    <div class="mb-5">
        <a href="{{ route('admin.posts.index') }}" class="text-xs text-ink-400 hover:text-brand-700">← العودة إلى المقالات</a>
        <h1 class="text-xl font-bold text-ink-900 mt-1">إضافة مقال جديد</h1>
    </div>

    <form method="POST" action="{{ route('admin.posts.store') }}" enctype="multipart/form-data">
        @include('admin.posts._form')
    </form>
@endsection
