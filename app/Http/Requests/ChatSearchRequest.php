<?php

namespace App\Http\Requests;

class ChatSearchRequest extends NumericFormRequest
{
    protected const INTEGERS = ['before_id'];

    public function rules(): array
    {
        return ['q' => 'required|string|max:100', 'before_id' => 'nullable|integer|min:1'];
    }

    public function messages(): array
    {
        return ['q.*' => 'عبارت جستجو را حداکثر در ۱۰۰ نویسه وارد کنید.'];
    }
}
