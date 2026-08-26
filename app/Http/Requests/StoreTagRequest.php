<?php

namespace App\Http\Requests;

use App\Models\Tag;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Categories are managed by administrators in the admin area — the API does not
 * mint new ones. This request therefore accepts only a category that already
 * exists (by slug or name), or one of the platform's shipped defaults, so the
 * API can materialise a default without ever inventing a category.
 */
class StoreTagRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $defaults = Tag::categories();
        $existing = Tag::query()->pluck('name', 'slug')->all();

        $allowed = array_unique(array_merge(
            array_keys($defaults),
            array_values($defaults),
            array_keys($existing),
            array_values($existing),
        ));

        return [
            'name' => ['required', 'string', 'max:50', Rule::in($allowed)],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'اسم التصنيف مطلوب.',
            'name.in' => 'هذا التصنيف غير موجود. تُدار التصنيفات من لوحة الإدارة.',
        ];
    }
}
