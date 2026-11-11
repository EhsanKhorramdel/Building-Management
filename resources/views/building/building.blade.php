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
        $availablePanels = \App\Http\Controllers\AccountPreferenceController::panels($isManager);
        $requestedPanel = request()->query('tab');
        if (in_array($requestedPanel, $availablePanels, true)) $initialPanel = $requestedPanel;
        elseif (!session()->has('page') && in_array($savedPanel, $availablePanels, true)) $initialPanel = $savedPanel;
        if ($errors->hasBag('complexLocation')) $initialPanel = 'setting';
        elseif ($errors->hasBag('notifRegister')) $initialPanel = 'notification';
        elseif ($errors->hasBag('pollCreate')) $initialPanel = 'poll';
        elseif ($errors->hasBag('incidentalCost') || $errors->hasBag('incidentalPayCost')) $initialPanel = 'cost';
        elseif ($errors->hasBag('buildingCharge') || $errors->hasBag('buildingPayCharge')) $initialPanel = 'charge';
        if (!in_array($initialPanel, $availablePanels, true)) $initialPanel = 'home';
    @endphp
    <script id="building-config" type="application/json">{!! json_encode([
        'connection' => config('broadcasting.default'),
        'key' => config('broadcasting.connections.pusher.key'),
        'cluster' => config('broadcasting.connections.pusher.options.cluster'),
        'broadcastAuth' => url('/broadcasting/auth'),
        'complexId' => $complex->id,
        'userId' => $userLogInId,
        'userName' => auth()->user()->name,
        'canPin' => $isManager,
        'reactionPalette' => \App\Models\MessageReaction::PALETTE,
        'initialPanel' => $initialPanel,
        'initialLayout' => $savedLayout ?? 'gridView',
        'persistInitialPanel' => $initialPanel === $requestedPanel && in_array($requestedPanel, $availablePanels, true),
        'preferencesUrl' => route('building.preferences', $complex),
    ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
</body>
</html>
