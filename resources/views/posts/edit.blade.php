@extends('layouts.app')

@section('title', 'تعديل: '.$post->title.' — منصة المعرفة السعودية')

@section('content')
    <section class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <x-breadcrumbs class="mb-4" :items="[
            ['label' => 'الرئيسية', 'href' => route('home')],
            ['label' => 'الصفحة الشخصية', 'href' => route('dashboard')],
            ['label' => 'تعديل المقال'],
        ]" />

        <div class="mb-8">
            <h1 class="text-2xl sm:text-3xl font-bold text-ink-900">تعديل المقال</h1>
            <p class="text-sm text-ink-500 mt-1.5">حدّث محتوى مقالك وتصنيفاته وصورة الغلاف.</p>
        </div>

        <form method="POST" action="{{ route('posts.update', $post) }}" enctype="multipart/form-data">
            @include('posts._form', ['post' => $post])
        </form>
    </section>
@endsection
