<?php

namespace App\Http\Requests\Communication;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNoticeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'sometimes|required|string|max:255',
            'body' => 'sometimes|required|string|max:50000',
            'priority' => 'nullable|in:normal,important,critical',
            'status' => 'nullable|in:draft,published,archived',
            'publish_date' => 'nullable|date',
            'expiry_date' => 'nullable|date|after_or_equal:publish_date',
            'is_pinned' => 'nullable|boolean',
            'audiences' => 'sometimes|required|array|min:1',
            'audiences.*.audience_type' => 'required|in:all,department,role',
            'audiences.*.audience_id' => 'nullable|uuid|required_if:audiences.*.audience_type,department|exists:departments,id',
            'audiences.*.audience_role' => 'nullable|string|required_if:audiences.*.audience_type,role|in:admin,manager,staff',
        ];
    }
}
