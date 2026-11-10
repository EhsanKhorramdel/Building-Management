<?php

namespace Database\Factories;

use App\Models\IncidentalCost;
use App\Models\PaymentAdditionalCost;
use App\Models\UserRole;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentAdditionalCostFactory extends Factory
{
    protected $model = PaymentAdditionalCost::class;

    public function definition(): array
    {
        return [
            'role_id' => UserRole::factory(),
            'incidental_costs_id' => fn (array $attributes) => IncidentalCost::factory()->create([
                'complex_id' => UserRole::findOrFail($attributes['role_id'])->complex_id,
            ])->id,
            'amount' => fn (array $attributes) => IncidentalCost::findOrFail($attributes['incidental_costs_id'])->share_amount,
        ];
    }
}
