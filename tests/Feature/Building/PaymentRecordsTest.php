<?php

use App\Models\ChargePayment;
use App\Models\IncidentalCost;
use App\Models\MonthlyCharge;
use App\Models\PaymentAdditionalCost;
use App\Models\Transaction;
use App\Models\UserRole;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Morilog\Jalali\Jalalian;
use Tests\Support\BuildingContext;

beforeEach(function () {
    $this->building = new BuildingContext;
    $this->payer = $this->building->resident();
    $this->charge = MonthlyCharge::factory()->for($this->building->complex)->create(['amount' => 125.75]);
    $this->cost = IncidentalCost::factory()->for($this->building->complex)->create(['title' => 'هزینه نمونه', 'share_amount' => 42]);
    $this->admin = UserRole::factory()->admin()->create()->user;
});

it('shows exact manual and online receipt fields to the payer manager and admin only', function (string $type, bool $online) {
    $payable = $type === 'charge' ? $this->charge : $this->cost;
    $payment = $type === 'charge'
        ? ChargePayment::factory()->create(['role_id' => $this->payer->id, 'monthly_charge_id' => $payable->id, 'period_year' => 1404, 'period_month' => 3])
        : PaymentAdditionalCost::factory()->create(['role_id' => $this->payer->id, 'incidental_costs_id' => $payable->id, 'amount' => 42]);
    if ($online) {
        Transaction::create([
            'payable_type' => $payable->getMorphClass(), 'payable_id' => $payable->id, 'role_id' => $this->payer->id,
            'user_id' => $this->payer->user_id, 'amount' => $payment->amount, 'status' => Transaction::PAID,
            'payment_id' => $payment->id, 'authority' => 'receipt-private-authority', 'reference_id' => 'receipt-reference',
        ]);
    }
    $url = route('payments.receipt', [$type, $payment->id]);
    $this->get($url)->assertRedirect('/login');
    foreach ([$this->payer->user, $this->building->manager, $this->admin] as $allowed) {
        $this->actingAs($allowed)->get($url)->assertOk()->assertViewHasAll([
            'number' => strtoupper($type).'-'.$payment->id,
            'complex' => $this->building->complex->name,
            'unit' => $this->payer->unit->unit_number,
            'payer' => $this->payer->user->name,
            'item' => $type === 'charge' ? __('integrations::payments.charge') : $this->cost->title,
            'period' => $type === 'charge' ? '1404/03' : Jalalian::fromCarbon($payment->created_at)->format('Y/m'),
            'date' => Jalalian::fromCarbon($payment->created_at)->format('Y/m/d'),
            'amount' => $payment->fresh()->amount,
            'method' => $online ? 'آنلاین' : 'دستی',
            'reference' => $online ? 'receipt-reference' : null,
        ])->assertDontSee('receipt-private-authority');
    }
    foreach ([$this->building->resident()->user, (new BuildingContext)->manager] as $denied) {
        $this->actingAs($denied)->get($url)->assertForbidden();
    }
})->with(['charge', 'cost'])->with(['manual' => false, 'online' => true]);

it('lets payers find receipts even when the existing report selects a different role on their unit', function () {
    $role = UserRole::factory()->for($this->building->complex)->for($this->payer->unit)->create();
    $mine = ChargePayment::factory()->create(['role_id' => $role->id, 'monthly_charge_id' => $this->charge->id]);
    $other = ChargePayment::factory()->create(['role_id' => $this->payer->id, 'monthly_charge_id' => $this->charge->id]);
    $url = route('payments.receipts.complex', $this->building->complex);
    $this->actingAs($role->user)->get($url)->assertOk()
        ->assertSee(route('payments.receipt', ['charge', $mine->id]), false)
        ->assertDontSee(route('payments.receipt', ['charge', $other->id]), false);
    $this->get(route('payments.receipts.admin'))->assertForbidden();
    $this->actingAs($this->admin)->get(route('payments.receipts.admin'))->assertOk()
        ->assertSee(route('payments.receipt', ['charge', $other->id]), false);
});

it('records the mock state chain in order and keeps replays separate from immutable transitions', function (string $outcome, string $status) {
    config(['payments.enabled' => true, 'payments.driver' => 'mock', 'sms.enabled' => false]);
    $this->freezeSecond();
    $this->actingAs($this->payer->user)->post(route('payments.initiate'), [
        'type' => 'charge', 'payable_id' => $this->charge->id, 'unitNumber' => $this->payer->unit->unit_number,
    ])->assertRedirect();
    $attempt = Transaction::sole();
    $started = now()->copy();
    $this->travel($outcome === 'expired' ? 40 : 1)->minutes();
    $data = ['_token' => 'receipt-test-session', 'authority' => $attempt->authority, 'amount' => $outcome === 'mismatch' ? '1.00' : $attempt->amount,
        'outcome' => $outcome === 'cancel' ? 'cancel' : 'pay'];
    $this->withSession(['_token' => 'receipt-test-session'])->post(route('payments.callback', 'mock'), $data)->assertRedirect();
    $attempt->refresh();
    expect($attempt->status)->toBe($status);
    $rows = $attempt->transitions()->get();
    expect($rows->map(fn ($row) => [$row->from_state, $row->to_state, $row->actor_id])->all())
        ->toBe([[null, 'pending', $this->payer->user_id], ['pending', $status, $this->payer->user_id]]);
    expect($rows[0]->created_at->equalTo($started))->toBeTrue()
        ->and($rows[1]->created_at->isAfter($rows[0]->created_at))->toBeTrue();
    if ($status === Transaction::PAID) {
        expect($attempt->payment_id)->toBe(ChargePayment::sole()->id);
        $this->get($attempt->receiptUrl())->assertOk()->assertViewHas('reference', 'mock-'.$attempt->id);
    } else {
        $this->assertDatabaseCount('charge_payments', 0);
        expect($attempt->receiptUrl())->toBeNull();
    }
    $this->post(route('payments.callback', 'mock'), $data)->assertRedirect();
    $this->post(route('payments.callback', 'mock'), $data)->assertRedirect();
    expect($attempt->callbackIssues()->where('kind', 'duplicate_callback')->count())->toBe(1);
    if ($outcome === 'mismatch') {
        expect($attempt->callbackIssues()->where('kind', 'mismatch')->exists())->toBeTrue();
    }
    expect(fn () => DB::table('transaction_transitions')->where('id', $rows[0]->id)->update(['to_state' => 'paid']))->toThrow(QueryException::class);
    expect(fn () => DB::table('transaction_transitions')->where('id', $rows[0]->id)->delete())->toThrow(QueryException::class);
    expect($attempt->transitions()->get()->toArray())->toBe($rows->toArray());
})->with([
    'paid' => ['pay', Transaction::PAID],
    'cancelled' => ['cancel', Transaction::CANCELLED],
    'expired' => ['expired', Transaction::EXPIRED],
    'mismatch' => ['mismatch', Transaction::FAILED],
]);

it('shows redacted reconciliation only to the correct staff and leaves legacy history empty', function () {
    $foreign = new BuildingContext;
    $foreignCharge = MonthlyCharge::factory()->for($foreign->complex)->create();
    $local = Transaction::create([
        'payable_type' => MonthlyCharge::class, 'payable_id' => $this->charge->id, 'role_id' => $this->payer->id,
        'amount' => 125.75, 'status' => Transaction::FAILED, 'authority' => 'internal-authority-secret',
        'reference_id' => 'internal-reference-4321', 'meta' => ['gateway_payload' => 'private-response-secret'],
    ]);
    $expired = Transaction::create([
        'payable_type' => MonthlyCharge::class, 'payable_id' => $foreignCharge->id,
        'role_id' => $foreign->managerRole->id, 'amount' => 123, 'status' => Transaction::EXPIRED,
    ]);
    $local->callbackIssues()->create(['kind' => 'mismatch']);
    $scope = route('payments.reconciliation.complex', $this->building->complex);
    $this->actingAs($this->payer->user)->get($scope)->assertForbidden();
    $this->actingAs($foreign->manager)->get($scope)->assertForbidden();
    $this->actingAs($this->building->manager)->get(route('payments.reconciliation.admin'))->assertForbidden();
    $this->get($scope)->assertOk()->assertSee('data-reconciliation-attempt="'.$local->id.'"', false)
        ->assertDontSee('data-reconciliation-attempt="'.$expired->id.'"', false)->assertSee('••••4321')
        ->assertDontSee('internal-reference-4321')->assertDontSee('internal-authority-secret')->assertDontSee('private-response-secret')
        ->assertDontSee('payment-timeline')->assertSee('عدم تطابق پاسخ پرداخت');
    $this->actingAs($this->admin)->get(route('payments.reconciliation.admin'))->assertOk()
        ->assertSee('data-reconciliation-attempt="'.$local->id.'"', false)
        ->assertSee('data-reconciliation-attempt="'.$expired->id.'"', false);
    $this->assertDatabaseCount('transaction_transitions', 0);
});

it('links unambiguous legacy receipts without fabricating history or guessing an ambiguous reference', function () {
    $migration = require database_path('migrations/2026_11_11_000003_add_payment_receipts_and_transaction_history.php');
    $migration->down();
    $paidAt = now()->subDay();
    $cost = PaymentAdditionalCost::factory()->create([
        'role_id' => $this->payer->id, 'incidental_costs_id' => $this->cost->id,
        'amount' => 42, 'created_at' => $paidAt,
    ]);
    $charge = ChargePayment::factory()->create([
        'role_id' => $this->payer->id, 'monthly_charge_id' => $this->charge->id,
        'amount' => 125.75, 'created_at' => $paidAt,
    ]);
    $legacy = [
        'role_id' => $this->payer->id, 'user_id' => $this->payer->user_id, 'status' => Transaction::PAID,
        'created_at' => $paidAt->copy()->subMinute(), 'updated_at' => $paidAt->copy()->addSecond(),
    ];
    $costAttempt = DB::table('transactions')->insertGetId($legacy + [
        'payable_type' => IncidentalCost::class, 'payable_id' => $this->cost->id,
        'amount' => 42, 'reference_id' => 'legacy-cost-reference',
    ]);
    for ($index = 1; $index <= 2; $index++) {
        DB::table('transactions')->insert($legacy + [
            'payable_type' => MonthlyCharge::class, 'payable_id' => $this->charge->id,
            'amount' => 125.75, 'reference_id' => 'ambiguous-legacy-'.$index,
        ]);
    }
    $migration->up();

    expect(Transaction::findOrFail($costAttempt)->payment_id)->toBe($cost->id);
    expect(Transaction::where('payable_type', MonthlyCharge::class)->whereNotNull('payment_id')->count())->toBe(0);
    $this->assertDatabaseCount('transaction_transitions', 0);
    $this->actingAs($this->payer->user)->get(route('payments.receipt', ['cost', $cost->id]))
        ->assertOk()->assertViewHas('reference', 'legacy-cost-reference');
    $this->get(route('payments.receipt', ['charge', $charge->id]))->assertOk()
        ->assertViewHas('method', 'نامشخص')->assertViewHas('reference', null)->assertDontSee('ambiguous-legacy');
});

it('pages the current payer history by descending ID without duplicates or gaps and keeps older receipts', function () {
    config(['payments.enabled' => true]);
    $ids = [];
    for ($index = 1; $index <= 32; $index++) {
        $payment = ChargePayment::factory()->create([
            'role_id' => $this->payer->id, 'monthly_charge_id' => $this->charge->id,
            'period_year' => 1400 + intdiv($index - 1, 12), 'period_month' => 1 + (($index - 1) % 12),
        ]);
        $ids[] = Transaction::create([
            'payable_type' => MonthlyCharge::class, 'payable_id' => $this->charge->id,
            'role_id' => $this->payer->id, 'user_id' => $this->payer->user_id,
            'status' => Transaction::PAID, 'amount' => $payment->amount, 'payment_id' => $payment->id,
        ])->id;
    }
    $other = Transaction::create([
        'payable_type' => MonthlyCharge::class, 'payable_id' => $this->charge->id, 'user_id' => $this->building->manager->id,
        'status' => Transaction::FAILED, 'amount' => 999,
    ]);
    $url = route('payments.history', $this->building->complex);
    $this->actingAs($this->payer->user);
    $seen = [];
    $before = null;
    do {
        $query = $before ? '?before_id='.$before : '';
        $page = $this->getJson($url.$query)->assertOk()->json();
        preg_match_all('/data-transaction-id="(\d+)"/', $page['html'], $matches);
        $seen = array_merge($seen, array_map('intval', $matches[1]));
        expect($page['html'])->toContain('/payments/receipt/charge/')->not->toContain('data-transaction-id="'.$other->id.'"');
        $before = $page['cursor'];
    } while ($before);
    expect($seen)->toBe(array_reverse($ids));
    $this->getJson($url.'?before_id=bad')->assertUnprocessable();
    $this->actingAs((new BuildingContext)->manager)->getJson($url)->assertForbidden();
});
