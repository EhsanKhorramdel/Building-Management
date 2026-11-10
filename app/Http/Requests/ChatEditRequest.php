<?php

namespace App\Http\Requests;

class ChatEditRequest extends NumericFormRequest
{
    protected const INTEGERS = ['messageId'];

    public function rules(): array
    {
        return [
            'messageId' => 'nullable|integer',
            'messageText' => 'nullable',
        ];
    }
}
