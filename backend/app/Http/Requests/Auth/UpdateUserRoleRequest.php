<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->hasAnyRole(['super_admin', 'admin']);
    }

    public function rules(): array
    {
        return [
            'role' => ['required', 'string', 'in:super_admin,admin,manager,staff'],
        ];
    }
}
