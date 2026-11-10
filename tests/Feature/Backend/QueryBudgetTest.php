<?php

use App\Http\Controllers\BuildingDashboardController;
use App\Http\Controllers\PaymentsReportController;
use App\Http\Controllers\UserController;
use App\Http\Requests\PaymentsReportRequest;
use App\Models\Announcement;
use App\Models\ChargePayment;
use App\Models\Complex;
use App\Models\Group;
use App\Models\IncidentalCost;
use App\Models\MonthlyCharge;
use App\Models\PaymentAdditionalCost;
use App\Models\Poll;
use App\Models\Unit;
use App\Models\UserRole;
use Tests\Support\QueryBudget;

function queryBudgetFixture(int $size): array
{
    $complex = Complex::factory()->create(['number_of_units' => $size]);
    Group::factory()->for($complex)->create();

    $roles = [];
    for ($number = 1; $number <= $size; $number++) {
        $unit = Unit::factory()->for($complex)->create(['unit_number' => $number]);
        $roles[] = UserRole::factory()->for($complex)->for($unit)->create([
            'role' => $number === 1 ? 'manager' : 'resident',
        ]);
        $poll = Poll::factory()->for($complex)->create();
        $poll->pollOptions()->createMany([
            ['option_number' => 1, 'option' => 'Yes'],
            ['option_number' => 2, 'option' => 'No'],
        ]);
    }
    $manager = $roles[0]->user;
    Announcement::factory()->for($complex)->create(['manager_id' => $manager->id]);
    $charge = MonthlyCharge::factory()->for($complex)->create(['user_id' => $manager->id]);
    $cost = IncidentalCost::factory()->for($complex)->create();

    foreach ($roles as $role) {
        ChargePayment::factory()->create(['role_id' => $role->id, 'monthly_charge_id' => $charge->id]);
        PaymentAdditionalCost::factory()->create(['role_id' => $role->id, 'incidental_costs_id' => $cost->id]);
    }

    return [$complex, $manager];
}

it('keeps the action within its query budget as units grow', function (string $surface, int $cap) {
    config(['payments.enabled' => false, 'sms.enabled' => false]);
    $budget = new QueryBudget;
    $counts = [];

    // Match the comparison audit's action counts. Auth, route binding and Blade rendering are outside this measurement.
    foreach ([2, 8, 32] as $size) {
        [$complex, $manager] = queryBudgetFixture($size);
        $this->actingAs($manager);

        $request = PaymentsReportRequest::create('/', 'POST', ['complex_id' => $complex->id]);
        $request->setValidator(validator($request->all(), $request->rules()));
        $action = match ($surface) {
            'payments report' => fn () => app(PaymentsReportController::class)->payments($request),
            'building dashboard' => fn () => app(BuildingDashboardController::class)->index($complex),
            'user dashboard' => fn () => app(UserController::class)->index(),
        };

        $response = null;
        $counts[$size] = $budget->measure(function () use ($action, &$response) {
            $response = $action();
        });

        match ($surface) {
            'payments report' => expect($response->getData())->toHaveCount($size * 2),
            'building dashboard' => expect($response->getName())->toBe('building.building'),
            'user dashboard' => expect($response->getName())->toBe('dashboard'),
        };
    }

    expect(array_values($counts))->toBe(array_fill(0, 3, $counts[2]))
        ->and(max($counts))->toBeLessThanOrEqual($cap);
})->with([
    'payments report' => ['payments report', 6],
    'building dashboard' => ['building dashboard', 11],
    'user dashboard' => ['user dashboard', 2],
]);
