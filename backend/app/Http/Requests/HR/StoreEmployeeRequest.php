<?php

namespace App\Http\Requests\HR;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:employees,email',
            'phone' => 'nullable|string|max:30',
            'department_id' => 'required|uuid|exists:departments,id',
            'designation_id' => 'required|uuid|exists:designations,id',
            'manager' => 'nullable|string|max:255',
            'status' => 'nullable|in:active,on_leave,terminated',
            'join_date' => 'required|date',
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
            'date_of_birth.before' => 'Date of birth must be before today.',
        ];
    }
}
