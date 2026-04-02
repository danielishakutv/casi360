<?php

namespace App\Http\Requests\HR;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $departmentId = $this->route('id');

        return [
            'name' => ['sometimes', 'string', 'max:255', Rule::unique('departments', 'name')->ignore($departmentId)],
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
