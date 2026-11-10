<?php

use App\Models\IncidentalCost;
use Tests\Support\BuildingContext;

test('a resident can record an incidental cost payment only once', function () {
    $building = new BuildingContext;
    $resident = $building->resident();
    $cost = IncidentalCost::factory()->create(['complex_id' => $building->complex->id]);
    $payload = ['complexId' => $building->complex->id, 'unitNumber' => $resident->unit->unit_number, 'incidentalCostId' => $cost->id, 'Amount' => $cost->share_amount];

    $this->actingAs($resident->user)->post(route('building.incidentalCostPayment'), $payload)->assertSessionHas('status', 'success');
    $this->assertDatabaseHas('payment_additional_costs', ['role_id' => $resident->id, 'incidental_costs_id' => $cost->id, 'amount' => $cost->share_amount]);
    $this->post(route('building.incidentalCostPayment'), $payload)->assertSessionHas('status', 'error');
    $this->assertDatabaseCount('payment_additional_costs', 1);
});
