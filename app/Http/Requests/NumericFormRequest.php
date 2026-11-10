<?php

namespace App\Http\Requests;

use App\Support\TypedInput;
use Illuminate\Foundation\Http\FormRequest;

abstract class NumericFormRequest extends FormRequest
{
    protected const INTEGERS = [];

    protected const DECIMALS = [];

    protected const IDENTIFIERS = [];

    protected function prepareForValidation(): void
    {
        foreach ($this->only(array_merge(static::INTEGERS, static::DECIMALS, static::IDENTIFIERS)) as $key => $value) {
            if (is_int($value) && in_array($key, static::IDENTIFIERS, true)) {
                $value = (string) $value;
            }
            $this->merge([$key => TypedInput::normalize($value)]);
        }
    }

    public function validated($key = null, $default = null)
    {
        $data = parent::validated();

        foreach (static::INTEGERS as $field) {
            if (isset($data[$field])) {
                $data[$field] = TypedInput::int($data[$field]);
            }
        }

        foreach (static::DECIMALS as $field) {
            if (array_key_exists($field, $data)) {
                $data[$field] = TypedInput::nullableFloat($data[$field]);
            }
        }

        return data_get($data, $key, $default);
    }
}
