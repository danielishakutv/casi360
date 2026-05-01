<?php

namespace App\Http\Requests\Procurement;

use Illuminate\Foundation\Http\FormRequest;

class ProcessInvoiceApprovalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'action'          => ['required', 'in:approve,reject'],
            // A reason is mandatory when rejecting so the supplier and
            // procurement team understand why the invoice was bounced.
            'rejected_reason' => ['required_if:action,reject', 'nullable', 'string', 'max:1000'],
        ];
    }
}
