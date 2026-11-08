<?php

namespace Database\Factories;

use App\Models\Complex;
use App\Models\Group;
use Illuminate\Database\Eloquent\Factories\Factory;

class GroupFactory extends Factory
{
    protected $model = Group::class;

    public function definition(): array
    {
        return [
            'complex_id' => Complex::factory(),
            'group_name' => fake()->company(),
        ];
    }
}
