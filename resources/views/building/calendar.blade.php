@extends('layouts.account-document')
@section('title')تقویم ساختمان {{ $complex->name }}@endsection
@section('actions')
    <a class="action" href="{{ route('building', [$complex, 'tab' => 'home']) }}">بازگشت به ساختمان</a>
@endsection
@section('content')
    <div class="space-y-4">
        <nav aria-label="انتخاب ماه" class="flex flex-wrap items-center gap-3">
            <a class="action" href="{{ route('building.calendar', [$complex, 'year' => $previous->getYear(), 'month' => $previous->getMonth()]) }}">ماه قبل</a>
            <strong>{{ $month->format('F Y') }}</strong>
            <a class="action" href="{{ route('building.calendar', [$complex, 'year' => $next->getYear(), 'month' => $next->getMonth()]) }}">ماه بعد</a>
        </nav>
        <ol class="grid grid-cols-2 gap-2 sm:grid-cols-4 lg:grid-cols-7">
            @for ($day = 1; $day <= $days; $day++)
                <li class="min-w-0 rounded-lg border border-[var(--line)] p-2">
                    <span>{{ $day }}</span>
                    @foreach ($entries[$day] ?? [] as $entry)
                        <a class="flex min-h-10 items-center break-words underline" href="{{ route('building', [$complex, 'tab' => $entry['panel']]) }}">{{ $entry['label'] }}</a>
                    @endforeach
                </li>
            @endfor
        </ol>
    </div>
@endsection
