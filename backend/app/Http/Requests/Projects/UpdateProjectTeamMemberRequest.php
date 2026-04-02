<?php

namespace App\Http\Requests\Projects;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProjectTeamMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $projectId = $this->route('projectId');
        $memberId = $this->route('memberId');

        return [
            'employee_id' => [
                'sometimes',
                'uuid',
                'exists:employees,id',
                Rule::unique('project_team_members', 'employee_id')
                    ->where('project_id', $projectId)
                    ->ignore($memberId),
            ],
            'role' => 'sometimes|string|max:100',
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
