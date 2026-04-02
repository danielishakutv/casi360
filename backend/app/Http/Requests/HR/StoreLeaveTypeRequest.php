<?php

namespace App\Http\Requests\HR;

use Illuminate\Foundation\Http\FormRequest;

class StoreLeaveTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'days_allowed' => ['required', 'integer', 'min:1', 'max:365'],
            'carry_over_max' => ['nullable', 'integer', 'min:0'],
            'paid' => ['required', 'boolean'],
            'requires_approval' => ['required', 'boolean'],
            'status' => ['required', 'in:active,inactive'],
            'description' => ['nullable', 'string'],
        ];
    }
}
