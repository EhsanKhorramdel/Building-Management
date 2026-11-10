<?php

namespace Database\Factories;

use App\Models\Announcement;
use App\Models\Complex;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AnnouncementFactory extends Factory
{
    protected $model = Announcement::class;

    public function definition(): array
    {
        return [
            'complex_id' => Complex::factory(),
            'manager_id' => User::factory(),
            'text' => fake()->paragraph(),
            'image_url' => null,
            'archive' => false,
        ];
    }
}
