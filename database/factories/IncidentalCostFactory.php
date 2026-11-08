<?php

namespace Database\Factories;

use App\Models\Complex;
use App\Models\IncidentalCost;
use Illuminate\Database\Eloquent\Factories\Factory;

class IncidentalCostFactory extends Factory
{
    protected $model = IncidentalCost::class;

    public function definition(): array
    {
        return [
            'complex_id' => Complex::factory(),
            'cost_invoice' => null,
            'cost_explanation' => fake()->sentence(),
            'title' => fake()->words(3, true),
            'total_amount' => 1200000,
            'share_amount' => 100000,
        ];
    }
}
