@csrf
@if (isset($post))
    @method('PUT')
@endif

<div class="space-y-6 bg-white border border-ink-100 rounded-2xl p-6">
    <div>
        <label for="title" class="block text-sm font-medium text-ink-700 mb-1.5">عنوان المقال</label>
        <input type="text" id="title" name="title" value="{{ old('title', $post->title ?? '') }}" required
            class="w-full rounded-lg border border-ink-200 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-300">
        @error('title')
            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="content" class="block text-sm font-medium text-ink-700 mb-1.5">المحتوى</label>
        <textarea id="content" name="content" rows="10" required
            class="w-full rounded-lg border border-ink-200 px-4 py-2.5 text-sm leading-relaxed focus:outline-none focus:ring-2 focus:ring-brand-300"
        >{{ old('content', $post->content ?? '') }}</textarea>
        @error('content')
            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="image" class="block text-sm font-medium text-ink-700 mb-1.5">صورة الغلاف</label>
        @if (isset($post) && $post->image)
            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($post->image) }}" alt="" class="w-40 rounded-lg mb-3 aspect-video object-cover">
        @endif
        <input type="file" id="image" name="image" accept="image/png,image/jpeg,image/webp"
            class="w-full text-sm text-ink-500 file:me-4 file:rounded-lg file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-brand-700 hover:file:bg-brand-100">
        <p class="text-xs text-ink-400 mt-1">JPEG أو PNG أو WEBP، بحد أقصى 4 ميجابايت.</p>
        @error('image')
            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="published_at" class="block text-sm font-medium text-ink-700 mb-1.5">تاريخ النشر</label>
        <input type="date" id="published_at" name="published_at"
            value="{{ old('published_at', isset($post) && $post->published_at ? $post->published_at->format('Y-m-d') : '') }}"
            class="w-full sm:w-60 rounded-lg border border-ink-200 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-300">
        @error('published_at')
            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="tags_input" class="block text-sm font-medium text-ink-700 mb-1.5">التصنيفات (مفصولة بفاصلة)</label>
        <input type="text" id="tags_input" name="tags_input"
            value="{{ old('tags_input', isset($post) ? $post->tags->pluck('name')->implode(', ') : '') }}"
            placeholder="رؤية 2030, التقنية, السياحة"
            class="w-full rounded-lg border border-ink-200 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-300">
        @if ($tags->isNotEmpty())
            <div class="flex flex-wrap gap-1.5 mt-2">
                @foreach ($tags as $tag)
                    <dga-tag label="{{ $tag->name }}" variant="neutral" size="sm" rounded="true"></dga-tag>
                @endforeach
            </div>
        @endif
        @error('tags')
            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
        @enderror
    </div>
</div>

<div class="flex items-center gap-3 mt-6">
    <button type="submit" class="inline-flex items-center rounded-lg bg-brand-600 text-white px-6 py-2.5 text-sm font-semibold hover:bg-brand-700 transition-colors">
        {{ isset($post) ? 'حفظ التعديلات' : 'نشر المقال' }}
    </button>
    <a href="{{ isset($post) ? route('posts.show', $post) : route('dashboard') }}" class="text-sm text-ink-500 hover:text-ink-700">
        إلغاء
    </a>
</div>
