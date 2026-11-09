<?php

use App\Models\ChargePayment;
use App\Models\Group;
use App\Models\IncidentalCost;
use App\Models\MonthlyCharge;
use App\Models\Transaction;
use App\Models\UserRole;
use App\Services\Payments\GatewayManager;
use App\Services\Payments\MockGateway;
use App\Services\Payments\PaymentException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Morilog\Jalali\Jalalian;

beforeEach(function () {
    config(['payments.enabled' => true, 'payments.driver' => 'mock', 'sms.enabled' => false]);
    $this->payer = UserRole::factory()->create();
    $this->charge = MonthlyCharge::factory()->for($this->payer->complex)->create(['amount' => 125.75]);
    $this->input = ['type' => 'charge', 'payable_id' => $this->charge->id, 'unitNumber' => $this->payer->unit->unit_number];
});

function mockPaymentPayload(Transaction $transaction, array $changes = []): array
{
    return $changes + [
        '_token' => 'mock-session-token',
        'authority' => $transaction->authority,
        'amount' => $transaction->amount,
        'outcome' => 'pay',
    ];
}

it('completes mock checkout with the stored charge amount and Jalali period', function () {
    $this->actingAs($this->payer->user)->post(route('payments.initiate'), $this->input + ['amount' => 1])->assertRedirect();
    $transaction = Transaction::sole();
    expect($transaction->amount)->toBe('125.75')->and($transaction->status)->toBe(Transaction::PENDING);
    $this->get(route('payments.mock', $transaction))->assertOk()->assertSee($this->payer->complex->name);

    $this->withSession(['_token' => 'mock-session-token'])->post(route('payments.callback', 'mock'), mockPaymentPayload($transaction))
        ->assertRedirect(route('building', $this->payer->complex))->assertSessionHas('page', 'paymentInfo')->assertSessionHas('status', 'success');
    $period = Jalalian::fromCarbon(now());
    $this->assertDatabaseHas('charge_payments', [
        'monthly_charge_id' => $this->charge->id,
        'role_id' => $this->payer->id,
        'amount' => 125.75,
        'period_year' => $period->getYear(),
        'period_month' => $period->getMonth(),
    ]);
    expect($transaction->refresh()->status)->toBe(Transaction::PAID)->and($transaction->reference_id)->toBe('mock-'.$transaction->id);
});

it('records a mock incidental cost at its server-side share only once', function () {
    $cost = IncidentalCost::factory()->for($this->payer->complex)->create(['share_amount' => 17.50]);
    $input = array_replace($this->input, ['type' => 'cost', 'payable_id' => $cost->id, 'amount' => 1]);
    $this->actingAs($this->payer->user)->post(route('payments.initiate'), $input)->assertRedirect();
    $transaction = Transaction::sole();
    $this->withSession(['_token' => 'mock-session-token'])->post(route('payments.callback', 'mock'), mockPaymentPayload($transaction))
        ->assertSessionHas('status', 'success');
    $this->assertDatabaseHas('payment_additional_costs', ['incidental_costs_id' => $cost->id, 'amount' => 17.50]);
    $this->post(route('payments.initiate'), $input)->assertSessionHas('message', __('integrations::payments.already_paid'));
    $this->assertDatabaseCount('transactions', 1);
});

it('leaves no payment after cancellation or a tampered mock callback', function (array $input, string $status) {
    $this->actingAs($this->payer->user)->post(route('payments.initiate'), $this->input);
    $transaction = Transaction::sole();
    $this->withSession(['_token' => 'mock-session-token'])
        ->post(route('payments.callback', 'mock'), mockPaymentPayload($transaction, $input))
        ->assertSessionHas('status', 'error')->assertSessionHas('page', 'paymentInfo');
    expect($transaction->refresh()->status)->toBe($status);
    $this->assertDatabaseEmpty('charge_payments');
})->with([
    'cancelled' => [['outcome' => 'cancel'], Transaction::CANCELLED],
    'wrong amount' => [['amount' => '1.00'], Transaction::FAILED],
    'unknown status' => [['outcome' => 'approved'], Transaction::FAILED],
]);

it('returns the recorded result on callback replay without a second payment', function () {
    $this->actingAs($this->payer->user)->post(route('payments.initiate'), $this->input);
    $transaction = Transaction::sole();
    $this->withSession(['_token' => 'mock-session-token']);
    $this->post(route('payments.callback', 'mock'), mockPaymentPayload($transaction))->assertSessionHas('status', 'success');
    $this->post(route('payments.callback', 'mock'), mockPaymentPayload($transaction, ['outcome' => 'cancel']))
        ->assertSessionHas('status', 'success');
    $this->assertDatabaseCount('charge_payments', 1);
    expect($transaction->refresh()->reference_id)->toBe('mock-'.$transaction->id);
});

it('refuses online initiation when disabled and preserves manual payment', function () {
    config(['payments.enabled' => false]);
    Group::factory()->for($this->payer->complex)->create();
    $this->actingAs($this->payer->user)->get(route('building', $this->payer->complex))->assertOk()
        ->assertSee(route('building.buildingChargePayment'), false)->assertDontSee('data-online-payments', false);
    $this->post(route('payments.initiate'), $this->input)->assertNotFound();
    $this->post(route('building.buildingChargePayment'), [
        'monthlyChargeId' => $this->charge->id,
        'complexId' => $this->payer->complex_id,
        'unitNumber' => $this->payer->unit->unit_number,
    ])->assertSessionHas('status', 'success');
    $this->assertDatabaseCount('charge_payments', 1);
    $this->assertDatabaseEmpty('transactions');
});

it('refuses initiation for a charge already paid this Jalali period', function () {
    ChargePayment::factory()->create(['role_id' => $this->payer->id, 'monthly_charge_id' => $this->charge->id]);
    $this->actingAs($this->payer->user)->post(route('payments.initiate'), $this->input)
        ->assertSessionHas('message', __('integrations::payments.already_paid'));
    $this->assertDatabaseEmpty('transactions');
});

it('forbids foreign-complex initiation and the manual endpoint while online is enabled', function () {
    $foreign = UserRole::factory()->manager()->create();
    $this->actingAs($foreign->user)->post(route('payments.initiate'), $this->input)->assertForbidden();
    $this->actingAs($this->payer->user)->post(route('building.buildingChargePayment'), [
        'monthlyChargeId' => $this->charge->id,
        'complexId' => $this->payer->complex_id,
        'unitNumber' => $this->payer->unit->unit_number,
    ])->assertForbidden();
    $this->assertDatabaseEmpty('transactions');
    $this->assertDatabaseEmpty('charge_payments');
});

it('expires a pending transaction before verifying a late callback', function () {
    $this->actingAs($this->payer->user)->post(route('payments.initiate'), $this->input);
    $transaction = Transaction::sole();
    $this->travel(31)->minutes();
    $this->withSession(['_token' => 'mock-session-token'])->post(route('payments.callback', 'mock'), mockPaymentPayload($transaction))
        ->assertSessionHas('message', __('integrations::payments.outcome.expired'));
    expect($transaction->refresh()->status)->toBe(Transaction::EXPIRED);
    $this->assertDatabaseEmpty('charge_payments');
});

it('checks again for a payment recorded after initiation', function () {
    $this->actingAs($this->payer->user)->post(route('payments.initiate'), $this->input);
    $transaction = Transaction::sole();
    ChargePayment::factory()->create(['role_id' => $this->payer->id, 'monthly_charge_id' => $this->charge->id]);
    $this->withSession(['_token' => 'mock-session-token'])->post(route('payments.callback', 'mock'), mockPaymentPayload($transaction))
        ->assertSessionHas('status', 'error');
    expect($transaction->refresh()->meta['failure_reason'])->toBe('payment_conflict');
    $this->assertDatabaseCount('charge_payments', 1);
});

it('restricts mock checkout and callback to the payer and checks mock form CSRF', function () {
    $this->actingAs($this->payer->user)->post(route('payments.initiate'), $this->input);
    $transaction = Transaction::sole();
    $this->post(route('payments.callback', 'mock'), mockPaymentPayload($transaction, ['_token' => 'invalid']))->assertStatus(419);
    $other = UserRole::factory()->for($this->payer->complex)->create();
    $this->actingAs($other->user)->get(route('payments.mock', $transaction))->assertForbidden();
    $this->withSession(['_token' => 'mock-session-token'])
        ->post(route('payments.callback', 'mock'), mockPaymentPayload($transaction))->assertForbidden();
    $this->assertDatabaseEmpty('charge_payments');
});

it('fails closed when HTTP credentials are missing', function () {
    config(['payments.driver' => 'gateway']);
    Http::fake();
    $this->actingAs($this->payer->user)->post(route('payments.initiate'), $this->input)
        ->assertSessionHas('message', __('integrations::payments.unconfigured'));
    expect(Transaction::sole()->status)->toBe(Transaction::FAILED);
    Http::assertNothingSent();
});

it('uses server verification rather than callback amount or status', function (array $verification, string $status) {
    config([
        'payments.driver' => 'gateway',
        'payments.drivers.gateway.base_url' => 'https://gateway.example.test',
        'payments.drivers.gateway.merchant_id' => 'test-merchant',
        'payments.drivers.gateway.api_key' => 'test-key',
    ]);
    Http::fake([
        'https://gateway.example.test/payments' => Http::response(['authority' => 'test-authority']),
        'https://gateway.example.test/payments/verify' => Http::response($verification + ['reference_id' => 'test-reference']),
    ]);
    $this->actingAs($this->payer->user)->post(route('payments.initiate'), $this->input)
        ->assertRedirect('https://gateway.example.test/checkout/test-authority');
    $this->get(route('payments.callback', ['driver' => 'gateway', 'authority' => 'test-authority', 'amount' => '1.00', 'status' => 'success']))
        ->assertRedirect(route('building', $this->payer->complex));
    expect(Transaction::sole()->status)->toBe($status);
    $this->assertDatabaseCount('charge_payments', $status === Transaction::PAID ? 1 : 0);
    Http::assertSent(fn (Request $request) => $request->url() === 'https://gateway.example.test/payments/verify'
        && $request->hasHeader('Authorization', 'Bearer test-key') && $request['authority'] === 'test-authority' && $request['amount'] === '125.75');
})->with([
    'verified' => [['status' => 'success', 'amount' => '125.75'], Transaction::PAID],
    'provider reports wrong amount' => [['status' => 'success', 'amount' => '1.00'], Transaction::FAILED],
    'provider reports failure' => [['status' => 'failure', 'amount' => '125.75'], Transaction::FAILED],
]);

it('rejects repeated non-null gateway authorities in the database', function () {
    $fields = ['amount' => 1, 'gateway' => 'mock', 'authority' => 'unique-test'];
    Transaction::create($fields);
    expect(fn () => Transaction::create($fields))->toThrow(UniqueConstraintViolationException::class);
});

it('requires explicit mock selection in production', function () {
    app()->instance('env', 'production');
    config(['payments.mock_explicit' => false]);

    $manager = app(GatewayManager::class);
    expect($manager->mockSelected())->toBeFalse();
    expect(fn () => $manager->driver())->toThrow(PaymentException::class, 'unconfigured');

    config(['payments.mock_explicit' => true]);
    expect($manager->mockSelected())->toBeTrue();
    expect($manager->driver())->toBeInstanceOf(MockGateway::class);
});

it('blocks a second checkout for the same payable while one is pending', function () {
    $this->actingAs($this->payer->user)->post(route('payments.initiate'), $this->input)->assertRedirect();
    $this->post(route('payments.initiate'), $this->input)->assertSessionHas('message', __('integrations::payments.pending_attempt'));
    $this->assertDatabaseCount('transactions', 1);
});

it('only lists the signed-in payer’s transactions for the current complex', function () {
    Group::factory()->for($this->payer->complex)->create();
    $other = UserRole::factory()->for($this->payer->complex)->create();
    $foreignCharge = MonthlyCharge::factory()->create();

    foreach ([
        [$this->payer->user_id, $this->charge, 'own-reference'],
        [$other->user_id, $this->charge, 'other-user-reference'],
        [$this->payer->user_id, $foreignCharge, 'other-complex-reference'],
    ] as [$userId, $charge, $reference]) {
        Transaction::create([
            'payable_type' => $charge->getMorphClass(), 'payable_id' => $charge->id,
            'user_id' => $userId, 'amount' => 10, 'status' => Transaction::PAID, 'reference_id' => $reference,
        ]);
    }

    $this->actingAs($this->payer->user)->get(route('building', $this->payer->complex))->assertOk()
        ->assertSee('own-reference')->assertDontSee('other-user-reference')->assertDontSee('other-complex-reference');
});

it('maps configurable request keys and nested verification fields', function () {
    $this->payer->user->update(['phone_number' => '09120000001']);
    config([
        'payments.driver' => 'gateway',
        'payments.drivers.gateway.base_url' => 'https://gateway.example.test',
        'payments.drivers.gateway.merchant_id' => 'test-merchant',
        'payments.drivers.gateway.api_key' => 'test-key',
        'payments.drivers.gateway.fields.amount' => 'total',
        'payments.drivers.gateway.fields.initiate_authority' => 'result.token',
        'payments.drivers.gateway.fields.callback_authority' => 'token',
        'payments.drivers.gateway.fields.verify_authority' => 'token',
        'payments.drivers.gateway.fields.verified_reference' => 'result.reference',
    ]);
    Http::fake([
        'https://gateway.example.test/payments' => Http::response(['result' => ['token' => 'mapped-token']]),
        'https://gateway.example.test/payments/verify' => Http::response([
            'status' => 'success', 'amount' => '125.75', 'result' => ['reference' => 'mapped-reference'],
        ]),
    ]);

    $this->actingAs($this->payer->user)->post(route('payments.initiate'), $this->input)
        ->assertRedirect('https://gateway.example.test/checkout/mapped-token');
    $this->post(route('payments.callback', 'gateway'), ['token' => 'mapped-token'])->assertSessionHas('status', 'success');
    expect(Transaction::sole()->reference_id)->toBe('mapped-reference');
    Http::assertSent(fn (Request $request) => $request->url() === 'https://gateway.example.test/payments'
        && $request['total'] === '125.75' && $request['mobile'] === '09120000001'
        && $request['merchant_id'] === 'test-merchant' && $request['callback_url'] === route('payments.callback', 'gateway'));
    Http::assertSent(fn (Request $request) => $request->url() === 'https://gateway.example.test/payments/verify'
        && $request['total'] === '125.75' && $request['token'] === 'mapped-token');
});
