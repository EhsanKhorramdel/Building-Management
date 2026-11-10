<?php

use App\Models\Poll;
use App\Models\PollOption;
use App\Models\UserRole;
use App\Models\Vote;

test('unit userRoles returns only roles referencing that unit', function () {
    $role = UserRole::factory()->create();
    $other = UserRole::factory()->create(['complex_id' => $role->complex_id]);

    expect($role->unit->userRoles()->pluck('id')->all())->toBe([$role->id])
        ->and($other->unit->userRoles()->pluck('id')->all())->toBe([$other->id]);
});

test('poll option votes follows poll_option_id rather than the role id', function () {
    $poll = Poll::factory()->create();
    $first = PollOption::factory()->create(['poll_id' => $poll->id]);
    $second = PollOption::factory()->create(['poll_id' => $poll->id]);
    $role = UserRole::factory()->create(['complex_id' => $poll->complex_id]);
    $vote = Vote::factory()->create(['poll_id' => $poll->id, 'poll_option_id' => $second->id, 'role_id' => $role->id]);

    expect($second->votes()->pluck('id')->all())->toBe([$vote->id])->and($first->votes()->count())->toBe(0);
});
