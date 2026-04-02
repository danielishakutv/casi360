<?php

namespace App\Http\Requests\Communication;

use Illuminate\Foundation\Http\FormRequest;

class StoreForumRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'type' => 'required|in:general,department',
            'department_id' => 'required_if:type,department|nullable|uuid|exists:departments,id|unique:forums,department_id',
            'status' => 'nullable|in:active,archived',
        ];
    }

    public function messages(): array
    {
        return [
            'department_id.required_if' => 'Department is required for department forums.',
            'department_id.unique' => 'A forum already exists for this department.',
        ];
    }
}
