<?php

namespace App\Http\Requests\Procurement;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequisitionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'department_id' => 'required|uuid|exists:departments,id',
            'title' => 'required|string|max:255',
            'justification' => 'nullable|string|max:5000',
            'priority' => 'nullable|in:low,medium,high,urgent',
            'needed_by' => 'nullable|date|after_or_equal:today',
            'notes' => 'nullable|string|max:5000',
            'delivery_location' => 'nullable|string|max:500',
            'purchase_scenario' => 'nullable|string|max:255',
            'logistics_involved' => 'nullable|boolean',
            'boq' => 'nullable|boolean',
            'project_code' => 'nullable|string|max:100',
            'donor' => 'nullable|string|max:255',
            'currency' => 'nullable|string|max:10',
            'exchange_rate' => 'nullable|numeric|min:0',
            'signoffs' => 'nullable|array',
            'signoffs.*.type' => 'required|string|max:100',
            'signoffs.*.name' => 'nullable|string|max:255',
            'signoffs.*.position' => 'nullable|string|max:255',
            'signoffs.*.email' => 'nullable|email|max:255',
            'signoffs.*.date' => 'nullable|date',
            'signoffs.*.signature' => 'nullable|string|max:500',
            'status' => 'nullable|in:draft',
            'items' => 'sometimes|array|min:1',
            'items.*.inventory_item_id' => 'nullable|uuid|exists:inventory_items,id',
            'items.*.description' => 'required|string|max:500',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit' => 'nullable|string|max:50',
            'items.*.estimated_unit_cost' => 'required|numeric|min:0',
            'items.*.project_code' => 'nullable|string|max:100',
            'items.*.budget_line' => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'department_id.exists' => 'The selected department does not exist.',
            'requested_by.exists' => 'The selected employee does not exist.',
            'needed_by.after_or_equal' => 'The needed-by date must be today or later.',
        ];
    }
}
