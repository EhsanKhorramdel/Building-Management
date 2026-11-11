@extends('layouts.payment-document')
@section('title', 'بررسی پرداخت‌های آنلاین')
@section('actions')
    <a class="action" href="{{ $complex ? route('building', $complex) : route('adminPanel.index') }}">بازگشت</a>
@endsection
@section('content')
    @if ($complex)<p class="mb-3">{{ $complex->name }}</p>@endif
    @forelse ($attempts as $attempt)
        <article class="payment-attempt" data-reconciliation-attempt="{{ $attempt->id }}">
            <h2 class="font-bold">{{ $attempt->itemLabel() }} · <bdi dir="ltr">#{{ $attempt->id }}</bdi></h2>
            <p>{{ $attempt->payable?->complex?->name ?? $attempt->role?->complex?->name ?? '—' }} · {{ $attempt->amount }}</p>
            <p>{{ __('integrations::payments.status.'.$attempt->status) }}
                @if ($attempt->status === 'pending' && $attempt->created_at?->lte($cutoff)) · مهلت گذشته @endif
            </p>
            @if ($attempt->reference_id !== null)
                <p>شماره پیگیری <bdi dir="ltr">••••{{ mb_strlen($attempt->reference_id) > 4 ? mb_substr($attempt->reference_id, -4) : '' }}</bdi></p>
            @endif
            @if ($attempt->callbackIssues->contains('kind', 'mismatch') || in_array(data_get($attempt->meta, 'failure_reason'), ['amount_mismatch', 'payment_conflict', 'invalid_status'], true))
                <p>عدم تطابق پاسخ پرداخت</p>
            @endif
            @if ($attempt->callbackIssues->contains('kind', 'duplicate_callback'))<p>پاسخ تکراری پرداخت</p>@endif
            @if ($attempt->transitions->isNotEmpty())
                <ol class="payment-timeline" aria-label="تاریخچه وضعیت">
                    @foreach ($attempt->transitions as $transition)
                        <li>
                            {{ $transition->from_state ? __('integrations::payments.status.'.$transition->from_state) : '—' }}
                            ← {{ __('integrations::payments.status.'.$transition->to_state) }}
                            · {{ $transition->actor_id === null ? 'سامانه' : 'کاربر '.$transition->actor_id }}
                            · <bdi dir="ltr">{{ \Morilog\Jalali\Jalalian::fromCarbon($transition->created_at)->format('Y/m/d H:i:s') }}</bdi>
                        </li>
                    @endforeach
                </ol>
            @endif
        </article>
    @empty
        <p class="empty-state">پرداختی برای بررسی وجود ندارد.</p>
    @endforelse
    @include('payments.page-links', ['page' => $attempts])
@endsection
