@props(['close' => false])

@if ($close)
    <button type="button" class="icon-button self-end" data-close-drawer aria-label="بستن منو">
        <x-icon name="close" />
    </button>
@else
    <button type="button" class="icon-button" data-open-drawer aria-controls="app-drawer" aria-expanded="false" aria-label="باز کردن منو">
        <x-icon name="menu" />
    </button>
@endif
