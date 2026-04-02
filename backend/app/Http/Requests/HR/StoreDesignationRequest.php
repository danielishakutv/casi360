<?php

namespace App\Http\Requests\HR;

use Illuminate\Foundation\Http\FormRequest;

class StoreDesignationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'department_id' => 'required|uuid|exists:departments,id',
            'level' => 'required|in:junior,mid,senior,lead,executive',
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
