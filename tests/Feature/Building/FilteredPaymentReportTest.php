<?php

use App\Http\Controllers\PaymentsReportController;
use App\Http\Requests\PaymentsReportRequest;
use App\Models\ChargePayment;
use App\Models\IncidentalCost;
use App\Models\MonthlyCharge;
use App\Models\PaymentAdditionalCost;
use App\Models\Unit;
use App\Models\UserRole;
use Morilog\Jalali\Jalalian;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\Support\BuildingContext;
use Tests\Support\QueryBudget;

beforeEach(function () {
    $this->building = new BuildingContext;
    $this->building->managerRole->unit->update(['unit_number' => 2]);
    $unit = Unit::factory()->for($this->building->complex)->create(['unit_number' => 1]);
    $this->resident = UserRole::factory()->for($this->building->complex)->for($unit)->create();
    $this->charge = MonthlyCharge::factory()->for($this->building->complex)->create();
    $paid = Jalalian::fromFormat('Y/m/d', '1404/02/10')->toCarbon()->endOfDay();
    ChargePayment::factory()->create([
        'role_id' => $this->resident->id, 'monthly_charge_id' => $this->charge->id,
        'period_year' => 1404, 'period_month' => 2, 'amount' => 101, 'created_at' => $paid,
    ]);
    ChargePayment::factory()->create([
        'role_id' => $this->resident->id, 'monthly_charge_id' => $this->charge->id,
        'period_year' => 1404, 'period_month' => 1, 'amount' => 202,
        'created_at' => Jalalian::fromFormat('Y/m/d', '1404/01/10')->toCarbon(),
    ]);
    PaymentAdditionalCost::factory()->create([
        'role_id' => $this->building->managerRole->id, 'amount' => 303, 'created_at' => $paid,
        'incidental_costs_id' => IncidentalCost::factory()->for($this->building->complex)->create()->id,
    ]);
    $this->scope = ['complex_id' => $this->building->complex->id];
});

it('filters completed report rows by each individual filter and their intersection', function (array $filters, array $amounts) {
    $rows = $this->actingAs($this->building->manager)->postJson(route('building.payments'), $this->scope + $filters)
        ->assertOk()->json();
    expect(collect($rows)->pluck('amount')->map(fn ($amount) => (int) $amount)->sort()->values()->all())->toBe($amounts);
})->with([
    'period' => [['period_year' => '۱۴۰۴', 'period_month' => '۰۲'], [101]],
    'from date' => [['paid_from' => '۱۴۰۴/۰۲/۱۰'], [101, 303]],
    'inclusive to date' => [['paid_to' => '1404/02/10'], [101, 202, 303]],
    'older to date' => [['paid_to' => '1404/01/10'], [202]],
    'unit' => [['unit_number' => '١'], [101, 202]],
    'charge type' => [['type' => 'charge'], [101, 202]],
    'cost type' => [['type' => 'cost'], [303]],
    'completed status' => [['status' => 'paid'], [101, 202, 303]],
    'combined' => [['period_year' => 1404, 'period_month' => 2, 'unit_number' => 1, 'type' => 'charge', 'status' => 'paid',
        'paid_from' => '1404/02/10', 'paid_to' => '1404/02/10'], [101]],
    'charge period with cost type' => [['period_year' => 1404, 'period_month' => 2, 'type' => 'cost'], []],
]);

it('validates incomplete periods invalid dates reversed ranges and non-completed statuses', function (array $input, string $field) {
    $this->actingAs($this->building->manager)->postJson(route('building.payments'), $this->scope + $input)
        ->assertUnprocessable()->assertJsonValidationErrors($field);
})->with([
    [['period_year' => 1404], 'period_month'],
    [['period_month' => 13, 'period_year' => 1404], 'period_month'],
    [['paid_from' => '1404/07/31'], 'paid_from'],
    [['paid_from' => '1404/02/10', 'paid_to' => '1404/01/10'], 'paid_to'],
    [['status' => 'failed'], 'status'],
]);

it('streams the same filtered data columns with a UTF-8 BOM', function () {
    $this->actingAs($this->building->manager);
    $scope = $this->scope + ['paid_from' => '1404/02/10'];
    $screen = $this->postJson(route('building.payments'), $scope)->assertOk()->json();
    $download = $this->get(route('building.payments.export', $scope))->assertOk()->assertDownload('payments.csv');
    expect($download->baseResponse)->toBeInstanceOf(StreamedResponse::class);
    $csv = $download->streamedContent();
    expect(substr($csv, 0, 3))->toBe("\xEF\xBB\xBF");
    $stream = fopen('php://temp', 'w+');
    fwrite($stream, substr($csv, 3));
    rewind($stream);
    expect(fgetcsv($stream, escape: ''))->toBe(['نام', 'شماره واحد', 'نوع پرداخت', 'تاریخ پرداخت', 'مبلغ']);
    foreach ($screen as $row) {
        expect(fgetcsv($stream, escape: ''))->toBe(array_map('strval', [$row['user_name'], $row['unit_number'], $row['type'], $row['date'], $row['amount']]));
    }
    expect(fgetcsv($stream, escape: ''))->toBeFalse();
    fclose($stream);
});

it('keeps authorization on the exact report complex and excludes foreign payment records', function () {
    $foreign = new BuildingContext;
    $this->actingAs($foreign->manager);
    $filters = $this->scope + ['complexId' => $foreign->complex->id, 'unit_number' => 1, 'period_year' => 1404, 'period_month' => 2];
    $this->postJson(route('building.payments'), $filters)->assertForbidden();
    $this->get(route('building.payments.export', $filters))->assertForbidden();

    $foreignCharge = MonthlyCharge::factory()->for($foreign->complex)->create();
    ChargePayment::factory()->create([
        'role_id' => $this->resident->id, 'monthly_charge_id' => $foreignCharge->id,
        'period_year' => 1404, 'period_month' => 2, 'amount' => 999,
    ]);
    $this->actingAs($this->building->manager)->postJson(route('building.payments'), $filters)
        ->assertOk()->assertJsonCount(1)->assertJsonMissing(['amount' => 999]);
});

it('keeps the filtered action at five queries across 2 8 and 32 units', function () {
    $budget = new QueryBudget;
    $counts = [];
    foreach ([2, 8, 32] as $size) {
        $complex = (new BuildingContext)->complex;
        $manager = $complex->userRoles()->firstOrFail();
        $manager->unit->update(['unit_number' => 1]);
        $charge = MonthlyCharge::factory()->for($complex)->create();
        for ($number = 1; $number <= $size; $number++) {
            $unit = Unit::factory()->for($complex)->create(['unit_number' => $number + 100]);
            $role = UserRole::factory()->for($complex)->for($unit)->create();
            ChargePayment::factory()->create(['role_id' => $role->id, 'monthly_charge_id' => $charge->id, 'period_year' => 1404, 'period_month' => 2]);
        }
        $this->actingAs($manager->user);
        $request = PaymentsReportRequest::create('/', 'POST', ['complex_id' => $complex->id, 'period_year' => 1404, 'period_month' => 2, 'type' => 'charge']);
        $request->setValidator(validator($request->all(), $request->rules()));
        $counts[] = $budget->measure(function () use ($request, $size) {
            expect(app(PaymentsReportController::class)->payments($request)->getData())->toHaveCount($size);
        });
    }
    expect($counts)->toBe([5, 5, 5]);
});
