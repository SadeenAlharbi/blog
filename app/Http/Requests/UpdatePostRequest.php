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
            'image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:4096'],
            'published_at' => ['nullable', 'date'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:50'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('tags_input') && ! $this->has('tags')) {
            $this->merge([
                'tags' => collect(explode(',', $this->string('tags_input')))
                    ->map(fn ($tag) => trim($tag))
                    ->filter()
                    ->values()
                    ->all(),
            ]);
        }
    }
}
