<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ __('direction') }}">

<head>
    <meta charset="UTF-8" />
    <title>پنل کاربری</title>
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('assets/images/favIcon/apartment.ico') }}" />
    <link rel="stylesheet" href="{{ asset('css/fonts.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/dashboard/normalize.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/dashboard/style.css') }}" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.15/dist/tailwind.min.css" />
    <link href="https://unpkg.com/tailwindcss@0.3.0/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.4/Chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/randomcolor@0.6.2/randomColor.min.js"></script>
    <script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
</head>

<body>
    <div class="app-container">
        @include('building.header')
        <div class="app-content">
            @include('building.alert')
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
    <p class="hidden isManager">{{ $isManager }}</p>
    <p class="hidden userLogInId">{{ $userLogInId }}</p>
    <script src="{{ asset('js/building.js') }}"></script>
    <script>
        @switch(session('page'))
            @case('home')
            handleClick('home-window');
            @break

            @case('setting')
            handleClick('setting-window');
            @break

            @case('notification')
            handleClick('notification-window');
            @break

            @case('poll')
            handleClick('poll-window');
            @break

            @case('cost')
            handleClick('cost-window');
            @break

            @case('charge')
            handleClick('charge-window');
            @break

            @case('paymentInfo')
            handleClick('paymentInfo-window');
            @break
        @endswitch

        @if ($errors->hasBag('notifRegister'))
            handleClick('notification-window');
        @elseif ($errors->hasBag('pollCreate'))
            handleClick('poll-window');
        @elseif ($errors->hasBag('incidentalCost') || $errors->hasBag('incidentalPayCost'))
            handleClick('cost-window');
        @elseif ($errors->hasBag('buildingCharge') || $errors->hasBag('buildingPayCharge'))
            handleClick('charge-window');
        @endif
    </script>


</body>

</html>
