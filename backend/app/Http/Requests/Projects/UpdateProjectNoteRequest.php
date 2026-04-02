<?php

namespace App\Http\Requests\Projects;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProjectNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'sometimes|required|string|max:255',
            'content' => 'nullable|string|max:10000',
            'link_url' => 'nullable|url|max:2048',
            'link_label' => 'nullable|string|max:255',
        ];
    }
}
