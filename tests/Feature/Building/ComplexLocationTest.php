<?php

use App\Models\Complex;
use App\Models\Group;
use App\Models\UserRole;

beforeEach(function () {
    $this->complex = Complex::factory()->create(['location' => 'Near the park']);
    $this->manager = UserRole::factory()->manager()->for($this->complex)->create()->user;
    $this->locationUrl = route('building.location.update', $this->complex);
    $this->buildingUrl = route('building', $this->complex);
});

it('stores a coordinate pair as floats without changing the text location', function () {
    $this->actingAs($this->manager)->patch($this->locationUrl, [
        'latitude' => '35.6891984',
        'longitude' => '51.3889742',
        'location' => 'Ignored',
    ])->assertRedirect($this->buildingUrl)->assertSessionHas('page', 'setting');

    $this->complex->refresh();
    expect($this->complex->latitude)->toBe(35.6891984)
        ->and($this->complex->longitude)->toBe(51.3889742)
        ->and($this->complex->location)->toBe('Near the park');
});

it('rejects invalid coordinate pairs without writing them', function (array $coordinates, array $fields) {
    $this->actingAs($this->manager)->from($this->buildingUrl)->patch($this->locationUrl, $coordinates)
        ->assertRedirect($this->buildingUrl)->assertSessionHasErrors($fields, null, 'complexLocation');

    $this->complex->refresh();
    expect($this->complex->latitude)->toBeNull()->and($this->complex->longitude)->toBeNull();
})->with([
    'latitude out of range' => [['latitude' => '90.01', 'longitude' => '0'], ['latitude']],
    'longitude out of range' => [['latitude' => '0', 'longitude' => '-180.01'], ['longitude']],
    'latitude without longitude' => [['latitude' => '0'], ['longitude']],
    'longitude without latitude' => [['longitude' => '0'], ['latitude']],
    'non-numeric latitude' => [['latitude' => 'Tehran', 'longitude' => '0'], ['latitude']],
]);

it('allows an omitted pair and keeps the saved coordinates', function () {
    $this->complex->update(['latitude' => 0, 'longitude' => 180]);
    $this->actingAs($this->manager)->patch($this->locationUrl)->assertRedirect($this->buildingUrl)->assertSessionHasNoErrors();

    $this->complex->refresh();
    expect($this->complex->latitude)->toBe(0.0)->and($this->complex->longitude)->toBe(180.0);
});

it('clears saved coordinates when both fields are blank', function () {
    $this->complex->update(['latitude' => 35.6892, 'longitude' => 51.389]);
    $this->actingAs($this->manager)->patch($this->locationUrl, ['latitude' => '', 'longitude' => ''])
        ->assertRedirect($this->buildingUrl)->assertSessionHasNoErrors();

    $this->complex->refresh();
    expect($this->complex->latitude)->toBeNull()->and($this->complex->longitude)->toBeNull();
});

it('denies location edits outside active management of the complex', function (string $actor) {
    $role = match ($actor) {
        'foreign manager' => UserRole::factory()->manager()->create(),
        'admin' => UserRole::factory()->admin()->create(),
        'inactive manager' => UserRole::factory()->manager()->for($this->complex)->create(['active' => false]),
        'resident' => UserRole::factory()->for($this->complex)->create(),
    };

    $this->actingAs($role->user)->patch($this->locationUrl, ['latitude' => '35', 'longitude' => '51'])->assertForbidden();
    expect($this->complex->fresh()->latitude)->toBeNull();
})->with(['foreign manager', 'resident', 'admin', 'inactive manager']);

it('redirects guests to sign in before a location edit', function () {
    $this->patch($this->locationUrl, ['latitude' => '35', 'longitude' => '51'])->assertRedirect(route('login'));
});

it('shows the manager the settings hint when the home map has no coordinates', function () {
    Group::factory()->for($this->complex)->create();

    $this->actingAs($this->manager)->get($this->buildingUrl)->assertOk()
        ->assertSee($this->complex->address)->assertSee('data-map-settings', false);
});

it('shows residents the address only until a location is saved', function () {
    Group::factory()->for($this->complex)->create();
    $resident = UserRole::factory()->for($this->complex)->create()->user;

    $this->actingAs($resident)->get($this->buildingUrl)->assertOk()
        ->assertSee($this->complex->address)->assertDontSee('data-complex-map', false)->assertDontSee('data-map-settings', false);

    $this->complex->update(['latitude' => 0, 'longitude' => 51.389]);
    $this->get($this->buildingUrl)->assertOk()
        ->assertSee('data-complex-map', false)->assertSee('data-latitude="0"', false)
        ->assertSee('data-longitude="51.389"', false)->assertDontSee('data-coordinate-picker', false)
        ->assertSee('https://www.openstreetmap.org/directions?to=0%2C51.389', false);
});
