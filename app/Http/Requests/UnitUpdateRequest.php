<?php

namespace App\Http\Requests;

class UnitUpdateRequest extends NumericFormRequest
{
    protected const INTEGERS = ['complexId', 'currentUnitNumber', 'newUnitNumber', 'floorNumber'];

    protected const IDENTIFIERS = ['zipCode'];

    public function rules(): array
    {
        return [
            'complexId' => 'nullable|integer',
            'currentUnitNumber' => 'nullable|integer',
            'newUnitNumber' => 'nullable|integer',
            'zipCode' => 'nullable',
            'floorNumber' => 'nullable|integer',
        ];
    }
}
