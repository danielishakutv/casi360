<?php

namespace App\Http\Requests\HR;

use Illuminate\Foundation\Http\FormRequest;

class StoreNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => 'required|uuid|exists:employees,id',
            'title' => 'required|string|max:255',
            'content' => 'required|string|max:10000',
            'type' => 'sometimes|in:general,performance,disciplinary,commendation,medical,training',
            'priority' => 'sometimes|in:low,medium,high',
        ];
    }

    public function messages(): array
    {
        return [
            'employee_id.exists' => 'The selected employee does not exist.',
        ];
    }
}
