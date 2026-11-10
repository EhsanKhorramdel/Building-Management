<?php

use App\Models\ChargePayment;
use App\Models\IncidentalCost;
use App\Models\MonthlyCharge;
use App\Models\PaymentAdditionalCost;
use Tests\Support\BuildingContext;

test('payment report includes charge and incidental payments for a residents unit', function () {
    $building = new BuildingContext;
    $resident = $building->resident();
    $charge = MonthlyCharge::factory()->create(['complex_id' => $building->complex->id]);
    $cost = IncidentalCost::factory()->create(['complex_id' => $building->complex->id]);
    ChargePayment::factory()->create(['role_id' => $resident->id, 'monthly_charge_id' => $charge->id]);
    PaymentAdditionalCost::factory()->create(['role_id' => $resident->id, 'incidental_costs_id' => $cost->id]);

    $this->actingAs($building->manager)->postJson(route('building.payments'), ['complex_id' => $building->complex->id])
        ->assertOk()->assertJsonCount(2)->assertJsonFragment(['unit_number' => $resident->unit->unit_number, 'user_name' => $resident->user->name]);
});
