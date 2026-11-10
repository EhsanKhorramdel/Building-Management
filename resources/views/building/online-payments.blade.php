<section class="mt-4 space-y-3" data-online-payments>
    <h2 class="text-lg font-bold">پرداخت‌های آنلاین من</h2>
    <div class="flex flex-wrap gap-2">
        <button type="button" class="action print:hidden" data-payment-tab="charge">پرداخت آنلاین شارژ</button>
        <button type="button" class="action print:hidden" data-payment-tab="cost">پرداخت آنلاین هزینه جانبی</button>
    </div>
    @if ($onlineTransactions->isEmpty())
        <p class="empty-state">هنوز پرداخت آنلاینی ثبت نشده است.</p>
    @else
        <div class="table-scroll" tabindex="0" aria-label="پرداخت‌های آنلاین من">
            <table class="payments-table !min-w-0 table-fixed break-words">
                <caption class="sr-only">پرداخت‌های آنلاین من</caption>
                <thead><tr>
                    <th scope="col">مورد پرداخت</th>
                    <th scope="col">مبلغ</th>
                    <th scope="col">وضعیت</th>
                    <th scope="col">شماره پیگیری</th>
                </tr></thead>
                <tbody>
                    @foreach ($onlineTransactions as $transaction)
                        <tr>
                            <td>{{ $transaction->itemLabel() }}</td>
                            <td>{{ $transaction->amount }}</td>
                            <td>{{ __('integrations::payments.status.'.$transaction->status) }}</td>
                            <td class="break-all"><bdi dir="ltr">{{ $transaction->reference_id ?? '—' }}</bdi></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</section>
