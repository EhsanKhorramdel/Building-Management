@extends('layouts.account-document')
@section('title')رویدادهای ساختمان {{ $complex->name }}@endsection
@section('actions')
    <a class="action" href="{{ route('building', [$complex, 'tab' => 'setting']) }}">بازگشت به تنظیمات ساختمان</a>
@endsection
@section('content')
    <x-audit-events :complex="$complex" detail />
@endsection
