@extends('layouts.payment-document')
@section('title', 'رسید پرداخت')
@section('actions')
    <button type="button" class="action" data-print-receipt>چاپ یا ذخیره PDF</button>
@endsection
@section('content')
    <dl class="payment-receipt">
        <div><dt>شماره رسید</dt><dd><bdi dir="ltr" data-receipt-number>{{ $number }}</bdi></dd></div>
        <div><dt>ساختمان</dt><dd>{{ $complex }}</dd></div>
        <div><dt>واحد</dt><dd>{{ $unit }}</dd></div>
        <div><dt>پرداخت‌کننده</dt><dd>{{ $payer }}</dd></div>
        <div><dt>مورد پرداخت</dt><dd>{{ $item }}</dd></div>
        <div><dt>دوره شمسی</dt><dd><bdi dir="ltr">{{ $period }}</bdi></dd></div>
        <div><dt>تاریخ پرداخت</dt><dd><bdi dir="ltr">{{ $date }}</bdi></dd></div>
        <div><dt>مبلغ</dt><dd>{{ $amount }}</dd></div>
        <div><dt>روش پرداخت</dt><dd>{{ $method }}</dd></div>
        @if ($reference !== null)
            <div><dt>شماره پیگیری</dt><dd><bdi dir="ltr">{{ $reference }}</bdi></dd></div>
        @endif
    </dl>
@endsection
