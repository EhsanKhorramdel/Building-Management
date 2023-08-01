<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ __('direction') }}">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>پنل ادمین</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.17/dist/tailwind.min.css" rel="stylesheet" />
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('assets/images/favIcon/apartment.ico') }}" />
    <link rel="stylesheet" href="{{ asset('css/fonts.css') }}" />
    <style>
        .tab {
            display: none;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
            padding: 2rem;
            border-radius: 0.5rem;
        }

        .tab-title {
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .tab-content {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            width: 100%
        }

        body {
            background-image: url('{{ asset('assets/images/adminPanel/2.svg') }}')
        }
    </style>
</head>

<body>
    <div class="max-w-5xl mx-auto">
        <div class="mt-10">
            <div class="flex justify-between mb-4">
                <ul class="flex overflow-x-auto">
                    <li class="mr-1">
                        <button
                            class="py-2 px-4 bg-green-500 text-white rounded focus:outline-none shadow-md hover:shadow-lg transition-colors"
                            onclick="openTab(event, 'accepted')">
                            پذیرفته شده ها
                        </button>
                    </li>
                    <li class="mr-1">
                        <button
                            class="py-2 px-4 bg-blue-500 text-white focus:outline-none shadow-md hover:shadow-lg transition-colors rounded"
                            onclick="openTab(event, 'pending')">
                            در حال برسی
                        </button>
                    </li>
                    <li class="mr-1">
                        <button
                            class="py-2 px-4 bg-red-500 text-white rounded focus:outline-none shadow-md hover:shadow-lg transition-colors"
                            onclick="openTab(event, 'rejected')">
                            رد شده ها
                        </button>
                    </li>
                </ul>
                <a href="{{ route('logout') }}"
                    class="py-2 px-4 bg-red-500 text-white rounded focus:outline-none shadow-md hover:shadow-lg transition-colors">
                    خروج
                </a>
            </div>
        </div>
        <div id="accepted" class="tab">
            <h2 class="text-xl font-bold mt-6 text-green-600">پذیرفته شده ها</h2>
            <ul class="mt-4 space-y-4">
                @foreach ($allMembershipRequests as $request)
                    @if ($request->request_status === 'accepted')
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
                    @endif
                @endforeach
            </ul>
        </div>

        <div id="pending" class="tab">
            <h2 class="text-xl font-bold mt-6 text-blue-600">در حال برسی</h2>
            <ul class="mt-4 space-y-4">
                @foreach ($allMembershipRequests as $request)
                    @if ($request->request_status === 'pending')
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
                                <div>
                                    <span class="tab-title">تعداد طبقات:</span>
                                    <span>{{ $request->number_of_floors }}</span>
                                </div>
                                <div>
                                    <span class="tab-title">تعداد واحدها:</span>
                                    <span>{{ $request->number_of_units }}</span>
                                </div>
                                <div class="mt-2 flex flex-col gap-2 justify-end">
                                    <a href="{{ URL::route('adminPanel.accept', $request->id) }}"
                                        class="py-2 px-4 bg-green-500 text-white rounded focus:outline-none shadow-md hover:shadow-lg transition-colors text-center">
                                        پذیرش
                                    </a>

                                    <a href="{{ URL::route('adminPanel.reject', $request->id) }}"
                                        class="py-2 px-4 bg-red-500 text-white rounded focus:outline-none shadow-md hover:shadow-lg transition-colors text-center">
                                        رد کردن
                                    </a>

                                </div>
                            </div>
                        </li>
                    @endif
                @endforeach
            </ul>

        </div>


        <div id="rejected" class="tab">
            <h2 class="text-xl font-bold mt-6 text-red-600">رد شده ها</h2>
            <ul class="mt-4 space-y-4">
                @foreach ($allMembershipRequests as $request)
                    @if ($request->request_status === 'rejected')
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
                    @endif
                @endforeach
            </ul>
        </div>
    </div>
    <script>
        function openTab(evt, tabName) {
            let borderColor;
            const tabs = document.getElementsByClassName("tab");
            for (let i = 0; i < tabs.length; i++) {
                tabs[i].style.display = "none";
            }

            const tabContent = document.getElementById(tabName);
            tabContent.style.display = "block";

            const tabButtons = document.getElementsByTagName("button");
            for (let i = 0; i < tabButtons.length; i++) {

                tabButtons[i].classList.remove(borderColor);
                tabButtons[i].classList.remove("border-b-2");
            }
            if (evt) {
                if (evt.target.textContent.trim() === 'رد شده ها')
                    borderColor = "border-red-400";
                else if (evt.target.textContent.trim() === 'پذیرفته شده ها')
                    borderColor = "border-green-400";
                else
                    borderColor = "border-blue-400";

                evt.target.classList.add(borderColor);
                evt.target.classList.add("border-b-2");
            }

        }

        openTab(null, "pending");
    </script>
</body>

</html>
