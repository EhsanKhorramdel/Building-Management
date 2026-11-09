<?php

use App\Models\Announcement;
use App\Models\ChargePayment;
use App\Models\MembershipRequest;
use App\Models\MonthlyCharge;
use App\Models\SmsLog;
use App\Models\Transaction;
use App\Models\UserRole;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Morilog\Jalali\Jalalian;

function triggerBuildingSms($test, string $trigger): array
{
    $manager = UserRole::factory()->manager()->create();
    $manager->user->update(['phone_number' => '09120000001']);
    $resident = UserRole::factory()->for($manager->complex)->create();
    $resident->user->update(['phone_number' => '09120000002']);
    UserRole::factory()->for($manager->complex)->create();
    $unassigned = UserRole::factory()->unassigned()->for($manager->complex)->create();
    $unassigned->user->update(['phone_number' => '09120000003']);
    $foreign = UserRole::factory()->create();
    $foreign->user->update(['phone_number' => '09120000004']);

    $building = route('building', $manager->complex);
    $test->actingAs($manager->user)->from($building);
    switch ($trigger) {
        case 'announcement':
            $test->post(route('building.NotificationRegistration', $manager->complex), ['notifText' => 'اطلاعیه آزمایشی'])
                ->assertRedirect($building)->assertSessionHas('status', 'success');
            expect(Announcement::count())->toBe(1);

            return ['09120000001', '09120000002', '09120000003'];

        case 'monthly_charge':
            $period = Jalalian::fromCarbon(now());
            $test->post(route('building.buildingCharge', $manager->complex), [
                'chargeAmount' => 250,
                'dayStart' => 1, 'monthStart' => $period->getMonth(), 'yearStart' => $period->getYear(),
                'dayEnd' => 20, 'monthEnd' => $period->getMonth(), 'yearEnd' => $period->getYear(),
            ])->assertRedirect($building)->assertSessionHas('status', 'success');
            expect(MonthlyCharge::count())->toBe(1);

            return ['09120000001', '09120000002'];

        case 'membership_approved':
            $request = MembershipRequest::factory()->for($resident->user)->create();
            $admin = UserRole::factory()->admin()->create();
            $test->actingAs($admin->user)->from(route('adminPanel.index'))->post(route('adminPanel.accept', $request))
                ->assertRedirect(route('adminPanel.index'));
            expect($request->fresh()->request_status)->toBe('accepted');

            return ['09120000002'];

        case 'payment_receipt':
            config(['payments.enabled' => true, 'payments.driver' => 'mock']);
            $charge = MonthlyCharge::factory()->for($manager->complex)->create();
            $test->actingAs($resident->user)->post(route('payments.initiate'), [
                'type' => 'charge', 'payable_id' => $charge->id, 'unitNumber' => $resident->unit->unit_number,
            ])->assertRedirect();
            $transaction = Transaction::sole();
            $payload = [
                '_token' => 'sms-test-token', 'authority' => $transaction->authority,
                'outcome' => 'pay', 'amount' => $transaction->amount,
            ];
            $test->withSession(['_token' => 'sms-test-token']);
            $test->post(route('payments.callback', 'mock'), $payload)->assertSessionHas('status', 'success');
            $test->post(route('payments.callback', 'mock'), $payload)->assertSessionHas('status', 'success');
            expect(ChargePayment::count())->toBe(1);

            return ['09120000002'];
    }

    throw new InvalidArgumentException($trigger);
}

it('does not enqueue or log SMS for any trigger with the flag off', function (string $trigger) {
    config(['sms.enabled' => false]);
    triggerBuildingSms($this, $trigger);
    $this->assertDatabaseEmpty('jobs');
    $this->assertDatabaseEmpty('sms_logs');
})->with(['announcement', 'monthly_charge', 'membership_approved', 'payment_receipt']);

it('queues one log job per trigger and skips members without phone numbers', function (string $trigger) {
    config(['sms.enabled' => true, 'sms.driver' => 'log']);
    $expected = triggerBuildingSms($this, $trigger);
    $this->assertDatabaseCount('jobs', 1);
    $this->assertDatabaseHas('jobs', ['queue' => 'sms']);
    $this->assertDatabaseEmpty('sms_logs');

    $this->artisan('queue:work', ['connection' => 'database', '--queue' => 'sms', '--once' => true])->assertExitCode(0);

    $logs = SmsLog::orderBy('recipient')->get();
    expect($logs->pluck('recipient')->all())->toBe($expected);
    expect($logs->pluck('status')->unique()->all())->toBe([SmsLog::LOGGED]);
    expect($logs->pluck('template')->unique()->all())->toBe([$trigger]);
    if ($trigger === 'monthly_charge') {
        expect($logs->first()->body)->toContain('250', Jalalian::fromCarbon(now())->format('Y/m'));
    }
    if ($trigger === 'payment_receipt') {
        expect($logs->sole()->body)->toContain(Transaction::sole()->reference_id);
    }
})->with(['announcement', 'monthly_charge', 'membership_approved', 'payment_receipt']);

it('posts the SMS payload with bearer auth and records HTTP failure outside the announcement request', function (int $response, string $status) {
    config([
        'sms.enabled' => true, 'sms.driver' => 'gateway',
        'sms.drivers.gateway.base_url' => 'https://sms.example.test/send',
        'sms.drivers.gateway.api_key' => 'fixture-token',
        'sms.drivers.gateway.sender' => 'fixture-sender',
    ]);
    Http::fake(['https://sms.example.test/send' => Http::response([], $response)]);
    $recipients = triggerBuildingSms($this, 'announcement');
    Http::assertNothingSent();
    $this->assertDatabaseEmpty('sms_logs');
    $this->artisan('queue:work', ['connection' => 'database', '--queue' => 'sms', '--once' => true])->assertExitCode(0);

    $announcement = Announcement::sole();
    foreach ($recipients as $to) {
        Http::assertSent(fn (Request $request) => $request->url() === 'https://sms.example.test/send'
            && $request->hasHeader('Authorization', 'Bearer fixture-token')
            && $request->hasHeader('X-Sender', 'fixture-sender')
            && $request->data() === ['to' => $to, 'message' => __('integrations::sms.announcement', ['complex' => $announcement->complex->name])]);
    }
    $this->assertDatabaseCount('sms_logs', count($recipients));
    expect(SmsLog::first()->status)->toBe($status)->and(SmsLog::first()->meta)->toBe(['http_status' => $response]);
})->with([
    'successful delivery' => [202, SmsLog::SENT],
    'provider failure' => [500, SmsLog::FAILED],
]);

it('logs a closed failure when the SMS gateway is unconfigured', function () {
    config(['sms.enabled' => true, 'sms.driver' => 'gateway']);
    Http::fake();
    triggerBuildingSms($this, 'membership_approved');
    $this->artisan('queue:work', ['connection' => 'database', '--queue' => 'sms', '--once' => true])->assertExitCode(0);
    expect(SmsLog::sole()->status)->toBe(SmsLog::FAILED)->and(SmsLog::sole()->meta)->toBe(['reason' => 'unconfigured']);
    Http::assertNothingSent();
});

it('keeps the announcement successful when enqueueing fails', function () {
    config(['sms.enabled' => true]);
    Bus::shouldReceive('dispatch')->once()->andThrow(new RuntimeException('queue unavailable'));
    triggerBuildingSms($this, 'announcement');
    $this->assertDatabaseEmpty('sms_logs');
});
