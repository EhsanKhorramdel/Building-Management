<?php

namespace Database\Factories;

use App\Models\Poll;
use App\Models\PollOption;
use App\Models\UserRole;
use App\Models\Vote;
use Illuminate\Database\Eloquent\Factories\Factory;

class VoteFactory extends Factory
{
    protected $model = Vote::class;

    public function definition(): array
    {
        return [
            'poll_id' => Poll::factory(),
            'role_id' => fn (array $attributes) => UserRole::factory()->create([
                'complex_id' => Poll::findOrFail($attributes['poll_id'])->complex_id,
            ])->id,
            'poll_option_id' => fn (array $attributes) => PollOption::factory()->create(['poll_id' => $attributes['poll_id']])->id,
        ];
    }
}
