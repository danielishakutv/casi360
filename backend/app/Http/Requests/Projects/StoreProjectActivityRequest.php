<?php

namespace App\Http\Requests\Projects;

use Illuminate\Foundation\Http\FormRequest;

class StoreProjectActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'target_date' => 'nullable|date',
            'status' => 'nullable|in:not_started,in_progress,completed,delayed,cancelled',
            'completion_percentage' => 'nullable|integer|min:0|max:100',
            'sort_order' => 'nullable|integer|min:0',
            'notes' => 'nullable|string|max:2000',
        ];
    }
}
