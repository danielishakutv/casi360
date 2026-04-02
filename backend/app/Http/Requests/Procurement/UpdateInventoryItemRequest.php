<?php

namespace App\Http\Requests\Procurement;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateInventoryItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $itemId = $this->route('id');

        return [
            'name' => 'sometimes|string|max:255',
            'sku' => ['nullable', 'string', 'max:100', Rule::unique('inventory_items', 'sku')->ignore($itemId)],
            'category' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:2000',
            'unit' => 'nullable|string|max:50',
            'quantity_in_stock' => 'nullable|integer|min:0',
            'reorder_level' => 'nullable|integer|min:0',
            'unit_cost' => 'nullable|numeric|min:0',
            'location' => 'nullable|string|max:255',
            'status' => 'nullable|in:active,inactive,out_of_stock',
        ];
    }

    public function messages(): array
    {
        return [
            'sku.unique' => 'An inventory item with this SKU already exists.',
        ];
    }
}
