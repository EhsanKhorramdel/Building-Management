<?php

use App\Models\Unit;
use App\Models\UserRole;

test('adding a unit completes an unassigned membership', function () {
    $role = UserRole::factory()->unassigned()->create();

    $this->actingAs($role->user)->from('/dashboard')->post(route('dashboard.addUnit'), [
        'complexId' => $role->complex_id, 'unitNumber' => 4, 'floorNumber' => 2, 'zipCode' => '1234567890',
    ])->assertRedirect('/dashboard')->assertSessionHas('status', 'success');

    $unit = Unit::where('complex_id', $role->complex_id)->firstOrFail();
    expect($role->refresh()->unit_id)->toBe($unit->id)->and($unit->unit_number)->toBe(4);
    $this->assertDatabaseCount('user_roles', 1);
});

test('an occupied unit cannot be added again', function () {
    $occupied = UserRole::factory()->create();
    $joining = UserRole::factory()->unassigned()->create(['complex_id' => $occupied->complex_id]);

    $this->actingAs($joining->user)->post(route('dashboard.addUnit'), [
        'complexId' => $occupied->complex_id, 'unitNumber' => $occupied->unit->unit_number, 'floorNumber' => 1,
    ])->assertSessionHas('status', 'error');

    expect($joining->refresh()->unit_id)->toBeNull();
    $this->assertDatabaseCount('units', 1);
});

test('a resident can update the details and number of their unit', function () {
    $role = UserRole::factory()->create();

    $this->actingAs($role->user)->post(route('dashboard.unit'), [
        'complexId' => $role->complex_id, 'currentUnitNumber' => $role->unit->unit_number,
        'newUnitNumber' => 200001, 'floorNumber' => 3, 'zipCode' => '9876543210',
    ])->assertSessionHas('status', 'success');

    expect($role->unit->refresh()->unit_number)->toBe(200001)
        ->and($role->unit->floor_number)->toBe(3)->and($role->unit->zip_code)->toBe('9876543210');
});
