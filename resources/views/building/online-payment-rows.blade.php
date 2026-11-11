@foreach ($onlineTransactions as $transaction)
    <tr data-transaction-id="{{ $transaction->id }}">
        <td>{{ $transaction->itemLabel() }}</td>
        <td>{{ $transaction->amount }}</td>
        <td>{{ __('integrations::payments.status.'.$transaction->status) }}</td>
        <td class="break-all">
            <bdi dir="ltr">{{ $transaction->reference_id ?? '—' }}</bdi>
            @if ($receipt = $transaction->receiptUrl())<a class="action" href="{{ $receipt }}">رسید</a>@endif
        </td>
    </tr>
@endforeach
