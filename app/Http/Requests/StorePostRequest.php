<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'published_at' => ['nullable', 'date'],
            // Categories are optional. Values are category slugs from the fixed list.
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'عنوان المقال مطلوب.',
            'title.max' => 'عنوان المقال طويل جداً (بحد أقصى 255 حرفاً).',
            'content.required' => 'محتوى المقال مطلوب.',
            'image.image' => 'الملف المرفوع يجب أن يكون صورة.',
            'image.mimes' => 'صيغة الصورة يجب أن تكون JPEG أو PNG أو WEBP.',
            'image.max' => 'حجم الصورة يجب ألا يتجاوز 5 ميجابايت.',
            'published_at.date' => 'تاريخ النشر غير صالح.',
            'tags.array' => 'صيغة التصنيفات غير صحيحة.',
            'tags.*.string' => 'أحد التصنيفات غير صالح.',
        ];
    }

    /**
     * Normalize `tags` into a clean list of non-empty strings BEFORE validation,
     * regardless of how they arrive (category-checkbox slugs, a legacy comma
     * `tags_input`, or malformed values). This guarantees the backend never
     * sees null / objects / empty items — so "tags.0 must be a string" can't
     * happen — and an empty selection becomes a valid empty array.
     */
    protected function prepareForValidation(): void
    {
        $tags = $this->input('tags', []);

        if (! is_array($tags)) {
            $tags = [];
        }

        // Backward-compatible: support the old free-text field if it's ever sent.
        if ($tags === [] && $this->filled('tags_input')) {
            $tags = explode(',', (string) $this->string('tags_input'));
        }

        $tags = collect($tags)
            ->map(fn ($tag) => is_scalar($tag) ? trim((string) $tag) : null)
            ->filter(fn ($tag) => $tag !== null && $tag !== '')
            ->unique()
            ->values()
            ->all();

        $this->merge(['tags' => $tags]);
    }
}
