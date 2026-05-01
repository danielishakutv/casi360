<?php

namespace App\Http\Requests\Procurement;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'invoice_number' => ['sometimes', 'required', 'string', 'max:100'],
            'po_id'          => ['sometimes', 'required', 'uuid', 'exists:purchase_orders,id'],
            'amount'         => ['sometimes', 'required', 'numeric', 'gt:0'],
            'currency'       => ['sometimes', 'nullable', 'string', 'max:10'],
            'invoice_date'   => ['sometimes', 'required', 'date'],
            'due_date'       => ['sometimes', 'nullable', 'date', 'after_or_equal:invoice_date'],
            'notes'          => ['sometimes', 'nullable', 'string', 'max:2000'],
        ];
    }
}
