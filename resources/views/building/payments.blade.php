<section class="buildings-section paymentInfo-window" data-panel="paymentInfo" hidden>
    <div class="buildings-section-header">
        <p>پرداختی های ساختمان</p>
        <div class="flex flex-wrap gap-2 print:hidden">
            <button class="action" type="button" data-print disabled>چاپ گزارش</button>
            <button class="action" type="button" data-report-export disabled>دریافت CSV</button>
            <a class="action" href="{{ route('payments.receipts.complex', $complex) }}">رسیدها</a>
            @if ($isManager)<a class="action" href="{{ route('payments.reconciliation.complex', $complex) }}">بررسی پرداخت‌ها</a>@endif
        </div>
    </div>
    <details class="payment-filters print:hidden">
        <summary>فیلتر گزارش</summary>
        <form data-report-filters data-export-url="{{ route('building.payments.export') }}">
            <div class="payment-filter-grid">
                <label>سال شارژ <input name="period_year" inputmode="numeric"></label>
                <label>ماه شارژ <input name="period_month" inputmode="numeric"></label>
                <label>پرداخت از تاریخ شمسی <input name="paid_from" inputmode="numeric" placeholder="۱۴۰۵/۰۸/۰۱"></label>
                <label>پرداخت تا تاریخ شمسی <input name="paid_to" inputmode="numeric" placeholder="۱۴۰۵/۰۸/۳۰"></label>
                <label>شماره واحد <input name="unit_number" inputmode="numeric"></label>
                <label>نوع پرداخت <select name="type"><option value="">همه</option><option value="charge">شارژ</option><option value="cost">جانبی</option></select></label>
            </div>
            <div class="flex flex-wrap gap-2 mt-3">
                <button class="action" type="submit">اعمال فیلتر</button>
                <button class="action" type="button" data-report-reset>پاک کردن فیلترها</button>
            </div>
        </form>
    </details>
    <p class="mb-2" data-report-scope data-complex-name="{{ $complex->name }}" aria-live="polite">{{ $complex->name }} · وضعیت: پرداخت‌شده · همه واحدها</p>
    <p class="empty-state" role="status" data-payments-state hidden></p>
    <p class="report-scroll-cue" data-report-scroll-cue hidden>برای دیدن ستون‌های بیشتر، جدول را به چپ بکشید.</p>
    <div class="report-scroll-frame" data-report-scroll-frame>
        <div class="table-scroll" tabindex="0" aria-label="جدول پرداخت‌ها" data-payments-table hidden>
            <table class="payments-table">
                <caption class="sr-only">گزارش پرداخت‌های ساختمان</caption>
                <thead><tr>
                    <th scope="col">نام</th>
                    <th scope="col">شماره واحد</th>
                    <th scope="col">نوع پرداخت</th>
                    <th scope="col">تاریخ پرداخت</th>
                    <th scope="col">مبلغ</th>
                    <th scope="col" class="print:hidden">رسید</th>
                </tr></thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
    @if (config('payments.enabled'))
        <div class="print:hidden">@include('building.online-payments')</div>
    @endif
</section>
