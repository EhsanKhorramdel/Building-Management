<?php

use App\Models\ChargePayment;
use App\Models\MonthlyCharge;
use App\Models\PaymentAdditionalCost;
use App\Models\SeenMessage;
use App\Models\UserRole;
use App\Models\Vote;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Morilog\Jalali\Jalalian;

test('legacy duplicates are merged before constraints and Jalali backfill preserves month boundaries', function () {
    $migration = require database_path('migrations/2026_11_09_000001_harden_domain_uniqueness.php');
    $migration->down();
    Log::spy();
    $role = UserRole::factory()->create();
    $duplicateUnit = $role->unit->getAttributes();
    unset($duplicateUnit['id']);
    $duplicateUnitId = DB::table('units')->insertGetId($duplicateUnit);
    $duplicateRole = $role->getAttributes();
    unset($duplicateRole['id']);
    $duplicateRole['unit_id'] = $duplicateUnitId;
    $duplicateRoleId = DB::table('user_roles')->insertGetId($duplicateRole);
    $unassigned = UserRole::factory()->create(['complex_id' => $role->complex_id, 'user_id' => $role->user_id, 'unit_id' => null]);
    $secondUnassigned = $unassigned->getAttributes();
    unset($secondUnassigned['id']);
    DB::table('user_roles')->insert($secondUnassigned);

    $vote = Vote::factory()->create(['role_id' => $role->id]);
    $costPayment = PaymentAdditionalCost::factory()->create(['role_id' => $role->id]);
    $seen = SeenMessage::factory()->create();
    $deleted = ['units' => $duplicateUnitId, 'user_roles' => $duplicateRoleId];
    foreach ([$vote, $costPayment, $seen] as $record) {
        $attributes = $record->getAttributes();
        unset($attributes['id']);
        if (isset($attributes['role_id'])) {
            $attributes['role_id'] = $duplicateRoleId;
        }
        $deleted[$record->getTable()] = DB::table($record->getTable())->insertGetId($attributes);
    }

    $firstDate = (new Jalalian(1405, 1, 31))->toCarbon()->endOfDay();
    $nextDate = (new Jalalian(1405, 2, 1))->toCarbon()->startOfDay();
    $charge = MonthlyCharge::factory()->create(['complex_id' => $role->complex_id]);
    $paymentAttributes = ['role_id' => $role->id, 'monthly_charge_id' => $charge->id, 'amount' => 10, 'created_at' => $firstDate, 'updated_at' => $firstDate];
    $firstPayment = DB::table('charge_payments')->insertGetId($paymentAttributes);
    $paymentAttributes['role_id'] = $duplicateRoleId;
    $deleted['charge_payments'] = DB::table('charge_payments')->insertGetId($paymentAttributes);
    $paymentAttributes['created_at'] = $nextDate;
    $paymentAttributes['updated_at'] = $nextDate;
    $nextPayment = DB::table('charge_payments')->insertGetId($paymentAttributes);

    $migration->up();

    foreach ($deleted as $table => $id) {
        $this->assertDatabaseMissing($table, ['id' => $id]);
        Log::shouldHaveReceived('info')->with('Database duplicate cleanup complete', [
            'table' => $table, 'deleted_count' => 1, 'deleted_ids' => [$id],
        ])->once();
    }
    expect($role->refresh()->unit_id)->toBe($role->unit->id);
    expect(UserRole::where('user_id', $role->user_id)->whereNull('unit_id')->count())->toBe(2);
    expect($vote->refresh()->role_id)->toBe($role->id);
    expect($costPayment->refresh()->role_id)->toBe($role->id);
    $this->assertDatabaseHas('charge_payments', ['id' => $firstPayment, 'role_id' => $role->id, 'period_year' => 1405, 'period_month' => 1]);
    $this->assertDatabaseHas('charge_payments', ['id' => $nextPayment, 'role_id' => $role->id, 'period_year' => 1405, 'period_month' => 2]);
    expect(ChargePayment::count())->toBe(2);
});

test('all new migrations reverse and can be reapplied', function () {
    $files = glob(database_path('migrations/2026_11_09_*.php'));
    foreach (array_reverse($files) as $file) {
        (require $file)->down();
    }
    expect(Schema::hasColumn('charge_payments', 'period_year'))->toBeFalse();
    expect(Schema::hasColumn('complexes', 'latitude'))->toBeFalse();
    expect(Schema::hasTable('transactions'))->toBeFalse();
    expect(Schema::hasTable('sms_logs'))->toBeFalse();
    foreach ($files as $file) {
        (require $file)->up();
    }
    expect(Schema::hasColumn('charge_payments', 'period_month'))->toBeTrue();
    expect(Schema::hasTable('transactions'))->toBeTrue();
    expect(Schema::hasTable('sms_logs'))->toBeTrue();
    foreach ([
        'messages' => 'messages_group_id_id_index',
        'user_roles' => 'user_roles_user_complex_index',
        'seen_messages' => 'seen_messages_group_user_index',
    ] as $table => $name) {
        expect(Schema::hasIndex($table, $name))->toBeTrue();
    }
});
