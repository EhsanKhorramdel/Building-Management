<?php

use App\Events\NewChatMessage;
use App\Models\Message;
use App\Models\Poll;
use App\Models\PollOption;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Tests\Support\BuildingContext;

test('an expired vote returns the standard poll error redirect', function () {
    $building = new BuildingContext;
    $poll = Poll::factory()->expired()->create(['complex_id' => $building->complex->id]);
    $option = PollOption::factory()->create(['poll_id' => $poll->id]);

    $this->actingAs($building->manager)->from('/dashboard')->post(route('building.vote'), ['voteRadio' => $option->id])
        ->assertRedirect('/dashboard')->assertSessionHas('status', 'error')->assertSessionHas('page', 'poll')
        ->assertSessionHas('message', 'مهلت شرکت در این رای گیری به پایان رسیده است.');
    $this->assertDatabaseCount('votes', 0);
});

test('poll creation handles sparse option keys and preserves their numeric order', function () {
    $building = new BuildingContext;
    $this->actingAs($building->manager)->post(route('building.poll', $building->complex), [
        'question' => 'Which?', 'endMinutes' => 5, 'option4' => 'Later', 'option3' => '', 'option2' => 'Earlier',
    ])->assertSessionHas('status', 'success');

    expect(PollOption::orderBy('option_number')->get(['option_number', 'option'])->toArray())->toBe([
        ['option_number' => 1, 'option' => 'Earlier'], ['option_number' => 2, 'option' => 'Later'],
    ]);
});

test('empty chat anchor fetch returns empty messages and null cursors', function () {
    $building = new BuildingContext;
    $this->actingAs($building->manager)->getJson('/dashboard/building/'.$building->complex->id.'/group/0')
        ->assertOk()->assertExactJson(['messages' => [], 'first_message_id' => null, 'last_message_id' => null]);
});

test('profile omission preserves existing optional values and explicit empty values clear them', function () {
    $user = User::factory()->create(['national_code' => '1234567890', 'phone_number' => '09123456789']);
    $this->actingAs($user)->patch(route('profile.update'), ['name' => 'Updated', 'email' => $user->email])->assertSessionHasNoErrors();
    expect($user->refresh()->national_code)->toBe('1234567890')->and($user->phone_number)->toBe('09123456789');
    $this->patch(route('profile.update'), ['name' => 'Updated', 'email' => $user->email, 'ncode' => '', 'phoneNumber' => ''])->assertSessionHasNoErrors();
    expect($user->refresh()->national_code)->toBeNull()->and($user->phone_number)->toBeNull();
});

test('chat single zero text retains the existing no-write behavior', function () {
    Event::fake();
    $building = new BuildingContext;
    $this->actingAs($building->manager)->post('/dashboard/building/group/send', ['complexId' => $building->complex->id, 'messageText' => '0'])->assertSuccessful();
    $this->assertDatabaseCount('messages', 0);
});

test('message broadcasts keep the new-message payload fields', function () {
    Event::fake([NewChatMessage::class]);
    $building = new BuildingContext;
    $this->actingAs($building->manager)->post('/dashboard/building/group/send', ['complexId' => $building->complex->id, 'messageText' => 'Hello']);
    $message = Message::firstOrFail();
    Event::assertDispatched(NewChatMessage::class, fn ($event) => $event->message['message_id'] === $message->id
        && $event->message['is_owned_by_logged_in_user'] === false && $event->message['text'] === 'Hello');
});
