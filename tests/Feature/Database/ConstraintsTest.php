<?php

use App\Models\ChargePayment;
use App\Models\PaymentAdditionalCost;
use App\Models\SeenMessage;
use App\Models\Unit;
use App\Models\UserRole;
use App\Models\Vote;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

test('the database rejects a duplicate domain key', function (string $model) {
    $row = $model::factory()->create();
    $attributes = $row->getAttributes();
    unset($attributes['id']);

    expect(fn () => DB::table($row->getTable())->insert($attributes))->toThrow(QueryException::class);
})->with([Vote::class, SeenMessage::class, Unit::class, PaymentAdditionalCost::class, UserRole::class, ChargePayment::class]);

test('unassigned roles may repeat for the same user and complex', function () {
    $role = UserRole::factory()->create(['unit_id' => null]);
    UserRole::factory()->create(['complex_id' => $role->complex_id, 'user_id' => $role->user_id, 'unit_id' => null]);

    expect(UserRole::where('user_id', $role->user_id)->where('complex_id', $role->complex_id)->whereNull('unit_id')->count())->toBe(2);
});

test('a charge can be recorded in different Jalali periods at database level', function () {
    $payment = ChargePayment::factory()->create(['period_year' => 1405, 'period_month' => 1]);
    $attributes = $payment->getAttributes();
    unset($attributes['id']);
    $attributes['period_month'] = 2;
    DB::table('charge_payments')->insert($attributes);
    $attributes['period_year'] = 1406;
    $attributes['period_month'] = 1;
    DB::table('charge_payments')->insert($attributes);

    expect(ChargePayment::count())->toBe(3);
});

test('charge payments require explicit period columns', function () {
    $payment = ChargePayment::factory()->create();
    $attributes = $payment->getAttributes();
    unset($attributes['id'], $attributes['period_year'], $attributes['period_month']);

    expect(fn () => DB::table('charge_payments')->insert($attributes))->toThrow(QueryException::class);
    expect($payment->isFillable('date'))->toBeFalse();
});
