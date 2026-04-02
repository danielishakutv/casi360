<?php

namespace App\Http\Requests\HR;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDesignationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'sometimes|string|max:255',
            'department_id' => 'sometimes|uuid|exists:departments,id',
            'level' => 'sometimes|in:junior,mid,senior,lead,executive',
            'description' => 'nullable|string|max:1000',
            'status' => 'nullable|in:active,inactive',
        ];
    }

    public function messages(): array
    {
        return [
            'department_id.exists' => 'The selected department does not exist.',
            'level.in' => 'Level must be one of: junior, mid, senior, lead, executive.',
        ];
    }
}
