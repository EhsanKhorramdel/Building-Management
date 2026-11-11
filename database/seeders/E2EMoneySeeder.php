<?php

namespace Database\Seeders;

use App\Models\IncidentalCost;
use App\Models\MonthlyCharge;
use App\Models\PaymentAdditionalCost;
use App\Models\Transaction;
use App\Models\User;

class E2EMoneySeeder extends E2ESeeder
{
    public function run(): void
    {
        parent::run();
        $payer = User::where('email', 'resident@example.test')->sole();
        $role = $payer->userRoles()->sole();
        $cost = IncidentalCost::where('complex_id', $role->complex_id)->sole();
        $payment = PaymentAdditionalCost::create(['role_id' => $role->id, 'incidental_costs_id' => $cost->id, 'amount' => $cost->share_amount]);
        $scope = ['payable_type' => IncidentalCost::class, 'payable_id' => $cost->id, 'role_id' => $role->id, 'user_id' => $payer->id];
        Transaction::create($scope + ['amount' => $payment->amount, 'status' => Transaction::PAID,
            'payment_id' => $payment->id, 'reference_id' => 'older-receipt-reference']);
        for ($index = 0; $index < 16; $index++) {
            $attempt = Transaction::create($scope + ['amount' => $cost->share_amount, 'status' => Transaction::FAILED]);
        }
        $attempt->update(['reference_id' => 'fixture-finance-reference-6198', 'authority' => 'fixture-private-authority',
            'meta' => ['gateway_payload' => 'fixture-private-payload']]);
        $attempt->callbackIssues()->create(['kind' => 'mismatch']);
        $attempt->transitions()->create(['from_state' => null, 'to_state' => Transaction::PENDING, 'actor_id' => $payer->id]);
        $attempt->transitions()->create(['from_state' => Transaction::PENDING, 'to_state' => Transaction::FAILED, 'actor_id' => null]);

        $foreign = User::where('email', 'other-resident@example.test')->sole()->userRoles()->sole();
        $charge = MonthlyCharge::factory()->for($foreign->complex)->create();
        Transaction::create([
            'payable_type' => MonthlyCharge::class, 'payable_id' => $charge->id, 'role_id' => $foreign->id,
            'user_id' => $foreign->user_id, 'amount' => $charge->amount, 'status' => Transaction::EXPIRED,
        ]);
    }
}
