<?php

use App\Models\Poll;
use Carbon\Carbon;
use Tests\Support\BuildingContext;

it('creates a poll from a browser form and stores its end time', function (array $duration, int $minutes) {
    $building = new BuildingContext;
    $start = Carbon::parse('2026-11-10 12:00:00');

    $this->travelTo($start, function () use ($building, $duration, $minutes, $start) {
        $fields = $duration + ['question' => 'Which day?', 'option1' => 'Thursday', 'option2' => 'Friday'];
        $body = http_build_query($fields);
        parse_str($body, $form);

        $this->actingAs($building->manager)->call(
            'POST',
            route('building.poll', $building->complex),
            $form,
            server: ['CONTENT_TYPE' => 'application/x-www-form-urlencoded'],
            content: $body,
        )->assertRedirect()->assertSessionHasNoErrors()->assertSessionHas('status', 'success');

        $poll = Poll::sole();
        expect($poll->start_date->format('Y-m-d H:i:s'))->toBe($start->format('Y-m-d H:i:s'))
            ->and($poll->end_date->format('Y-m-d H:i:s'))->toBe($start->copy()->addMinutes($minutes)->format('Y-m-d H:i:s'));
    });
})->with([
    'hours only' => [['endHours' => '2'], 120],
    'all three parts' => [['endMinutes' => '17', 'endHours' => '2', 'endDays' => '1'], 1577],
    'days only' => [['endDays' => '3'], 4320],
]);
