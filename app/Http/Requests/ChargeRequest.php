<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChargeRequest extends FormRequest
{
    protected $errorBag = 'buildingCharge';

    public function rules(): array
    {
        return [
            'chargeAmount' => 'required|numeric|min:0',
            'dayStart' => 'required|integer|min:1|max:31',
            'monthStart' => 'required|integer|min:1|max:12',
            'yearStart' => 'required|integer|min:1300',
            'dayEnd' => 'required|integer|min:1|max:31',
            'monthEnd' => 'required|integer|min:1|max:12',
            'yearEnd' => 'required|integer|min:1300',
        ];
    }

    public function messages(): array
    {
        return [
            'chargeAmount.required' => 'مقدار شارژ الزامی است.',
            'chargeAmount.numeric' => 'مقدار شارژ باید عددی باشد.',
            'chargeAmount.min' => 'مقدار شارژ باید حداقل :min باشد.',
            'dayStart.required' => 'روز شروع الزامی است.',
            'dayStart.integer' => 'روز شروع باید یک عدد صحیح باشد.',
            'dayStart.min' => 'روز شروع باید حداقل :min باشد.',
            'dayStart.max' => 'روز شروع باید حداکثر :max باشد.',
            'monthStart.required' => 'ماه شروع الزامی است.',
            'monthStart.integer' => 'ماه شروع باید یک عدد صحیح باشد.',
            'monthStart.min' => 'ماه شروع باید حداقل :min باشد.',
            'monthStart.max' => 'ماه شروع باید حداکثر :max باشد.',
            'yearStart.required' => 'سال شروع الزامی است.',
            'yearStart.integer' => 'سال شروع باید یک عدد صحیح باشد.',
            'yearStart.min' => 'سال شروع باید حداقل :min باشد.',
            'dayEnd.required' => 'روز پایان الزامی است.',
            'dayEnd.integer' => 'روز پایان باید یک عدد صحیح باشد.',
            'dayEnd.min' => 'روز پایان باید حداقل :min باشد.',
            'dayEnd.max' => 'روز پایان باید حداکثر :max باشد.',
            'monthEnd.required' => 'ماه پایان الزامی است.',
            'monthEnd.integer' => 'ماه پایان باید یک عدد صحیح باشد.',
            'monthEnd.min' => 'ماه پایان باید حداقل :min باشد.',
            'monthEnd.max' => 'ماه پایان باید حداکثر :max باشد.',
            'yearEnd.required' => 'سال پایان الزامی است.',
            'yearEnd.integer' => 'سال پایان باید یک عدد صحیح باشد.',
            'yearEnd.min' => 'سال پایان باید حداقل :min باشد.',
        ];
    }
}
