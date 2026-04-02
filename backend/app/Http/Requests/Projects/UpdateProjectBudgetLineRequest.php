<?php

namespace App\Http\Requests\Projects;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProjectBudgetLineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'budget_category_id' => 'sometimes|uuid|exists:budget_categories,id',
            'description' => 'sometimes|string|max:500',
            'unit' => 'nullable|string|max:100',
            'quantity' => 'sometimes|numeric|min:0.01',
            'unit_cost' => 'sometimes|numeric|min:0',
            'notes' => 'nullable|string|max:2000',
        ];
    }
}
