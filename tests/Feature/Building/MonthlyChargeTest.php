<?php

use App\Models\MonthlyCharge;
use Tests\Support\BuildingContext;

test('a manager can create a charge with Jalali date fields', function () {
    $building = new BuildingContext;

    $this->actingAs($building->manager)->post(route('building.buildingCharge', $building->complex), [
        'chargeAmount' => 250000, 'yearStart' => 1405, 'monthStart' => 2, 'dayStart' => 1,
        'yearEnd' => 1405, 'monthEnd' => 3, 'dayEnd' => 1,
    ])->assertSessionHasNoErrors()->assertSessionHas('status', 'success');

    $charge = MonthlyCharge::firstOrFail();
    expect($charge->complex_id)->toBe($building->complex->id)->and($charge->user_id)->toBe($building->manager->id)
        ->and((float) $charge->amount)->toBe(250000.0)
        ->and(array_map('intval', explode('-', $charge->start_date)))->toBe([1405, 2, 1])
        ->and(array_map('intval', explode('-', $charge->end_date)))->toBe([1405, 3, 1]);
});

test('negative charge amounts are rejected', function () {
    $building = new BuildingContext;

    $this->actingAs($building->manager)->post(route('building.buildingCharge', $building->complex), ['chargeAmount' => -1])
        ->assertSessionHasErrorsIn('buildingCharge', 'chargeAmount');

    $this->assertDatabaseCount('monthly_charges', 0);
});
