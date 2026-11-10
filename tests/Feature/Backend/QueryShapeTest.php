<?php

use App\Models\ChargePayment;
use App\Models\Complex;
use App\Models\IncidentalCost;
use App\Models\MonthlyCharge;
use App\Models\PaymentAdditionalCost;
use App\Models\UserRole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

test('payments endpoint query count stays constant as units and payments grow', function () {
    $counts = [];
    foreach ([2, 8] as $size) {
        $complex = Complex::factory()->create();
        $charge = MonthlyCharge::factory()->create(['complex_id' => $complex->id]);
        $cost = IncidentalCost::factory()->create(['complex_id' => $complex->id]);
        for ($i = 0; $i < $size; $i++) {
            $role = UserRole::factory()->create(['complex_id' => $complex->id]);
            ChargePayment::factory()->create(['role_id' => $role->id, 'monthly_charge_id' => $charge->id]);
            PaymentAdditionalCost::factory()->create(['role_id' => $role->id, 'incidental_costs_id' => $cost->id]);
        }
        $this->actingAs($role->user);
        Model::preventLazyLoading();
        DB::enableQueryLog();
        DB::flushQueryLog();
        try {
            $response = $this->postJson(route('building.payments'), ['complex_id' => $complex->id]);
            $counts[] = count(DB::getQueryLog());
        } finally {
            DB::disableQueryLog();
            Model::preventLazyLoading(false);
        }
        $response->assertOk()->assertJsonCount($size * 2);
    }
    expect($counts[0])->toBe($counts[1])->toBe(8);
});

test('payments report keeps the existing first-role selection for a shared unit', function () {
    $role = UserRole::factory()->create();
    $second = UserRole::factory()->create(['complex_id' => $role->complex_id, 'unit_id' => $role->unit_id]);
    ChargePayment::factory()->create(['role_id' => $role->id]);
    ChargePayment::factory()->create(['role_id' => $second->id]);

    $this->actingAs($role->user)->postJson(route('building.payments'), ['complex_id' => $role->complex_id])
        ->assertOk()->assertJsonCount(1)->assertJsonPath('0.user_name', $role->user->name);
});
