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
            <h2 class="text-2xl font-semibold mb-6 text-center">ورود به سایت</h2>

            @if ($errors->any())
                <div id="messageBox" class="mb-2 p-4 rounded bg-red-200 animate__animated animate__shakeX">
                    <ul class=" text-sm text-red-600 dark:text-red-400 space-y-1">
                        @foreach ($errors->all() as $error)
                            <li class="text-center">{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <x-auth-session-status class="mb-4" :status="session('status')" />

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <div class="relative">
                    <label class="block text-gray-700 text-sm mb-2" for="email" :value="__('Email')">ایمیل
                        :</label>
                    <div class="relative">
                        <input
                            class="appearance-none border rounded w-full py-2 px-3 text-gray-700 mb-3 leading-tight focus:outline-none focus:shadow-outline"
                            id="email" type="email" name="email" :value="old('email')" required autofocus
                            autocomplete="username" placeholder="ایمیل خود را وارد کنید" />
                    </div>
                </div>
                <div class="relative">
                    <label class="block text-gray-700 text-sm mb-2" for="password" :value="__('Password')"">کلمه عبور
                        :</label>
                    <div class="relative">
                        <input
                            class="appearance-none border rounded w-full py-2 px-3 text-gray-700 mb-3 leading-tight focus:outline-none focus:shadow-outline"
                            id="password" type="password" name="password" autocomplete="current-password"
                            placeholder="کلمه عبور خود را وارد کنید" />
                        <img src="{{ asset('assets/images/signUp-logIn/eye-svgrepo-com.svg') }}"
                            class="w-6 h-6 absolute left-3 top-2 toggle-password cursor-pointer toggle-password-show"
                            style="
                  filter: invert(99%) sepia(4%) saturate(1212%)
                    hue-rotate(175deg) brightness(80%) contrast(95%);
                " />
                        <img src="{{ asset('assets/images/signUp-logIn/eye-off-svgrepo-com.svg') }}"
                            class="w-6 h-6 absolute left-3 top-2 toggle-password cursor-pointer toggle-password-hide hidden"
                            style="
                  filter: invert(99%) sepia(4%) saturate(1212%)
                    hue-rotate(175deg) brightness(80%) contrast(95%);
                " />
                    </div>
                </div>
                <div class="block mb-2 mt-3">
                    <label for="remember_me" class="inline-flex items-center">
                        <input id="remember_me" type="checkbox"
                            class="rounded dark:bg-gray-900 border-gray-300 dark:border-gray-700 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800 "
                            name="remember">
                        <span class="mr-2 text-sm text-gray-600 dark:text-gray-400">{{ __('Remember me') }}</span>
                    </label>
                </div>


                <div class="flex items-center justify-between">
                    <button
                        class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline mt-2"
                        type="submit">
                        {{ __('Log in') }}
                    </button>
                    @if (Route::has('password.request'))
                        <a class="inline-block align-baseline font-bold text-sm text-blue-500 hover:text-blue-800 mt-3"
                            href="{{ route('password.request') }}">
                            {{ __('Forgot your password?') }}
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>
    <script src="{{ asset('js/signUp-logIn.js') }}"></script>

</body>

</html>
