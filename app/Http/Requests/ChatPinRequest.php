<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChatPinRequest extends FormRequest
{
    public function rules(): array
    {
        return ['pinned' => 'required|boolean'];
    }

    public function messages(): array
    {
        return ['pinned.*' => 'وضعیت سنجاق پیام معتبر نیست.'];
    }
}
