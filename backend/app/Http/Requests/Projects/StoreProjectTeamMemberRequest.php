<?php

namespace App\Http\Requests\Projects;

use Illuminate\Foundation\Http\FormRequest;

class StoreProjectTeamMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $projectId = $this->route('projectId');

        return [
            'employee_id' => [
                'required',
                'uuid',
                'exists:employees,id',
                "unique:project_team_members,employee_id,NULL,id,project_id,{$projectId}",
            ],
            'role' => 'required|string|max:100',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'notes' => 'nullable|string|max:2000',
        ];
    }

    public function messages(): array
    {
        return [
            'employee_id.unique' => 'This employee is already assigned to this project.',
        ];
    }
}
