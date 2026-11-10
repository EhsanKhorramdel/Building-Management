<?php

namespace App\Http\Requests;

class ChargePaymentRequest extends NumericFormRequest
{
    protected const INTEGERS = ['unitNumber', 'complexId', 'monthlyChargeId'];

    protected $errorBag = 'buildingPayCharge';

    public function rules(): array
    {
        return [
            'unitNumber' => 'required|integer|min:1',
            'complexId' => 'nullable|integer',
            'monthlyChargeId' => 'nullable|integer',
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
