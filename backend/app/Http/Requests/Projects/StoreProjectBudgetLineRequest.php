<?php

namespace App\Http\Requests\Projects;

use Illuminate\Foundation\Http\FormRequest;

class StoreProjectBudgetLineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'budget_category_id' => 'required|uuid|exists:budget_categories,id',
            'description' => 'required|string|max:500',
            'unit' => 'nullable|string|max:100',
            'quantity' => 'required|numeric|min:0.01',
            'unit_cost' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:2000',
        ];
    }
}
