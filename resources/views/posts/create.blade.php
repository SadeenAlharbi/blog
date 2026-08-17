@extends('layouts.app')

@section('title', 'مقال جديد — منصة المعرفة السعودية')

@section('content')
    <section class="max-w-2xl mx-auto px-4 sm:px-6 py-10">
        <h1 class="text-2xl font-bold text-ink-900 mb-8">إنشاء مقال جديد</h1>

        <form method="POST" action="{{ route('posts.store') }}" enctype="multipart/form-data">
            @include('posts._form', ['tags' => $tags])
        </form>
    </section>
@endsection
