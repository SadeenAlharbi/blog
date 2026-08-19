@extends('layouts.app')

@section('title', 'الصفحة الشخصية — منصة المعرفة السعودية')

@php
    $user = auth()->user();
    $postsTotal = $posts->total();
    $commentsTotal = \App\Models\Comment::whereIn('post_id', $user->posts()->select('id'))->count();
@endphp

@section('content')
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <x-breadcrumbs class="mb-4" :items="[
            ['label' => 'الرئيسية', 'href' => route('home')],
            ['label' => 'الصفحة الشخصية'],
        ]" />

        <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 mb-8">
            <div>
                <h1 class="text-2xl sm:text-3xl font-bold text-ink-900">الصفحة الشخصية</h1>
                <p class="text-sm text-ink-500 mt-1.5">مرحباً {{ $user->name }} — أدر مقالاتك وتعليقاتك على المنصة.</p>
            </div>
            <a href="{{ route('posts.create') }}" class="shrink-0 inline-flex items-center gap-1.5 rounded-xl bg-brand-600 text-white px-5 py-2.5 text-sm font-semibold hover:bg-brand-700 transition-colors">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                مقال جديد
            </a>
        </div>

        @include('dashboard._tabs')

        {{-- Overview --}}
        <div class="grid gap-4 sm:grid-cols-3 mb-8">
            <div class="rounded-2xl border border-ink-100 bg-white p-5">
                <p class="text-sm text-ink-500">مقالاتك</p>
                <p class="text-3xl font-extrabold text-ink-900 mt-1">{{ $postsTotal }}</p>
            </div>
            <div class="rounded-2xl border border-ink-100 bg-white p-5">
                <p class="text-sm text-ink-500">التعليقات على مقالاتك</p>
                <p class="text-3xl font-extrabold text-ink-900 mt-1">{{ $commentsTotal }}</p>
            </div>
            <div class="rounded-2xl border border-brand-100 bg-brand-50/50 p-5 flex items-center justify-between">
                <div>
                    <p class="text-sm text-brand-700">ابدأ الكتابة</p>
                    <p class="text-xs text-ink-500 mt-1">شارك مقالاً جديداً مع القرّاء.</p>
                </div>
                <a href="{{ route('posts.create') }}" class="text-sm font-semibold text-brand-700 hover:text-brand-800">نشر ←</a>
            </div>
        </div>

        @if ($posts->isEmpty())
            <x-empty-state title="لم تنشر أي مقال بعد.">
                <a href="{{ route('posts.create') }}" class="text-brand-600 font-semibold hover:text-brand-700">ابدأ بنشر أول مقال ←</a>
            </x-empty-state>
        @else
            <div class="overflow-x-auto rounded-2xl border border-ink-100 bg-white">
                <table class="w-full text-sm text-start min-w-[640px]">
                    <thead>
                        <tr class="border-b border-ink-100 bg-ink-25 text-ink-400 text-xs">
                            <th class="px-5 py-3 font-medium text-start">العنوان</th>
                            <th class="px-5 py-3 font-medium text-start">التصنيفات</th>
                            <th class="px-5 py-3 font-medium text-start">التعليقات</th>
                            <th class="px-5 py-3 font-medium text-start">تاريخ النشر</th>
                            <th class="px-5 py-3 font-medium text-start">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($posts as $post)
                            <tr class="border-b border-ink-50 last:border-0 hover:bg-ink-25 transition-colors">
                                <td class="px-5 py-4">
                                    <a href="{{ route('posts.show', $post) }}" class="font-medium text-ink-800 hover:text-brand-600">{{ \Illuminate\Support\Str::limit($post->title, 50) }}</a>
                                </td>
                                <td class="px-5 py-4 text-ink-500">{{ $post->tags_count }}</td>
                                <td class="px-5 py-4 text-ink-500">{{ $post->comments_count }}</td>
                                <td class="px-5 py-4 text-ink-500 whitespace-nowrap">{{ optional($post->published_at)->format('Y/m/d') }}</td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <a href="{{ route('posts.edit', $post) }}" class="text-brand-600 hover:text-brand-700 font-medium">تعديل</a>
                                        <form method="POST" action="{{ route('posts.destroy', $post) }}" onsubmit="return confirm('هل أنت متأكد من حذف هذا المقال؟');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-500 hover:text-red-700 font-medium">حذف</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-6">{{ $posts->links() }}</div>
        @endif
    </section>
@endsection
