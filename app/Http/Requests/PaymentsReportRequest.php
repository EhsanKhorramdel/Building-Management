<?php

namespace App\Http\Requests;

class PaymentsReportRequest extends NumericFormRequest
{
    protected const INTEGERS = ['complex_id'];

    public function rules(): array
    {
        return [
            'complex_id' => 'nullable|integer',
        ];
    }
}
