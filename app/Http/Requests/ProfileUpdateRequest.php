<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        // return [
        //     'name' => ['string', 'max:255'],
        //     'email' => ['email', 'max:255', Rule::unique(User::class)->ignore($this->user()->id)],
        //     'ncode' => ['nullable', 'string', 'max:10'],
        //     'phoneNumber' => ['nullable', 'string', 'max:13'],
        // ];
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique(User::class)->ignore($this->user()->id)],
            'ncode' => ['nullable', 'string', 'regex:/^[0-9]{1,10}$/'],
            'phoneNumber' => ['nullable', 'string', 'regex:/^[0-9]{1,13}$/'],
        ];
    }
}
