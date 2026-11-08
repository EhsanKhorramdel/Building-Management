<?php

namespace Database\Factories;

use App\Models\Complex;
use App\Models\Unit;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserRoleFactory extends Factory
{
    protected $model = UserRole::class;

    public function definition(): array
    {
        return [
            'complex_id' => Complex::factory(),
            'unit_id' => fn (array $attributes) => Unit::factory()->create(['complex_id' => $attributes['complex_id']])->id,
            'user_id' => User::factory(),
            'role' => 'resident',
            'start_date' => now()->toDateString(),
            'end_date' => null,
            'active' => true,
        ];
    }

    public function manager(): static
    {
        return $this->state(fn () => ['role' => 'manager']);
    }

    public function admin(): static
    {
        return $this->state(fn () => ['role' => 'admin', 'complex_id' => null, 'unit_id' => null]);
    }

    public function unassigned(): static
    {
        return $this->state(fn () => ['unit_id' => null]);
    }
}
