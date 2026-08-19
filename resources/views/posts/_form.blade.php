@csrf
@if (isset($post))
    @method('PUT')
@endif

@if ($errors->any())
    <div class="mb-6">
        <x-alert type="error">
            <p class="font-medium mb-1">يرجى تصحيح الأخطاء التالية:</p>
            <ul class="list-disc ps-5 space-y-0.5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </x-alert>
    </div>
@endif

<div class="space-y-6 bg-white border border-ink-100 rounded-2xl p-6 shadow-sm">
    <div>
        <label for="title" class="block text-sm font-medium text-ink-700 mb-1.5">عنوان المقال</label>
        <input type="text" id="title" name="title" value="{{ old('title', $post->title ?? '') }}" required
            class="w-full rounded-xl border border-ink-200 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-300 focus:border-brand-300 @error('title') border-red-300 @enderror">
        @error('title')
            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="content" class="block text-sm font-medium text-ink-700 mb-1.5">المحتوى</label>
        <textarea id="content" name="content" rows="12" required
            class="w-full rounded-xl border border-ink-200 px-4 py-2.5 text-sm leading-relaxed focus:outline-none focus:ring-2 focus:ring-brand-300 focus:border-brand-300 @error('content') border-red-300 @enderror"
        >{{ old('content', $post->content ?? '') }}</textarea>
        @error('content')
            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="image" class="block text-sm font-medium text-ink-700 mb-1.5">صورة الغلاف</label>
        @if (isset($post) && $post->image)
            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($post->image) }}" alt="" class="w-40 rounded-xl mb-3 aspect-video object-cover border border-ink-100">
        @endif
        <input type="file" id="image" name="image" accept="image/png,image/jpeg,image/webp" data-max-mb="5"
            class="w-full text-sm text-ink-500 file:me-4 file:rounded-lg file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-brand-700 hover:file:bg-brand-100 file:cursor-pointer">
        <p class="text-xs text-ink-400 mt-1.5">JPEG أو PNG أو WEBP، بحد أقصى 5 ميجابايت.</p>
        <p id="image-size-error" class="text-xs text-red-600 mt-1 hidden"></p>
        @error('image')
            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="published_at" class="block text-sm font-medium text-ink-700 mb-1.5">تاريخ النشر</label>
        <input type="date" id="published_at" name="published_at"
            value="{{ old('published_at', isset($post) && $post->published_at ? \Illuminate\Support\Carbon::parse($post->published_at)->format('Y-m-d') : '') }}"
            class="w-full sm:w-60 rounded-xl border border-ink-200 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-300 focus:border-brand-300">
        <p class="text-xs text-ink-400 mt-1.5">اتركه فارغاً لنشر المقال فوراً.</p>
        @error('published_at')
            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <span class="block text-sm font-medium text-ink-700 mb-2">التصنيفات <span class="text-ink-400 font-normal">(اختر من القائمة — اختياري)</span></span>
        @php
            $categoryOptions = \App\Models\Tag::categories();
            $selectedTags = old('tags', isset($post) ? $post->tags->pluck('slug')->all() : []);
            // Preserve any legacy tags already on this post that aren't in the
            // canonical list, so editing an old article never silently drops them.
            if (isset($post)) {
                foreach ($post->tags as $existingTag) {
                    if (! array_key_exists($existingTag->slug, $categoryOptions)) {
                        $categoryOptions[$existingTag->slug] = $existingTag->name;
                    }
                }
            }
        @endphp
        <div class="flex flex-wrap gap-2">
            @foreach ($categoryOptions as $slug => $name)
                <label class="cursor-pointer">
                    <input type="checkbox" name="tags[]" value="{{ $slug }}" class="peer sr-only" @checked(in_array($slug, (array) $selectedTags, true))>
                    <span class="inline-flex items-center rounded-full border border-ink-200 bg-white px-3 py-1.5 text-xs font-medium text-ink-600 transition-colors hover:border-brand-300 peer-checked:bg-brand-600 peer-checked:text-white peer-checked:border-brand-600 peer-focus-visible:ring-2 peer-focus-visible:ring-brand-300">
                        {{ $name }}
                    </span>
                </label>
            @endforeach
        </div>
        @error('tags')
            <p class="text-xs text-red-600 mt-1.5">{{ $message }}</p>
        @enderror
        @error('tags.*')
            <p class="text-xs text-red-600 mt-1.5">{{ $message }}</p>
        @enderror
    </div>
</div>

<div class="flex items-center gap-3 mt-6">
    <button type="submit" class="inline-flex items-center rounded-xl bg-brand-600 text-white px-6 py-2.5 text-sm font-semibold hover:bg-brand-700 transition-colors">
        {{ isset($post) ? 'حفظ التعديلات' : 'نشر المقال' }}
    </button>
    <a href="{{ isset($post) ? route('posts.show', $post) : route('dashboard') }}" class="text-sm text-ink-500 hover:text-ink-700">إلغاء</a>
</div>

<script>
    // Block oversize images in the browser so the upload never hits PHP's
    // post_max_size (which would otherwise return a raw 413 error page).
    (function () {
        var input = document.getElementById('image');
        var err = document.getElementById('image-size-error');
        if (!input) return;
        var form = input.closest('form');
        var maxMb = parseFloat(input.getAttribute('data-max-mb')) || 5;
        var maxBytes = maxMb * 1024 * 1024;

        function tooBig() {
            return input.files && input.files[0] && input.files[0].size > maxBytes;
        }
        function showError(show) {
            if (!err) return;
            err.textContent = show ? ('حجم الصورة يتجاوز ' + maxMb + ' ميجابايت. يرجى اختيار صورة أصغر.') : '';
            err.classList.toggle('hidden', !show);
        }

        input.addEventListener('change', function () { showError(tooBig()); });
        if (form) {
            form.addEventListener('submit', function (e) {
                if (tooBig()) { e.preventDefault(); showError(true); input.scrollIntoView({ block: 'center' }); }
            });
        }
    })();
</script>
