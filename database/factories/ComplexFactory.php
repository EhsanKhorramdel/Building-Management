<?php

namespace Database\Factories;

use App\Models\Complex;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ComplexFactory extends Factory
{
    protected $model = Complex::class;

    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'address' => fake()->address(),
            'location' => null,
            'number_of_floors' => 4,
            'number_of_units' => 12,
            'join_link' => Str::random(32),
        ];
    }
}
