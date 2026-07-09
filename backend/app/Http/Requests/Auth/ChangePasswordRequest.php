<?php

namespace App\Http\Requests\Auth;

use App\Support\PasswordPolicy;
use Illuminate\Foundation\Http\FormRequest;

class ChangePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string'],
            'new_password' => [
                'required',
                'string',
                PasswordPolicy::rule(),
                'different:current_password',
            ],
            'new_password_confirmation' => ['required', 'same:new_password'],
        ];
    }

    public function messages(): array
    {
        return [
            'new_password.different' => 'New password must be different from current password.',
            'new_password_confirmation.same' => 'Password confirmation does not match.',
        ];
    }
}
