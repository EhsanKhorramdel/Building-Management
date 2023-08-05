<div class="buildings-section cost-window hidden">
    <div class="buildings-section-header justify-center">
        <p> هزینه های جانبی ساختمان </p>
    </div>
    <div class="building-boxes jsListView">
        @foreach ($incidentalCosts as $incidentalCost)
            <div class="building-box-wrapper">
                <div class="building-box" style="background-color: #fee4cb">
                    <div class="building-box-content-header">
                        <p class="box-content-header">{{ $incidentalCost->title }} </p>
                        <p class="box-content-header">مبلغ کل : {{ $incidentalCost->total_amount }} </p>
                        <p class="box-content-header">سهم هر واحد : {{ $incidentalCost->share_amount }}
                        </p>
                    </div>
                    <div class="box-address-wrapper overflow-auto">
                        <p class="box-address-header p-8">{{ $incidentalCost->cost_explanation }}</p>
                    </div>

                    <div class="building-box-footer flex justify-center gap-2.5">
                        <a class="text-center cost-pay-open-Btn">پرداخت</a>
                        <p class="hidden">{{ $incidentalCost->id }}</p>
                        <a class="text-center cost-open-Btn">مشاهده فاکتور</a>
                        <p class="hidden">
                            @if ($incidentalCost->cost_invoice)
                                {{ '/storage/incidentalCostFiles/' . $incidentalCost->cost_invoice }}
                            @else
                                {{ '/storage/notificationFiles/default.png' }}
                            @endif
                        </p>

                    </div>
                </div>
            </div>
        @endforeach
        @if (count($incidentalCosts) === 0)
            <div class="bg-white shadow overflow-hidden sm:rounded-md ">
                <ul class="divide-y divide-gray-200 contactList"></ul>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-2 rounded">
                    هزینه ای تعریف نشده است.
                </div>
            </div>
        @endif
        <div
            class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50 rounded-2xl cost-modal hidden">
            <div class="bg-white p-6 rounded shadow-md max-w-sm mx-auto rounded-xl">
                <h3 class="text-xl font-semibold mb-4 text-center">
                    فاکتور هزینه
                </h3>
                <img alt="Cost Invoice"
                    class="w-full rounded-2xl transition-transform hover:scale-125 transition-all duration-300"
                    id="CostInvoice">
                <div class="flex flex-col mt-5">
                    <button
                        class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline mr-2 cost-close-Btn"
                        type="button">
                        بستن
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<form class="register-form" method="POST" action="{{ URL::route('building.incidentalCostPayment') }}">
    @csrf
    <div
        class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50 rounded-lg cost-pay-modal hidden">
        <div class="bg-white p-6 rounded shadow-md max-w-sm mx-auto">
            <h3 class="text-xl font-semibold mb-4 text-center">
                پرداخت هزینه جانبی
            </h3>
            <div class="mb-4">
                <input id="complexId" class="border border-gray-300 px-4 py-2 rounded w-full " type="hidden"
                    name="complexId" value="{{ $complex->id }}" />
            </div>
            <div class="mb-4">
                <input id="incidentalCostId" class="border border-gray-300 px-4 py-2 rounded w-full " type="hidden"
                    name="incidentalCostId" />
            </div>
            <div class="mb-6">
                <input class="border border-gray-300 px-4 py-2 rounded w-full" type="number" placeholder="شماره واحد"
                    autocomplete="off" name="unitNumber" required min="1" />
            </div>
            <div class="mb-6">
                <input class="border border-gray-300 px-4 py-2 rounded w-full" step="0.01" type="hidden"
                    placeholder="مبلغ پرداختی" autocomplete="off" name="Amount" min="1" required
                    value="{{ $incidentalCost->share_amount ?? 0}}" />
            </div>
            <div class="flex">
                <button
                    class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline"
                    type="submit">
                    پرداخت
                </button>
                <button
                    class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline mr-2 cost-pay-close-Btn"
                    type="button">
                    لغو
                </button>
            </div>
        </div>
    </div>
</form>


@if ($isManager)
    <div class="register-section cost-window hidden">
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
            <p>ثبت هزینه جانبی </p>
        </div>
        <div class="register">
            @if ($errors->hasBag('incidentalCost'))
                <div id="messageBox" class="mb-2 mr-3 ml-3 p-4 rounded bg-red-200 animate__animated animate__shakeX">
                    <ul class=" text-sm text-red-600 text-red-400 space-y-1">
                        @foreach ($errors->getBag('incidentalCost')->all() as $error)
                            <li class="text-center">{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <form class="register-form" method="POST"
                action="{{ URL::route('building.incidentalCost', $complex->id) }}" enctype="multipart/form-data">
                @csrf

                <div class="search-wrapper add-wrapper">
                    <input class="search-input add-input" type="text" name="title" placeholder="عنوان" />
                </div>
                <div class="search-wrapper add-wrapper">
                    <input class="search-input add-input" type="text" name="cost_explanation"
                        placeholder="توضیح هزینه" />
                </div>

                <label
                    class="uppercase text-lg font-bold text-white bg-gray-500 rounded-lg p-2 text-center cursor-pointer"
                    for="costInvoice">
                    تصویر هزینه
                </label>

                <div>
                    <input
                        class="relative m-0 block w-full min-w-0 flex-auto rounded border border-solid border-neutral-300 bg-clip-padding px-3 py-[0.32rem] text-base font-normal text-neutral-700 transition duration-300 ease-in-out file:-mx-3 file:-my-[0.32rem] file:overflow-hidden file:rounded-none file:border-0 file:border-solid file:border-inherit file:bg-neutral-100 file:px-3 file:py-[0.32rem] file:text-neutral-700 file:transition file:duration-150 file:ease-in-out file:[border-inline-end-width:1px] file:[margin-inline-end:0.75rem] hover:file:bg-gray-100 hover:file:text-black focus:border-primary focus:text-neutral-700 focus:shadow-te-primary focus:outline-none dark:border-neutral-600 dark:text-neutral-200 dark:file:bg-neutral-700 dark:file:text-neutral-100 dark:focus:border-primary"
                        type="file" id="costInvoice" name="cost_invoice" multiple />
                </div>

                <div class="search-wrapper add-wrapper">
                    <input class="search-input add-input" type="number" step="0.01" name="total_amount"
                        min="0" placeholder="مبلغ کل" />
                </div>
                <div class="search-wrapper add-wrapper">
                    <input class="search-input add-input" type="number" step="0.01" name="share_amount"
                        min="0" placeholder="سهم پرداختی هر فرد" />
                </div>

                <button type="submit" class="view-btn grid-view active">ثبت</button>
            </form>
        </div>
    </div>
@endif
