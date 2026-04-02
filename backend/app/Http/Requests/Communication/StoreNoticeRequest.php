<?php

namespace App\Http\Requests\Communication;

use Illuminate\Foundation\Http\FormRequest;

class StoreNoticeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'body' => 'required|string|max:50000',
            'priority' => 'nullable|in:normal,important,critical',
            'status' => 'nullable|in:draft,published,archived',
            'publish_date' => 'nullable|date',
            'expiry_date' => 'nullable|date|after_or_equal:publish_date',
            'is_pinned' => 'nullable|boolean',
            'audiences' => 'required|array|min:1',
            'audiences.*.audience_type' => 'required|in:all,department,role',
            'audiences.*.audience_id' => 'nullable|uuid|required_if:audiences.*.audience_type,department|exists:departments,id',
            'audiences.*.audience_role' => 'nullable|string|required_if:audiences.*.audience_type,role|in:admin,manager,staff',
        ];
    }

    public function messages(): array
    {
        return [
            'audiences.required' => 'At least one audience must be specified.',
            'audiences.*.audience_id.required_if' => 'Department is required for department-targeted audiences.',
            'audiences.*.audience_role.required_if' => 'Role is required for role-targeted audiences.',
        ];
    }
}
