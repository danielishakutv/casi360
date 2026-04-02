<?php

namespace App\Http\Requests\HR;

use Illuminate\Foundation\Http\FormRequest;

class StoreDepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:departments,name',
            'head' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'color' => 'nullable|string|max:10|regex:/^#[0-9A-Fa-f]{6}$/',
            'status' => 'nullable|in:active,inactive',
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'A department with this name already exists.',
            'color.regex' => 'Color must be a valid hex color code (e.g., #6366F1).',
        ];
    }
}
