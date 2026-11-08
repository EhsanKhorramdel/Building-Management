<?php

use Illuminate\Support\Facades\DB;

test('each model factory creates a record without foreign key violations', function (string $model) {
    $record = $model::factory()->create();

    $this->assertDatabaseHas($record->getTable(), ['id' => $record->id]);
    expect(DB::select('PRAGMA foreign_key_check'))->toBe([]);
})->with([
    App\Models\User::class, App\Models\Complex::class, App\Models\Unit::class,
    App\Models\UserRole::class, App\Models\Announcement::class, App\Models\MonthlyCharge::class,
    App\Models\ChargePayment::class, App\Models\IncidentalCost::class, App\Models\PaymentAdditionalCost::class,
    App\Models\Group::class, App\Models\Message::class, App\Models\Poll::class,
    App\Models\PollOption::class, App\Models\Vote::class, App\Models\MembershipRequest::class,
    App\Models\SeenMessage::class,
]);

test('the test connection is an isolated in-memory SQLite database', function () {
    expect(config('database.default'))->toBe('sqlite')
        ->and(DB::connection()->getDatabaseName())->toBe(':memory:')
        ->and((int) DB::selectOne('PRAGMA foreign_keys')->foreign_keys)->toBe(1);
});
