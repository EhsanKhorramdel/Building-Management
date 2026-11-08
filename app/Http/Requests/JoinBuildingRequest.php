<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class JoinBuildingRequest extends FormRequest
{
    public function rules(): array
    {
        return ['link' => $this->filled('link') ? 'string' : 'nullable'];
    }
}
