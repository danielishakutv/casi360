<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Only admins can create users
        return $this->user() && $this->user()->hasAnyRole(['super_admin', 'admin']);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => [
                'required',
                'string',
                Password::min((int) env('PASSWORD_MIN_LENGTH', 8))
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised(),
            ],
            // nullable: the frontend always sends these keys, and Laravel's
            // ConvertEmptyStringsToNull middleware turns empty inputs into null.
            // Without nullable, an empty phone/department/role would fail the
            // string rule and return a 422. These fields are optional by design.
            'role' => ['sometimes', 'nullable', 'string', 'in:super_admin,admin,manager,staff'],
            'department' => ['sometimes', 'nullable', 'string', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:20'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'A user with this email already exists.',
            'password.min' => 'Password must be at least ' . env('PASSWORD_MIN_LENGTH', 8) . ' characters.',
        ];
    }
}
