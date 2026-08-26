<?php

namespace App\Http\Requests;

use App\Models\Post;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePostRequest extends FormRequest
{
    /** Ownership is enforced by PostPolicy in the controller. */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $post = $this->route('post');
        $postId = is_object($post) ? $post->id : null;

        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'alpha_dash', Rule::unique('posts', 'slug')->ignore($postId)],
            'content' => ['sometimes', 'required', 'string'],
            'image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'status' => ['nullable', 'string', Rule::in(array_keys(Post::statuses()))],
            'published_at' => ['nullable', 'date'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'عنوان المقال مطلوب.',
            'title.max' => 'عنوان المقال طويل جداً (بحد أقصى 255 حرفاً).',
            'slug.alpha_dash' => 'الرابط المختصر يجب أن يحتوي على حروف وأرقام وشرطات فقط.',
            'slug.unique' => 'هذا الرابط المختصر مستخدم في مقال آخر.',
            'content.required' => 'محتوى المقال مطلوب.',
            'image.image' => 'الملف المرفوع يجب أن يكون صورة.',
            'image.mimes' => 'صيغة الصورة يجب أن تكون JPEG أو PNG أو WEBP.',
            'image.max' => 'حجم الصورة يجب ألا يتجاوز 5 ميجابايت.',
            'status.in' => 'حالة النشر غير صالحة.',
            'published_at.date' => 'تاريخ النشر غير صالح.',
            'tags.array' => 'صيغة التصنيفات غير صحيحة.',
            'tags.*.string' => 'أحد التصنيفات غير صالح.',
        ];
    }

    /** Same normalization as StorePostRequest — see the note there. */
    protected function prepareForValidation(): void
    {
        $merge = [];

        if ($this->has('tags') || $this->filled('tags_input')) {
            $tags = $this->input('tags', []);

            if (! is_array($tags)) {
                $tags = [];
            }

            if ($tags === [] && $this->filled('tags_input')) {
                $tags = explode(',', (string) $this->string('tags_input'));
            }

            $merge['tags'] = collect($tags)
                ->map(fn ($tag) => is_scalar($tag) ? trim((string) $tag) : null)
                ->filter(fn ($tag) => $tag !== null && $tag !== '')
                ->unique()
                ->values()
                ->all();
        }

        if ($this->has('slug') && blank($this->input('slug'))) {
            $merge['slug'] = null;
        }

        if ($merge !== []) {
            $this->merge($merge);
        }
    }
}
