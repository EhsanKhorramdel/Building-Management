<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PaymentsReportRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'complex_id' => 'nullable',
        ];
    }
}
