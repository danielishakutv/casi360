<?php

namespace App\Http\Requests\HR;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $employeeId = $this->route('id');

        return [
            'name' => 'sometimes|string|max:255',
            'email' => ['sometimes', 'email', 'max:255', Rule::unique('employees', 'email')->ignore($employeeId)],
            'phone' => 'nullable|string|max:30',
            'department_id' => 'sometimes|uuid|exists:departments,id',
            'designation_id' => 'sometimes|uuid|exists:designations,id',
            'manager' => 'nullable|string|max:255',
            'status' => 'nullable|in:active,on_leave,terminated',
            'join_date' => 'sometimes|date',
            'termination_date' => 'nullable|date|after_or_equal:join_date',
            'salary' => 'nullable|numeric|min:0',
            'avatar' => 'nullable|string|max:500',
            'address' => 'nullable|string|max:1000',
            'gender' => 'nullable|in:male,female,other',
            'date_of_birth' => 'nullable|date|before:today',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:30',
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'An employee with this email already exists.',
            'department_id.exists' => 'The selected department does not exist.',
            'designation_id.exists' => 'The selected designation does not exist.',
            'termination_date.after_or_equal' => 'Termination date must be on or after the join date.',
        ];
    }
}
