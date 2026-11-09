@props(['complex' => null, 'state' => null])

<div class="building-box-wrapper">
    <article {{ $attributes->merge(['class' => 'building-box']) }} @if ($state) data-state="{{ $state }}" @endif
        @if ($complex) data-complex-id="{{ $complex->id }}" @endif>
        @if ($complex)
            <div class="building-box-header justify-between">
                <h2 class="box-content-header">{{ $complex->name }}</h2>
                <details class="more-wrapper">
                    <summary class="building-btn-more" aria-label="گزینه‌های واحد"><x-icon name="dots" /></summary>
                    <div class="dropdownDots">
                        <button type="button" class="open-add-unit-Btn">افزودن واحد</button>
                        <button type="button" class="open-Btn">ویرایش واحد</button>
                    </div>
                </details>
            </div>
            <p class="box-content-subheader">تعداد واحدها: {{ $complex->number_of_units }}</p>
            <p class="box-address-header">{{ $complex->address }}</p>
            <div class="building-box-footer">
                <a href="{{ route('building', $complex) }}">مشاهده</a>
            </div>
        @else
            {{ $slot }}
        @endif
    </article>
</div>
