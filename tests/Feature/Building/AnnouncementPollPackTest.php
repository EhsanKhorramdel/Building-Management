<?php

use App\Models\Announcement;
use App\Models\AuditEvent;
use App\Models\Poll;
use App\Models\PollOption;
use App\Models\UserRole;
use App\Models\Vote;
use App\Services\AnnouncementService;
use Tests\Support\BuildingContext;

beforeEach(function () {
    $this->building = new BuildingContext;
    $this->resident = $this->building->resident();
    $this->announcement = Announcement::factory()->for($this->building->complex)->create(['manager_id' => $this->building->manager->id, 'archive' => false]);
    $this->poll = Poll::factory()->for($this->building->complex)->create(['end_date' => now()->addDay()]);
    $this->option = PollOption::factory()->for($this->poll)->create(['option_number' => 1]);
    PollOption::factory()->for($this->poll)->create(['option_number' => 2]);
});

it('denies residents foreign managers and inactive managers the management actions', function (string $action, string $scope) {
    $role = match ($scope) {
        'resident' => $this->resident,
        'foreign' => (new BuildingContext)->managerRole,
        'inactive' => $this->building->managerRole,
    };
    if ($scope === 'inactive') {
        $role->update(['active' => false]);
    }
    $this->actingAs($role->user);
    $route = fn ($name) => route($name, [$this->building->complex, $this->poll]);
    (match ($action) {
        'pin' => $this->post(route('announcements.pin', $this->announcement), ['pinned' => 1]),
        'restore' => $this->post(route('announcements.restore', $this->announcement)),
        'search' => $this->getJson(route('announcements.search', $this->building->complex).'?state=all'),
        'readers' => $this->getJson(route('announcements.readers', $this->announcement)),
        'close' => $this->post($route('polls.close')),
        'update' => $this->patch($route('polls.update'), ['question' => 'New', 'end_date' => now()->addDays(2)->toDateTimeString()]),
        'ballots' => $this->getJson($route('polls.ballots')),
    })->assertForbidden();
})->with(['pin', 'restore', 'search', 'readers', 'close', 'update', 'ballots'])->with(['resident', 'foreign', 'inactive']);

it('pins several announcements in pin order and records scoped changes only once', function () {
    $second = Announcement::factory()->for($this->building->complex)->create();
    $this->actingAs($this->building->manager);
    $this->freezeSecond();
    $this->post(route('announcements.pin', $second), ['pinned' => '1'])->assertSessionHas('status', 'success');
    $at = $second->fresh()->pinned_at;
    $this->travelTo(now()->addMicrosecond());
    $this->post(route('announcements.pin', $this->announcement), ['pinned' => 1]);
    $this->post(route('announcements.pin', $second), ['pinned' => 1]);
    expect($second->fresh()->pinned_at->equalTo($at))->toBeTrue();
    expect(app(AnnouncementService::class)->listing($this->building->complex, $this->building->manager->id)->pluck('id')->all())
        ->toBe([$second->id, $this->announcement->id]);
    $this->assertDatabaseCount('audit_events', 2);
    $event = AuditEvent::where('subject_id', $second->id)->sole();
    expect($event->actor_id)->toBe($this->building->manager->id)->and($event->complex_id)->toBe($this->building->complex->id)
        ->and($event->subject_type)->toBe(Announcement::class)->and($event->event)->toBe('announcement.pinned');
    $this->post(route('announcements.pin', $second), ['pinned' => 0]);
    expect($second->fresh()->pinned_at)->toBeNull()->and($second->fresh()->pinned_by)->toBeNull();
    expect(AuditEvent::where('event', 'announcement.unpinned')->count())->toBe(1);
});

it('archives and restores without leaking archived text to the member panel or losing a manual pin', function () {
    $this->announcement->update(['text' => 'Private archive token']);
    $this->actingAs($this->building->manager)->post(route('announcements.pin', $this->announcement), ['pinned' => 1]);
    $this->post(route('building.cancelNotification', $this->announcement));
    $this->actingAs($this->resident->user)->get('/dashboard/building/'.$this->building->complex->id)
        ->assertOk()->assertDontSee('Private archive token')->assertDontSee('data-announcement-search', false);
    $this->post(route('announcements.read', $this->announcement))->assertNotFound();
    $this->actingAs($this->building->manager)->getJson(route('announcements.search', $this->building->complex).'?state=archived')
        ->assertOk()->assertSee('Private archive token');
    $this->post(route('announcements.restore', $this->announcement))->assertSessionHas('status', 'success');
    expect($this->announcement->fresh()->archive)->toBeFalse()->and($this->announcement->fresh()->pinned_at)->not->toBeNull();
    $this->actingAs($this->resident->user)->get('/dashboard/building/'.$this->building->complex->id)->assertSee('Private archive token');
});

it('normalizes search on both sides with active archived and empty filters', function (string $body, string $query) {
    $this->announcement->update(['text' => $body]);
    $archived = Announcement::factory()->for($this->building->complex)->create(['text' => $body.' archive', 'archive' => true]);
    $foreign = Announcement::factory()->create(['text' => 'Foreign unique token']);
    $this->actingAs($this->building->manager);
    $url = route('announcements.search', $this->building->complex);
    $active = $this->getJson($url.'?'.http_build_query(['state' => 'active', 'q' => $query]))->assertOk()->json('html');
    expect($active)->toContain('data-announcement-id="'.$this->announcement->id.'"')->not->toContain('data-announcement-id="'.$archived->id.'"', 'Foreign unique token');
    $archive = $this->getJson($url.'?'.http_build_query(['state' => 'archived', 'q' => $query]))->assertOk()->json('html');
    expect($archive)->toContain('data-announcement-id="'.$archived->id.'"')->not->toContain('data-announcement-id="'.$this->announcement->id.'"');
    $this->getJson($url.'?'.http_build_query(['state' => 'all', 'q' => 'no-match-unique']))->assertSee('empty-state');
})->with([['علي', 'علی'], ['علی', 'علي'], ['كتاب', 'کتاب'], ['کتاب', 'كتاب'], ['۱۲۳', '١٢٣'], ['١٢٣', '123']]);

it('marks reading once and exposes names only to local managers', function () {
    $this->actingAs($this->resident->user)->get('/dashboard/building/'.$this->building->complex->id)->assertSee('data-announcement-unread', false);
    $url = route('announcements.read', $this->announcement);
    $this->postJson($url)->assertExactJson(['read' => true]);
    $this->postJson($url)->assertExactJson(['read' => true]);
    $this->assertDatabaseCount('announcement_reads', 1);
    $this->getJson(route('announcements.readers', $this->announcement))->assertForbidden();
    $this->actingAs($this->building->manager)->getJson(route('announcements.readers', $this->announcement))
        ->assertOk()->assertJsonPath('readers.0.name', $this->resident->user->name)->assertJsonCount(1, 'readers');
});

it('denies foreign and inactive readers and rejects guest actions', function () {
    $url = route('announcements.read', $this->announcement);
    $this->post($url)->assertRedirect('/login');
    $this->actingAs((new BuildingContext)->manager)->postJson($url)->assertForbidden();
    $this->resident->update(['active' => false]);
    $this->actingAs($this->resident->user)->postJson($url)->assertForbidden();
    $this->assertDatabaseCount('announcement_reads', 0);
});

it('closes irreversibly records actor and time and blocks form and direct votes', function () {
    $this->freezeSecond();
    $url = route('polls.close', [$this->building->complex, $this->poll]);
    $this->actingAs($this->building->manager)->post($url)->assertSessionHas('status', 'success');
    $poll = $this->poll->fresh();
    expect($poll->closed_by)->toBe($this->building->manager->id)->and($poll->closed_at->equalTo(now()))->toBeTrue()->and($poll->isExpired())->toBeTrue();
    $this->post($url)->assertSessionHas('status', 'error');
    $this->patch(route('polls.update', [$this->building->complex, $poll]), ['question' => 'Still closed', 'end_date' => now()->addDays(2)->toDateTimeString()])->assertSessionHas('status', 'success');
    expect($poll->fresh()->isExpired())->toBeTrue();
    $this->actingAs($this->resident->user)->post(route('building.vote'), ['voteRadio' => (string) $this->option->id])->assertSessionHas('status', 'error');
    $this->postJson(route('building.vote'), ['voteRadio' => $this->option->id])->assertSessionHas('status', 'error');
    $this->assertDatabaseCount('votes', 0);
    expect($poll->fresh()->closed_at->equalTo($poll->closed_at))->toBeTrue();
});

it('keeps named ballots hidden while open and exposes them only after closure to managers', function (string $closure) {
    Vote::create(['poll_id' => $this->poll->id, 'poll_option_id' => $this->option->id, 'role_id' => $this->resident->id]);
    $url = route('polls.ballots', [$this->building->complex, $this->poll]);
    $this->actingAs($this->building->manager)->getJson($url)->assertForbidden();
    $this->postJson(route('building.voteResult'), ['poll_id' => $this->poll->id])->assertOk()
        ->assertJsonMissingPath('0.voters')->assertJsonMissing(['name' => $this->resident->user->name]);
    if ($closure === 'manual') {
        $this->post(route('polls.close', [$this->building->complex, $this->poll]));
    } else {
        $this->poll->update(['end_date' => now()->subSecond()]);
    }
    $this->getJson($url)->assertOk()->assertJsonPath('options.0.voters.0', $this->resident->user->name)->assertJsonPath('options.1.voters', []);
    $this->actingAs($this->resident->user)->getJson($url)->assertForbidden();
})->with(['manual', 'deadline']);

it('edits only text and deadline before votes and then locks without changing options', function () {
    $url = route('polls.update', [$this->building->complex, $this->poll]);
    $data = ['title' => 'Edited', 'question' => 'New question', 'end_date' => now()->addDays(2)->toDateTimeString(), 'option1' => 'Tampered'];
    $oldOption = $this->option->option;
    $this->actingAs($this->building->manager)->patch($url, $data)->assertSessionHas('status', 'success');
    expect($this->poll->fresh()->title)->toBe('Edited')->and($this->poll->fresh()->question)->toBe('New question')->and($this->option->fresh()->option)->toBe($oldOption);
    $this->actingAs($this->resident->user)->post(route('building.vote'), ['voteRadio' => $this->option->id])->assertSessionHas('status', 'success');
    $this->get('/dashboard/building/'.$this->building->complex->id)->assertSee('data-current-choice="'.$this->option->id.'"', false);
    $this->actingAs($this->building->manager)->patch($url, array_replace($data, ['question' => 'Locked']))->assertSessionHas('status', 'error');
    expect($this->poll->fresh()->question)->toBe('New question');
});

it('rejects a foreign poll behind an otherwise local route and validates edit dates', function () {
    $foreign = Poll::factory()->create();
    $this->actingAs($this->building->manager)->post(route('polls.close', [$this->building->complex, $foreign]))->assertForbidden();
    $this->getJson(route('polls.ballots', [$this->building->complex, $foreign]))->assertForbidden();
    $this->patchJson(route('polls.update', [$this->building->complex, $this->poll]), ['question' => 'New', 'end_date' => 'wrong'])
        ->assertUnprocessable()->assertJsonValidationErrors('end_date');
});

it('lists a person once per option and never imports a foreign role or mismatched poll into named ballots', function () {
    $extra = UserRole::factory()->create(['complex_id' => $this->building->complex->id, 'user_id' => $this->resident->user_id, 'unit_id' => null]);
    foreach ([$this->resident, $extra, (new BuildingContext)->managerRole] as $role) {
        Vote::create(['poll_id' => $this->poll->id, 'poll_option_id' => $this->option->id, 'role_id' => $role->id]);
    }
    $foreignPoll = Poll::factory()->create();
    Vote::create(['poll_id' => $foreignPoll->id, 'poll_option_id' => $this->option->id, 'role_id' => $this->building->managerRole->id]);
    $this->poll->update(['end_date' => now()->subSecond()]);
    $this->actingAs($this->building->manager)->getJson(route('polls.ballots', [$this->building->complex, $this->poll]))
        ->assertOk()->assertJsonPath('options.0.voters', [$this->resident->user->name]);
});
