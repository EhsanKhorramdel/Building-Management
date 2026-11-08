<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChatEditRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'messageId' => 'nullable',
            'messageText' => 'nullable',
        ];
    }
}
