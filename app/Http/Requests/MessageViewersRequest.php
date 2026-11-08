<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MessageViewersRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'message_id' => 'nullable',
        ];
    }
}
