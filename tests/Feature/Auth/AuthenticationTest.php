<?php

use App\Models\User;
use App\Models\UserRole;

test('login screen can be rendered', function () {
    $response = $this->get('/login');

    $response->assertStatus(200);
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create();

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect('/dashboard');
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

test('admins authenticate into the admin panel', function () {
    $admin = User::factory()->create();
    UserRole::factory()->admin()->create(['user_id' => $admin->id]);

    $this->post('/login', ['email' => $admin->email, 'password' => 'password'])->assertRedirect('/adminPanel');

    $this->assertAuthenticatedAs($admin);
});

test('users can log out through the registered post route', function () {
    $this->actingAs(User::factory()->create())->post('/logout')->assertRedirect('/');

    $this->assertGuest();
});
