<?php

namespace App\Http\Requests\Procurement;

use Illuminate\Foundation\Http\FormRequest;

class StorePurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'vendor_id' => 'required|uuid|exists:vendors,id',
            'department_id' => 'required|uuid|exists:departments,id',
            'requested_by' => 'required|uuid|exists:employees,id',
            'order_date' => 'required|date',
            'expected_delivery_date' => 'nullable|date|after_or_equal:order_date',
            'tax_amount' => 'nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:10',
            'notes' => 'nullable|string|max:5000',
            'pr_reference' => 'nullable|string|max:255',
            'rfq_reference' => 'nullable|string|max:255',
            'deliver_name' => 'nullable|string|max:255',
            'deliver_address' => 'nullable|string|max:500',
            'deliver_position' => 'nullable|string|max:255',
            'deliver_contact' => 'nullable|string|max:255',
            'payment_terms' => 'nullable|array',
            'payment_terms.*' => 'string|max:50',
            'delivery_terms' => 'nullable|string|max:5000',
            'remarks' => 'nullable|string|max:5000',
            'delivery_charges' => 'nullable|numeric|min:0',
            'signoffs' => 'nullable|array',
            'signoffs.*.type' => 'required|string|max:100',
            'signoffs.*.name' => 'nullable|string|max:255',
            'signoffs.*.position' => 'nullable|string|max:255',
            'signoffs.*.date' => 'nullable|date',
            'signoffs.*.signature' => 'nullable|string|max:500',
            'status' => 'nullable|in:draft',
            'payment_status' => 'nullable|in:unpaid,partially_paid,paid',
            'items' => 'sometimes|array|min:1',
            'items.*.inventory_item_id' => 'nullable|uuid|exists:inventory_items,id',
            'items.*.description' => 'required|string|max:500',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit' => 'nullable|string|max:50',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.pr_no' => 'nullable|string|max:100',
            'items.*.project_code' => 'nullable|string|max:100',
            'items.*.budget_line' => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'vendor_id.exists' => 'The selected vendor does not exist.',
            'department_id.exists' => 'The selected department does not exist.',
            'requested_by.exists' => 'The selected employee does not exist.',
            'expected_delivery_date.after_or_equal' => 'Expected delivery date must be on or after the order date.',
        ];
    }
}
