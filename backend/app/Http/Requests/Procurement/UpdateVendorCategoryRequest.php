<?php

namespace App\Http\Requests\Procurement;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVendorCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('vendor_categories', 'name')->ignore($this->route('id')),
            ],
            'description' => 'nullable|string|max:2000',
            'status' => 'nullable|in:active,inactive',
        ];
    }
}
