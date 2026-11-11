<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class PollUpdateRequest extends FormRequest
{
    protected $errorBag = 'pollEdit';

    protected function failedValidation(Validator $validator)
    {
        session()->flash('page', 'poll');
        session()->flash('edit_poll_id', $this->route('poll')->id);
        parent::failedValidation($validator);
    }

    public function rules(): array
    {
        return ['title' => 'nullable|string|max:255', 'question' => 'required|string|max:2000', 'end_date' => 'required|date|after:now'];
    }

    public function messages(): array
    {
        return ['question.required' => 'متن سوال را وارد کنید.', 'end_date.*' => 'زمان پایان باید تاریخی معتبر در آینده باشد.'];
    }
}
