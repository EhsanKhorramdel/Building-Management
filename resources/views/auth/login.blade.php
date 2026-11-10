<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ __('direction') }}">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>ورود به سایت</title>


    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('assets/images/favIcon/apartment.ico') }}" />


    <x-page-assets />
</head>

<body class="auth-page">
    <x-toasts />
    <x-theme-button class="page-theme" />
    <div class="auth-shell">
        <div class="auth-card">
            <h2 class="text-2xl font-semibold mb-6 text-center">ورود به سایت</h2>





            <form method="POST" action="{{ route('login') }}">
                @csrf

                <div class="relative">
                    <label class="block text-gray-700 text-sm mb-2" for="email">ایمیل
                        :</label>
                    <div class="relative">
                        <input
                            class="appearance-none border rounded w-full py-2 px-3 text-gray-700 mb-3 leading-tight focus:outline-none focus:shadow-outline"
                            id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                            autocomplete="username" placeholder="ایمیل خود را وارد کنید" @error('email') aria-invalid="true" aria-describedby="email-error" @enderror />@error('email')<span class="field-error" id="email-error">{{ $message }}</span>@enderror
                    </div>
                </div>
                <div class="relative">
                    <label class="block text-gray-700 text-sm mb-2" for="password">کلمه عبور
                        :</label>
                    <div class="relative">
                        <input
                            class="appearance-none border rounded w-full py-2 px-3 text-gray-700 mb-3 leading-tight focus:outline-none focus:shadow-outline"
                            id="password" type="password" data-password-input name="password" autocomplete="current-password"
                            placeholder="کلمه عبور خود را وارد کنید" @error('password') aria-invalid="true" aria-describedby="password-error" @enderror />@error('password')<span class="field-error" id="password-error">{{ $message }}</span>@enderror
<button type="button" class="password-toggle" data-password-toggle="password" aria-label="نمایش کلمه عبور" aria-pressed="false"><x-icon name="eye" /></button>
                    </div>
                </div>
                <div class="block mb-2 mt-3">
                    <label for="remember_me" class="inline-flex min-h-10 items-center">
                        <input id="remember_me" type="checkbox"
                            class="rounded dark:bg-gray-900 border-gray-300 dark:border-gray-700 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800 "
                            name="remember">
                        <span class="mr-2 text-sm text-gray-600 dark:text-gray-400">{{ __('Remember me') }}</span>
                    </label>
                </div>


                <div class="flex items-center justify-between">
                    <button
                        class="bg-blue-700 hover:bg-blue-800 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline mt-2"
                        type="submit">
                        {{ __('Log in') }}
                    </button>
                    @if (Route::has('password.request'))
                        <a class="inline-block align-baseline font-bold text-sm text-blue-700 hover:text-blue-800 mt-3"
                            href="{{ route('password.request') }}">
                            {{ __('Forgot your password?') }}
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>


</body>

</html>
