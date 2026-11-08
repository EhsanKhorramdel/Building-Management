<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AnnouncementRequest extends FormRequest
{
    protected $errorBag = 'notifRegister';

    public function rules(): array
    {
        return [
            'notifPicture' => 'nullable|file|mimes:gif,jpg,webp,png|max:5120',
            'notifText' => 'nullable',
            'required_at_least_one' => 'required_without_all:notifPicture,notifText',
        ];
    }

    public function messages(): array
    {
        return [
            'notifPicture.required' => 'وارد کردن تصویر اعلان الزامی است.',
            'notifPicture.file' => 'فایل تصویر اعلان باید باشد.',
            'notifPicture.mimes' => 'فرمت تصویر اعلان باید یکی از gif، jpg، webp، png باشد.',
            'notifPicture.max' => 'حجم تصویر اعلان نباید بیشتر از 5 مگابایت باشد.',
            'notifText.required' => 'وارد کردن متن اعلان الزامی است.',
            'required_at_least_one.required_without_all' => 'حداقل یکی از فیلدها باید پر شود.',
        ];
    }
}
