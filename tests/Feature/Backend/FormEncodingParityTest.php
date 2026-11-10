<?php

use App\Models\ChargePayment;
use App\Models\IncidentalCost;
use App\Models\MembershipRequest;
use App\Models\Message;
use App\Models\MonthlyCharge;
use App\Models\PaymentAdditionalCost;
use App\Models\Poll;
use App\Models\SeenMessage;
use App\Models\Transaction;
use App\Models\Unit;
use App\Models\Vote;
use Illuminate\Support\Facades\DB;
use Tests\Support\BuildingContext;

beforeEach(function () {
    config(['payments.enabled' => false, 'sms.enabled' => false]);
    $this->freezeSecond();
    $this->building = new BuildingContext;
    $this->resident = $this->building->resident();
    $this->actingAs($this->building->manager)->from(route('dashboard'));
});

function compareFormEncoding(
    string $route,
    array $payload,
    callable $state,
    string $method = 'POST',
    int $status = 302,
    array $browserOverrides = [],
): void {
    $test = test();
    $actor = auth()->user();
    $snapshots = [];

    foreach ([false, true] as $browser) {
        $test->actingAs($actor->fresh());
        DB::beginTransaction();

        try {
            if ($browser) {
                $form = $browserOverrides + array_map(fn ($value) => (string) $value, $payload);
                $body = http_build_query($form);
                parse_str($body, $fields);
                $response = $test->call($method, $route, $fields, server: ['CONTENT_TYPE' => 'application/x-www-form-urlencoded'], content: $body);
            } else {
                $response = $test->call($method, $route, server: ['CONTENT_TYPE' => 'application/json'], content: json_encode($payload, JSON_THROW_ON_ERROR));
            }

            $response->assertStatus($status)->assertSessionHasNoErrors();
            $snapshots[] = [
                $response->baseResponse::class,
                $response->headers->get('Location'),
                session('status'),
                session('message'),
                $status === 200 ? $response->getContent() : null,
                $state(),
            ];
        } finally {
            DB::rollBack();
        }
    }

    expect($snapshots[1])->toBe($snapshots[0]);
}

it('matches form and typed charge amounts and Jalali date parts', function () {
    compareFormEncoding(route('building.buildingCharge', $this->building->complex), [
        'chargeAmount' => 42.75,
        'dayStart' => 1, 'monthStart' => 2, 'yearStart' => 1405,
        'dayEnd' => 5, 'monthEnd' => 3, 'yearEnd' => 1405,
    ], fn () => MonthlyCharge::sole()->only(['amount', 'start_date', 'end_date']));
});

it('matches form and typed incidental cost amounts', function () {
    compareFormEncoding(route('building.incidentalCost', $this->building->complex), [
        'title' => 'Window repair', 'total_amount' => 125.50, 'share_amount' => 12.55,
    ], fn () => IncidentalCost::sole()->only(['title', 'total_amount', 'share_amount']));
});

it('matches form and typed poll durations with a blank middle part', function () {
    compareFormEncoding(route('building.poll', $this->building->complex), [
        'question' => 'Which day?', 'option1' => 'Thursday', 'option2' => 'Friday',
        'endMinutes' => 15, 'endHours' => null, 'endDays' => 1,
    ], fn () => Poll::sole()->end_date->toDateTimeString());
});

it('matches form and typed unit creation including a blank floor', function () {
    compareFormEncoding(route('dashboard.addUnit'), [
        'complexId' => $this->building->complex->id, 'unitNumber' => 200001,
        'floorNumber' => null, 'zipCode' => '0012345678',
    ], fn () => Unit::where('unit_number', 200001)->sole()->only(['unit_number', 'floor_number', 'zip_code']));
});

it('matches form and typed unit updates including the authorization lookup', function () {
    $unit = $this->resident->unit;
    compareFormEncoding(route('dashboard.unit'), [
        'complexId' => $this->building->complex->id, 'currentUnitNumber' => $unit->unit_number,
        'newUnitNumber' => 200001, 'floorNumber' => -2, 'zipCode' => '0012345678',
    ], fn () => $unit->fresh()->only(['unit_number', 'floor_number', 'zip_code']),
        browserOverrides: ['currentUnitNumber' => strtr((string) $unit->unit_number, array_combine(range(0, 9), mb_str_split('۰۱۲۳۴۵۶۷۸۹')))]);
});

it('matches form and typed registration counts and coordinates', function () {
    compareFormEncoding(route('dashboard.register'), [
        'name' => 'New building', 'address' => 'Main street',
        'floors' => 5, 'units' => 20, 'latitude' => 35.6892, 'longitude' => 51.389,
    ], fn () => MembershipRequest::sole()->only(['number_of_floors', 'number_of_units', 'latitude', 'longitude']));
});

it('matches form and typed building coordinates', function (array $coordinates, array $overrides) {
    $complex = $this->building->complex;
    $complex->update(['latitude' => 35.0, 'longitude' => 51.0]);
    compareFormEncoding(route('building.location.update', $complex), $coordinates,
        fn () => $complex->fresh()->only(['latitude', 'longitude']), method: 'PATCH', browserOverrides: $overrides);
})->with([
    'ASCII decimals' => [['latitude' => 35.6892, 'longitude' => 51.389], []],
    'Persian digits' => [['latitude' => 35.6892, 'longitude' => 51.389], ['latitude' => ' ۳۵.۶۸۹۲ ']],
    'blank pair' => [['latitude' => null, 'longitude' => null], []],
    'omitted pair' => [[], []],
]);

it('matches form and typed optional profile numbers', function (array $numbers, array $overrides) {
    $user = $this->building->manager;
    $user->update(['national_code' => '0012345678', 'phone_number' => '09123456789']);
    compareFormEncoding(route('profile.update'), ['name' => 'Updated', 'email' => $user->email] + $numbers,
        fn () => $user->fresh()->only(['name', 'email', 'national_code', 'phone_number']), method: 'PATCH', browserOverrides: $overrides);
})->with([
    'blank numbers' => [['ncode' => null, 'phoneNumber' => null], []],
    'omitted numbers' => [[], []],
    'localized identifier with leading zeroes' => [['ncode' => '00042', 'phoneNumber' => '09123456789'], ['ncode' => '۰۰۰۴۲']],
]);

it('matches form and typed unit and payable IDs for manual payments', function (string $type) {
    $role = $this->resident;
    $this->actingAs($role->user);
    $charge = $type === 'charge';
    $payable = ($charge ? MonthlyCharge::factory() : IncidentalCost::factory())->for($role->complex)->create();
    compareFormEncoding(route($charge ? 'building.buildingChargePayment' : 'building.incidentalCostPayment'), [
        'complexId' => $role->complex_id, 'unitNumber' => $role->unit->unit_number,
        ($charge ? 'monthlyChargeId' : 'incidentalCostId') => $payable->id,
    ], fn () => ($charge ? ChargePayment::sole() : PaymentAdditionalCost::sole())->only(['role_id', 'amount']));
})->with(['charge', 'cost']);

it('matches form and typed online payment initiation IDs', function () {
    config(['payments.enabled' => true, 'payments.driver' => 'mock']);
    $role = $this->resident;
    $this->actingAs($role->user);
    $charge = MonthlyCharge::factory()->for($role->complex)->create(['amount' => 125.75]);
    compareFormEncoding(route('payments.initiate'), [
        'type' => 'charge', 'payable_id' => $charge->id, 'unitNumber' => $role->unit->unit_number,
    ], fn () => Transaction::sole()->only(['payable_id', 'role_id', 'amount', 'status']));
});

it('matches form and typed mock callback amounts', function () {
    config(['payments.enabled' => true, 'payments.driver' => 'mock']);
    $role = $this->resident;
    $this->actingAs($role->user);
    $charge = MonthlyCharge::factory()->for($role->complex)->create(['amount' => 125.75]);
    $this->post(route('payments.initiate'), [
        'type' => 'charge', 'payable_id' => $charge->id, 'unitNumber' => $role->unit->unit_number,
    ])->assertRedirect();
    $transaction = Transaction::sole();
    $this->withSession(['_token' => 'form-parity-token']);
    compareFormEncoding(route('payments.callback', 'mock'), [
        '_token' => 'form-parity-token', 'authority' => $transaction->authority, 'amount' => 125.75, 'outcome' => 'pay',
    ], fn () => [$transaction->fresh()->status, ChargePayment::sole()->only(['amount', 'period_year', 'period_month'])]);
});

it('matches form and typed vote option IDs', function () {
    $poll = Poll::factory()->for($this->building->complex)->create();
    $option = $poll->pollOptions()->create(['option_number' => 1, 'option' => 'Yes']);
    compareFormEncoding(route('building.vote'), ['voteRadio' => $option->id],
        fn () => Vote::sole()->only(['poll_id', 'poll_option_id', 'role_id']));
});

it('matches form and typed poll result IDs', function () {
    $poll = Poll::factory()->for($this->building->complex)->create();
    $option = $poll->pollOptions()->create(['option_number' => 1, 'option' => 'Yes']);
    Vote::factory()->create(['poll_id' => $poll->id, 'poll_option_id' => $option->id, 'role_id' => $this->resident->id]);
    compareFormEncoding(route('building.voteResult'), ['poll_id' => $poll->id], fn () => Vote::count(), status: 200);
});

it('matches form and typed payments report complex IDs', function () {
    $role = $this->resident;
    $charge = MonthlyCharge::factory()->for($role->complex)->create();
    ChargePayment::factory()->create(['role_id' => $role->id, 'monthly_charge_id' => $charge->id]);
    compareFormEncoding(route('building.payments'), ['complex_id' => $role->complex_id], fn () => ChargePayment::count(), status: 200);
});

it('matches form and typed chat send complex IDs', function () {
    compareFormEncoding('/dashboard/building/group/send', [
        'complexId' => $this->building->complex->id, 'messageText' => 'Hello',
    ], fn () => Message::sole()->only(['group_id', 'user_id', 'text']), status: 200);
});

it('matches form and typed chat message IDs', function (string $path, string $field, Closure $state) {
    $message = Message::factory()->for($this->building->group)->for($this->building->manager)->create();
    SeenMessage::factory()->create([
        'message_id' => $message->id, 'group_id' => $message->group_id, 'user_id' => $this->resident->user_id,
    ]);
    compareFormEncoding("/dashboard/building/{$path}", [$field => $message->id, 'messageText' => 'Edited'],
        fn () => $state($message), status: 200);
})->with([
    'edit' => ['group/edit', 'messageId', fn (Message $message) => $message->fresh()->only(['text', 'edited'])],
    'seen' => ['group/seen', 'messageId', fn (Message $message) => SeenMessage::where('message_id', $message->id)->count()],
    'viewers' => ['getMessageViewers', 'message_id', fn (Message $message) => SeenMessage::where('message_id', $message->id)->count()],
]);
