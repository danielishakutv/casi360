<?php

namespace App\Http\Requests\Procurement;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBoqRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'pr_reference' => ['nullable', 'string', 'max:255'],
            'project_code' => ['nullable', 'string', 'max:255'],
            'prepared_by' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'in:draft,submitted,approved,revised'],
            'date' => ['nullable', 'date'],
            'department' => ['nullable', 'string', 'max:255'],
            'delivery_location' => ['nullable', 'string', 'max:500'],
            'category' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],

            'signoffs' => ['nullable', 'array'],
            'signoffs.*.type' => ['required', 'string', 'max:100'],
            'signoffs.*.name' => ['nullable', 'string', 'max:255'],
            'signoffs.*.position' => ['nullable', 'string', 'max:255'],
            'signoffs.*.email' => ['nullable', 'email', 'max:255'],
            'signoffs.*.date' => ['nullable', 'date'],
            'signoffs.*.signature' => ['nullable', 'string'],

            'items' => ['sometimes', 'array', 'min:1'],
            'items.*.id' => ['nullable', 'uuid'],
            'items.*.section' => ['nullable', 'string', 'max:255'],
            'items.*.description' => ['required', 'string', 'max:500'],
            'items.*.unit' => ['nullable', 'string', 'max:50'],
            'items.*.quantity' => ['required', 'numeric', 'min:0'],
            'items.*.unit_rate' => ['required', 'numeric', 'min:0'],
            'items.*.comment' => ['nullable', 'string', 'max:500'],
        ];
    }
}
