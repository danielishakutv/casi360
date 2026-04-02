<?php

namespace App\Http\Requests\HR;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLeaveTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'days_allowed' => ['sometimes', 'integer', 'min:1', 'max:365'],
            'carry_over_max' => ['nullable', 'integer', 'min:0'],
            'paid' => ['sometimes', 'boolean'],
            'requires_approval' => ['sometimes', 'boolean'],
            'status' => ['sometimes', 'in:active,inactive'],
            'description' => ['nullable', 'string'],
        ];
    }
}
