<?php

namespace App\Http\Requests\Programs;

use Illuminate\Foundation\Http\FormRequest;

class StoreBeneficiaryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'project_id' => ['required', 'uuid', 'exists:projects,id'],
            'gender' => ['nullable', 'in:male,female,other'],
            'age' => ['nullable', 'integer', 'min:0', 'max:150'],
            'location' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'enrollment_date' => ['required', 'date'],
            'status' => ['required', 'in:active,inactive,graduated,withdrawn'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
