<?php

namespace Database\Factories;

use App\Models\Complex;
use App\Models\Poll;
use Illuminate\Database\Eloquent\Factories\Factory;

class PollFactory extends Factory
{
    protected $model = Poll::class;

    public function definition(): array
    {
        return [
            'complex_id' => Complex::factory(),
            'title' => fake()->words(3, true),
            'question' => fake()->sentence(),
            'start_date' => now()->subHour(),
            'end_date' => now()->addDay(),
        ];
    }

    public function expired(): static
    {
        return $this->state(fn () => ['start_date' => now()->subDays(2), 'end_date' => now()->subDay()]);
    }
}
