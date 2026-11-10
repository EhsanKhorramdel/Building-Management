<?php

namespace App\Http\Requests;

class MessageViewersRequest extends NumericFormRequest
{
    protected const INTEGERS = ['message_id'];

    public function rules(): array
    {
        return [
            'message_id' => 'nullable|integer',
        ];
    }
}
