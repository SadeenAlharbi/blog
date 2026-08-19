@extends('layouts.app')

@section('title', 'تعليقاتي — منصة المعرفة السعودية')

@section('content')
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <x-breadcrumbs class="mb-4" :items="[
            ['label' => 'الرئيسية', 'href' => route('home')],
            ['label' => 'الصفحة الشخصية', 'href' => route('dashboard')],
            ['label' => 'تعليقاتي'],
        ]" />

        <div class="mb-8">
            <h1 class="text-2xl sm:text-3xl font-bold text-ink-900">الصفحة الشخصية</h1>
            <p class="text-sm text-ink-500 mt-1.5">جميع التعليقات التي كتبتها على المنصة.</p>
        </div>

        @include('dashboard._tabs')

        @if ($comments->isEmpty())
            <x-empty-state title="لم تكتب أي تعليق بعد.">
                <a href="{{ route('posts.index') }}" class="text-brand-600 font-semibold hover:text-brand-700">تصفّح المقالات وشارك برأيك ←</a>
            </x-empty-state>
        @else
            <div class="space-y-4">
                @foreach ($comments as $comment)
                    <div class="rounded-2xl border border-ink-100 bg-white p-5">
                        @if ($comment->post)
                            <a href="{{ route('posts.show', $comment->post) }}" class="flex items-center gap-3 mb-3 group">
                                <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-brand-100 to-sand-100 overflow-hidden">
                                    @if ($comment->post->image)
                                        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($comment->post->image) }}" alt="" class="w-full h-full object-cover">
                                    @else
                                        <span class="text-brand-600/40 font-bold">م</span>
                                    @endif
                                </span>
                                <span class="min-w-0">
                                    <span class="block text-xs text-ink-400">تعليقك على مقال:</span>
                                    <span class="block text-sm font-semibold text-ink-800 group-hover:text-brand-600 transition-colors truncate">{{ $comment->post->title }}</span>
                                </span>
                            </a>
                        @else
                            <p class="text-xs text-ink-400 mb-3">المقال المرتبط بهذا التعليق لم يعد متاحاً.</p>
                        @endif

                        <p class="text-sm text-ink-600 leading-relaxed bg-ink-25 rounded-xl px-4 py-3">{{ $comment->content }}</p>

                        <div class="flex items-center justify-between mt-3">
                            <span class="text-xs text-ink-400">{{ $comment->created_at->format('Y/m/d H:i') }}</span>
                            <form method="POST" action="{{ route('comments.destroy', $comment) }}"
                                  onsubmit="return confirm('هل أنت متأكد أنك تريد حذف هذا التعليق؟');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="inline-flex items-center gap-1 text-xs font-medium text-red-500 hover:text-red-700 transition-colors">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                                    حذف
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-6">{{ $comments->links() }}</div>
        @endif
    </section>
@endsection
