<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UnitStoreRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'complexId' => 'nullable',
            'unitNumber' => 'nullable',
            'zipCode' => 'nullable',
            'floorNumber' => 'nullable',
        ];
    }
}
