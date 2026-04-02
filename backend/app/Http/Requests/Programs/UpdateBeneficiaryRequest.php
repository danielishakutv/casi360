<?php

namespace App\Http\Requests\Programs;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBeneficiaryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'project_id' => ['sometimes', 'uuid', 'exists:projects,id'],
            'gender' => ['nullable', 'in:male,female,other'],
            'age' => ['nullable', 'integer', 'min:0', 'max:150'],
            'location' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'enrollment_date' => ['sometimes', 'date'],
            'status' => ['sometimes', 'in:active,inactive,graduated,withdrawn'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
