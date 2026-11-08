<?php

use App\Models\User;

test('residents can submit a building approval request', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->from('/dashboard')->post(route('dashboard.register'), [
        'name' => 'Requested Building', 'address' => 'Example Street', 'floors' => 3, 'units' => 6,
    ])->assertRedirect('/dashboard')->assertSessionHas('status', 'success');

    $this->assertDatabaseHas('membership_requests', ['user_id' => $user->id, 'name' => 'Requested Building', 'number_of_floors' => 3, 'number_of_units' => 6, 'request_status' => 'pending']);
    $this->assertDatabaseCount('complexes', 0);
});

test('building approval requests validate required fields', function () {
    $this->actingAs(User::factory()->create())->post(route('dashboard.register'), ['floors' => 0, 'units' => 0])
        ->assertSessionHasErrorsIn('registerBuilding', ['name', 'address', 'floors', 'units']);

    $this->assertDatabaseCount('membership_requests', 0);
});
