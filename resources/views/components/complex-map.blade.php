@props(['latitude' => null, 'longitude' => null, 'picker' => false])

<div class="complex-map" dir="ltr" data-complex-map
    data-tile-url="{{ config('map.tile_url') }}"
    data-tile-attribution="{{ config('map.tile_attribution') }}"
    data-latitude="{{ $latitude }}" data-longitude="{{ $longitude }}"
    role="region" aria-label="{{ $picker ? 'انتخاب موقعیت ساختمان روی نقشه' : 'موقعیت ساختمان روی نقشه' }}"></div>
