<?php

use App\Models\Poll;
use App\Models\PollOption;
use App\Models\Vote;
use App\Services\PollService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\Support\BuildingContext;

test('poll service filters and renumbers sparse options and retains the minimum option error', function () {
    $building = new BuildingContext;
    $polls = app(PollService::class);
    $data = ['question' => 'When?', 'endMinutes' => 30, 'endHours' => 0, 'endDays' => 0, 'option4' => 'Last', 'option2' => 'First', 'option3' => null];
    expect($polls->create($building->complex->id, $data)['status'])->toBe('success');
    expect(PollOption::orderBy('option_number')->get(['option_number', 'option'])->toArray())->toBe([
        ['option_number' => 1, 'option' => 'First'], ['option_number' => 2, 'option' => 'Last'],
    ]);
    $data['option4'] = null;
    expect($polls->create($building->complex->id, $data))->toBe(['status' => 'error', 'message' => 'کاربر محترم حداقل دو گزینه برای ایجاد رای گیری ضروری است.', 'page' => 'poll']);
    $this->assertDatabaseCount('polls', 1);
});

test('poll results include zero counts with one grouped aggregate query', function () {
    $building = new BuildingContext;
    $poll = Poll::factory()->create(['complex_id' => $building->complex->id]);
    $first = PollOption::factory()->create(['poll_id' => $poll->id, 'option' => 'First']);
    PollOption::factory()->create(['poll_id' => $poll->id, 'option' => 'Second']);
    foreach ([$building->managerRole, $building->resident()] as $role) {
        Vote::factory()->create(['poll_id' => $poll->id, 'poll_option_id' => $first->id, 'role_id' => $role->id]);
    }
    DB::enableQueryLog();
    DB::flushQueryLog();
    $results = app(PollService::class)->results($poll->id);
    $queries = DB::getQueryLog();
    DB::disableQueryLog();
    expect($results)->toBe([['option' => 'First', 'voteCount' => 2], ['option' => 'Second', 'voteCount' => 0]])
        ->and($queries)->toHaveCount(1)->and(strtolower($queries[0]['query']))->toContain('count(', 'group by');
});

test('voting and changing an open vote work but an expired change does not write', function () {
    $building = new BuildingContext;
    $poll = Poll::factory()->create(['complex_id' => $building->complex->id]);
    $first = PollOption::factory()->create(['poll_id' => $poll->id]);
    $second = PollOption::factory()->create(['poll_id' => $poll->id]);
    $service = app(PollService::class);
    expect($service->vote($first->id, $building->manager->id)['message'])->toBe('رای شما ثبت شد')
        ->and($service->vote($second->id, $building->manager->id)['message'])->toBe('رای شما بروزرسانی شد');
    Carbon::setTestNow($poll->end_date->addSecond());
    try {
        expect($service->vote($first->id, $building->manager->id)['status'])->toBe('error');
        expect(Vote::firstOrFail()->poll_option_id)->toBe($second->id);
    } finally {
        Carbon::setTestNow();
    }
});
