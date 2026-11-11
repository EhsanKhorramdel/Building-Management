<?php

use App\Models\Complex;
use App\Models\MembershipRequest;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

test('coordinate pairs and ranges are enforced and checks can be rolled back and reapplied', function (string $model) {
    $record = $model::factory()->create(['latitude' => null, 'longitude' => null]);
    $set = fn ($latitude, $longitude) => DB::table($record->getTable())->where('id', $record->id)->update(compact('latitude', 'longitude'));
    $set(-90, 180);
    $set(90, -180);
    $set(null, null);
    foreach ([[null, 1], [1, null], [90.01, 0], [-90.01, 0], [0, 180.01], [0, -180.01]] as [$latitude, $longitude]) {
        expect(fn () => $set($latitude, $longitude))->toThrow(QueryException::class);
    }

    $migration = require database_path('migrations/2026_11_11_000002_check_coordinate_pairs.php');
    $migration->down();
    $set(null, 1);
    expect(fn () => $migration->up())->toThrow(RuntimeException::class, 'Invalid coordinates');
    $set(null, null);
    $migration->up();
    expect(fn () => $set(null, 1))->toThrow(QueryException::class);
})->with(['complexes' => [Complex::class], 'membership requests' => [MembershipRequest::class]]);
