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
            <h2 class="text-2xl font-semibold mb-6 text-center">بازیابی کلمه عبور</h2>




            <!-- Session Status -->


            <form method="POST" action="{{ route('password.email') }}">
                @csrf
                <div class="mb-4 text-sm text-gray-600 dark:text-gray-400 text-center">
                    {{ __('Forgot your password? No problem. Just let us know your email address and we will email you a password reset link that will allow you to choose a new one.') }}
                </div>
                <div class="relative">
                    <label class="block text-gray-700 text-sm mb-2" for="email">ایمیل
                        :</label>
                    <div class="relative">
                        <input
                            class="appearance-none border rounded w-full py-2 px-3 text-gray-700 mb-3 leading-tight focus:outline-none focus:shadow-outline"
                            id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="username"
                            placeholder="ایمیل خود را وارد کنید" @error('email') aria-invalid="true" aria-describedby="email-error" @enderror />@error('email')<span class="field-error" id="email-error">{{ $message }}</span>@enderror
                    </div>
                </div>

                <button
                    class="bg-blue-700 hover:bg-blue-800 text-white font-bold py-2 px-4 mt-2 rounded focus:outline-none focus:shadow-outline"
                    type="submit">
                    {{ __('Email Password Reset Link') }}
                </button>

            </form>
</body>

</html>
