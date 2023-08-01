<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ __('direction') }}">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>ثبت نام</title>
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
            <h2 class="text-2xl font-semibold mb-6 text-center">ثبت نام</h2>

            @if ($errors->any())
                <div id="messageBox" class="mb-2 p-4 rounded bg-red-200 animate__animated animate__shakeX">
                    <ul class=" text-sm text-red-600 dark:text-red-400 space-y-1">
                        @foreach ($errors->all() as $error)
                            <li class="text-center">{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif



            <form method="POST" action="{{ route('register') }}">
                @csrf
                <div>
                    <label class="block text-gray-700 text-sm mb-2" for="name" :value="__('Name')"">نام و نام
                        خانوادگی :
                    </label>
                    <input
                        class="appearance-none border rounded w-full py-2 px-3 mb-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                        id="name" type="text" name="name" :value="old('name')" autofocus
                        autocomplete="name" placeholder="نام و نام خانوادگی خود را وارد کنید" />
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
                <div class="relative">
                    <label class="block text-gray-700 text-sm mb-2" for="password" :value="__('Password')"">کلمه عبور
                        :</label>
                    <div class="relative">
                        <input
                            class="appearance-none border rounded w-full py-2 px-3 text-gray-700 mb-3 leading-tight focus:outline-none focus:shadow-outline"
                            id="password" type="password" name="password" autocomplete="new-password"
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
                <div class="relative mb-4">
                    <label class="block text-gray-700 text-sm mb-2" for="password_confirmation"
                        :value="__('Confirm Password')">تکرار کلمه عبور :</label>
                    <div class="relative">
                        <input
                            class="appearance-none border rounded w-full py-2 px-3 text-gray-700 mb-3 leading-tight focus:outline-none focus:shadow-outline"
                            id="password_confirmation" type="password" name="password_confirmation"
                            autocomplete="new-password" placeholder="کلمه عبور خود را مجدد وارد کنید" />
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

                <div class="flex items-center justify-between">
                    <button
                        class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline"
                        type="submit">
                        {{ __('Register') }}
                    </button>
                    <a class="inline-block align-baseline font-bold text-sm text-blue-500 hover:text-blue-800"
                        href="{{ route('login') }}">
                        {{ __('Already registered?') }}
                    </a>
                </div>
            </form>
        </div>
    </div>
    <script src="{{ asset('js/signUp-logIn.js') }}"></script>

</body>

</html>
