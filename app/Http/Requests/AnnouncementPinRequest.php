<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AnnouncementPinRequest extends FormRequest
{
    public function rules(): array
    {
        return ['pinned' => 'required|boolean'];
    }
}
