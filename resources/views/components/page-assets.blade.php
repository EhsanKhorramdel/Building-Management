@props(['entry' => null])

<meta name="theme-color" content="#146457">
<link rel="manifest" href="{{ asset('app.webmanifest') }}">
@vite(array_filter(['resources/css/app.css', 'resources/js/app.js', $entry]))
