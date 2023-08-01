<div class="buildings-section charge-window hidden">
    <div class="buildings-section-header justify-center">
        <p> شارژ های ساختمان </p>
    </div>
    <div class="building-boxes jsListView">
        @if (count($monthlyCharges) === 0)
            <div class="bg-white shadow overflow-hidden sm:rounded-md ">
                <ul class="divide-y divide-gray-200 contactList"></ul>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-2 rounded">
                    شارژی تعریف نشده است.
                </div>
            </div>
        @endif
        @foreach ($monthlyCharges as $monthlyCharge)
            <div class="building-box-wrapper">
                <div class="building-box" style="background-color: #fee4cb">
                    <div class="building-box-content-header">
                        <p class="box-content-header">مبلغ شارژ : {{ $monthlyCharge->amount }} </p>
                        </p>
                    </div>
                    <div class="box-address-wrapper overflow-auto">
                        <p class="box-address-header p-4">تاریخ شروع :
                            {{ implode('-', array_reverse(explode('-', $monthlyCharge->start_date))) }}</p>
                        <p class="box-address-header p-4">تاریخ پایان :
                            {{ implode('-', array_reverse(explode('-', $monthlyCharge->end_date))) }}</p>
                    </div>
                    <div class="building-box-footer flex justify-center gap-2.5">
                        <a class="text-center charge-pay-open-Btn">پرداخت</a>
                        <p class="hidden">{{ $monthlyCharge->id }}</p>
                    </div>
                </div>
            </div>
        @endforeach

    </div>
</div>

<form class="register-form" method="POST" action="{{ URL::route('building.buildingChargePayment') }}">
    @csrf
    <div
        class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50 rounded-lg charge-pay-modal hidden">
        <div class="bg-white p-6 rounded shadow-md max-w-sm mx-auto">
            <h3 class="text-xl font-semibold mb-4 text-center">
                پرداخت شارژ ماهیانه
            </h3>
            <div class="mb-4">
                <input id="complexId" class="border border-gray-300 px-4 py-2 rounded w-full " type="hidden"
                    name="complexId" value="{{ $complex->id }}" />
            </div>
            <div class="mb-4">
                <input id="monthlyChargeId" class="border border-gray-300 px-4 py-2 rounded w-full " type="hidden"
                    name="monthlyChargeId" />
            </div>
            <div class="mb-6">
                <input class="border border-gray-300 px-4 py-2 rounded w-full" type="number" placeholder="شماره واحد"
                    autocomplete="off" name="unitNumber" required min="1" />
            </div>
            <div class="mb-6">
                <input class="border border-gray-300 px-4 py-2 rounded w-full" step="0.01" type="hidden"
                    placeholder="مبلغ پرداختی" autocomplete="off" name="Amount" min="1" required
                    value="{{ $monthlyCharge->amount }}" />
            </div>
            <div class="flex">
                <button
                    class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline"
                    type="submit">
                    پرداخت
                </button>
                <button
                    class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline mr-2 charge-pay-close-Btn"
                    type="button">
                    لغو
                </button>
            </div>
        </div>
    </div>
</form>


@if ($isManager)
    <div class="register-section charge-window hidden">
        <button class="register-close">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                class="feather feather-x-circle">
                <circle cx="12" cy="12" r="10" />
                <line x1="15" y1="9" x2="9" y2="15" />
                <line x1="9" y1="9" x2="15" y2="15" />
            </svg>
        </button>
        <div class="buildings-section-header register-container">
            <p>ثبت شارژ ساختمان </p>
        </div>
        <div class="register">
            @if ($errors->hasBag('buildingCharge'))
                <div id="messageBox" class="mb-2 mr-3 ml-3 p-4 rounded bg-red-200 animate__animated animate__shakeX">
                    <ul class=" text-sm text-red-600 text-red-400 space-y-1">
                        @foreach ($errors->getBag('buildingCharge')->all() as $error)
                            <li class="text-center">{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <form class="register-form" method="POST"
                action="{{ URL::route('building.buildingCharge', $complex->id) }}">
                @csrf
                <div class="search-wrapper add-wrapper">
                    <input class="search-input add-input" type="number" step="0.01" name="chargeAmount"
                        placeholder="مبلغ شارژ" min="0" />
                </div>

                <label class="uppercase text-lg font-bold text-white bg-gray-500 rounded-lg p-2">
                    تاریخ شروع شارژ
                </label>
                <div class="flex flex-wrap mt-3">
                    <div class="w-full md:w-1/3 px-3 mb-6 md:mb-0">
                        <input
                            class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-3 px-4 leading-tight focus:outline-none focus:bg-white focus:border-gray-500"
                            type="number" name="dayStart" placeholder="روز" min="1" max="31">
                    </div>
                    <div class="w-full md:w-1/3 px-3 mb-6 md:mb-0">
                        <input
                            class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-3 px-4 leading-tight focus:outline-none focus:bg-white focus:border-gray-500"
                            type="number" name="monthStart" placeholder="ماه" min="1" max="12">
                    </div>
                    <div class="w-full md:w-1/3 px-3 mb-6 md:mb-0">
                        <input
                            class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-3 px-4 leading-tight focus:outline-none focus:bg-white focus:border-gray-500"
                            type="number" name="yearStart" placeholder="سال" min="1300">
                    </div>
                </div>

                <label class="uppercase text-lg font-bold text-white bg-gray-500 rounded-lg p-2">
                    تاریخ پایان شارژ
                </label>
                <div class="flex flex-wrap mt-3">
                    <div class="w-full md:w-1/3 px-3 mb-6 md:mb-0">
                        <input
                            class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-3 px-4 leading-tight focus:outline-none focus:bg-white focus:border-gray-500"
                            type="number" name="dayEnd" placeholder="روز" min="1" max="31">
                    </div>
                    <div class="w-full md:w-1/3 px-3 mb-6 md:mb-0">
                        <input
                            class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-3 px-4 leading-tight focus:outline-none focus:bg-white focus:border-gray-500"
                            type="number" name="monthEnd" placeholder="ماه" min="1" max="12">
                    </div>
                    <div class="w-full md:w-1/3 px-3 mb-6 md:mb-0">
                        <input
                            class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-3 px-4 leading-tight focus:outline-none focus:bg-white focus:border-gray-500"
                            type="number" name="yearEnd" placeholder="سال" min="1300">
                    </div>
                </div>
                <button type="submit" class="view-btn grid-view active">ثبت</button>
            </form>
        </div>
    </div>
@endif
