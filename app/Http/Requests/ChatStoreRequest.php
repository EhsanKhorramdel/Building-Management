<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChatStoreRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'messageImage' => 'nullable|file|mimes:gif,jpg,webp,png|max:5120',
            'messageText' => 'nullable|string|required_without:messageImage',
            'complexId' => 'nullable',
        ];
    }
}
