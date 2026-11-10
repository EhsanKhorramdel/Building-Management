<?php

namespace App\Http\Requests;

use App\Models\MessageReaction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChatReactionRequest extends FormRequest
{
    public function rules(): array
    {
        return ['emoji' => ['required', 'string', Rule::in(MessageReaction::PALETTE)]];
    }

    public function messages(): array
    {
        return ['emoji.*' => 'یک واکنش از فهرست انتخاب کنید.'];
    }
}
