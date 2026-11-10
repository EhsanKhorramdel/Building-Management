<?php

namespace App\Http\Requests;

class IncidentalCostRequest extends NumericFormRequest
{
    protected const DECIMALS = ['total_amount', 'share_amount'];

    protected $errorBag = 'incidentalCost';

    public function rules(): array
    {
        return [
            'cost_invoice' => 'nullable|file|mimes:gif,jpg,webp,png|max:5120',
            'cost_explanation' => 'nullable|string',
            'title' => 'required|string',
            'total_amount' => 'required|numeric',
            'share_amount' => 'required|numeric',
        ];
    }

    public function messages(): array
    {
        return [
            'cost_invoice.file' => 'فایل فاکتور هزینه باید باشد.',
            'cost_invoice.mimes' => 'فرمت فاکتور هزینه باید یکی از gif، jpg، webp، png باشد.',
            'cost_invoice.max' => 'حجم تصویر فاکتور هزینه نباید بیشتر از 5 مگابایت باشد.',
            'cost_explanation.string' => 'فیلد توضیح هزینه باید یک رشته باشد.',
            'title.required' => 'فیلد عنوان اجباری است.',
            'title.string' => 'فیلد عنوان باید یک رشته باشد.',
            'total_amount.required' => 'فیلد مبلغ کل اجباری است.',
            'share_amount.required' => 'فیلد مبلغ سهم افراد اجباری است.',
            'total_amount.numeric' => 'فیلد مبلغ کل باید عددی باشد.',
            'share_amount.numeric' => 'فیلد مبلغ سهم باید عددی باشد.',
        ];
    }
}
