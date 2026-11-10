<?php

namespace App\Http\Requests;

class UnitStoreRequest extends NumericFormRequest
{
    protected const INTEGERS = ['complexId', 'unitNumber', 'floorNumber'];

    protected const IDENTIFIERS = ['zipCode'];

    public function rules(): array
    {
        return [
            'complexId' => 'nullable|integer',
            'unitNumber' => 'nullable|integer',
            'zipCode' => 'nullable',
            'floorNumber' => 'nullable|integer',
        ];
    }
}
