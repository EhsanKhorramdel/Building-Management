@props(['name', 'size' => 24])

<svg {{ $attributes->merge(['width' => $size, 'height' => $size]) }} viewBox="0 0 24 24" fill="none"
    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    @switch($name)
        @case('moon')
            <path d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z" />
            @break
        @case('close')
            <path d="m6 6 12 12M18 6 6 18" />
            @break
        @case('menu')
            <path d="M4 6h16M4 12h16M4 18h16" />
            @break
        @case('list')
            <path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01" />
            @break
        @case('grid')
            <rect x="3" y="3" width="7" height="7" />
            <rect x="14" y="3" width="7" height="7" />
            <rect x="14" y="14" width="7" height="7" />
            <rect x="3" y="14" width="7" height="7" />
            @break
        @case('plus')
            <path d="M12 5v14M5 12h14" />
            @break
        @case('eye')
            <path d="M2 12s4-6 10-6 10 6 10 6-4 6-10 6-10-6-10-6Z" />
            <circle cx="12" cy="12" r="2.5" />
            @break
        @case('dots')
            <circle cx="12" cy="5" r="1" />
            <circle cx="12" cy="12" r="1" />
            <circle cx="12" cy="19" r="1" />
            @break
    @endswitch
</svg>
