@extends('layouts.app')

@section('title', 'لوحة التحكم — منصة المعرفة السعودية')

@section('content')
    <section class="max-w-6xl mx-auto px-4 sm:px-6 py-10">
        <div class="flex items-end justify-between mb-8">
            <div>
                <h1 class="text-2xl font-bold text-ink-900">لوحة التحكم</h1>
                <p class="text-sm text-ink-500 mt-1">إدارة مقالاتك المنشورة على المنصة.</p>
            </div>
            <a href="{{ route('posts.create') }}" class="inline-flex items-center rounded-lg bg-brand-600 text-white px-5 py-2.5 text-sm font-semibold hover:bg-brand-700 transition-colors">
                + مقال جديد
            </a>
        </div>

        @if ($posts->isEmpty())
            <div class="rounded-2xl border border-dashed border-ink-200 py-16 text-center">
                <p class="text-ink-400 text-sm mb-4">لم تنشر أي مقال بعد.</p>
                <a href="{{ route('posts.create') }}" class="text-brand-600 font-semibold hover:text-brand-700">ابدأ بنشر أول مقال ←</a>
            </div>
        @else
            <div class="overflow-x-auto rounded-2xl border border-ink-100 bg-white">
                <table class="w-full text-sm text-start">
                    <thead>
                        <tr class="border-b border-ink-100 text-ink-400 text-xs uppercase tracking-wide">
                            <th class="px-5 py-3 font-medium text-start">العنوان</th>
                            <th class="px-5 py-3 font-medium text-start">التصنيفات</th>
                            <th class="px-5 py-3 font-medium text-start">التعليقات</th>
                            <th class="px-5 py-3 font-medium text-start">تاريخ النشر</th>
                            <th class="px-5 py-3 font-medium text-start">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($posts as $post)
                            <tr class="border-b border-ink-50 last:border-0 hover:bg-ink-25">
                                <td class="px-5 py-4">
                                    <a href="{{ route('posts.show', $post) }}" class="font-medium text-ink-800 hover:text-brand-600">
                                        {{ \Illuminate\Support\Str::limit($post->title, 50) }}
                                    </a>
                                </td>
                                <td class="px-5 py-4 text-ink-500">{{ $post->tags_count }}</td>
                                <td class="px-5 py-4 text-ink-500">{{ $post->comments_count }}</td>
                                <td class="px-5 py-4 text-ink-500">{{ optional($post->published_at)->format('Y/m/d') }}</td>
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
