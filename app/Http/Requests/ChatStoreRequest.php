<?php

namespace App\Http\Requests;

class ChatStoreRequest extends NumericFormRequest
{
    protected const INTEGERS = ['complexId', 'parent_id'];

    public function rules(): array
    {
        return [
            'messageImage' => 'nullable|file|mimes:gif,jpg,webp,png|max:5120',
            'messageText' => 'nullable|string|required_without:messageImage',
            'complexId' => 'nullable|integer',
            'parent_id' => 'nullable|integer',
        ];
    }

    public function messages(): array
    {
        return ['parent_id.*' => 'پیام پاسخ در این گفتگو در دسترس نیست.'];
    }
}
