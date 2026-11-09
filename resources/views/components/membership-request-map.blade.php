@props(['membershipRequest'])

@if ($membershipRequest->latitude !== null && $membershipRequest->longitude !== null)
    <details class="my-2">
        <summary class="action">نمایش موقعیت ثبت‌شده روی نقشه</summary>
        <div class="mt-2">
            <x-complex-map :latitude="$membershipRequest->latitude" :longitude="$membershipRequest->longitude" />
        </div>
    </details>
@endif
