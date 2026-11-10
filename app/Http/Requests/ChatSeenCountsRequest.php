<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChatSeenCountsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['message_ids' => ['required', 'array', 'min:1', 'max:50'], 'message_ids.*' => ['required', 'integer', 'distinct', 'min:1']];
    }
}
