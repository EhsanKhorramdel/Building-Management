<?php

use App\Models\ChargePayment;
use App\Models\Message;
use App\Models\Poll;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoSeeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Morilog\Jalali\Jalalian;

test('demo data includes the local accounts and building scenarios', function () {
    Storage::fake('local');
    $this->seed(DemoSeeder::class);

    $this->assertDatabaseCount('users', 5);
    $this->assertDatabaseCount('complexes', 1);
    $this->assertDatabaseCount('units', 4);
    $this->assertDatabaseCount('user_roles', 5);
    $this->assertDatabaseCount('monthly_charges', 2);
    $this->assertDatabaseCount('charge_payments', 1);
    $this->assertDatabaseCount('polls', 2);
    $this->assertDatabaseCount('messages', 3);
    $this->assertDatabaseHas('membership_requests', ['request_status' => 'pending']);
    expect(Poll::all()->filter->isExpired())->toHaveCount(1);
    expect(Jalalian::fromCarbon(ChargePayment::firstOrFail()->created_at)->format('Y-m'))->toBe(Jalalian::fromCarbon(now())->format('Y-m'));
    $image = Message::whereNotNull('image_url')->firstOrFail();
    Storage::disk('local')->assertExists('public/chats/'.$image->image_url);

    foreach (['admin', 'manager', 'resident1', 'resident2', 'resident3'] as $account) {
        expect(Hash::check('password', User::where('email', $account.'@example.test')->firstOrFail()->password))->toBeTrue();
    }
});

test('the default seeder does not create demo accounts', function () {
    $this->seed(DatabaseSeeder::class);

    $this->assertDatabaseCount('users', 0);
    $this->assertDatabaseCount('complexes', 0);
});

test('demo seeding is refused outside local and testing environments', function () {
    $this->app['env'] = 'production';

    try {
        $this->app->make(DemoSeeder::class)->run();
    } finally {
        $this->app['env'] = 'testing';
        $this->assertDatabaseCount('users', 0);
    }
})->throws(RuntimeException::class, 'Demo accounts may only be seeded in local or testing environments.');
