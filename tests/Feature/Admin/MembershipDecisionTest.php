<?php

use App\Models\Complex;
use App\Models\MembershipRequest;
use App\Models\User;
use App\Models\UserRole;

beforeEach(function () {
    $this->admin = User::factory()->create();
    UserRole::factory()->admin()->create(['user_id' => $this->admin->id]);
});

test('an admin can review pending building requests', function () {
    $request = MembershipRequest::factory()->create();

    $this->actingAs($this->admin)->get('/adminPanel')->assertOk()->assertViewIs('adminPanel')->assertSee($request->name);
});

test('accepting a request creates its building manager role and group', function () {
    $request = MembershipRequest::factory()->create();

    $this->actingAs($this->admin)->from('/adminPanel')->get(route('adminPanel.accept', $request))->assertRedirect('/adminPanel');

    $complex = Complex::firstOrFail();
    expect($request->refresh()->request_status)->toBe('accepted')->and($complex->name)->toBe($request->name)->and($complex->join_link)->not->toBeEmpty();
    $this->assertDatabaseHas('user_roles', ['user_id' => $request->user_id, 'complex_id' => $complex->id, 'role' => 'manager']);
    $this->assertDatabaseHas('groups', ['complex_id' => $complex->id, 'group_name' => $request->name]);
});

test('rejecting a request changes its status without creating a building', function () {
    $request = MembershipRequest::factory()->create();

    $this->actingAs($this->admin)->from('/adminPanel')->get(route('adminPanel.reject', $request))->assertRedirect('/adminPanel');

    expect($request->refresh()->request_status)->toBe('rejected');
    $this->assertDatabaseCount('complexes', 0);
    $this->assertDatabaseCount('groups', 0);
});

test('residents cannot enter the admin panel', function () {
    $this->actingAs(User::factory()->create())->get('/adminPanel')->assertForbidden();
});
