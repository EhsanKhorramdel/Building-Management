<?php

use App\Models\Complex;
use App\Models\Group;
use App\Models\MembershipRequest;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Event;

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

    $this->actingAs($this->admin)->from('/adminPanel')->post(route('adminPanel.accept', $request))->assertRedirect('/adminPanel');

    $complex = Complex::firstOrFail();
    expect($request->refresh()->request_status)->toBe('accepted')->and($complex->name)->toBe($request->name)->and(strlen($complex->join_link))->toBe(32);
    $this->assertDatabaseHas('user_roles', ['user_id' => $request->user_id, 'complex_id' => $complex->id, 'role' => 'manager']);
    $this->assertDatabaseHas('groups', ['complex_id' => $complex->id, 'group_name' => $request->name]);
});

test('rejecting a request changes its status without creating a building', function () {
    $request = MembershipRequest::factory()->create();

    $this->actingAs($this->admin)->from('/adminPanel')->post(route('adminPanel.reject', $request))->assertRedirect('/adminPanel');

    expect($request->refresh()->request_status)->toBe('rejected');
    $this->assertDatabaseCount('complexes', 0);
    $this->assertDatabaseCount('groups', 0);
});

test('residents cannot enter the admin panel', function () {
    $this->actingAs(User::factory()->create())->get('/adminPanel')->assertForbidden();
});

test('accepting the same request twice creates one complete building', function () {
    $request = MembershipRequest::factory()->create();
    $this->actingAs($this->admin)->from('/adminPanel')->post(route('adminPanel.accept', $request))->assertRedirect('/adminPanel');
    $this->post(route('adminPanel.accept', $request))->assertRedirect('/adminPanel')->assertSessionHas('status', 'error');

    $this->assertDatabaseCount('complexes', 1);
    $this->assertDatabaseCount('groups', 1);
    expect(UserRole::where('role', 'manager')->count())->toBe(1);
});

test('a rejected request cannot be accepted later', function () {
    $request = MembershipRequest::factory()->create(['request_status' => 'rejected']);
    $this->actingAs($this->admin)->from('/adminPanel')->post(route('adminPanel.accept', $request))
        ->assertRedirect('/adminPanel')->assertSessionHas('status', 'error');

    expect($request->fresh()->request_status)->toBe('rejected');
    $this->assertDatabaseCount('complexes', 0);
    $this->assertDatabaseCount('groups', 0);
    expect(UserRole::where('role', 'manager')->count())->toBe(0);
});

test('a failed group insert rolls back the building role and decision', function () {
    $request = MembershipRequest::factory()->create();
    $event = 'eloquent.creating: '.Group::class;
    Event::listen($event, function (Group $group) {
        $group->group_name = null;
    });
    try {
        $this->withoutExceptionHandling()->actingAs($this->admin);
        expect(fn () => $this->post(route('adminPanel.accept', $request)))->toThrow(QueryException::class);
    } finally {
        Event::forget($event);
    }
    $this->assertDatabaseCount('complexes', 0);
    $this->assertDatabaseCount('groups', 0);
    expect(UserRole::where('role', 'manager')->count())->toBe(0);
    expect($request->fresh()->request_status)->toBe('pending');
});
