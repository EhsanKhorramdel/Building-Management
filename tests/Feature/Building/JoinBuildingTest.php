<?php

use App\Models\Complex;
use App\Models\User;
use App\Models\UserRole;

test('a valid join token creates a resident membership', function () {
    $complex = Complex::factory()->create();
    $user = User::factory()->create();

    $this->actingAs($user)->from('/dashboard')->post(route('dashboard.store'), ['link' => $complex->join_link])
        ->assertRedirect('/dashboard')->assertSessionHas('status', 'success');

    $this->assertDatabaseHas('user_roles', ['user_id' => $user->id, 'complex_id' => $complex->id, 'role' => 'resident', 'unit_id' => null]);
});

test('an invalid or empty join token creates no membership', function (string $token) {
    $this->actingAs(User::factory()->create())->post(route('dashboard.store'), ['link' => $token])->assertSessionHas('status', 'error');

    $this->assertDatabaseCount('user_roles', 0);
})->with(['invalid' => 'missing-token', 'empty' => '']);

test('joining the same building twice does not duplicate an active resident role', function () {
    $role = UserRole::factory()->unassigned()->create();

    $this->actingAs($role->user)->post(route('dashboard.store'), ['link' => $role->complex->join_link])->assertSessionHas('status', 'error');

    $this->assertDatabaseCount('user_roles', 1);
});

test('guests cannot join a building', function () {
    $this->post(route('dashboard.store'), ['link' => 'any'])->assertRedirect('/login');
});
