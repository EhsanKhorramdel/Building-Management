<?php

use App\Models\Complex;
use App\Models\MembershipRequest;
use App\Models\User;
use App\Models\UserRole;

beforeEach(function () {
    $this->applicant = User::factory()->create();
    $this->registration = [
        'name' => 'Map test complex',
        'address' => 'Test address',
        'floors' => 3,
        'units' => 8,
    ];
});

it('persists the optional registration coordinates as floats', function () {
    $this->actingAs($this->applicant)->from(route('dashboard'))
        ->post(route('dashboard.register'), $this->registration + [
            'latitude' => '-35.6891984',
            'longitude' => '0',
        ])->assertRedirect(route('dashboard'))->assertSessionHasNoErrors();

    $request = MembershipRequest::sole();
    expect($request->request_status)->toBe('pending')
        ->and($request->latitude)->toBe(-35.6891984)
        ->and($request->longitude)->toBe(0.0);
});

it('rejects invalid coordinate pairs on the registration endpoint', function (array $coordinates, string $field) {
    $this->actingAs($this->applicant)->from(route('dashboard'))
        ->post(route('dashboard.register'), $this->registration + $coordinates)
        ->assertRedirect(route('dashboard'))->assertSessionHasErrors([$field], null, 'registerBuilding');

    $this->assertDatabaseEmpty('membership_requests');
})->with([
    'latitude out of range' => [['latitude' => '-91', 'longitude' => '0'], 'latitude'],
    'longitude out of range' => [['latitude' => '0', 'longitude' => '181'], 'longitude'],
    'latitude without longitude' => [['latitude' => '0'], 'longitude'],
    'longitude without latitude' => [['longitude' => '0'], 'latitude'],
]);

it('copies submitted coordinates to the complex on approval', function (array $coordinates, ?float $latitude, ?float $longitude) {
    $this->actingAs($this->applicant)->from(route('dashboard'))
        ->post(route('dashboard.register'), $this->registration + $coordinates)
        ->assertRedirect(route('dashboard'))->assertSessionHasNoErrors();

    $request = MembershipRequest::sole();
    $admin = UserRole::factory()->admin()->create()->user;

    $review = $this->actingAs($admin)->get(route('adminPanel.index'))->assertOk();
    if ($coordinates === []) {
        $review->assertDontSee('data-complex-map', false);
    } else {
        $review->assertSee('data-complex-map', false)->assertSee('data-latitude="35.6892"', false)
            ->assertDontSee('data-coordinate-picker', false);
    }

    $this->from(route('adminPanel.index'))->post(route('adminPanel.accept', $request))
        ->assertRedirect(route('adminPanel.index'));

    $complex = Complex::sole();
    expect($request->fresh()->request_status)->toBe('accepted')
        ->and($complex->latitude)->toBe($latitude)
        ->and($complex->longitude)->toBe($longitude)
        ->and($complex->location)->toBeNull();
})->with([
    'with coordinates' => [['latitude' => '35.6892', 'longitude' => '51.389'], 35.6892, 51.389],
    'without coordinates' => [[], null, null],
]);
