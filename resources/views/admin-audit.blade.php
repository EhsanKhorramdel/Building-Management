@extends('layouts.account-document')
@section('title', 'جزئیات رویدادها')
@section('actions')
    <a class="action" href="{{ route('adminPanel.index') }}">بازگشت به پنل ادمین</a>
@endsection
@section('content')
    <x-audit-events detail />
@endsection
