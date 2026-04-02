<?php

namespace App\Http\Requests\Procurement;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGrnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'po_reference' => ['nullable', 'string', 'max:255'],
            'vendor_id' => ['nullable', 'uuid', 'exists:vendors,id'],
            'office' => ['nullable', 'string', 'max:255'],
            'received_by' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'in:draft,inspected,accepted,rejected,partial'],
            'received_date' => ['nullable', 'date'],
            'delivery_note_no' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],

            'signoffs' => ['nullable', 'array'],
            'signoffs.*.type' => ['required', 'string', 'max:100'],
            'signoffs.*.name' => ['nullable', 'string', 'max:255'],
            'signoffs.*.position' => ['nullable', 'string', 'max:255'],
            'signoffs.*.date' => ['nullable', 'date'],
            'signoffs.*.signature' => ['nullable', 'string'],

            'items' => ['sometimes', 'array', 'min:1'],
            'items.*.id' => ['nullable', 'uuid'],
            'items.*.description' => ['required', 'string', 'max:500'],
            'items.*.ordered_qty' => ['required', 'integer', 'min:0'],
            'items.*.received_qty' => ['required', 'integer', 'min:0'],
            'items.*.quality_status' => ['nullable', 'in:good,damaged,defective,wrong_item'],
            'items.*.accepted_qty' => ['nullable', 'integer', 'min:0'],
            'items.*.rejected_qty' => ['nullable', 'integer', 'min:0'],
            'items.*.rejection_reason' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
