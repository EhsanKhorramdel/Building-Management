<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ __('direction') }}">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>پنل کاربری</title>
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('assets/images/favIcon/apartment.ico') }}" />




    <x-page-assets entry="resources/js/dashboard.js" />
</head>

@php
    $initialPanel = match (true) {
        session('page') === 'home' => 'home',
        session('page') === 'setting' || $errors->hasBag('updatePassword') || $errors->hasBag('profileUpdate') || $errors->any() && !$errors->hasBag('registerBuilding') => 'setting',
        session('page') === 'register' || $errors->hasBag('registerBuilding') => 'register',
        default => 'home',
    };
@endphp
<body data-initial-panel="{{ $initialPanel }}">
    <div class="app-container">
        <header class="app-header">
            <div class="app-header-left"><x-drawer-controls /><span class="app-icon" aria-hidden="true"></span><p class="app-name">پنل کاربری</p></div>
            <div class="app-header-right"><span class="profile-btn">{{ $userName }}</span><x-theme-button /></div>
        </header>
        <div class="app-content">
<x-toasts />
<div data-drawer-overlay hidden></div>
<nav class="app-sidebar" id="app-drawer" data-drawer aria-label="منوی پنل">
    <x-drawer-controls :close="true" />
                <button type="button" class="app-sidebar-link active" title="خانه" id="home" data-tab="home">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" class="feather feather-home">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
                        <polyline points="9 22 9 12 15 12 15 22" />
                    </svg>
                <span>خانه</span></button>
                <button type="button" class="app-sidebar-link" title="ثبت ساختمان" id="register" data-tab="register">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor"
                        class="bi bi-building" viewBox="0 0 16 16">
                        <path fill-rule="evenodd"
                            d="M14.763.075A.5.5 0 0 1 15 .5v15a.5.5 0 0 1-.5.5h-3a.5.5 0 0 1-.5-.5V14h-1v1.5a.5.5 0 0 1-.5.5h-9a.5.5 0 0 1-.5-.5V10a.5.5 0 0 1 .342-.474L6 7.64V4.5a.5.5 0 0 1 .276-.447l8-4a.5.5 0 0 1 .487.022zM6 8.694 1 10.36V15h5V8.694zM7 15h2v-1.5a.5.5 0 0 1 .5-.5h2a.5.5 0 0 1 .5.5V15h2V1.309l-7 3.5V15z" />
                        <path
                            d="M2 11h1v1H2v-1zm2 0h1v1H4v-1zm-2 2h1v1H2v-1zm2 0h1v1H4v-1zm4-4h1v1H8V9zm2 0h1v1h-1V9zm-2 2h1v1H8v-1zm2 0h1v1h-1v-1zm2-2h1v1h-1V9zm0 2h1v1h-1v-1zM8 7h1v1H8V7zm2 0h1v1h-1V7zm2 0h1v1h-1V7zM8 5h1v1H8V5zm2 0h1v1h-1V5zm2 0h1v1h-1V5zm0-2h1v1h-1V3z" />
                    </svg>
                <span>ثبت ساختمان</span></button>
                <button type="button" class="app-sidebar-link" title="تنظیمات" id="setting" data-tab="setting">
                    <svg class="link-icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                        fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                        stroke-width="2" class="feather feather-settings" viewBox="0 0 24 24">
                        <defs />
                        <circle cx="12" cy="12" r="3" />
                        <path
                            d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-2 2 2 2 0 01-2-2v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83 0 2 2 0 010-2.83l.06-.06a1.65 1.65 0 00.33-1.82 1.65 1.65 0 00-1.51-1H3a2 2 0 01-2-2 2 2 0 012-2h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 010-2.83 2 2 0 012.83 0l.06.06a1.65 1.65 0 001.82.33H9a1.65 1.65 0 001-1.51V3a2 2 0 012-2 2 2 0 012 2v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 0 2 2 0 010 2.83l-.06.06a1.65 1.65 0 00-.33 1.82V9a1.65 1.65 0 001.51 1H21a2 2 0 012 2 2 2 0 01-2 2h-.09a1.65 1.65 0 00-1.51 1z" />
                    </svg>
                <span>تنظیمات</span></button>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"  class="app-sidebar-link" title="خروج">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                        stroke="currentColor" width="28" height="28">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" />
                    </svg>
                </button></form>
</nav>
            <div class="buildings-section home-window" data-panel="home">
                <div class="buildings-section-header">
                    <p>ساختمان های من</p>
                    <form method="POST" action="{{ URL::route('dashboard.store') }}" class="form-wrapper">
                        @csrf
                        <div class="search-wrapper add-wrapper">
                            <label class="sr-only" for="dashboard-link-1">لینک ساختمان خود را وارد کنید</label><input id="dashboard-link-1" class="search-input add-input" type="text" name="link"
                                placeholder="لینک ساختمان خود را وارد کنید" value="{{ is_scalar(old('link')) ? old('link') : '' }}" @error('link') aria-invalid="true" aria-describedby="dashboard-link-1-error" @enderror />@error('link')<span class="field-error" id="dashboard-link-1-error">{{ $message }}</span>@enderror
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
                        <button type="button" class="view-btn list-view" aria-label="نمایش فهرستی"><x-icon name="list" /></button>
                        <button type="button" class="view-btn grid-view" aria-label="نمایش شبکه‌ای"><x-icon name="grid" /></button>
                    </div>
                </div>
                <div class="building-boxes jsGridView" data-layout>
                    @forelse ($allComplexes as $complex)
    <x-building-card :complex="$complex" />
@empty
    <p class="empty-state">ساختمانی ندارید. با لینک دعوت به یک ساختمان بپیوندید.</p>
@endforelse

                    <form class="register-form" method="POST" action="{{ URL::route('dashboard.addUnit') }}">
                        @csrf
                        <div class="dialog-shell add-unit-modal hidden" role="dialog" aria-modal="true">
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
                                    <label class="sr-only" for="dashboard-zipCode-2">کد پستی</label><input id="dashboard-zipCode-2" class="border border-gray-300 px-4 py-2 rounded w-full" type="text"
                                        placeholder="کد پستی" autocomplete="off" name="zipCode" required value="{{ is_scalar(old('zipCode')) ? old('zipCode') : '' }}" @error('zipCode') aria-invalid="true" aria-describedby="dashboard-zipCode-2-error" @enderror />@error('zipCode')<span class="field-error" id="dashboard-zipCode-2-error">{{ $message }}</span>@enderror
                                </div>
                                <div class="mb-6">
                                    <label class="sr-only" for="dashboard-unitNumber-3">شماره واحد</label><input id="dashboard-unitNumber-3" class="border border-gray-300 px-4 py-2 rounded w-full" type="number"
                                        placeholder="شماره واحد" autocomplete="off" name="unitNumber" required value="{{ is_scalar(old('unitNumber')) ? old('unitNumber') : '' }}" @error('unitNumber') aria-invalid="true" aria-describedby="dashboard-unitNumber-3-error" @enderror />@error('unitNumber')<span class="field-error" id="dashboard-unitNumber-3-error">{{ $message }}</span>@enderror
                                </div>
                                <div class="mb-6">
                                    <label class="sr-only" for="dashboard-floorNumber-4">شماره طبقه</label><input id="dashboard-floorNumber-4" class="border border-gray-300 px-4 py-2 rounded w-full" type="number"
                                        placeholder="شماره طبقه" autocomplete="off" name="floorNumber" required value="{{ is_scalar(old('floorNumber')) ? old('floorNumber') : '' }}" @error('floorNumber') aria-invalid="true" aria-describedby="dashboard-floorNumber-4-error" @enderror />@error('floorNumber')<span class="field-error" id="dashboard-floorNumber-4-error">{{ $message }}</span>@enderror
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
                        <div class="dialog-shell unit-edit-modal hidden" role="dialog" aria-modal="true">
                            <div class="bg-white p-6 rounded shadow-md max-w-sm mx-auto">
                                <h3 class="text-xl font-semibold mb-4 text-center">
                                    ویرایش مشخصات واحد
                                </h3>
                                <div class="mb-4">
                                    <input id="complexId" class="border border-gray-300 px-4 py-2 rounded w-full "
                                        type="hidden" name="complexId" />
                                </div>
                                <div class="mb-6">
                                    <label class="sr-only" for="dashboard-currentUnitNumber-5">شماره فعلی واحد</label><input id="dashboard-currentUnitNumber-5" class="border border-gray-300 px-4 py-2 rounded w-full" type="number"
                                        placeholder="شماره فعلی واحد" autocomplete="off" name="currentUnitNumber"
                                        required value="{{ is_scalar(old('currentUnitNumber')) ? old('currentUnitNumber') : '' }}" @error('currentUnitNumber') aria-invalid="true" aria-describedby="dashboard-currentUnitNumber-5-error" @enderror />@error('currentUnitNumber')<span class="field-error" id="dashboard-currentUnitNumber-5-error">{{ $message }}</span>@enderror
                                </div>
                                <div class="mb-6">
                                    <label class="sr-only" for="dashboard-newUnitNumber-6">شماره جدید واحد</label><input id="dashboard-newUnitNumber-6" class="border border-gray-300 px-4 py-2 rounded w-full" type="number"
                                        placeholder="شماره جدید واحد" autocomplete="off" name="newUnitNumber"
                                        required value="{{ is_scalar(old('newUnitNumber')) ? old('newUnitNumber') : '' }}" @error('newUnitNumber') aria-invalid="true" aria-describedby="dashboard-newUnitNumber-6-error" @enderror />@error('newUnitNumber')<span class="field-error" id="dashboard-newUnitNumber-6-error">{{ $message }}</span>@enderror
                                </div>
                                <div class="mb-6">
                                    <label class="sr-only" for="dashboard-zipCode-7">کد پستی</label><input id="dashboard-zipCode-7" class="border border-gray-300 px-4 py-2 rounded w-full" type="text"
                                        placeholder="کد پستی" autocomplete="off" name="zipCode" required value="{{ is_scalar(old('zipCode')) ? old('zipCode') : '' }}" @error('zipCode') aria-invalid="true" aria-describedby="dashboard-zipCode-7-error" @enderror />@error('zipCode')<span class="field-error" id="dashboard-zipCode-7-error">{{ $message }}</span>@enderror
                                </div>
                                <div class="mb-6">
                                    <label class="sr-only" for="dashboard-floorNumber-8">شماره طبقه</label><input id="dashboard-floorNumber-8" class="border border-gray-300 px-4 py-2 rounded w-full" type="number"
                                        placeholder="شماره طبقه" autocomplete="off" name="floorNumber" required value="{{ is_scalar(old('floorNumber')) ? old('floorNumber') : '' }}" @error('floorNumber') aria-invalid="true" aria-describedby="dashboard-floorNumber-8-error" @enderror />@error('floorNumber')<span class="field-error" id="dashboard-floorNumber-8-error">{{ $message }}</span>@enderror
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
            <div class="buildings-section register-window" data-panel="register" hidden>

                <div class="buildings-section-header register-container">
                    <p>ثبت ساختمان در سیستم</p>
                </div>
                <div class="register">

                    <form class="register-form" method="POST" action="{{ URL::route('dashboard.register') }}">
                        @csrf
                        <div class="search-wrapper add-wrapper">
                            <label class="sr-only" for="dashboard-name-9">نام ساختمان</label><input id="dashboard-name-9" class="search-input add-input" type="text" name="name"
                                placeholder="نام ساختمان" value="{{ is_scalar(old('name')) ? old('name') : '' }}" @error('name', 'registerBuilding') aria-invalid="true" aria-describedby="dashboard-name-9-error" @enderror />@error('name', 'registerBuilding')<span class="field-error" id="dashboard-name-9-error">{{ $message }}</span>@enderror
                        </div>
                        <div class="search-wrapper add-wrapper">
                            <label class="sr-only" for="dashboard-address-10">آدرس ساختمان</label><input id="dashboard-address-10" class="search-input add-input" type="text" name="address"
                                placeholder="آدرس ساختمان" value="{{ is_scalar(old('address')) ? old('address') : '' }}" @error('address', 'registerBuilding') aria-invalid="true" aria-describedby="dashboard-address-10-error" @enderror />@error('address', 'registerBuilding')<span class="field-error" id="dashboard-address-10-error">{{ $message }}</span>@enderror
                        </div>
                        <div class="search-wrapper add-wrapper">
                            <label class="sr-only" for="dashboard-floors-11">تعداد طبقات ساختمان</label><input id="dashboard-floors-11" class="search-input add-input" type="number" name="floors" min="0"
                                placeholder="تعداد طبقات ساختمان" value="{{ is_scalar(old('floors')) ? old('floors') : '' }}" @error('floors', 'registerBuilding') aria-invalid="true" aria-describedby="dashboard-floors-11-error" @enderror />@error('floors', 'registerBuilding')<span class="field-error" id="dashboard-floors-11-error">{{ $message }}</span>@enderror
                        </div>
                        <div class="search-wrapper add-wrapper">
                            <label class="sr-only" for="dashboard-units-12">تعداد واحدهای ساختمان</label><input id="dashboard-units-12" class="search-input add-input" type="number" name="units" min="0"
                                placeholder="تعداد واحدهای ساختمان" value="{{ is_scalar(old('units')) ? old('units') : '' }}" @error('units', 'registerBuilding') aria-invalid="true" aria-describedby="dashboard-units-12-error" @enderror />@error('units', 'registerBuilding')<span class="field-error" id="dashboard-units-12-error">{{ $message }}</span>@enderror
                        </div>
                        <button type="submit"
                            class="view-btn grid-view active w-1/3 sm:w-72 register-building-btn">ثبت</button>
                    </form>
                </div>
            </div>
            <section class="buildings-section setting-window" data-panel="setting" hidden>
<div class="settings-panel">
                <div class="buildings-section-header register-container">
                    <p>بروزرسانی اطلاعات پروفایل کاربری</p>
                </div>
                <div class="register">

                    <form class="register-form" method="POST" action="{{ route('profile.update') }}">
                        @csrf
                        @method('patch')

                        <div class="search-wrapper add-wrapper">
                            <label class="sr-only" for="dashboard-name-13">نام و نام خانوادگی خود را وارد کنید</label><input id="dashboard-name-13" class="search-input add-input" type="text" name="name"
                                placeholder="نام و نام خانوادگی خود را وارد کنید" value="{{ is_scalar(old('name')) ? old('name') : Auth::user()->name }}"
                                required @error('name') aria-invalid="true" aria-describedby="dashboard-name-13-error" @enderror />@error('name')<span class="field-error" id="dashboard-name-13-error">{{ $message }}</span>@enderror
                        </div>
                        <div class="search-wrapper add-wrapper">
                            <label class="sr-only" for="dashboard-email-14">ایمیل خود را وارد کنید</label><input id="dashboard-email-14" class="search-input add-input" type="email" name="email"
                                placeholder="ایمیل خود را وارد کنید" value="{{ is_scalar(old('email')) ? old('email') : Auth::user()->email }}" required @error('email') aria-invalid="true" aria-describedby="dashboard-email-14-error" @enderror />@error('email')<span class="field-error" id="dashboard-email-14-error">{{ $message }}</span>@enderror
                        </div>
                        <div class="search-wrapper add-wrapper">
                            <label class="sr-only" for="dashboard-ncode-15">کد ملی</label><input id="dashboard-ncode-15" class="search-input add-input" type="text" name="ncode" placeholder="کد ملی"
                                value="{{ is_scalar(old('ncode')) ? old('ncode') : Auth::user()->national_code }}" @error('ncode') aria-invalid="true" aria-describedby="dashboard-ncode-15-error" @enderror />@error('ncode')<span class="field-error" id="dashboard-ncode-15-error">{{ $message }}</span>@enderror
                        </div>
                        <div class="search-wrapper add-wrapper">
                            <label class="sr-only" for="dashboard-phoneNumber-16">شماره تلفن</label><input id="dashboard-phoneNumber-16" class="search-input add-input" type="text" name="phoneNumber"
                                placeholder="شماره تلفن" value="{{ is_scalar(old('phoneNumber')) ? old('phoneNumber') : Auth::user()->phone_number }}" @error('phoneNumber') aria-invalid="true" aria-describedby="dashboard-phoneNumber-16-error" @enderror />@error('phoneNumber')<span class="field-error" id="dashboard-phoneNumber-16-error">{{ $message }}</span>@enderror
                        </div>
                        <button type="submit" class="view-btn grid-view active">ثبت</button>
                    </form>
                </div>
            </div>
            <div class="settings-panel">
                <div class="buildings-section-header register-container">
                    <p>بروزرسانی کلمه عبور</p>
                </div>
                <div class="register">

                    <form class="register-form" method="POST" action="{{ route('password.update') }}">
                        @csrf
                        @method('put')

                        <div class="search-wrapper add-wrapper">
                            <label class="sr-only" for="current_password">کلمه عبور فعلی</label><input class="search-input add-input" id="current_password" name="current_password"
                                type="password" autocomplete="current-password" placeholder="کلمه عبور فعلی" @error('current_password', 'updatePassword') aria-invalid="true" aria-describedby="current_password-error" @enderror />@error('current_password', 'updatePassword')<span class="field-error" id="current_password-error">{{ $message }}</span>@enderror
                        </div>
                        <div class="search-wrapper add-wrapper">
                            <label class="sr-only" for="password">کلمه عبور جدید</label><input class="search-input add-input" id="password" name="password" type="password"
                                placeholder="کلمه عبور جدید" @error('password', 'updatePassword') aria-invalid="true" aria-describedby="password-error" @enderror />@error('password', 'updatePassword')<span class="field-error" id="password-error">{{ $message }}</span>@enderror

                        </div>
                        <div class="search-wrapper add-wrapper">
                            <label class="sr-only" for="password_confirmation">تکرار کلمه عبور جدید</label><input class="search-input add-input" id="password_confirmation"
                                name="password_confirmation" type="password" placeholder="تکرار کلمه عبور جدید" @error('password_confirmation', 'updatePassword') aria-invalid="true" aria-describedby="password_confirmation-error" @enderror />@error('password_confirmation', 'updatePassword')<span class="field-error" id="password_confirmation-error">{{ $message }}</span>@enderror
                        </div>

                        <button type="submit" class="view-btn grid-view active">ثبت</button>
</form>
</div>
</div>
</section>
        </div>
    </div>



</body>

</html>
