<div class="buildings-section charge-window" data-panel="charge" hidden>
    <div class="buildings-section-header justify-center">
        <p> شارژ های ساختمان </p>
    </div>
    <div class="building-boxes jsListView">
        @if (count($monthlyCharges) === 0)
            <p class="empty-state">شارژی تعریف نشده است. موارد تازه اینجا نمایش داده می‌شوند.</p>
        @endif
        @foreach ($monthlyCharges as $monthlyCharge)
            <div class="building-box-wrapper">
                <div class="building-box" >
                    <div class="building-box-content-header">
                        <p class="box-content-header">مبلغ شارژ : {{ $monthlyCharge->amount }} </p>
                    </div>
                    <div class="box-address-wrapper overflow-auto">
                        <p class="box-address-header p-4">تاریخ شروع :
                            {{ implode('-', array_reverse(explode('-', $monthlyCharge->start_date))) }}</p>
                        <p class="box-address-header p-4">تاریخ پایان :
                            {{ implode('-', array_reverse(explode('-', $monthlyCharge->end_date))) }}</p>
                    </div>
                    <div class="building-box-footer flex justify-center gap-2.5">
                        <button type="button" data-amount="{{ $monthlyCharge->amount }}" data-payment-id="{{ $monthlyCharge->id }}" class="text-center charge-pay-open-Btn">{{ config('payments.enabled') ? 'پرداخت آنلاین' : 'پرداخت' }}</button>

                    </div>
                </div>
            </div>
        @endforeach

    </div>
</div>

<form class="register-form" method="POST" action="{{ config('payments.enabled') ? route('payments.initiate') : URL::route('building.buildingChargePayment') }}">
    @csrf
    @if (config('payments.enabled'))
        <input type="hidden" name="type" value="charge">
    @endif
    <div class="dialog-shell charge-pay-modal hidden" role="dialog" aria-modal="true" @if ($errors->getBag('buildingPayCharge')->any()) data-reopen @endif>
        <div class="bg-white p-6 rounded shadow-md max-w-sm mx-auto">
            <h3 class="text-xl font-semibold mb-4 text-center">
                پرداخت شارژ ماهیانه
            </h3>
            <div class="mb-4">
                <input class="border border-gray-300 px-4 py-2 rounded w-full " type="hidden"
                    name="complexId" value="{{ $complex->id }}" />
            </div>
            <div class="mb-4">
                <input id="monthlyChargeId" class="border border-gray-300 px-4 py-2 rounded w-full " type="hidden"
                    name="{{ config('payments.enabled') ? 'payable_id' : 'monthlyChargeId' }}" value="{{ is_scalar(old(config('payments.enabled') ? 'payable_id' : 'monthlyChargeId')) ? old(config('payments.enabled') ? 'payable_id' : 'monthlyChargeId') : '' }}" />
            </div>
            <div class="mb-6">
                <label class="sr-only" for="charge-unitNumber-1">شماره واحد</label><input id="charge-unitNumber-1" class="border border-gray-300 px-4 py-2 rounded w-full" type="number" placeholder="شماره واحد"
                    autocomplete="off" name="unitNumber" required min="1" value="{{ is_scalar(old('unitNumber')) ? old('unitNumber') : '' }}" @error('unitNumber', 'buildingPayCharge') aria-invalid="true" aria-describedby="charge-unitNumber-1-error" @enderror />@error('unitNumber', 'buildingPayCharge')<span class="field-error" id="charge-unitNumber-1-error">{{ $message }}</span>@enderror
            </div>
            <div class="mb-6">
                <p class="charge-pay-amount" aria-live="polite">{{ $monthlyCharges->firstWhere('id', old(config('payments.enabled') ? 'payable_id' : 'monthlyChargeId'))?->amount }}</p>
            </div>
            <div class="flex">
                <button
                    class="bg-green-700 hover:bg-green-800 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline"
                    type="submit">
                    {{ config('payments.enabled') ? 'پرداخت آنلاین' : 'پرداخت' }}
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
    <div class="register-section hidden" data-panel="charge" @if ($errors->getBag('buildingCharge')->any()) data-reopen @endif role="dialog" aria-modal="true"><div class="dialog-panel">
        <button type="button" class="register-close" aria-label="بستن"><x-icon name="close" /></button>
        <div class="buildings-section-header register-container">
            <p>ثبت شارژ ساختمان </p>
        </div>
        <div class="register">

            <form class="register-form" method="POST"
                action="{{ URL::route('building.buildingCharge', $complex->id) }}">
                @csrf
                <div class="search-wrapper add-wrapper">
                    <label class="sr-only" for="charge-chargeAmount-2">مبلغ شارژ</label><input id="charge-chargeAmount-2" class="search-input add-input" type="number" step="0.01" name="chargeAmount"
                        placeholder="مبلغ شارژ" min="0" value="{{ is_scalar(old('chargeAmount')) ? old('chargeAmount') : '' }}" @error('chargeAmount', 'buildingCharge') aria-invalid="true" aria-describedby="charge-chargeAmount-2-error" @enderror />@error('chargeAmount', 'buildingCharge')<span class="field-error" id="charge-chargeAmount-2-error">{{ $message }}</span>@enderror
                </div>

                <label class="uppercase text-lg font-bold text-white bg-gray-500 rounded-lg p-2">
                    تاریخ شروع شارژ
                </label>
                <div class="flex flex-wrap mt-3">
                    <div class="w-full md:w-1/3 px-3 mb-6 md:mb-0">
                        <label class="sr-only" for="charge-dayStart-3">روز</label><input id="charge-dayStart-3"
                            class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-3 px-4 leading-tight focus:outline-none focus:bg-white focus:border-gray-500"
                            type="number" name="dayStart" placeholder="روز" min="1" max="31" value="{{ is_scalar(old('dayStart')) ? old('dayStart') : '' }}" @error('dayStart', 'buildingCharge') aria-invalid="true" aria-describedby="charge-dayStart-3-error" @enderror />@error('dayStart', 'buildingCharge')<span class="field-error" id="charge-dayStart-3-error">{{ $message }}</span>@enderror
                    </div>
                    <div class="w-full md:w-1/3 px-3 mb-6 md:mb-0">
                        <label class="sr-only" for="charge-monthStart-4">ماه</label><input id="charge-monthStart-4"
                            class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-3 px-4 leading-tight focus:outline-none focus:bg-white focus:border-gray-500"
                            type="number" name="monthStart" placeholder="ماه" min="1" max="12" value="{{ is_scalar(old('monthStart')) ? old('monthStart') : '' }}" @error('monthStart', 'buildingCharge') aria-invalid="true" aria-describedby="charge-monthStart-4-error" @enderror />@error('monthStart', 'buildingCharge')<span class="field-error" id="charge-monthStart-4-error">{{ $message }}</span>@enderror
                    </div>
                    <div class="w-full md:w-1/3 px-3 mb-6 md:mb-0">
                        <label class="sr-only" for="charge-yearStart-5">سال</label><input id="charge-yearStart-5"
                            class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-3 px-4 leading-tight focus:outline-none focus:bg-white focus:border-gray-500"
                            type="number" name="yearStart" placeholder="سال" min="1300" value="{{ is_scalar(old('yearStart')) ? old('yearStart') : '' }}" @error('yearStart', 'buildingCharge') aria-invalid="true" aria-describedby="charge-yearStart-5-error" @enderror />@error('yearStart', 'buildingCharge')<span class="field-error" id="charge-yearStart-5-error">{{ $message }}</span>@enderror
                    </div>
                </div>

                <label class="uppercase text-lg font-bold text-white bg-gray-500 rounded-lg p-2">
                    تاریخ پایان شارژ
                </label>
                <div class="flex flex-wrap mt-3">
                    <div class="w-full md:w-1/3 px-3 mb-6 md:mb-0">
                        <label class="sr-only" for="charge-dayEnd-6">روز</label><input id="charge-dayEnd-6"
                            class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-3 px-4 leading-tight focus:outline-none focus:bg-white focus:border-gray-500"
                            type="number" name="dayEnd" placeholder="روز" min="1" max="31" value="{{ is_scalar(old('dayEnd')) ? old('dayEnd') : '' }}" @error('dayEnd', 'buildingCharge') aria-invalid="true" aria-describedby="charge-dayEnd-6-error" @enderror />@error('dayEnd', 'buildingCharge')<span class="field-error" id="charge-dayEnd-6-error">{{ $message }}</span>@enderror
                    </div>
                    <div class="w-full md:w-1/3 px-3 mb-6 md:mb-0">
                        <label class="sr-only" for="charge-monthEnd-7">ماه</label><input id="charge-monthEnd-7"
                            class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-3 px-4 leading-tight focus:outline-none focus:bg-white focus:border-gray-500"
                            type="number" name="monthEnd" placeholder="ماه" min="1" max="12" value="{{ is_scalar(old('monthEnd')) ? old('monthEnd') : '' }}" @error('monthEnd', 'buildingCharge') aria-invalid="true" aria-describedby="charge-monthEnd-7-error" @enderror />@error('monthEnd', 'buildingCharge')<span class="field-error" id="charge-monthEnd-7-error">{{ $message }}</span>@enderror
                    </div>
                    <div class="w-full md:w-1/3 px-3 mb-6 md:mb-0">
                        <label class="sr-only" for="charge-yearEnd-8">سال</label><input id="charge-yearEnd-8"
                            class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-3 px-4 leading-tight focus:outline-none focus:bg-white focus:border-gray-500"
                            type="number" name="yearEnd" placeholder="سال" min="1300" value="{{ is_scalar(old('yearEnd')) ? old('yearEnd') : '' }}" @error('yearEnd', 'buildingCharge') aria-invalid="true" aria-describedby="charge-yearEnd-8-error" @enderror />@error('yearEnd', 'buildingCharge')<span class="field-error" id="charge-yearEnd-8-error">{{ $message }}</span>@enderror
                    </div>
                </div>
                <button type="submit" class="view-btn grid-view active">ثبت</button>
            </form>
        </div>
    </div>
</div>
@endif
