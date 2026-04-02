<?php

namespace App\Http\Requests\Projects;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBudgetCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $categoryId = $this->route('id');

        return [
            'name' => ['sometimes', 'string', 'max:255', Rule::unique('budget_categories', 'name')->ignore($categoryId)],
            'description' => 'nullable|string|max:1000',
            'sort_order' => 'nullable|integer|min:0',
            'status' => 'nullable|in:active,inactive',
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'A budget category with this name already exists.',
        ];
    }
}
