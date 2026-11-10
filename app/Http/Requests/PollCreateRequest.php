<?php

namespace App\Http\Requests;

class PollCreateRequest extends NumericFormRequest
{
    protected const INTEGERS = ['endMinutes', 'endHours', 'endDays'];

    protected $errorBag = 'pollCreate';

    public function rules(): array
    {
        $rules = [
            'title' => 'nullable|string',
            'question' => 'required|string',
            'endMinutes' => 'nullable|integer|min:0',
            'endHours' => 'nullable|integer|min:0',
            'endDays' => 'nullable|integer|min:0',
            'required_at_least_one' => 'required_without_all:endMinutes,endHours,endDays',
        ];
        foreach ($this->keys() as $key) {
            if (preg_match('/^option[0-9]+$/', $key)) {
                $rules[$key] = 'nullable';
            }
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'title.string' => 'فیلد عنوان باید یک رشته باشد.',
            'question.required' => 'فیلد سوال اجباری است.',
            'question.string' => 'فیلد سوال باید یک رشته باشد.',
            'endMinutes.integer' => 'فیلد دقیقه پایان باید یک عدد صحیح باشد.',
            'endMinutes.min' => 'فیلد دقیقه پایان باید بزرگتر یا مساوی ۰ باشد.',
            'endHours.integer' => 'فیلد ساعت پایان باید یک عدد صحیح باشد.',
            'endHours.min' => 'فیلد ساعت پایان باید بزرگتر یا مساوی ۰ باشد.',
            'endDays.integer' => 'فیلد روز پایان باید یک عدد صحیح باشد.',
            'endDays.min' => 'فیلد روز پایان باید بزرگتر یا مساوی ۰ باشد.',
            'required_at_least_one' => 'حداقل یکی از فیلدهای زمان پایان باید پر شود.',
        ];
    }
}
