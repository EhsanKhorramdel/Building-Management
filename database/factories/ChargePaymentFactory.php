<?php

namespace Database\Factories;

use App\Models\ChargePayment;
use App\Models\MonthlyCharge;
use App\Models\UserRole;
use Illuminate\Database\Eloquent\Factories\Factory;
use Morilog\Jalali\Jalalian;

class ChargePaymentFactory extends Factory
{
    protected $model = ChargePayment::class;

    public function definition(): array
    {
        return [
            'role_id' => UserRole::factory(),
            'monthly_charge_id' => fn (array $attributes) => MonthlyCharge::factory()->create([
                'complex_id' => UserRole::findOrFail($attributes['role_id'])->complex_id,
            ])->id,
            'amount' => fn (array $attributes) => MonthlyCharge::findOrFail($attributes['monthly_charge_id'])->amount,
            'period_year' => Jalalian::fromCarbon(now())->getYear(),
            'period_month' => Jalalian::fromCarbon(now())->getMonth(),
        ];
    }
}
