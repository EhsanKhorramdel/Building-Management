<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UnitUpdateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'complexId' => 'nullable',
            'currentUnitNumber' => 'nullable',
            'newUnitNumber' => 'nullable',
            'zipCode' => 'nullable',
            'floorNumber' => 'nullable',
        ];
    }
}
