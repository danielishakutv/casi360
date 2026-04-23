<?php

namespace App\Http\Requests\Procurement;

use Illuminate\Foundation\Http\FormRequest;

class ProcessBoqApprovalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'action'   => 'required|in:approve,revision,reject',
            'comments' => 'required_if:action,reject|required_if:action,revision|nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'action.required'      => 'An approval action is required.',
            'action.in'            => 'The action must be one of: approve, revision, reject.',
            'comments.required_if' => 'Comments are required when rejecting or requesting a revision.',
        ];
    }
}
