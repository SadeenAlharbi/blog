@extends('layouts.app')

@section('title', 'مقال جديد — منصة المعرفة السعودية')

@section('content')
    <section class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <x-breadcrumbs class="mb-4" :items="[
            ['label' => 'الرئيسية', 'href' => route('home')],
            ['label' => 'الصفحة الشخصية', 'href' => route('dashboard')],
            ['label' => 'مقال جديد'],
        ]" />

        <div class="mb-8">
            <h1 class="text-2xl sm:text-3xl font-bold text-ink-900">نشر مقال جديد</h1>
            <p class="text-sm text-ink-500 mt-1.5">اكتب مقالاً موثّقاً وشاركه مع قرّاء المنصة.</p>
        </div>

        <form method="POST" action="{{ route('posts.store') }}" enctype="multipart/form-data">
            @include('posts._form')
        </form>
    </section>
@endsection
