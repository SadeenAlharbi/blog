@csrf
@if (isset($post))
    @method('PUT')
@endif

@php
    $currentStatus = old('status', $post->status ?? \App\Models\Post::STATUS_PUBLISHED);
    $currentDate = old('published_at', isset($post) && $post->published_at
        ? $post->published_at->format('Y-m-d\TH:i')
        : '');
    $selectedTags = old('tags', isset($post) ? $post->tags->pluck('slug')->all() : []);

    $categoryOptions = $categories;
    if (isset($post)) {
        foreach ($post->tags as $existingTag) {
            if (! array_key_exists($existingTag->slug, $categoryOptions)) {
                $categoryOptions[$existingTag->slug] = $existingTag->name;
            }
        }
    }
@endphp

<div class="grid gap-5 lg:grid-cols-3">
    {{-- Main column --}}
    <div class="lg:col-span-2 space-y-5">
        <div class="rounded-2xl border border-ink-100 bg-white p-5 space-y-5">
            <div>
                <label for="title" class="block text-sm font-medium text-ink-700 mb-1.5">عنوان المقال <span class="text-red-500">*</span></label>
                <input type="text" id="title" name="title" value="{{ old('title', $post->title ?? '') }}" required
                       class="w-full rounded-xl border border-ink-200 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-300 focus:border-brand-300 @error('title') border-red-300 @enderror">
                @error('title') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="slug" class="block text-sm font-medium text-ink-700 mb-1.5">
                    الرابط المختصر <span class="text-ink-400 font-normal">(اختياري — يُولَّد تلقائياً)</span>
                </label>
                <input type="text" id="slug" name="slug" value="{{ old('slug', $post->slug ?? '') }}" dir="ltr"
                       class="w-full rounded-xl border border-ink-200 px-4 py-2.5 text-sm text-start focus:outline-none focus:ring-2 focus:ring-brand-300 focus:border-brand-300 @error('slug') border-red-300 @enderror">
                @error('slug') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="content" class="block text-sm font-medium text-ink-700 mb-1.5">المحتوى <span class="text-red-500">*</span></label>
                <textarea id="content" name="content" rows="16" required
                          class="w-full rounded-xl border border-ink-200 px-4 py-2.5 text-sm leading-relaxed focus:outline-none focus:ring-2 focus:ring-brand-300 focus:border-brand-300 @error('content') border-red-300 @enderror">{{ old('content', $post->content ?? '') }}</textarea>
                @error('content') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
        </div>

        {{-- Categories --}}
        <div class="rounded-2xl border border-ink-100 bg-white p-5">
            <span class="block text-sm font-medium text-ink-700 mb-1">التصنيفات</span>
            <p class="text-xs text-ink-400 mb-3">اختر من القائمة المركزية للمنصة — لا يمكن إنشاء تصنيفات جديدة من هنا.</p>
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
            @error('tags') <p class="text-xs text-red-600 mt-1.5">{{ $message }}</p> @enderror
            @error('tags.*') <p class="text-xs text-red-600 mt-1.5">{{ $message }}</p> @enderror
        </div>
    </div>

    {{-- Sidebar column --}}
    <div class="space-y-5">
        {{-- Publishing --}}
        <div class="rounded-2xl border border-ink-100 bg-white p-5">
            <h3 class="text-sm font-bold text-ink-900 mb-4">النشر</h3>

            <div class="space-y-2 mb-4">
                @foreach ($statuses as $value => $label)
                    <label class="flex items-center gap-2.5 rounded-xl border border-ink-200 px-3 py-2.5 cursor-pointer transition-colors hover:border-brand-300 has-[:checked]:border-brand-500 has-[:checked]:bg-brand-50/60">
                        <input type="radio" name="status" value="{{ $value }}" @checked($currentStatus === $value)
                               class="h-4 w-4 accent-[#0b6b45]">
                        <span class="text-sm font-medium text-ink-700">{{ $label }}</span>
                        <x-status-badge :status="$value" :label="$label" class="ms-auto" />
                    </label>
                @endforeach
            </div>

            <div>
                <label for="published_at" class="block text-sm font-medium text-ink-700 mb-1.5">تاريخ ووقت النشر</label>
                <input type="datetime-local" id="published_at" name="published_at" value="{{ $currentDate }}"
                       class="w-full rounded-xl border border-ink-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-300 focus:border-brand-300">
                <p class="text-xs text-ink-400 mt-1.5">للجدولة، اختر «مجدول» وحدّد تاريخاً في المستقبل. اتركه فارغاً للنشر الآن.</p>
                @error('published_at') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
        </div>

        {{-- Cover image --}}
        <div class="rounded-2xl border border-ink-100 bg-white p-5">
            <h3 class="text-sm font-bold text-ink-900 mb-3">صورة الغلاف</h3>

            @if (isset($post) && $post->image)
                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($post->image) }}" alt=""
                     class="w-full rounded-xl mb-3 aspect-video object-cover border border-ink-100">
            @endif

            <input type="file" id="image" name="image" accept="image/png,image/jpeg,image/webp" data-max-mb="5"
                   class="w-full text-sm text-ink-500 file:me-4 file:rounded-lg file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-brand-700 hover:file:bg-brand-100 file:cursor-pointer">
            <p class="text-xs text-ink-400 mt-1.5">JPEG أو PNG أو WEBP، بحد أقصى 5 ميجابايت.</p>
            <p id="image-size-error" class="text-xs text-red-600 mt-1 hidden"></p>
            @error('image') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>

        {{-- Actions --}}
        <div class="rounded-2xl border border-ink-100 bg-white p-5 flex flex-col gap-2">
            <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-brand-600 text-white px-5 py-2.5 text-sm font-semibold hover:bg-brand-700 transition-colors">
                {{ isset($post) ? 'حفظ التعديلات' : 'حفظ المقال' }}
            </button>
            <a href="{{ route('admin.posts.index') }}" class="inline-flex items-center justify-center rounded-xl border border-ink-200 px-5 py-2.5 text-sm font-medium text-ink-600 hover:bg-ink-50 transition-colors">إلغاء</a>
        </div>
    </div>
</div>

<script>
    // Block oversize images client-side so the upload never hits PHP's post_max_size.
    (function () {
        var input = document.getElementById('image');
        var err = document.getElementById('image-size-error');
        if (!input) return;
        var form = input.closest('form');
        var maxMb = parseFloat(input.getAttribute('data-max-mb')) || 5;
        var maxBytes = maxMb * 1024 * 1024;

        function tooBig() { return input.files && input.files[0] && input.files[0].size > maxBytes; }
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
