<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRoleRequest extends FormRequest
{
    /** Route-level authorization is the admin middleware + UserPolicy. */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'role' => ['required', 'string', Rule::in([User::ROLE_ADMIN, User::ROLE_USER])],
        ];
    }

    public function messages(): array
    {
        return [
            'role.required' => 'الدور مطلوب.',
            'role.in' => 'الدور المحدد غير صالح.',
        ];
    }
}
