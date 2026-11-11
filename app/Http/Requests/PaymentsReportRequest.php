<?php

namespace App\Http\Requests;

use Morilog\Jalali\CalendarUtils;

class PaymentsReportRequest extends NumericFormRequest
{
    protected const INTEGERS = ['complex_id', 'period_year', 'period_month', 'unit_number'];

    protected const IDENTIFIERS = ['paid_from', 'paid_to'];

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();
        foreach (['period_year', 'period_month'] as $field) {
            $value = $this->input($field);
            if (is_string($value) && preg_match('/^[0-9]{1,4}$/D', $value)) {
                $this->merge([$field => (int) $value]);
            }
        }
    }

    public function attributes(): array
    {
        return [
            'period_year' => 'سال شارژ', 'period_month' => 'ماه شارژ', 'unit_number' => 'شماره واحد',
            'paid_from' => 'تاریخ شروع', 'paid_to' => 'تاریخ پایان', 'type' => 'نوع پرداخت', 'status' => 'وضعیت',
        ];
    }

    public function rules(): array
    {
        $date = function ($attribute, $value, $fail) {
            if (! is_string($value) || ! preg_match('/^(\d{4})\/(\d{2})\/(\d{2})$/D', $value, $parts)
                || (int) $parts[1] < 1 || (int) $parts[1] > 3177
                || ! CalendarUtils::checkDate((int) $parts[1], (int) $parts[2], (int) $parts[3])) {
                $fail('تاریخ شمسی را با قالب سال/ماه/روز وارد کنید.');
            }
        };

        return [
            'complex_id' => 'nullable|integer',
            'period_year' => 'nullable|required_with:period_month|integer|between:1,3177',
            'period_month' => 'nullable|required_with:period_year|integer|between:1,12',
            'unit_number' => 'nullable|integer',
            'type' => 'nullable|in:charge,cost',
            'status' => 'nullable|in:paid',
            'paid_from' => ['nullable', $date],
            'paid_to' => ['nullable', $date],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->filled(['paid_from', 'paid_to']) && ! $validator->errors()->hasAny(['paid_from', 'paid_to'])
                && $this->input('paid_from') > $this->input('paid_to')) {
                $validator->errors()->add('paid_to', 'تاریخ پایان نباید قبل از تاریخ شروع باشد.');
            }
        });
    }
}
