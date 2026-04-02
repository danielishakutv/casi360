<?php

namespace App\Http\Requests\Procurement;

use Illuminate\Foundation\Http\FormRequest;

class StoreRfqRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'pr_reference' => ['nullable', 'string', 'max:255'],
            'project_code' => ['nullable', 'string', 'max:255'],
            'structure' => ['nullable', 'string', 'max:255'],
            'currency' => ['nullable', 'string', 'max:10'],
            'request_types' => ['nullable', 'array'],
            'request_types.*' => ['string', 'in:Goods,Services,Works,Consultancy'],
            'vendor_id' => ['nullable', 'uuid', 'exists:vendors,id'],
            'supplier_name' => ['nullable', 'string', 'max:255'],
            'supplier_address' => ['nullable', 'string', 'max:500'],
            'supplier_phone' => ['nullable', 'string', 'max:50'],
            'supplier_email' => ['nullable', 'email', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'in:draft,sent,received,evaluated,cancelled'],
            'issue_date' => ['nullable', 'date'],
            'deadline' => ['nullable', 'date'],
            'delivery_address' => ['nullable', 'string', 'max:500'],
            'delivery_date' => ['nullable', 'date'],
            'delivery_terms' => ['nullable', 'string'],
            'payment_terms' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],

            'signoffs' => ['nullable', 'array'],
            'signoffs.*.type' => ['required', 'string', 'max:100'],
            'signoffs.*.name' => ['nullable', 'string', 'max:255'],
            'signoffs.*.position' => ['nullable', 'string', 'max:255'],
            'signoffs.*.date' => ['nullable', 'date'],
            'signoffs.*.signature' => ['nullable', 'string'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.item_number' => ['nullable', 'string', 'max:50'],
            'items.*.description' => ['required', 'string', 'max:500'],
            'items.*.unit' => ['nullable', 'string', 'max:50'],
            'items.*.quantity' => ['required', 'numeric', 'min:0'],
            'items.*.unit_cost' => ['nullable', 'numeric', 'min:0'],
            'items.*.vendor_unit_price' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
