<?php

namespace App\Http\Requests;

use App\Models\Message;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChatDeleteRequest extends FormRequest
{
    public function authorize(): bool
    {
        $message = $this->route('Message');

        return $message instanceof Message && ($message->user_id === $this->user()->id
            || $this->user()->can('manage', $message->group->complex));
    }

    public function rules(): array
    {
        return ['reason' => [
            Rule::requiredIf(fn () => $this->route('Message')->user_id !== $this->user()->id),
            'nullable', 'string', 'max:240',
        ]];
    }

    public function messages(): array
    {
        return ['reason.*' => 'دلیل حذف پیام را حداکثر در ۲۴۰ نویسه وارد کنید.'];
    }
}
