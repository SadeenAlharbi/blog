<?php

namespace App\Http\Requests\Admin;

use App\Models\Tag;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Adding a category: the moderator types a NAME and nothing else.
 *
 * The id and the slug are the backend's business — neither is accepted from the
 * form, so neither can be mistyped or forged.
 */
class StoreCategoryRequest extends FormRequest
{
    /** Route-level authorization is the admin middleware. */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'اسم التصنيف مطلوب.',
            'name.min' => 'اسم التصنيف قصير جداً.',
            'name.max' => 'اسم التصنيف طويل جداً (بحد أقصى 50 حرفاً).',
        ];
    }

    /** Collapse stray whitespace before anything else looks at the name. */
    protected function prepareForValidation(): void
    {
        if ($this->has('name') && is_string($this->input('name'))) {
            $this->merge([
                'name' => preg_replace('/\s+/u', ' ', trim((string) $this->input('name'))),
            ]);
        }
    }

    /**
     * Duplicate protection, server-side.
     *
     * Compares on the NORMALIZED name, so spacing and Arabic spelling variants
     * are caught too — not just an identical string. The `tags.name` unique
     * index is the second line of defence underneath this. A category sitting
     * in the removed list is deliberately NOT an error here: the controller
     * restores it instead of refusing.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $name = (string) $this->input('name');

            if ($name !== '' && Tag::findByName($name) !== null) {
                $validator->errors()->add('name', 'هذا التصنيف موجود بالفعل.');
            }
        });
    }
}
