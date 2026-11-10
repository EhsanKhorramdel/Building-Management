<?php

namespace App\Http\Requests;

class ChatSeenRequest extends NumericFormRequest
{
    protected const INTEGERS = ['messageId'];

    public function rules(): array
    {
        return [
            'messageId' => 'nullable|integer',
        ];
    }
}
