<section class="buildings-section paymentInfo-window" data-panel="paymentInfo" hidden>
    <div class="buildings-section-header">
        <p>پرداختی های ساختمان</p>
        <button class="action" type="button" data-print disabled>چاپ گزارش</button>
    </div>
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
                </tr></thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
    @if (config('payments.enabled'))
        @include('building.online-payments')
    @endif
</section>
