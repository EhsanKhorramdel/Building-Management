<?php

namespace App\Http\Requests;

class VoteRequest extends NumericFormRequest
{
    protected const INTEGERS = ['voteRadio'];

    public function rules(): array
    {
        return [
            'voteRadio' => 'nullable|integer',
        ];
    }
}
