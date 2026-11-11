<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AnnouncementSearchRequest extends FormRequest
{
    public function rules(): array
    {
        return ['q' => 'nullable|string|max:200', 'state' => 'required|in:active,archived,all'];
    }
}
