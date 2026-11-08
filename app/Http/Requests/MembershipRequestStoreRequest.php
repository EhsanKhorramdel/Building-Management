<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MembershipRequestStoreRequest extends FormRequest
{
    protected $errorBag = 'registerBuilding';

    public function rules(): array
    {
        return [
            'name' => 'required|string',
            'address' => 'required|string',
            'floors' => 'required|integer|min:1',
            'units' => 'required|integer|min:1',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'فیلد نام الزامی است.',
            'name.string' => 'فیلد نام باید یک رشته باشد.',

            'address.required' => 'فیلد آدرس الزامی است.',
            'address.string' => 'فیلد آدرس باید یک رشته باشد.',

            'floors.required' => 'فیلد تعداد طبقات الزامی است.',
            'floors.integer' => 'فیلد تعداد طبقات باید عدد صحیح باشد.',
            'floors.min' => 'تعداد طبقات باید بزرگتر یا مساوی ۱ باشد.',

            'units.required' => 'فیلد تعداد واحدها الزامی است.',
            'units.integer' => 'فیلد تعداد واحدها باید عدد صحیح باشد.',
            'units.min' => 'تعداد واحدها باید بزرگتر یا مساوی ۱ باشد.',
        ];
    }
}
