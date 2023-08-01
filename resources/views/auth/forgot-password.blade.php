<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ __('direction') }}">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>ورود به سایت</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.15/dist/tailwind.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('assets/images/favIcon/apartment.ico') }}" />
    <link rel="stylesheet" href="{{ asset('css/fonts.css') }}" />
    <style>
        body {
            background-image: url('{{ asset('assets/images/signUp-logIn/backGround.svg') }}')
        }
    </style>
</head>

<body class="bg-gray-100">
    <div class="flex items-center justify-center min-h-screen">
        <div class="w-96 bg-white shadow-md rounded px-8 py-6">
            <h2 class="text-2xl font-semibold mb-6 text-center">بازیابی کلمه عبور</h2>

            @if ($errors->any())
                <div id="messageBox" class="mb-2 p-4 rounded bg-red-200 animate__animated animate__shakeX">
                    <ul class=" text-sm text-red-600 dark:text-red-400 space-y-1">
                        @foreach ($errors->all() as $error)
                            <li class="text-center">{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif


            <!-- Session Status -->
            <x-auth-session-status class="mb-4" :status="session('status')" />

            <form method="POST" action="{{ route('password.email') }}">
                @csrf
                <div class="mb-4 text-sm text-gray-600 dark:text-gray-400 text-center">
                    {{ __('Forgot your password? No problem. Just let us know your email address and we will email you a password reset link that will allow you to choose a new one.') }}
                </div>
                <div class="relative">
                    <label class="block text-gray-700 text-sm mb-2" for="email" :value="__('Email')">ایمیل
                        :</label>
                    <div class="relative">
                        <input
                            class="appearance-none border rounded w-full py-2 px-3 text-gray-700 mb-3 leading-tight focus:outline-none focus:shadow-outline"
                            id="email" type="email" name="email" :value="old('email')" autocomplete="username"
                            placeholder="ایمیل خود را وارد کنید" />
                    </div>
                </div>

                <button
                    class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 mt-2 rounded focus:outline-none focus:shadow-outline"
                    type="submit">
                    {{ __('Email Password Reset Link') }}
                </button>

            </form>
</body>

</html>
