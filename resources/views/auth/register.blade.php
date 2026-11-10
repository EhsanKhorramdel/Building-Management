<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ __('direction') }}">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>ثبت نام</title>


    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('assets/images/favIcon/apartment.ico') }}" />


    <x-page-assets />
</head>

<body class="auth-page">
    <x-toasts />
    <x-theme-button class="page-theme" />
    <div class="auth-shell">
        <div class="auth-card">
            <h2 class="text-2xl font-semibold mb-6 text-center">ثبت نام</h2>





            <form method="POST" action="{{ route('register') }}">
                @csrf
                <div>
                    <label class="block text-gray-700 text-sm mb-2" for="name">نام و نام
                        خانوادگی :
                    </label>
                    <input
                        class="appearance-none border rounded w-full py-2 px-3 mb-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                        id="name" type="text" name="name" value="{{ old('name') }}" autofocus
                        autocomplete="name" placeholder="نام و نام خانوادگی خود را وارد کنید" @error('name') aria-invalid="true" aria-describedby="name-error" @enderror />@error('name')<span class="field-error" id="name-error">{{ $message }}</span>@enderror
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
                <div class="relative">
                    <label class="block text-gray-700 text-sm mb-2" for="password">کلمه عبور
                        :</label>
                    <div class="relative">
                        <input
                            class="appearance-none border rounded w-full py-2 px-3 text-gray-700 mb-3 leading-tight focus:outline-none focus:shadow-outline"
                            id="password" type="password" data-password-input name="password" autocomplete="new-password"
                            placeholder="کلمه عبور خود را وارد کنید" @error('password') aria-invalid="true" aria-describedby="password-error" @enderror />@error('password')<span class="field-error" id="password-error">{{ $message }}</span>@enderror
<button type="button" class="password-toggle" data-password-toggle="password" aria-label="نمایش کلمه عبور" aria-pressed="false"><x-icon name="eye" /></button>
                    </div>
                </div>
                <div class="relative mb-4">
                    <label class="block text-gray-700 text-sm mb-2" for="password_confirmation"
                       >تکرار کلمه عبور :</label>
                    <div class="relative">
                        <input
                            class="appearance-none border rounded w-full py-2 px-3 text-gray-700 mb-3 leading-tight focus:outline-none focus:shadow-outline"
                            id="password_confirmation" type="password" data-password-input name="password_confirmation"
                            autocomplete="new-password" placeholder="کلمه عبور خود را مجدد وارد کنید" @error('password_confirmation') aria-invalid="true" aria-describedby="password_confirmation-error" @enderror />@error('password_confirmation')<span class="field-error" id="password_confirmation-error">{{ $message }}</span>@enderror
<button type="button" class="password-toggle" data-password-toggle="password_confirmation" aria-label="نمایش کلمه عبور" aria-pressed="false"><x-icon name="eye" /></button>
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <button
                        class="bg-blue-700 hover:bg-blue-800 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline"
                        type="submit">
                        {{ __('Register') }}
                    </button>
                    <a class="inline-block align-baseline font-bold text-sm text-blue-700 hover:text-blue-800"
                        href="{{ route('login') }}">
                        {{ __('Already registered?') }}
                    </a>
                </div>
            </form>
        </div>
    </div>


</body>

</html>
