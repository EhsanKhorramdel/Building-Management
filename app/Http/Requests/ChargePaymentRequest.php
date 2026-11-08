<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChargePaymentRequest extends FormRequest
{
    protected $errorBag = 'buildingPayCharge';

    public function rules(): array
    {
        return [
            'unitNumber' => 'required|integer|min:1',
            'complexId' => 'nullable',
            'monthlyChargeId' => 'nullable',
        ];
    }

    public function messages(): array
    {
        return [
            'unitNumber.required' => 'فیلد شماره واحد اجباری است.',
            'unitNumber.integer' => 'فیلد شماره واحد باید عدد صحیح باشد.',
            'unitNumber.min' => 'فیلد شماره واحد باید حداقل :min باشد.',
        ];
    }
}
