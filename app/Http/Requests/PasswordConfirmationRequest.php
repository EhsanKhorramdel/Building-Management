<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PasswordConfirmationRequest extends FormRequest
{
    public function rules(): array
    {
        return ['password' => 'nullable'];
    }
}
