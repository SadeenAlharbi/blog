<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'content' => ['sometimes', 'required', 'string'],
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
     * See StorePostRequest::prepareForValidation — same normalization so edit
     * always sends the backend a clean array of category slugs, and clearing
     * all categories (empty array) is valid.
     */
    protected function prepareForValidation(): void
    {
        $tags = $this->input('tags', []);

        if (! is_array($tags)) {
            $tags = [];
        }

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
