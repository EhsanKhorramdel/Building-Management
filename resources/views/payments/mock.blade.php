<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>پرداخت آزمایشی</title>
    <link rel="icon" href="{{ asset('assets/images/favIcon/apartment.ico') }}">
    <x-page-assets />
</head>
<body class="auth-page">
    <x-theme-button class="page-theme" />
    <main class="auth-shell">
        <section class="auth-card">
            <h1 class="mb-2 text-xl font-bold">پرداخت آزمایشی</h1>
            <p class="mb-4">این پرداخت آزمایشی است و مبلغی کسر نمی‌شود.</p>
            <dl class="mb-5 space-y-2 break-words">
                <div><dt class="field-label">ساختمان</dt><dd>{{ $transaction->payable->complex->name }}</dd></div>
                <div><dt class="field-label">مورد پرداخت</dt><dd>{{ $transaction->itemLabel() }}</dd></div>
                <div><dt class="field-label">مبلغ</dt><dd>{{ $transaction->amount }}</dd></div>
            </dl>
            <div class="flex flex-wrap gap-2">
                @foreach (['pay' => 'پرداخت', 'cancel' => 'لغو'] as $outcome => $label)
                    <form method="POST" action="{{ route('payments.callback', 'mock') }}">
                        @csrf
                        <input type="hidden" name="authority" value="{{ $transaction->authority }}">
                        <input type="hidden" name="amount" value="{{ $transaction->amount }}">
                        <input type="hidden" name="outcome" value="{{ $outcome }}">
                        <button type="submit" class="action {{ $outcome === 'cancel' ? 'action-danger' : '' }}" data-mock-outcome="{{ $outcome }}">{{ $label }}</button>
                    </form>
                @endforeach
            </div>
        </section>
    </main>
</body>
</html>
