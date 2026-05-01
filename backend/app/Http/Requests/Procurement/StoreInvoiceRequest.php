<?php

namespace App\Http\Requests\Procurement;

use Illuminate\Foundation\Http\FormRequest;

class StoreInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'invoice_number' => ['required', 'string', 'max:100'],
            'po_id'          => ['required', 'uuid', 'exists:purchase_orders,id'],
            'amount'         => ['required', 'numeric', 'gt:0'],
            'currency'       => ['nullable', 'string', 'max:10'],
            'invoice_date'   => ['required', 'date'],
            'due_date'       => ['nullable', 'date', 'after_or_equal:invoice_date'],
            'notes'          => ['nullable', 'string', 'max:2000'],
        ];
    }
}
