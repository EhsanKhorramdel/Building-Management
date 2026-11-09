<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OnlinePaymentRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        abort_unless(config('payments.enabled'), 404);

        $this->errorBag = $this->input('type') === 'cost' ? 'incidentalPayCost' : 'buildingPayCharge';
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(['charge', 'cost'])],
            'payable_id' => ['required', 'integer', 'min:1'],
            'unitNumber' => ['required', 'integer', 'min:1'],
        ];
    }

    public function attributes(): array
    {
        return [
            'type' => 'نوع پرداخت',
            'payable_id' => 'شناسه پرداخت',
            'unitNumber' => 'شماره واحد',
        ];
    }
}
