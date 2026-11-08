<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChatSeenRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'messageId' => 'nullable',
        ];
    }
}
