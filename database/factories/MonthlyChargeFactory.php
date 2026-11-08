<?php

namespace Database\Factories;

use App\Models\Complex;
use App\Models\MonthlyCharge;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Morilog\Jalali\Jalalian;

class MonthlyChargeFactory extends Factory
{
    protected $model = MonthlyCharge::class;

    public function definition(): array
    {
        return [
            'complex_id' => Complex::factory(),
            'user_id' => User::factory(),
            'amount' => 250000,
            'start_date' => Jalalian::fromCarbon(now()->subMonth())->format('Y-m-d'),
            'end_date' => Jalalian::fromCarbon(now()->addMonths(2))->format('Y-m-d'),
        ];
    }
}
