<?php

use App\Models\Poll;
use App\Models\PollOption;
use App\Models\Vote;
use Tests\Support\BuildingContext;

test('a manager can create a poll with two ordered options', function () {
    $building = new BuildingContext;

    $this->actingAs($building->manager)->post(route('building.poll', $building->complex->id), [
        'title' => 'Meeting', 'question' => 'Which day?', 'endMinutes' => 30,
        'endHours' => 0, 'endDays' => 0, 'option1' => 'Thursday', 'option2' => 'Friday',
    ])->assertSessionHasNoErrors()->assertSessionHas('status', 'success');

    $poll = Poll::firstOrFail();
    expect($poll->complex_id)->toBe($building->complex->id)
        ->and($poll->pollOptions()->orderBy('option_number')->pluck('option')->all())->toBe(['Thursday', 'Friday'])
        ->and($poll->end_date->isAfter($poll->start_date))->toBeTrue();
});

test('a resident can cast and change a vote without adding a second vote row', function () {
    $building = new BuildingContext;
    $resident = $building->resident();
    $poll = Poll::factory()->create(['complex_id' => $building->complex->id]);
    $options = PollOption::factory()->count(2)->sequence(['option_number' => 1], ['option_number' => 2])->create(['poll_id' => $poll->id]);

    $this->actingAs($resident->user)->post(route('building.vote'), ['voteRadio' => $options[0]->id])->assertSessionHas('status', 'success');
    $this->assertDatabaseHas('votes', ['poll_id' => $poll->id, 'role_id' => $resident->id, 'poll_option_id' => $options[0]->id]);

    $this->post(route('building.vote'), ['voteRadio' => $options[1]->id])->assertSessionHas('status', 'success');
    $this->assertDatabaseCount('votes', 1);
    expect(Vote::firstOrFail()->poll_option_id)->toBe($options[1]->id);
});

test('poll results include voted and zero-vote options', function () {
    $building = new BuildingContext;
    $resident = $building->resident();
    $poll = Poll::factory()->create(['complex_id' => $building->complex->id]);
    $first = PollOption::factory()->create(['poll_id' => $poll->id, 'option_number' => 1, 'option' => 'First']);
    PollOption::factory()->create(['poll_id' => $poll->id, 'option_number' => 2, 'option' => 'Second']);
    Vote::factory()->create(['poll_id' => $poll->id, 'poll_option_id' => $first->id, 'role_id' => $resident->id]);

    $this->actingAs($resident->user)->postJson(route('building.voteResult'), ['poll_id' => $poll->id])
        ->assertOk()->assertExactJson([['option' => 'First', 'voteCount' => 1], ['option' => 'Second', 'voteCount' => 0]]);
});

test('voting without selecting an option stores no vote', function () {
    $building = new BuildingContext;

    $this->actingAs($building->resident()->user)->post(route('building.vote'), [])->assertSessionHas('status', 'error');

    $this->assertDatabaseCount('votes', 0);
});
