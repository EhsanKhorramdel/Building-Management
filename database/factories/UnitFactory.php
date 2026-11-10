<?php

namespace Database\Factories;

use App\Models\Complex;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

class UnitFactory extends Factory
{
    protected $model = Unit::class;

    public function definition(): array
    {
        return [
            'complex_id' => Complex::factory(),
            'zip_code' => fake()->numerify('##########'),
            'unit_number' => fake()->unique()->numberBetween(1, 99999),
            'floor_number' => fake()->numberBetween(1, 4),
        ];
    }
}
