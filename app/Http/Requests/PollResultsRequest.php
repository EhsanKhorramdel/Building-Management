<?php

namespace App\Http\Requests;

class PollResultsRequest extends NumericFormRequest
{
    protected const INTEGERS = ['poll_id'];

    public function rules(): array
    {
        return [
            'poll_id' => 'nullable|integer',
        ];
    }
}
