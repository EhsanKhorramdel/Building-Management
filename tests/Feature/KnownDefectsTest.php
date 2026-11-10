<?php

use App\Models\IncidentalCost;
use App\Models\Message;
use App\Models\Poll;
use App\Models\PollOption;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Support\BuildingContext;

test('chat history rejects a member of another complex', function () {
    $building = new BuildingContext;
    $other = new BuildingContext;
    $message = Message::factory()->create(['group_id' => $building->group->id]);

    $this->actingAs($other->resident()->user)->getJson('/dashboard/building/'.$building->complex->id.'/group/'.$message->id)->assertForbidden();
});

test('a manager cannot publish an announcement in another complex', function () {
    $first = new BuildingContext;
    $other = new BuildingContext;

    $this->actingAs($first->manager)->post(route('building.NotificationRegistration', $other->complex), ['notifText' => 'Unrelated building'])->assertForbidden();
});

test('chat rejects a non-image upload', function () {
    Storage::fake('local');
    $building = new BuildingContext;

    $this->actingAs($building->resident()->user)->postJson('/dashboard/building/group/send', [
        'complexId' => $building->complex->id, 'messageImage' => UploadedFile::fake()->create('upload.php', 1, 'application/x-httpd-php'),
    ])->assertUnprocessable();
});

test('incidental cost payments use the recorded share instead of a submitted amount', function () {
    $building = new BuildingContext;
    $resident = $building->resident();
    $cost = IncidentalCost::factory()->create(['complex_id' => $building->complex->id]);

    $this->actingAs($resident->user)->post(route('building.incidentalCostPayment'), [
        'complexId' => $building->complex->id, 'unitNumber' => $resident->unit->unit_number, 'incidentalCostId' => $cost->id, 'Amount' => 1,
    ]);

    $this->assertDatabaseHas('payment_additional_costs', ['role_id' => $resident->id, 'incidental_costs_id' => $cost->id, 'amount' => $cost->share_amount]);
});

test('expired polls reject new votes', function () {
    $building = new BuildingContext;
    $poll = Poll::factory()->expired()->create(['complex_id' => $building->complex->id]);
    $option = PollOption::factory()->create(['poll_id' => $poll->id]);

    $this->actingAs($building->resident()->user)->post(route('building.vote'), ['voteRadio' => $option->id]);

    $this->assertDatabaseCount('votes', 0);
});

test('a blank middle poll option does not discard later options', function () {
    $building = new BuildingContext;

    $this->actingAs($building->manager)->post(route('building.poll', $building->complex->id), [
        'question' => 'Which day?', 'endMinutes' => 30, 'endHours' => 0, 'endDays' => 0,
        'option1' => 'Thursday', 'option2' => null, 'option3' => 'Friday',
    ])->assertSessionHasNoErrors();

    expect(PollOption::orderBy('option_number')->pluck('option')->all())->toBe(['Thursday', 'Friday']);
});

test('an empty chat returns no last-seen message without an error', function () {
    $building = new BuildingContext;

    $this->actingAs($building->resident()->user)->getJson('/dashboard/building/'.$building->complex->id.'/lastMessageSeenId')
        ->assertOk()->assertJsonPath('last_message_seen_id', null);
});

test('GET cannot delete a chat message', function () {
    $building = new BuildingContext;
    $resident = $building->resident();
    $message = Message::factory()->create(['group_id' => $building->group->id, 'user_id' => $resident->user_id]);

    $this->actingAs($resident->user)->get('/dashboard/building/group/'.$message->id)->assertStatus(405);
});

test('profile updates may omit optional national code and phone fields', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->from('/dashboard')->patch(route('profile.update'), ['name' => 'Resident', 'email' => $user->email])
        ->assertRedirect('/dashboard')->assertSessionHasNoErrors();
});
