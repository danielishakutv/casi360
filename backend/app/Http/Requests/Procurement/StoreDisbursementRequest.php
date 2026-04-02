<?php

namespace App\Http\Requests\Procurement;

use Illuminate\Foundation\Http\FormRequest;

class StoreDisbursementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:bank_transfer,cheque,cash,mobile_money',
            'payment_reference' => 'nullable|string|max:255',
            'payment_date' => 'required|date',
            'notes' => 'nullable|string|max:5000',
        ];
    }

    public function messages(): array
    {
        return [
            'amount.min' => 'The disbursement amount must be at least 0.01.',
            'payment_method.in' => 'Payment method must be bank_transfer, cheque, cash, or mobile_money.',
        ];
    }
}
