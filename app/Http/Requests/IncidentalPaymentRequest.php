<?php

namespace App\Http\Requests;

class IncidentalPaymentRequest extends NumericFormRequest
{
    protected const INTEGERS = ['unitNumber', 'complexId', 'incidentalCostId'];

    protected $errorBag = 'incidentalPayCost';

    public function rules(): array
    {
        return [
            'unitNumber' => 'required|integer|min:1',
            'complexId' => 'nullable|integer',
            'incidentalCostId' => 'nullable|integer',
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
