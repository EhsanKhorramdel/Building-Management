<?php

use App\Models\UserRole;
use Tests\Support\BuildingContext;

test('a manager can promote a resident in their building', function () {
    $building = new BuildingContext;
    $resident = $building->resident();

    $this->actingAs($building->manager)->post(route('building.upgradeRole', $resident))->assertSessionHas('status', 'success');

    expect($resident->refresh()->role)->toBe('manager');
});

test('a manager can step down when another manager remains', function () {
    $building = new BuildingContext;
    UserRole::factory()->manager()->create(['complex_id' => $building->complex->id]);

    $this->actingAs($building->manager)->post(route('building.downgradeRole', $building->complex))->assertRedirect('/login');

    expect($building->managerRole->refresh()->role)->toBe('resident');
    $this->assertGuest();
});

test('the last manager cannot step down', function () {
    $building = new BuildingContext;

    $this->actingAs($building->manager)->post(route('building.downgradeRole', $building->complex))->assertSessionHas('status', 'error');

    expect($building->managerRole->refresh()->role)->toBe('manager');
});
