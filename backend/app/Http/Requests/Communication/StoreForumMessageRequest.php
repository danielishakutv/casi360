<?php

namespace App\Http\Requests\Communication;

use Illuminate\Foundation\Http\FormRequest;

class StoreForumMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'body' => 'required|string|max:10000',
            'reply_to_id' => 'nullable|uuid|exists:forum_messages,id',
        ];
    }
}
