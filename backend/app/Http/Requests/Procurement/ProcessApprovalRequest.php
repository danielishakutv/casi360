<?php

namespace App\Http\Requests\Procurement;

use Illuminate\Foundation\Http\FormRequest;

class ProcessApprovalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'action' => 'required|in:approve,reject',
            'comments' => 'required_if:action,reject|nullable|string|max:5000',
        ];
    }

    public function messages(): array
    {
        return [
            'action.required' => 'An approval action (approve or reject) is required.',
            'action.in' => 'The action must be either approve or reject.',
            'comments.required_if' => 'Comments are required when rejecting.',
        ];
    }
}
