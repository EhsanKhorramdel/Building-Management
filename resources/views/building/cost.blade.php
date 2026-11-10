<div class="buildings-section cost-window" data-panel="cost" hidden>
    <div class="buildings-section-header justify-center">
        <p> هزینه های جانبی ساختمان </p>
    </div>
    <div class="building-boxes jsListView">
        @foreach ($incidentalCosts as $incidentalCost)
            <div class="building-box-wrapper">
                <div class="building-box" >
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
                        <button type="button" data-amount="{{ $incidentalCost->share_amount }}" data-payment-id="{{ $incidentalCost->id }}" class="text-center cost-pay-open-Btn">{{ config('payments.enabled') ? 'پرداخت آنلاین' : 'پرداخت' }}</button>

                        <button type="button" data-invoice="{{ $incidentalCost->cost_invoice ? route('incidental-costs.attachment', $incidentalCost) : asset('storage/default.png') }}">مشاهده فاکتور</button>
                    </div>
                </div>
            </div>
        @endforeach
        @if (count($incidentalCosts) === 0)
            <p class="empty-state">هزینه‌ای تعریف نشده است. موارد تازه اینجا نمایش داده می‌شوند.</p>
        @endif
        <div class="dialog-shell cost-modal hidden" role="dialog" aria-modal="true">
            <div class="bg-white p-6 rounded shadow-md max-w-sm mx-auto rounded-xl">
                <h3 class="text-xl font-semibold mb-4 text-center">
                    فاکتور هزینه
                </h3>
                <img alt="تصویر فاکتور هزینه"
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

<form class="register-form" method="POST" action="{{ config('payments.enabled') ? route('payments.initiate') : URL::route('building.incidentalCostPayment') }}">
    @csrf
    @if (config('payments.enabled'))
        <input type="hidden" name="type" value="cost">
    @endif
    <div class="dialog-shell cost-pay-modal hidden" role="dialog" aria-modal="true" @if ($errors->getBag('incidentalPayCost')->any()) data-reopen @endif>
        <div class="bg-white p-6 rounded shadow-md max-w-sm mx-auto">
            <h3 class="text-xl font-semibold mb-4 text-center">
                پرداخت هزینه جانبی
            </h3>
            <div class="mb-4">
                <input class="border border-gray-300 px-4 py-2 rounded w-full " type="hidden"
                    name="complexId" value="{{ $complex->id }}" />
            </div>
            <div class="mb-4">
                <input id="incidentalCostId" class="border border-gray-300 px-4 py-2 rounded w-full " type="hidden"
                    name="{{ config('payments.enabled') ? 'payable_id' : 'incidentalCostId' }}" value="{{ is_scalar(old(config('payments.enabled') ? 'payable_id' : 'incidentalCostId')) ? old(config('payments.enabled') ? 'payable_id' : 'incidentalCostId') : '' }}" />
            </div>
            <div class="mb-6">
                <label class="sr-only" for="cost-unitNumber-1">شماره واحد</label><input id="cost-unitNumber-1" class="border border-gray-300 px-4 py-2 rounded w-full" type="number" placeholder="شماره واحد"
                    autocomplete="off" name="unitNumber" required min="1" value="{{ is_scalar(old('unitNumber')) ? old('unitNumber') : '' }}" @error('unitNumber', 'incidentalPayCost') aria-invalid="true" aria-describedby="cost-unitNumber-1-error" @enderror />@error('unitNumber', 'incidentalPayCost')<span class="field-error" id="cost-unitNumber-1-error">{{ $message }}</span>@enderror
            </div>
            <div class="mb-6">
                <p class="cost-pay-amount" aria-live="polite">{{ $incidentalCosts->firstWhere('id', old(config('payments.enabled') ? 'payable_id' : 'incidentalCostId'))?->share_amount }}</p>
            </div>
            <div class="flex">
                <button
                    class="bg-green-700 hover:bg-green-800 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline"
                    type="submit">
                    {{ config('payments.enabled') ? 'پرداخت آنلاین' : 'پرداخت' }}
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
    <div class="register-section hidden" data-panel="cost" @if ($errors->getBag('incidentalCost')->any()) data-reopen @endif role="dialog" aria-modal="true"><div class="dialog-panel">
        <div class="register-dialog-tools"><button type="button" class="register-close" aria-label="بستن"><x-icon name="close" /></button></div>
        <div class="buildings-section-header register-container">
            <p>ثبت هزینه جانبی </p>
        </div>
        <div class="register">

            <form class="register-form" method="POST"
                action="{{ URL::route('building.incidentalCost', $complex->id) }}" enctype="multipart/form-data">
                @csrf

                <div class="search-wrapper add-wrapper">
                    <label class="sr-only" for="cost-title-2">عنوان</label><input id="cost-title-2" class="search-input add-input" type="text" name="title" placeholder="عنوان" value="{{ is_scalar(old('title')) ? old('title') : '' }}" @error('title', 'incidentalCost') aria-invalid="true" aria-describedby="cost-title-2-error" @enderror />@error('title', 'incidentalCost')<span class="field-error" id="cost-title-2-error">{{ $message }}</span>@enderror
                </div>
                <div class="search-wrapper add-wrapper">
                    <label class="sr-only" for="cost-cost_explanation-3">توضیح هزینه</label><input id="cost-cost_explanation-3" class="search-input add-input" type="text" name="cost_explanation"
                        placeholder="توضیح هزینه" value="{{ is_scalar(old('cost_explanation')) ? old('cost_explanation') : '' }}" @error('cost_explanation', 'incidentalCost') aria-invalid="true" aria-describedby="cost-cost_explanation-3-error" @enderror />@error('cost_explanation', 'incidentalCost')<span class="field-error" id="cost-cost_explanation-3-error">{{ $message }}</span>@enderror
                </div>

                <label
                    class="uppercase text-lg font-bold text-white bg-gray-500 rounded-lg p-2 text-center cursor-pointer"
                    for="costInvoice">
                    تصویر هزینه
                </label>

                <div>
                    <input
                        class="relative m-0 block w-full min-w-0 flex-auto rounded border border-solid border-neutral-300 bg-clip-padding px-3 py-[0.32rem] text-base font-normal text-neutral-700 transition duration-300 ease-in-out file:-mx-3 file:-my-[0.32rem] file:overflow-hidden file:rounded-none file:border-0 file:border-solid file:border-inherit file:bg-neutral-100 file:px-3 file:py-[0.32rem] file:text-neutral-700 file:transition file:duration-150 file:ease-in-out file:[border-inline-end-width:1px] file:[margin-inline-end:0.75rem] hover:file:bg-gray-100 hover:file:text-black focus:border-primary focus:text-neutral-700 focus:shadow-te-primary focus:outline-none dark:border-neutral-600 dark:text-neutral-200 dark:file:bg-neutral-700 dark:file:text-neutral-100 dark:focus:border-primary"
                        type="file" id="costInvoice" name="cost_invoice" multiple accept="image/gif,image/jpeg,image/png,image/webp" @error('cost_invoice', 'incidentalCost') aria-invalid="true" aria-describedby="costInvoice-error" @enderror />@error('cost_invoice', 'incidentalCost')<span class="field-error" id="costInvoice-error">{{ $message }}</span>@enderror
                </div>

                <div class="search-wrapper add-wrapper">
                    <label class="sr-only" for="cost-total_amount-4">مبلغ کل</label><input id="cost-total_amount-4" class="search-input add-input" type="number" step="0.01" name="total_amount"
                        min="0" placeholder="مبلغ کل" value="{{ is_scalar(old('total_amount')) ? old('total_amount') : '' }}" @error('total_amount', 'incidentalCost') aria-invalid="true" aria-describedby="cost-total_amount-4-error" @enderror />@error('total_amount', 'incidentalCost')<span class="field-error" id="cost-total_amount-4-error">{{ $message }}</span>@enderror
                </div>
                <div class="search-wrapper add-wrapper">
                    <label class="sr-only" for="cost-share_amount-5">سهم پرداختی هر فرد</label><input id="cost-share_amount-5" class="search-input add-input" type="number" step="0.01" name="share_amount"
                        min="0" placeholder="سهم پرداختی هر فرد" value="{{ is_scalar(old('share_amount')) ? old('share_amount') : '' }}" @error('share_amount', 'incidentalCost') aria-invalid="true" aria-describedby="cost-share_amount-5-error" @enderror />@error('share_amount', 'incidentalCost')<span class="field-error" id="cost-share_amount-5-error">{{ $message }}</span>@enderror
                </div>

                <button type="submit" class="view-btn grid-view active">ثبت</button>
            </form>
        </div>
    </div>
</div>
@endif
