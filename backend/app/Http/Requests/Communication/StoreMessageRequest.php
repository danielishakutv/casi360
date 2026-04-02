<?php

namespace App\Http\Requests\Communication;

use Illuminate\Foundation\Http\FormRequest;

class StoreMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'recipient_id' => 'required|uuid|exists:users,id',
            'thread_id' => 'nullable|uuid|exists:messages,id',
            'subject' => 'required_without:thread_id|nullable|string|max:255',
            'body' => 'required|string|max:10000',
        ];
    }

    public function messages(): array
    {
        return [
            'recipient_id.exists' => 'The selected recipient does not exist.',
            'thread_id.exists' => 'The referenced thread does not exist.',
            'subject.required_without' => 'Subject is required for new conversations.',
        ];
    }
}
