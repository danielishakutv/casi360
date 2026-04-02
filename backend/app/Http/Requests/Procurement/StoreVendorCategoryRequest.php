<?php

namespace App\Http\Requests\Procurement;

use Illuminate\Foundation\Http\FormRequest;

class StoreVendorCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:vendor_categories,name',
            'description' => 'nullable|string|max:2000',
            'status' => 'nullable|in:active,inactive',
        ];
    }
}
