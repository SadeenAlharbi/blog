<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Renaming a category.
 *
 * The slug is intentionally NOT editable here: article URLs and saved filters
 * reference it, so changing it would silently break existing links.
 */
class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tag = $this->route('category');
        $id = is_object($tag) ? $tag->id : null;

        return [
            'name' => ['required', 'string', 'max:50', Rule::unique('tags', 'name')->ignore($id)],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'اسم التصنيف مطلوب.',
            'name.max' => 'اسم التصنيف طويل جداً (بحد أقصى 50 حرفاً).',
            'name.unique' => 'هذا التصنيف موجود بالفعل.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('name') && is_string($this->input('name'))) {
            $this->merge([
                'name' => preg_replace('/\s+/u', ' ', trim((string) $this->input('name'))),
            ]);
        }
    }

    /** Same normalized duplicate check a new category goes through. */
    public function withValidator(\Illuminate\Contracts\Validation\Validator $validator): void
    {
        $validator->after(function ($validator) {
            $tag = $this->route('category');
            $name = (string) $this->input('name');
            $match = $name !== '' ? \App\Models\Tag::findByName($name) : null;

            if ($match && (! is_object($tag) || $match->id !== $tag->id)) {
                $validator->errors()->add('name', 'هذا التصنيف موجود بالفعل.');
            }
        });
    }
}
