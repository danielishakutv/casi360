<?php

namespace App\Http\Requests\Procurement;

use Illuminate\Foundation\Http\FormRequest;

class ProcessGrnConfirmationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'action' => ['required', 'in:accept,partial,reject'],
            // Notes are mandatory when partial-accepting or rejecting so the
            // receiver knows what to fix; optional on a clean accept.
            'notes'  => ['required_unless:action,accept', 'nullable', 'string', 'max:2000'],
        ];
    }
}
