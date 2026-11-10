<?php

use App\Models\ChargePayment;
use App\Models\IncidentalCost;
use App\Models\MonthlyCharge;
use App\Models\PaymentAdditionalCost;
use App\Services\PaymentService;
use Carbon\Carbon;
use Morilog\Jalali\Jalalian;
use Tests\Support\BuildingContext;

test('payment service owns authoritative amounts duplicate outcomes and Jalali period fields', function () {
    Carbon::setTestNow((new Jalalian(1405, 1, 31))->toCarbon()->setTime(12, 0));
    try {
        $building = new BuildingContext;
        $resident = $building->resident();
        $charge = MonthlyCharge::factory()->create(['complex_id' => $building->complex->id, 'start_date' => '1405-01-01', 'end_date' => '1405-12-29', 'amount' => 200]);
        $cost = IncidentalCost::factory()->create(['complex_id' => $building->complex->id, 'share_amount' => 50]);
        $service = app(PaymentService::class);
        $data = ['complexId' => $building->complex->id, 'unitNumber' => $resident->unit->unit_number, 'monthlyChargeId' => $charge->id, 'Amount' => 1];
        expect($service->buildingChargePayment($data, $resident->user_id)['status'])->toBe('success');
        $this->assertDatabaseHas('charge_payments', ['role_id' => $resident->id, 'amount' => 200, 'period_year' => 1405, 'period_month' => 1]);
        expect($service->buildingChargePayment($data, $resident->user_id)['status'])->toBe('error');
        Carbon::setTestNow((new Jalalian(1405, 2, 1))->toCarbon()->setTime(12, 0));
        expect($service->buildingChargePayment($data, $resident->user_id)['status'])->toBe('success');
        expect(ChargePayment::orderBy('id')->pluck('period_month')->all())->toBe([1, 2]);
        $data['incidentalCostId'] = $cost->id;
        expect($service->incidentalCostPayment($data, $resident->user_id)['status'])->toBe('success');
        expect((float) PaymentAdditionalCost::firstOrFail()->amount)->toBe(50.0);
        expect($service->incidentalCostPayment($data, $resident->user_id)['status'])->toBe('error');
    } finally {
        Carbon::setTestNow();
    }
});
