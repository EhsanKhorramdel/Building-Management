<?php

namespace App\Http\Requests;

class ChatMediaRequest extends NumericFormRequest
{
    protected const INTEGERS = ['cursor'];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['cursor' => ['nullable', 'integer', 'min:1'], 'direction' => ['required', 'in:before,after']];
    }
}
