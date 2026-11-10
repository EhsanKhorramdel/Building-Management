<?php

use App\Models\UserRole;
use Tests\Support\BuildingContext;

test('dashboard shows a users active buildings', function () {
    $building = new BuildingContext;
    $resident = $building->resident();
    $inactive = UserRole::factory()->create(['user_id' => $resident->user_id, 'active' => false]);

    $this->actingAs($resident->user)->get('/dashboard')->assertOk()->assertViewIs('dashboard')
        ->assertViewHas('residentCount', 1)->assertViewHas('allComplexes', fn ($complexes) => $complexes->modelKeys() === [$building->complex->id]);
});

test('building dashboard loads residents and manager controls for its manager', function () {
    $building = new BuildingContext;
    $resident = $building->resident();

    $this->actingAs($building->manager)->get(route('building', $building->complex))
        ->assertOk()->assertViewIs('building.building')->assertViewHas('isManager', true)->assertViewHas('residentCount', 1)->assertSee($resident->user->name);
});

test('building dashboard asks a member to assign their unit first', function () {
    $role = UserRole::factory()->unassigned()->create();

    $this->actingAs($role->user)->from('/dashboard')->get(route('building', $role->complex))
        ->assertRedirect('/dashboard')->assertSessionHas('status', 'error');
});
