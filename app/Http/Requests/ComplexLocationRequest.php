<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ComplexLocationRequest extends FormRequest
{
    protected $errorBag = 'complexLocation';

    public function rules(): array
    {
        return [
            'latitude' => ['nullable', 'required_with:longitude', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'required_with:latitude', 'numeric', 'between:-180,180'],
        ];
    }

    public function messages(): array
    {
        return [
            'latitude.required_with' => 'وارد کردن عرض جغرافیایی همراه با طول جغرافیایی الزامی است.',
            'latitude.numeric' => 'عرض جغرافیایی باید عدد باشد.',
            'latitude.between' => 'عرض جغرافیایی باید بین ۹۰- و ۹۰ باشد.',
            'longitude.required_with' => 'وارد کردن طول جغرافیایی همراه با عرض جغرافیایی الزامی است.',
            'longitude.numeric' => 'طول جغرافیایی باید عدد باشد.',
            'longitude.between' => 'طول جغرافیایی باید بین ۱۸۰- و ۱۸۰ باشد.',
        ];
    }

    /**
     * An omitted pair leaves the saved location alone. A blank pair clears it.
     *
     * @return array{}|array{latitude: float|null, longitude: float|null}
     */
    public function coordinates(): array
    {
        $data = $this->safe()->only(['latitude', 'longitude']);

        if ($data === []) {
            return [];
        }

        return [
            'latitude' => isset($data['latitude']) ? round((float) $data['latitude'], 7) : null,
            'longitude' => isset($data['longitude']) ? round((float) $data['longitude'], 7) : null,
        ];
    }
}
