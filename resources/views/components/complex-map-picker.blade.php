@props(['fieldId', 'errorBag', 'latitude' => null, 'longitude' => null])

<div class="map-picker" data-coordinate-picker>
    <x-complex-map :picker="true" />
    <div class="map-picker-fields">
        @foreach (['latitude' => ['عرض جغرافیایی', $latitude], 'longitude' => ['طول جغرافیایی', $longitude]] as $field => [$label, $value])
            @php($id = $fieldId.'-'.$field)
            <div>
                <label class="field-label" for="{{ $id }}">{{ $label }}</label>
                <input type="text" inputmode="decimal" dir="ltr" id="{{ $id }}" name="{{ $field }}"
                    value="{{ is_scalar(old($field, $value)) ? old($field, $value) : '' }}"
                    @error($field, $errorBag) aria-invalid="true" aria-describedby="{{ $id }}-error" @enderror>
                @error($field, $errorBag)
                    <span class="field-error" id="{{ $id }}-error">{{ $message }}</span>
                @enderror
            </div>
        @endforeach
    </div>
    <div class="map-picker-actions">
        <button type="button" class="action" data-map-locate>استفاده از موقعیت من</button>
        <button type="button" class="action" data-map-clear>پاک کردن موقعیت</button>
    </div>
</div>
