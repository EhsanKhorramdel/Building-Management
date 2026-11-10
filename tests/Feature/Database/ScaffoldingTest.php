<?php

use App\Models\Complex;
use App\Models\MonthlyCharge;
use App\Models\Poll;
use App\Models\SmsLog;
use App\Models\Transaction;
use App\Models\UserRole;
use Carbon\CarbonInterface;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

test('future integration records support neutral statuses nullable ownership and json', function () {
    $transaction = Transaction::create(['amount' => 12.5, 'meta' => ['attempt' => 1]]);
    $sms = SmsLog::create(['recipient' => 'local-demo', 'meta' => ['purpose' => 'test']]);

    expect($transaction->fresh()->status)->toBe('pending')
        ->and($transaction->fresh()->amount)->toBe('12.50')
        ->and($transaction->fresh()->payable_id)->toBeNull()
        ->and($transaction->fresh()->user_id)->toBeNull()
        ->and($transaction->fresh()->meta)->toBe(['attempt' => 1])
        ->and($sms->fresh()->status)->toBe('logged')
        ->and($sms->fresh()->meta)->toBe(['purpose' => 'test']);
    $transaction->update(['status' => 'awaiting_review']);
    $sms->update(['status' => 'queued']);
    expect($transaction->fresh()->status)->toBe('awaiting_review')->and($sms->fresh()->status)->toBe('queued');
    $complex = Complex::factory()->create();
    expect($complex->latitude)->toBeNull()->and($complex->longitude)->toBeNull();
    DB::table('complexes')->where('id', $complex->id)->update(['latitude' => 35.689198, 'longitude' => 51.388974]);
    expect((float) $complex->fresh()->latitude)->toBe(35.689198);
});

test('transaction ownership foreign keys reject nonexistent records', function (string $field) {
    expect(fn () => Transaction::create(['amount' => 1, $field => 999999]))->toThrow(QueryException::class);
})->with(['user_id', 'role_id']);

test('stored Jalali charge strings and real date casts retain their formats', function () {
    $charge = MonthlyCharge::factory()->create(['start_date' => '1405-01-31', 'end_date' => '1405-02-01']);
    $poll = Poll::factory()->create();
    $role = UserRole::factory()->create(['start_date' => '2026-04-20', 'end_date' => '2026-04-21']);

    expect($charge->fresh()->start_date)->toBe('1405-01-31')
        ->and($charge->fresh()->end_date)->toBe('1405-02-01')
        ->and($poll->fresh()->start_date)->toBeInstanceOf(CarbonInterface::class)
        ->and($poll->fresh()->end_date)->toBeInstanceOf(CarbonInterface::class)
        ->and($role->fresh()->start_date->toDateString())->toBe('2026-04-20')
        ->and($role->fresh()->end_date->toDateString())->toBe('2026-04-21');
});
