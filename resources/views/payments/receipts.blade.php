@extends('layouts.payment-document')
@section('title', 'رسیدهای پرداخت')
@section('actions')
    <a class="action" href="{{ $complex ? route('building', $complex) : route('adminPanel.index') }}">بازگشت</a>
@endsection
@section('content')
    <nav class="flex flex-wrap gap-2 mb-4" aria-label="نوع رسید">
        @foreach (['charge' => 'شارژ', 'cost' => 'هزینه جانبی'] as $kind => $label)
            <a class="action" href="?type={{ $kind }}" @if ($kind === $type) aria-current="page" @endif>{{ $label }}</a>
        @endforeach
    </nav>
    @forelse ($payments as $payment)
        <article class="payment-attempt">
            <p>{{ $payment->$relation?->complex?->name ?? '—' }} · {{ $payment->role?->unit?->unit_number ?? '—' }}</p>
            <p>{{ ($payment->transaction ? $payment->transaction->user?->name : $payment->role?->user?->name) ?? '—' }} · {{ $payment->amount }}</p>
            <a class="action" href="{{ route('payments.receipt', [$type, $payment->id]) }}">رسید {{ $payment->id }}</a>
        </article>
    @empty
        <p class="empty-state">در این بخش رسیدی ثبت نشده است.</p>
    @endforelse
    @include('payments.page-links', ['page' => $payments])
@endsection
