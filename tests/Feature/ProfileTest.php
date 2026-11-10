<?php

use App\Models\User;

test('profile details are saved through the dashboard update route', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->from('/dashboard')->patch(route('profile.update'), [
        'name' => 'Updated Resident', 'email' => 'updated@example.test',
        'ncode' => '1234567890', 'phoneNumber' => '09123456789',
    ])->assertSessionHasNoErrors()->assertRedirect('/dashboard')->assertSessionHas('page', 'setting');

    $user->refresh();
    expect($user->name)->toBe('Updated Resident')->and($user->email)->toBe('updated@example.test')
        ->and($user->national_code)->toBe('1234567890')->and($user->phone_number)->toBe('09123456789')
        ->and($user->email_verified_at)->toBeNull();
});

test('profile updates keep email verification when the email is unchanged', function () {
    $user = User::factory()->create();
    $verifiedAt = $user->email_verified_at->toDateTimeString();

    $this->actingAs($user)->from('/dashboard')->patch(route('profile.update'), [
        'name' => 'Same Email', 'email' => $user->email, 'ncode' => null, 'phoneNumber' => null,
    ])->assertSessionHasNoErrors()->assertRedirect('/dashboard');

    expect($user->refresh()->email_verified_at->toDateTimeString())->toBe($verifiedAt)
        ->and($user->national_code)->toBeNull()->and($user->phone_number)->toBeNull();
});

test('profile updates reject an email already used by another user', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    $this->actingAs($user)->from('/dashboard')->patch(route('profile.update'), [
        'name' => 'Changed', 'email' => $other->email, 'ncode' => null, 'phoneNumber' => null,
    ])->assertSessionHasErrors('email')->assertRedirect('/dashboard');

    expect($user->refresh()->email)->not->toBe($other->email);
});

test('profile updates validate national code and phone fields', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->patch(route('profile.update'), [
        'name' => $user->name, 'email' => $user->email, 'ncode' => 'not-a-number', 'phoneNumber' => 'invalid',
    ])->assertSessionHasErrors(['ncode', 'phoneNumber']);
});

test('guests cannot update profile details', function () {
    $this->patch(route('profile.update'), [])->assertRedirect('/login');
});
