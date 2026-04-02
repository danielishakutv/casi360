<?php

namespace App\Http\Requests\Projects;

use Illuminate\Foundation\Http\FormRequest;

class StoreProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'objectives' => 'nullable|string|max:5000',
            'department_id' => 'required|uuid|exists:departments,id',
            'project_manager_id' => 'nullable|uuid|exists:employees,id',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'location' => 'nullable|string|max:500',
            'currency' => 'nullable|string|max:10',
            'status' => 'nullable|in:draft,active,on_hold,completed,closed',
            'notes' => 'nullable|string|max:5000',
        ];
    }
}
