<?php

namespace App\Http\Requests\Procurement;

use Illuminate\Foundation\Http\FormRequest;

class StoreRfpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'po_reference' => ['nullable', 'string', 'max:255'],
            'grn_reference' => ['nullable', 'string', 'max:255'],
            'project_code' => ['nullable', 'string', 'max:255'],
            'vendor_id' => ['nullable', 'uuid', 'exists:vendors,id'],
            'payee' => ['nullable', 'string', 'max:255'],
            'payment_method' => ['nullable', 'in:bank_transfer,cash,cheque'],
            'currency' => ['nullable', 'string', 'max:10'],
            'exchange_rate' => ['nullable', 'numeric', 'min:0'],
            'department' => ['nullable', 'string', 'max:255'],
            'budget_line' => ['nullable', 'string', 'max:255'],
            'date' => ['nullable', 'date'],
            'status' => ['nullable', 'in:draft,pending,submitted,approved,paid,rejected,on_hold'],
            'payment_date' => ['nullable', 'date'],
            'subtotal' => ['nullable', 'numeric', 'min:0'],
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'bank_details' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],

            'signoffs' => ['nullable', 'array'],
            'signoffs.*.type' => ['required', 'string', 'max:100'],
            'signoffs.*.name' => ['nullable', 'string', 'max:255'],
            'signoffs.*.position' => ['nullable', 'string', 'max:255'],
            'signoffs.*.date' => ['nullable', 'date'],
            'signoffs.*.signature' => ['nullable', 'string'],

            'supporting_docs' => ['nullable', 'array'],
            'supporting_docs.*' => ['string'],

            'items' => ['nullable', 'array'],
            'items.*.description' => ['required', 'string', 'max:500'],
            'items.*.project_code' => ['nullable', 'string', 'max:255'],
            'items.*.budget_line' => ['nullable', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0'],
            'items.*.dept' => ['nullable', 'string', 'max:255'],
        ];
    }
}
