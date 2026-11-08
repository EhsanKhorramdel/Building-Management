<?php

use App\Models\ChargePayment;
use App\Models\MonthlyCharge;
use Carbon\Carbon;
use Morilog\Jalali\Jalalian;
use Tests\Support\BuildingContext;

test('a charge can be paid once per Jalali month and again in the next Jalali month', function () {
    $firstDate = (new Jalalian(1405, 1, 31, 12, 0, 0))->toCarbon();
    $nextDate = (new Jalalian(1405, 2, 1, 12, 0, 0))->toCarbon();
    Carbon::setTestNow($firstDate);

    try {
        $building = new BuildingContext;
        $resident = $building->resident();
        $charge = MonthlyCharge::factory()->create(['complex_id' => $building->complex->id, 'user_id' => $building->manager->id, 'start_date' => '1405-01-01', 'end_date' => '1405-12-29']);
        $payload = ['complexId' => $building->complex->id, 'unitNumber' => $resident->unit->unit_number, 'monthlyChargeId' => $charge->id, 'Amount' => $charge->amount];

        $this->actingAs($resident->user)->post(route('building.buildingChargePayment'), $payload)->assertSessionHas('status', 'success');
        $this->assertDatabaseCount('charge_payments', 1);
        $this->post(route('building.buildingChargePayment'), $payload)->assertSessionHas('status', 'error');
        $this->assertDatabaseCount('charge_payments', 1);

        Carbon::setTestNow($nextDate);
        $this->post(route('building.buildingChargePayment'), $payload)->assertSessionHas('status', 'success');
        $payments = ChargePayment::orderBy('id')->get();
        expect($payments)->toHaveCount(2)
            ->and($payments->pluck('role_id')->unique()->all())->toBe([$resident->id])
            ->and($payments->pluck('monthly_charge_id')->unique()->all())->toBe([$charge->id])
            ->and($payments->map(fn ($payment) => Jalalian::fromCarbon($payment->created_at)->format('Y-m'))->all())->toBe(['1405-01', '1405-02']);
        expect($firstDate->month)->toBe($nextDate->month);
    } finally {
        Carbon::setTestNow();
    }
});
