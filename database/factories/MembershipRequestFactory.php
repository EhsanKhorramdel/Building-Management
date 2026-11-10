<?php

namespace Database\Factories;

use App\Models\MembershipRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MembershipRequestFactory extends Factory
{
    protected $model = MembershipRequest::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->company(),
            'address' => fake()->address(),
            'location' => null,
            'number_of_floors' => 4,
            'number_of_units' => 12,
            'request_status' => 'pending',
        ];
    }
}
