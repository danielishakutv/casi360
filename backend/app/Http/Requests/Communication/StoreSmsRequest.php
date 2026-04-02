<?php

namespace App\Http\Requests\Communication;

use Illuminate\Foundation\Http\FormRequest;

class StoreSmsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'max:480'],
            'audience' => ['required', 'in:general,department,individual'],
            'recipient_ids' => ['required_if:audience,individual', 'nullable', 'array'],
            'recipient_ids.*' => ['uuid', 'exists:users,id'],
            'department_ids' => ['required_if:audience,department', 'nullable', 'array'],
            'department_ids.*' => ['uuid', 'exists:departments,id'],
        ];
    }
}
