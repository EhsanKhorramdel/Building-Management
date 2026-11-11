@extends('layouts.account-document')
@section('title', 'اعلان‌های من')
@section('actions')
    <a class="action" href="{{ route('dashboard') }}">بازگشت به پنل</a>
@endsection
@section('content')
    <div class="space-y-3">
        @forelse ($notices as $notice)
            <article class="rounded-lg border border-[var(--line)] p-3">
                <strong>{{ \App\Services\AccountNotifications::LABELS[$notice->type] ?? '' }}</strong>
                <p>{{ $notice->data['detail'] }}</p>
                <a class="action" href="{{ route('building', ['complex' => $notice->data['complex_id'], 'tab' => $notice->data['panel']]) }}">مشاهده در ساختمان</a>
            </article>
        @empty
            <p>اعلانی ثبت نشده است.</p>
        @endforelse
        {{ $notices->links() }}
    </div>
@endsection
