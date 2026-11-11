<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title')</title>
    <link rel="icon" href="{{ asset('assets/images/favIcon/apartment.ico') }}">
    <x-page-assets />
</head>
<body>
    <main class="payment-document">
        <header class="flex flex-wrap items-center justify-between gap-3 mb-4">
            <h1 class="text-xl font-bold">@yield('title')</h1>
            <div class="flex flex-wrap gap-2 print:hidden">
                @yield('actions')
                <x-theme-button />
            </div>
        </header>
        @yield('content')
    </main>
</body>
</html>
