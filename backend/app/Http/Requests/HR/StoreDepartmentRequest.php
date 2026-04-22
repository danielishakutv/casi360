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
            'code' => 'nullable|string|max:50|regex:/^[A-Z0-9_]+$/|unique:departments,code',
            'head' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'color' => 'nullable|string|max:10|regex:/^#[0-9A-Fa-f]{6}$/',
            'status' => 'nullable|in:active,inactive',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('code')) {
            $this->merge(['code' => strtoupper(trim($this->input('code')))]);
        }
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'A department with this name already exists.',
            'code.unique' => 'A department with this code already exists.',
            'code.regex'  => 'Code must contain only uppercase letters, digits and underscores (e.g., FINANCE, HR_ADMIN).',
            'color.regex' => 'Color must be a valid hex color code (e.g., #6366F1).',
        ];
    }
}
