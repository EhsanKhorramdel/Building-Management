<?php

use App\Events\DeleteChatMessage;
use App\Events\EditChatMessage;
use App\Events\NewChatMessage;
use App\Models\Message;
use App\Models\SeenMessage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Tests\Support\BuildingContext;

beforeEach(function () {
    $this->building = new BuildingContext;
    $this->resident = $this->building->resident();
    Event::fake([NewChatMessage::class, EditChatMessage::class, DeleteChatMessage::class]);
});

test('a member can send a text message', function () {
    $this->actingAs($this->resident->user)->post('/dashboard/building/group/send', [
        'complexId' => $this->building->complex->id, 'messageText' => 'Hello building',
    ])->assertSuccessful();

    $this->assertDatabaseHas('messages', ['group_id' => $this->building->group->id, 'user_id' => $this->resident->user_id, 'text' => 'Hello building']);
    Event::assertDispatched(NewChatMessage::class);
});

test('a member can send an image message', function () {
    Storage::fake('local');

    $this->actingAs($this->resident->user)->post('/dashboard/building/group/send', [
        'complexId' => $this->building->complex->id, 'messageImage' => UploadedFile::fake()->image('chat.png'),
    ])->assertSuccessful();

    $message = Message::firstOrFail();
    expect($message->text)->toBeNull();
    Storage::disk('local')->assertExists(Message::ATTACHMENT_DIRECTORY.'/'.$message->image_url);
});

test('a sender can edit their own message', function () {
    $message = Message::factory()->create(['group_id' => $this->building->group->id, 'user_id' => $this->resident->user_id, 'text' => 'Original']);

    $this->actingAs($this->resident->user)->post('/dashboard/building/group/edit', ['messageId' => $message->id, 'messageText' => 'Edited'])->assertSuccessful();

    expect($message->refresh()->text)->toBe('Edited')->and($message->edited)->toBeTrue();
    Event::assertDispatched(EditChatMessage::class);
});

test('another member cannot edit a senders message', function () {
    $message = Message::factory()->create(['group_id' => $this->building->group->id, 'user_id' => $this->resident->user_id, 'text' => 'Original']);

    $this->actingAs($this->building->manager)->post('/dashboard/building/group/edit', ['messageId' => $message->id, 'messageText' => 'Changed']);

    expect($message->refresh()->text)->toBe('Original')->and($message->edited)->toBeFalse();
    Event::assertNotDispatched(EditChatMessage::class);
});

test('a sender can mark their own message deleted', function () {
    $message = Message::factory()->create(['group_id' => $this->building->group->id, 'user_id' => $this->resident->user_id]);

    $this->actingAs($this->resident->user)->delete('/dashboard/building/group/'.$message->id);

    expect($message->refresh()->deleted)->toBeTrue();
    Event::assertDispatched(DeleteChatMessage::class);
});

test('another member cannot delete a senders message', function () {
    $message = Message::factory()->create(['group_id' => $this->building->group->id, 'user_id' => $this->resident->user_id]);

    $this->actingAs($this->building->manager)->delete('/dashboard/building/group/'.$message->id);

    expect($message->refresh()->deleted)->toBeFalse();
    Event::assertNotDispatched(DeleteChatMessage::class);
});

test('chat fetches the anchor and bounded messages before and after it', function () {
    $messages = Message::factory()->count(25)->create(['group_id' => $this->building->group->id, 'user_id' => $this->resident->user_id]);
    Message::factory()->create(['group_id' => $this->building->group->id, 'user_id' => $this->resident->user_id, 'deleted' => true]);
    $complexId = $this->building->complex->id;
    $anchorId = $messages[12]->id;

    $this->actingAs($this->resident->user)->getJson("/dashboard/building/$complexId/group/$anchorId")
        ->assertOk()->assertJsonCount(21, 'messages')
        ->assertJsonPath('first_message_id', $messages[2]->id)->assertJsonPath('last_message_id', $messages[22]->id);

    $this->getJson("/dashboard/building/getMessagesBeforeFrom/$complexId/$anchorId")
        ->assertOk()->assertJsonCount(10, 'messages')->assertJsonPath('first_message_id', $messages[2]->id);
    $this->getJson("/dashboard/building/getMessagesAfterFrom/$complexId/$anchorId")
        ->assertOk()->assertJsonCount(10, 'messages')->assertJsonPath('last_message_id', $messages[22]->id);
});

test('marking a message seen twice keeps a single receipt and excludes its sender from viewers', function () {
    $message = Message::factory()->create(['group_id' => $this->building->group->id, 'user_id' => $this->building->manager->id]);

    $this->actingAs($this->resident->user)->post('/dashboard/building/group/seen', ['messageId' => $message->id])->assertSuccessful();
    $this->post('/dashboard/building/group/seen', ['messageId' => $message->id])->assertSuccessful();
    $this->assertDatabaseCount('seen_messages', 1);
    SeenMessage::factory()->create(['message_id' => $message->id, 'group_id' => $this->building->group->id, 'user_id' => $this->building->manager->id]);

    $this->postJson('/dashboard/building/getMessageViewers', ['message_id' => $message->id])
        ->assertForbidden();
    $this->getJson('/dashboard/building/'.$this->building->complex->id.'/lastMessageSeenId')
        ->assertOk()->assertJsonPath('last_message_seen_id', $message->id);
});

test('only the author and a manager of the message complex can see viewer names', function (string $audience) {
    $message = Message::factory()->create([
        'group_id' => $this->building->group->id,
        'user_id' => $this->resident->user_id,
    ]);
    $neighbor = $this->building->resident();
    foreach ([$this->resident, $neighbor] as $role) {
        SeenMessage::factory()->create([
            'message_id' => $message->id,
            'group_id' => $message->group_id,
            'user_id' => $role->user_id,
        ]);
    }
    $user = match ($audience) {
        'author' => $this->resident->user,
        'manager' => $this->building->manager,
        'neighbor' => $neighbor->user,
        'foreign manager' => (new BuildingContext)->manager,
    };

    $response = $this->actingAs($user)->postJson('/dashboard/building/getMessageViewers', ['message_id' => $message->id]);

    if ($audience === 'author' || $audience === 'manager') {
        $response->assertOk()->assertJsonCount(1)->assertJsonPath('0.user_name', $neighbor->user->name);
    } else {
        $response->assertForbidden();
    }
})->with(['author', 'manager', 'neighbor', 'foreign manager']);

test('last seen is null when no receipt exists', function () {
    $message = Message::factory()->create(['group_id' => $this->building->group->id, 'user_id' => $this->building->manager->id]);
    Message::factory()->create(['group_id' => $this->building->group->id, 'user_id' => $this->building->manager->id, 'deleted' => true]);

    $this->actingAs($this->resident->user)->getJson('/dashboard/building/'.$this->building->complex->id.'/lastMessageSeenId')
        ->assertOk()->assertJsonPath('last_message_seen_id', null);
});
