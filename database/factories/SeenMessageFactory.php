<?php

namespace Database\Factories;

use App\Models\Message;
use App\Models\SeenMessage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SeenMessageFactory extends Factory
{
    protected $model = SeenMessage::class;

    public function definition(): array
    {
        return [
            'message_id' => Message::factory(),
            'user_id' => User::factory(),
            'group_id' => fn (array $attributes) => Message::findOrFail($attributes['message_id'])->group_id,
        ];
    }
}
