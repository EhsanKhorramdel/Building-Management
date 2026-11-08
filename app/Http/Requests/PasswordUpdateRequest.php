<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class PasswordUpdateRequest extends FormRequest
{
    protected $errorBag = 'updatePassword';

    public function rules(): array
    {
        return [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'current_password.required' => 'فیلد رمز عبور فعلی الزامی است.',
            'current_password.current_password' => 'رمز عبور فعلی نادرست است.',

            'password.required' => 'فیلد رمز عبور جدید الزامی است.',
            'password.confirmed' => 'تأیید رمز عبور جدید با رمز عبور وارد شده مطابقت ندارد.',
        ];
    }
}
