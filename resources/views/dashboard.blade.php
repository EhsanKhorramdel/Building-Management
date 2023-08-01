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
</head>

<body>
    <div class="app-container">
        <div class="app-header">
            <div class="app-header-left">
                <span class="app-icon"></span>
                <p class="app-name">پنل کاربری</p>
            </div>
            <div class="app-header-right">
                <button class="mode-switch active" title="تغییر تم صفحه">
                    <svg class="moon" fill="none" stroke="currentColor" stroke-linecap="round"
                        stroke-linejoin="round" stroke-width="2" width="24" height="24" viewBox="0 0 24 24">
                        <defs></defs>
                        <path d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"></path>
                    </svg>
                </button>

                <button class="profile-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="#000000"
                        viewBox="0 0 256 256">
                        <path
                            d="M230.92,212c-15.23-26.33-38.7-45.21-66.09-54.16a72,72,0,1,0-73.66,0C63.78,166.78,40.31,185.66,25.08,212a8,8,0,1,0,13.85,8c18.84-32.56,52.14-52,89.07-52s70.23,19.44,89.07,52a8,8,0,1,0,13.85-8ZM72,96a56,56,0,1,1,56,56A56.06,56.06,0,0,1,72,96Z">
                        </path>
                    </svg>
                    <span>{{ $userName }}</span>
                </button>
            </div>

        </div>
        <div class="app-content">
            @if (session('status') === 'error')
                <div class="alert-wrapper">
                    <div class="flex items-center p-5 text-red-800 rounded-xl bg-red-50 bg-gray-800 text-red-400 gap-2.5 text-center"
                        role="alert" id="myAlert">
                        <svg class="flex-shrink-0 inline w-8 h-8 mr-3" aria-hidden="true"
                            xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                            <path
                                d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z" />
                        </svg>
                        <span class="sr-only">Info</span>
                        <div>
                            <span class="font-medium">{{ session('message') }}</span>
                        </div>
                        <button type="button"
                            class="flex-shrink-0 ml-2 text-red-600 hover:text-red-800 focus:outline-none"
                            aria-label="بستن" onclick="closeAlert()">
                            <svg class="w-4 h-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
            @else
                @if (session('status') === 'success')
                    <div class="alert-wrapper">
                        <div class="flex items-center p-5 text-green-800 rounded-xl bg-green-50 bg-gray-800 text-green-400 gap-2.5 text-center"
                            role="alert" id="myAlert">
                            <svg class="flex-shrink-0 inline w-8 h-8 mr-3" aria-hidden="true"
                                xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                <path
                                    d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z" />
                            </svg>
                            <span class="sr-only">Info</span>
                            <div>
                                <span class="font-medium">{{ session('message') }}</span>
                            </div>
                            <button type="button"
                                class="flex-shrink-0 ml-2 text-green-600 hover:text-green-800 focus:outline-none"
                                aria-label="بستن" onclick="closeAlert()">
                                <svg class="w-4 h-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>
                @endif
            @endif
            <div class="app-sidebar">
                <a class="app-sidebar-link active" title="خانه" id="home" onclick="handleClick('home-window')">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" class="feather feather-home">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
                        <polyline points="9 22 9 12 15 12 15 22" />
                    </svg>
                </a>
                <a class="app-sidebar-link" title="ثبت ساختمان" id="register"
                    onclick="handleClick('register-window')">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor"
                        class="bi bi-building" viewBox="0 0 16 16">
                        <path fill-rule="evenodd"
                            d="M14.763.075A.5.5 0 0 1 15 .5v15a.5.5 0 0 1-.5.5h-3a.5.5 0 0 1-.5-.5V14h-1v1.5a.5.5 0 0 1-.5.5h-9a.5.5 0 0 1-.5-.5V10a.5.5 0 0 1 .342-.474L6 7.64V4.5a.5.5 0 0 1 .276-.447l8-4a.5.5 0 0 1 .487.022zM6 8.694 1 10.36V15h5V8.694zM7 15h2v-1.5a.5.5 0 0 1 .5-.5h2a.5.5 0 0 1 .5.5V15h2V1.309l-7 3.5V15z" />
                        <path
                            d="M2 11h1v1H2v-1zm2 0h1v1H4v-1zm-2 2h1v1H2v-1zm2 0h1v1H4v-1zm4-4h1v1H8V9zm2 0h1v1h-1V9zm-2 2h1v1H8v-1zm2 0h1v1h-1v-1zm2-2h1v1h-1V9zm0 2h1v1h-1v-1zM8 7h1v1H8V7zm2 0h1v1h-1V7zm2 0h1v1h-1V7zM8 5h1v1H8V5zm2 0h1v1h-1V5zm2 0h1v1h-1V5zm0-2h1v1h-1V3z" />
                    </svg>
                </a>
                <a class="app-sidebar-link" title="تنظیمات" id="setting" onclick="handleClick('setting-window')">
                    <svg class="link-icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                        fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                        stroke-width="2" class="feather feather-settings" viewBox="0 0 24 24">
                        <defs />
                        <circle cx="12" cy="12" r="3" />
                        <path
                            d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-2 2 2 2 0 01-2-2v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83 0 2 2 0 010-2.83l.06-.06a1.65 1.65 0 00.33-1.82 1.65 1.65 0 00-1.51-1H3a2 2 0 01-2-2 2 2 0 012-2h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 010-2.83 2 2 0 012.83 0l.06.06a1.65 1.65 0 001.82.33H9a1.65 1.65 0 001-1.51V3a2 2 0 012-2 2 2 0 012 2v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 0 2 2 0 010 2.83l-.06.06a1.65 1.65 0 00-.33 1.82V9a1.65 1.65 0 001.51 1H21a2 2 0 012 2 2 2 0 01-2 2h-.09a1.65 1.65 0 00-1.51 1z" />
                    </svg>
                </a>
                <a href="{{ route('logout') }}" class="app-sidebar-link" title="خروج">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                        stroke="currentColor" width="28" height="28">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" />
                    </svg>
                </a>
            </div>
            <div class="buildings-section home-window">
                <div class="buildings-section-header">
                    <p>ساختمان های من</p>
                    <form method="POST" action="{{ URL::route('dashboard.store') }}" class="form-wrapper">
                        @csrf
                        <div class="search-wrapper add-wrapper">
                            <input class="search-input add-input" type="text" name="link"
                                placeholder="لینک ساختمان خود را وارد کنید" />
                            <button class="add-btn" type="submit" title="پیوستن به ساختمان جدید">
                                <svg class="btn-icon" xmlns="http://www.w3.org/2000/svg" width="16"
                                    height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="12" y1="5" x2="12" y2="19"></line>
                                    <line x1="5" y1="12" x2="19" y2="12"></line>
                                </svg>
                            </button>
                        </div>
                    </form>

                </div>
                <div class="buildings-section-line">
                    <div class="buildings-status">
                        <div class="item-status">
                            <span class="status-number">{{ $managerCount }}</span>
                            <span class="status-type">مدیر</span>
                        </div>
                        <div class="item-status">
                            <span class="status-number">{{ $residentCount }}</span>
                            <span class="status-type">ساکن</span>
                        </div>
                        <div class="item-status">
                            <span class="status-number">{{ $residentCount + $managerCount }}</span>
                            <span class="status-type">کل</span>
                        </div>
                    </div>
                    <div class="view-actions">
                        <button class="view-btn list-view" title="List View">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round" class="feather feather-list">
                                <line x1="8" y1="6" x2="21" y2="6" />
                                <line x1="8" y1="12" x2="21" y2="12" />
                                <line x1="8" y1="18" x2="21" y2="18" />
                                <line x1="3" y1="6" x2="3.01" y2="6" />
                                <line x1="3" y1="12" x2="3.01" y2="12" />
                                <line x1="3" y1="18" x2="3.01" y2="18" />
                            </svg>
                        </button>
                        <button class="view-btn grid-view active" title="Grid View">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round" class="feather feather-grid">
                                <rect x="3" y="3" width="7" height="7" />
                                <rect x="14" y="3" width="7" height="7" />
                                <rect x="14" y="14" width="7" height="7" />
                                <rect x="3" y="14" width="7" height="7" />
                            </svg>
                        </button>
                    </div>
                </div>
                <div class="building-boxes jsGridView">
                    @foreach ($allComplexes as $complex)
                        <div class="building-box-wrapper">
                            <div class="building-box" style="background-color: #fee4cb">
                                <div class="building-box-header">
                                    <div class="more-wrapper relative">
                                        <div
                                            class="dropdownDots z-10 bg-white divide-y divide-gray-100 rounded-lg shadow w-44 dark:bg-gray-700 dark:divide-gray-600 absolute hidden">
                                            <ul class="py-2 text-sm text-gray-700 dark:text-gray-200"
                                                aria-labelledby="dropdownMenuIconButton">
                                                <li>
                                                    <a
                                                        class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white open-add-unit-Btn">افزودن
                                                        واحد
                                                    </a>
                                                </li>
                                                <li>
                                                    <a
                                                        class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white open-Btn">ویرایش
                                                        واحد
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                        <button class="building-btn-more">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                class="feather feather-more-vertical">
                                                <circle cx="12" cy="12" r="1" />
                                                <circle cx="12" cy="5" r="1" />
                                                <circle cx="12" cy="19" r="1" />
                                            </svg>
                                        </button>

                                    </div>
                                </div>
                                <div class="building-box-content-header">
                                    <p class="hidden">{{ $complex->id }}</p>
                                    <p class="box-content-header">{{ $complex->name }}</p>
                                    <p class="box-content-subheader">{{ $complex->number_of_units }} </p>
                                </div>
                                <div class="box-address-wrapper">
                                    <p class="box-address-header">{{ $complex->address }}</p>
                                </div>
                                <div class="building-box-footer">
                                    <a href="{{ URL::route('building', $complex->id) }}">مشاهده</a>
                                </div>
                            </div>
                        </div>
                    @endforeach

                    <form class="register-form" method="POST" action="{{ URL::route('dashboard.addUnit') }}">
                        @csrf
                        <div
                            class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50 rounded-lg add-unit-modal hidden">
                            <div class="bg-white p-6 rounded shadow-md max-w-sm mx-auto">
                                <h3 class="text-xl font-semibold mb-4 text-center">
                                    افزودن واحد
                                </h3>
                                <div class="mb-4">
                                    <input id="addUnitComplexId"
                                        class="border border-gray-300 px-4 py-2 rounded w-full " type="hidden"
                                        name="complexId" />
                                </div>
                                <div class="mb-6">
                                    <input class="border border-gray-300 px-4 py-2 rounded w-full" type="text"
                                        placeholder="کد پستی" autocomplete="off" name="zipCode" required />
                                </div>
                                <div class="mb-6">
                                    <input class="border border-gray-300 px-4 py-2 rounded w-full" type="number"
                                        placeholder="شماره واحد" autocomplete="off" name="unitNumber" required />
                                </div>
                                <div class="mb-6">
                                    <input class="border border-gray-300 px-4 py-2 rounded w-full" type="number"
                                        placeholder="شماره طبقه" autocomplete="off" name="floorNumber" required />
                                </div>
                                <div class="flex">
                                    <button
                                        class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline"
                                        type="submit">
                                        ذخیره
                                    </button>
                                    <button
                                        class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline mr-2 close-add-unit-Btn"
                                        type="button">
                                        لغو
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>

                    <form class="register-form" method="POST" action="{{ URL::route('dashboard.unit') }}">
                        @csrf
                        <div
                            class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50 rounded-lg modal hidden">
                            <div class="bg-white p-6 rounded shadow-md max-w-sm mx-auto">
                                <h3 class="text-xl font-semibold mb-4 text-center">
                                    ویرایش مشخصات واحد
                                </h3>
                                <div class="mb-4">
                                    <input id="complexId" class="border border-gray-300 px-4 py-2 rounded w-full "
                                        type="hidden" name="complexId" />
                                </div>
                                <div class="mb-6">
                                    <input class="border border-gray-300 px-4 py-2 rounded w-full" type="number"
                                        placeholder="شماره فعلی واحد" autocomplete="off" name="currentUnitNumber"
                                        required />
                                </div>
                                <div class="mb-6">
                                    <input class="border border-gray-300 px-4 py-2 rounded w-full" type="number"
                                        placeholder="شماره جدید واحد" autocomplete="off" name="newUnitNumber"
                                        required />
                                </div>
                                <div class="mb-6">
                                    <input class="border border-gray-300 px-4 py-2 rounded w-full" type="text"
                                        placeholder="کد پستی" autocomplete="off" name="zipCode" required />
                                </div>
                                <div class="mb-6">
                                    <input class="border border-gray-300 px-4 py-2 rounded w-full" type="number"
                                        placeholder="شماره طبقه" autocomplete="off" name="floorNumber" required />
                                </div>
                                <div class="flex">
                                    <button
                                        class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline"
                                        type="submit">
                                        ذخیره
                                    </button>
                                    <button
                                        class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline mr-2 close-Btn"
                                        type="button">
                                        لغو
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <div class="register-section register-window hidden">

                <div class="buildings-section-header register-container">
                    <p>ثبت ساختمان در سیستم</p>
                </div>
                <div class="register">
                    @if ($errors->hasBag('registerBuilding'))
                        <div id="messageBox"
                            class="mb-2 mr-3 ml-3 p-4 rounded bg-red-200 animate__animated animate__shakeX">
                            <ul class=" text-sm text-red-600 dark:text-red-400 space-y-1">
                                @foreach ($errors->getBag('registerBuilding')->all() as $error)
                                    <li class="text-center">{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    <form class="register-form" method="POST" action="{{ URL::route('dashboard.register') }}">
                        @csrf
                        <div class="search-wrapper add-wrapper">
                            <input class="search-input add-input" type="text" name="name"
                                placeholder="نام ساختمان" />
                        </div>
                        <div class="search-wrapper add-wrapper">
                            <input class="search-input add-input" type="text" name="address"
                                placeholder="آدرس ساختمان" />
                        </div>
                        <div class="search-wrapper add-wrapper">
                            <input class="search-input add-input" type="number" name="floors" min="0"
                                placeholder="تعداد طبقات ساختمان" />
                        </div>
                        <div class="search-wrapper add-wrapper">
                            <input class="search-input add-input" type="number" name="units" min="0"
                                placeholder="تعداد واحدهای ساختمان" />
                        </div>
                        <button type="submit"
                            class="view-btn grid-view active w-1/3 sm:w-72 register-building-btn">ثبت</button>
                    </form>
                </div>
            </div>
            <div class="register-section setting-window">
                <div class="buildings-section-header register-container">
                    <p>بروزرسانی اطلاعات پروفایل کاربری</p>
                </div>
                <div class="register">
                    @if ($errors->any())
                        <div id="messageBox"
                            class="mb-2 mr-3 ml-3 p-4 rounded bg-red-200 animate__animated animate__shakeX">
                            <ul class=" text-sm text-red-600 dark:text-red-400 space-y-1">
                                @foreach ($errors->all() as $error)
                                    <li class="text-center">{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    <form class="register-form" method="POST" action="{{ route('profile.update') }}">
                        @csrf
                        @method('patch')

                        <div class="search-wrapper add-wrapper">
                            <input class="search-input add-input" type="text" name="name"
                                placeholder="نام و نام خانوادگی خود را وارد کنید" value="{{ Auth::user()->name }}"
                                required />
                        </div>
                        <div class="search-wrapper add-wrapper">
                            <input class="search-input add-input" type="email" name="email"
                                placeholder="ایمیل خود را وارد کنید" value="{{ Auth::user()->email }}" required />
                        </div>
                        <div class="search-wrapper add-wrapper">
                            <input class="search-input add-input" type="text" name="ncode" placeholder="کد ملی"
                                value="{{ Auth::user()->national_code }}" />
                        </div>
                        <div class="search-wrapper add-wrapper">
                            <input class="search-input add-input" type="text" name="phoneNumber"
                                placeholder="شماره تلفن" value="{{ Auth::user()->phone_number }}" />
                        </div>
                        <button type="submit" class="view-btn grid-view active">ثبت</button>
                    </form>
                </div>
            </div>
            <div class="register-section setting-window">
                <div class="buildings-section-header register-container">
                    <p>بروزرسانی کلمه عبور</p>
                </div>
                <div class="register">
                    @if ($errors->hasBag('updatePassword'))
                        <div id="messageBox"
                            class="mb-2 mr-3 ml-3 p-4 rounded bg-red-200 animate__animated animate__shakeX">
                            <ul class=" text-sm text-red-600 dark:text-red-400 space-y-1">
                                @foreach ($errors->getBag('updatePassword')->all() as $error)
                                    <li class="text-center">{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    <form class="register-form" method="POST" action="{{ route('password.update') }}">
                        @csrf
                        @method('put')

                        <div class="search-wrapper add-wrapper">
                            <input class="search-input add-input" id="current_password" name="current_password"
                                type="password" autocomplete="current-password" placeholder="کلمه عبور فعلی" />
                        </div>
                        <div class="search-wrapper add-wrapper">
                            <input class="search-input add-input" id="password" name="password" type="password"
                                placeholder="کلمه عبور جدید" />

                        </div>
                        <div class="search-wrapper add-wrapper">
                            <input class="search-input add-input" id="password_confirmation"
                                name="password_confirmation" type="password" placeholder="تکرار کلمه عبور جدید" />
                        </div>

                        <button type="submit" class="view-btn grid-view active">ثبت</button>

                </div>
                </form>
            </div>
        </div>
    </div>

    <script src="{{ asset('js/dashboard/script.js') }}"></script>
    <script>
        @if (session('page') === 'home')
            handleClick('home-window');
        @elseif (session('page') === 'setting' ||
                $errors->hasBag('updatePassword') ||
                $errors->hasBag('profileUpdate') ||
                $errors->any())
            handleClick('setting-window');
        @elseif (session('page') === 'register' || $errors->hasBag('registerBuilding'))
            handleClick('register-window');
        @endif
    </script>

</body>

</html>
