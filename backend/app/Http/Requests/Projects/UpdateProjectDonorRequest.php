<?php

namespace App\Http\Requests\Projects;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProjectDonorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'type' => 'sometimes|in:individual,organization,government,multilateral',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'contribution_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:2000',
        ];
    }
}
