<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ __('direction') }}">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>پنل کاربری</title>
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('assets/images/favIcon/apartment.ico') }}" />









    <x-page-assets entry="resources/js/building.js" />
</head>

<body>
    <div class="app-container">
        @include('building.header')
        <div class="app-content">
            <x-toasts />
            <div data-drawer-overlay hidden></div>
            @include('building.sidebar')
            @include('building.main')
            @include('building.notification')
            @include('building.poll')
            @include('building.cost')
            @include('building.charge')
            @include('building.payments')
            @include('building.group')
            @if ($isManager)
                @include('building.setting')
            @endif
        </div>
    </div>
    @php
        $initialPanel = ['home' => 'home', 'setting' => 'setting', 'notification' => 'notification', 'poll' => 'poll', 'cost' => 'cost', 'charge' => 'charge', 'paymentInfo' => 'paymentInfo'][session('page') ?? 'home'] ?? 'home';
        if ($isManager && request()->query('tab') === 'setting') $initialPanel = 'setting';
        if ($errors->hasBag('complexLocation')) $initialPanel = 'setting';
        elseif ($errors->hasBag('notifRegister')) $initialPanel = 'notification';
        elseif ($errors->hasBag('pollCreate')) $initialPanel = 'poll';
        elseif ($errors->hasBag('incidentalCost') || $errors->hasBag('incidentalPayCost')) $initialPanel = 'cost';
        elseif ($errors->hasBag('buildingCharge') || $errors->hasBag('buildingPayCharge')) $initialPanel = 'charge';
    @endphp
    <script id="building-config" type="application/json">{!! json_encode([
        'connection' => config('broadcasting.default'),
        'key' => config('broadcasting.connections.pusher.key'),
        'cluster' => config('broadcasting.connections.pusher.options.cluster'),
        'broadcastAuth' => url('/broadcasting/auth'),
        'complexId' => $complex->id,
        'userId' => $userLogInId,
        'chatStorage' => asset('storage/chats'),
        'initialPanel' => $initialPanel,
    ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
</body>
</html>
