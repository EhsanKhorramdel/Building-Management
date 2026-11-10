<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ __('direction') }}">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>پنل ادمین</title>

    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('assets/images/favIcon/apartment.ico') }}" />


    <x-page-assets entry="resources/js/admin.js" />
</head>

<body>
    <main class="admin-page">
<x-toasts />
        <div class="mb-4"><h1 class="mb-4 text-xl font-bold">پنل ادمین</h1>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <ul class="admin-tabs">
                    <li class="mr-1">
                        <button
                            class="py-2 px-4 bg-green-700 text-white rounded focus:outline-none shadow-md hover:shadow-lg transition-colors"
                            type="button" data-admin-tab="accepted" aria-controls="accepted" aria-pressed="false">
                            پذیرفته شده ها
                        </button>
                    </li>
                    <li class="mr-1">
                        <button
                            class="py-2 px-4 bg-blue-700 text-white focus:outline-none shadow-md hover:shadow-lg transition-colors rounded"
                            type="button" data-admin-tab="pending" aria-controls="pending" aria-pressed="true">
                            در حال برسی
                        </button>
                    </li>
                    <li class="mr-1">
                        <button
                            class="py-2 px-4 bg-red-700 text-white rounded focus:outline-none shadow-md hover:shadow-lg transition-colors"
                            type="button" data-admin-tab="rejected" aria-controls="rejected" aria-pressed="false">
                            رد شده ها
                        </button>
                    </li>
                </ul>
                <x-theme-button /><form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                    class="py-2 px-4 bg-red-700 text-white rounded focus:outline-none shadow-md hover:shadow-lg transition-colors">
                    خروج
                </button></form>
            </div>
        </div>
        @if (config('payments.enabled') || config('sms.enabled'))
            <section class="mb-4 space-y-1">
                <h2 class="font-bold">وضعیت پرداخت و پیامک</h2>
                <p>پرداخت آنلاین: {{ config('payments.enabled') ? 'فعال' : 'غیرفعال' }}
                    @if (config('payments.enabled'))
                        ({{ config('payments.driver') === 'mock' || config('payments.drivers.gateway.sandbox') ? 'آزمایشی' : 'واقعی' }})
                    @endif
                </p>
                <p>پیامک: {{ config('sms.enabled') ? 'فعال' : 'غیرفعال' }}</p>
            </section>
        @endif
        <div id="accepted" class="tab" hidden>
            <h2 class="text-xl font-bold mt-6 text-green-600">پذیرفته شده ها</h2>
            <ul class="mt-4 space-y-4">
                @forelse ($allMembershipRequests->where('request_status', 'accepted') as $request)
                        <li class="border border-gray-300 rounded-lg p-4 flex items-center justify-between">
                            <div class="tab-content">
                                <div>
                                    <span class="tab-title">نام ساختمان:</span>
                                    <span>{{ $request->name }}</span>
                                </div>
                                <div>
                                    <span class="tab-title">آدرس:</span>
                                    <span>{{ $request->address }}</span>
                                </div>
                                <x-membership-request-map :membership-request="$request" />
                                <div>
                                    <span class="tab-title">تعداد طبقات:</span>
                                    <span>{{ $request->number_of_floors }}</span>
                                </div>
                                <div>
                                    <span class="tab-title">تعداد واحدها:</span>
                                    <span>{{ $request->number_of_units }}</span>
                                </div>

                            </div>
                        </li>
                    @empty
<li class="empty-state">درخواستی در این بخش وجود ندارد. درخواست‌های جدید در بخش در حال بررسی دیده می‌شوند.</li>
@endforelse
            </ul>
        </div>

        <div id="pending" class="tab">
            <h2 class="text-xl font-bold mt-6 text-blue-600">در حال برسی</h2>
            <ul class="mt-4 space-y-4">
                @forelse ($allMembershipRequests->where('request_status', 'pending') as $request)
                        <li class="border border-gray-300 rounded-lg p-4 flex items-center justify-between w-full">
                            <div class="tab-content">
                                <div>
                                    <span class="tab-title">نام ساختمان:</span>
                                    <span>{{ $request->name }}</span>
                                </div>
                                <div>
                                    <span class="tab-title">آدرس:</span>
                                    <span>{{ $request->address }}</span>
                                </div>
                                <x-membership-request-map :membership-request="$request" />
                                <div>
                                    <span class="tab-title">تعداد طبقات:</span>
                                    <span>{{ $request->number_of_floors }}</span>
                                </div>
                                <div>
                                    <span class="tab-title">تعداد واحدها:</span>
                                    <span>{{ $request->number_of_units }}</span>
                                </div>
                                <div class="mt-2 flex flex-col gap-2 justify-end">
                                    <form method="POST" action="{{ URL::route('adminPanel.accept', $request->id) }}">
                    @csrf
                    <button type="submit"
                                        class="py-2 px-4 bg-green-700 text-white rounded focus:outline-none shadow-md hover:shadow-lg transition-colors text-center">
                                        پذیرش
                                    </button></form>

                                    <form method="POST" action="{{ URL::route('adminPanel.reject', $request->id) }}" data-confirm="این درخواست رد شود؟">
                    @csrf
                    <button type="submit"
                                        class="py-2 px-4 bg-red-700 text-white rounded focus:outline-none shadow-md hover:shadow-lg transition-colors text-center">
                                        رد کردن
                                    </button></form>

                                </div>
                            </div>
                        </li>
                    @empty
<li class="empty-state">درخواستی در این بخش وجود ندارد. درخواست‌های جدید در بخش در حال بررسی دیده می‌شوند.</li>
@endforelse
            </ul>

        </div>


        <div id="rejected" class="tab" hidden>
            <h2 class="text-xl font-bold mt-6 text-red-600">رد شده ها</h2>
            <ul class="mt-4 space-y-4">
                @forelse ($allMembershipRequests->where('request_status', 'rejected') as $request)
                        <li class="border border-gray-300 rounded-lg p-4 flex items-center justify-between">
                            <div class="tab-content">
                                <div>
                                    <span class="tab-title">نام ساختمان:</span>
                                    <span>{{ $request->name }}</span>
                                </div>
                                <div>
                                    <span class="tab-title">آدرس:</span>
                                    <span>{{ $request->address }}</span>
                                </div>
                                <x-membership-request-map :membership-request="$request" />
                                <div>
                                    <span class="tab-title">تعداد طبقات:</span>
                                    <span>{{ $request->number_of_floors }}</span>
                                </div>
                                <div>
                                    <span class="tab-title">تعداد واحدها:</span>
                                    <span>{{ $request->number_of_units }}</span>
                                </div>

                            </div>
                        </li>
                    @empty
<li class="empty-state">درخواستی در این بخش وجود ندارد. درخواست‌های جدید در بخش در حال بررسی دیده می‌شوند.</li>
@endforelse
            </ul>
        </div>
        <x-audit-events />
    </main>
</body>

</html>
