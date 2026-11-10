<?php

use App\Events\ChatPinChanged;
use App\Events\ChatReactionsChanged;
use App\Events\DeleteChatMessage;
use App\Events\EditChatMessage;
use App\Events\NewChatMessage;
use App\Models\AuditEvent;
use App\Models\Group;
use App\Models\Message;
use App\Models\MessageReaction;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Tests\Support\BuildingContext;

beforeEach(function () {
    $this->building = new BuildingContext;
    $this->resident = $this->building->resident();
    $this->message = Message::factory()->for($this->building->group)->for($this->resident->user)->create([
        'text' => '<b>Original message</b>',
    ]);
    $this->chat = '/dashboard/building/group';
    $this->pins = '/dashboard/building/'.$this->building->complex->id.'/chat-pins';
    Event::fake([ChatPinChanged::class, ChatReactionsChanged::class, DeleteChatMessage::class, EditChatMessage::class, NewChatMessage::class]);
});

it('protects social actions and tombstone resources from unauthorized viewers', function (string $action, string $scope) {
    $actor = $scope === 'foreign' ? (new BuildingContext)->managerRole : $this->building->managerRole;
    if ($scope === 'inactive') {
        $actor->update(['active' => false]);
    }
    if ($scope !== 'guest') {
        $this->actingAs($actor->user);
    }
    $message = $this->chat.'/'.$this->message->id;
    $response = match ($action) {
        'reaction' => $this->postJson($message.'/reactions', ['emoji' => '👍']),
        'reply' => $this->postJson($this->chat.'/send', [
            'complexId' => $this->building->complex->id, 'parent_id' => $this->message->id, 'messageText' => 'Reply',
        ]),
        'pins' => $this->getJson($this->pins),
        'pin' => $this->putJson($message.'/pin', ['pinned' => true]),
        'history' => $this->get('/dashboard/building/'.$this->building->complex->id.'/group/0'),
        'delete' => $this->delete($message),
        'attachment' => $this->get(route('chat.attachments.show', $this->message)),
    };
    if ($scope === 'guest' && in_array($action, ['history', 'delete', 'attachment'], true)) {
        $response->assertRedirect('/login');
    } elseif ($scope === 'guest') {
        $response->assertUnauthorized();
    } else {
        $response->assertForbidden();
    }
    $this->assertDatabaseCount('message_reactions', 0);
    expect($this->message->fresh()->pinned_at)->toBeNull();
})->with(['reaction', 'reply', 'pins', 'pin', 'history', 'delete', 'attachment'])->with(['guest', 'foreign', 'inactive']);

it('toggles each palette emoji independently and broadcasts the full named summary', function () {
    $url = $this->chat.'/'.$this->message->id.'/reactions';
    $this->actingAs($this->resident->user)->postJson($url, ['emoji' => '👍'])
        ->assertOk()->assertJsonPath('reactions.0.reacted', true);
    $this->postJson($url, ['emoji' => '😂'])->assertOk()->assertJsonCount(2, 'reactions');
    $manager = $this->building->manager;
    $this->actingAs($manager)->postJson($url, ['emoji' => '👍'])->assertOk()
        ->assertJsonPath('reactions.0.count', 2)
        ->assertJsonPath('reactions.0.reactors.0.name', $this->resident->user->name)
        ->assertJsonPath('reactions.1.reacted', false);
    $this->actingAs($this->resident->user)->postJson($url, ['emoji' => '👍'])->assertOk()
        ->assertJsonPath('reactions.0.emoji', '😂')
        ->assertJsonPath('reactions.1.reacted', false);
    $this->assertDatabaseCount('message_reactions', 2);
    $this->assertDatabaseMissing('message_reactions', ['user_id' => $this->resident->user_id, 'emoji' => '👍']);
    $this->postJson($url, ['emoji' => '👍'])->assertOk();
    $this->assertDatabaseCount('message_reactions', 3);
    Event::assertDispatchedTimes(ChatReactionsChanged::class, 5);
    Event::assertDispatched(ChatReactionsChanged::class, function (ChatReactionsChanged $event) use ($manager) {
        $payload = $event->broadcastWith();
        $thumbs = collect($payload['reactions'])->firstWhere('emoji', '👍');

        return $event->broadcastOn()->name === 'private-group.'.$this->building->complex->id
            && $payload['message_id'] === $this->message->id
            && $payload['version'] === 5
            && $thumbs['count'] === 2
            && collect($thumbs['reactors'])->pluck('id')->contains($manager->id);
    });
});

it('rejects duplicate reaction rows at the database and invalid palette values at the endpoint', function () {
    $this->actingAs($this->resident->user)->postJson($this->chat.'/'.$this->message->id.'/reactions', ['emoji' => 'custom'])
        ->assertUnprocessable()->assertJsonValidationErrors('emoji');
    $attributes = ['user_id' => $this->resident->user_id, 'emoji' => MessageReaction::PALETTE[0]];
    $this->message->reactions()->create($attributes);
    expect(fn () => $this->message->reactions()->create($attributes))->toThrow(QueryException::class);
});

it('rejects a parent from another group or a deleted parent', function (string $scope) {
    $parent = match ($scope) {
        'another complex' => Message::factory()->for((new BuildingContext)->group)->create(),
        'another group' => Message::factory()->for(Group::factory()->for($this->building->complex))->create(),
        'deleted' => $this->message,
    };
    if ($scope === 'deleted') {
        $parent->update(['deleted' => true]);
    }
    $this->actingAs($this->resident->user)->postJson($this->chat.'/send', [
        'complexId' => $this->building->complex->id, 'parent_id' => $parent->id, 'messageText' => 'Reply',
    ])->assertUnprocessable()->assertJsonValidationErrors('parent_id');
    Event::assertNotDispatched(NewChatMessage::class);
})->with(['another complex', 'another group', 'deleted']);

it('serves the same safe quote in history and delayed broadcasts', function () {
    $this->actingAs($this->building->manager)->postJson($this->chat.'/send', [
        'complexId' => $this->building->complex->id, 'parent_id' => $this->message->id, 'messageText' => 'Reply',
    ])->assertSuccessful();
    $reply = Message::where('parent_id', $this->message->id)->sole();
    $event = Event::dispatched(NewChatMessage::class)->sole()[0];
    $anchor = '/dashboard/building/'.$this->building->complex->id.'/group/'.$reply->id;
    $quote = $event->broadcastWith()['message']['quote'];
    expect($quote)->toBe([
        'message_id' => $this->message->id,
        'user_name' => $this->resident->user->name,
        'snippet' => '<b>Original message</b>',
    ]);
    $this->getJson($anchor)->assertJsonPath('messages.1.quote', $quote);
    $this->actingAs($this->resident->user)->deleteJson($this->chat.'/'.$this->message->id)->assertOk();
    $redacted = ['message_id' => $this->message->id, 'deleted' => true];
    expect($event->broadcastWith()['message']['quote'])->toBe($redacted);
    $this->getJson($anchor)->assertJsonPath('messages.1.quote', $redacted)
        ->assertJsonPath('messages.0.text', null);
});

it('lets managers set and clear multiple pins in newest-first order and audits each change', function () {
    $second = Message::factory()->for($this->building->group)->create();
    $firstUrl = $this->chat.'/'.$this->message->id.'/pin';
    $secondUrl = $this->chat.'/'.$second->id.'/pin';
    $this->actingAs($this->resident->user)->putJson($firstUrl, ['pinned' => true])->assertForbidden();
    $manager = $this->building->manager;
    $this->actingAs($manager)->putJson($firstUrl, ['pinned' => true])->assertOk()->assertJsonPath('pin.message_id', $this->message->id);
    $this->putJson($firstUrl, ['pinned' => true])->assertOk();
    $this->travel(1)->seconds();
    $this->putJson($secondUrl, ['pinned' => true])->assertOk();
    expect($second->fresh()->pinned_by)->toBe($manager->id);
    $this->actingAs($this->resident->user)->getJson($this->pins)
        ->assertJsonCount(2)->assertJsonPath('0.message_id', $second->id)->assertJsonPath('1.message_id', $this->message->id);
    $this->actingAs($manager)->putJson($secondUrl, ['pinned' => false])->assertOk()->assertJsonPath('pin', null);
    $this->getJson($this->pins)->assertJsonCount(1);
    $this->actingAs($this->resident->user)->deleteJson($this->chat.'/'.$this->message->id)->assertOk();
    $this->getJson($this->pins)->assertExactJson([]);
    expect($this->message->fresh()->pinned_at)->toBeNull();
    expect(AuditEvent::where('complex_id', $this->building->complex->id)->orderBy('id')->pluck('event')->all())
        ->toBe(['chat.message_pinned', 'chat.message_pinned', 'chat.message_unpinned', 'chat.message_unpinned']);
    $this->assertDatabaseHas('audit_events', ['subject_id' => $this->message->id, 'actor_id' => $manager->id, 'event' => 'chat.message_pinned']);
    Event::assertDispatched(ChatPinChanged::class, function (ChatPinChanged $event) {
        return $event->broadcastOn()->name === 'private-group.'.$this->building->complex->id
            && $event->broadcastWith()['pin'] === null;
    });
});

it('keeps a deleted reply in the cursor history with no attachment body or quote', function () {
    Storage::fake('local');
    $file = Storage::disk('local')->putFile(Message::ATTACHMENT_DIRECTORY, UploadedFile::fake()->image('social.png'));
    $reply = Message::factory()->for($this->building->group)->for($this->resident->user)->create([
        'parent_id' => $this->message->id,
        'image_url' => basename($file),
        'text' => 'Removed body',
    ]);
    $attachment = route('chat.attachments.show', $reply);
    $delayedEdit = new EditChatMessage($reply);
    $this->actingAs($this->resident->user)->get($attachment)->assertOk();
    $this->deleteJson($this->chat.'/'.$reply->id)->assertOk()->assertJsonPath('deletedMessage.deletion_kind', 'author');
    $this->get($attachment)->assertNotFound();
    $history = $this->getJson('/dashboard/building/getMessagesAfterFrom/'.$this->building->complex->id.'/'.$this->message->id);
    $history->assertJsonCount(1, 'messages')->assertJsonPath('messages.0.message_id', $reply->id)
        ->assertJsonPath('messages.0.is_deleted', true)->assertJsonPath('messages.0.text', null)
        ->assertJsonPath('messages.0.image_url', null)->assertJsonMissingPath('messages.0.quote');
    $this->postJson($this->chat.'/edit', ['messageId' => $reply->id, 'messageText' => 'Trying to restore it']);
    Event::assertNotDispatched(EditChatMessage::class);
    $this->postJson($this->chat.'/'.$reply->id.'/reactions', ['emoji' => '👍'])->assertNotFound();
    $this->actingAs($this->building->manager)->putJson($this->chat.'/'.$reply->id.'/pin', ['pinned' => true])->assertNotFound();
    $event = Event::dispatched(DeleteChatMessage::class)->sole()[0];
    expect($event->broadcastWith())->toBe(['deletedMessage' => ['id' => $reply->id, 'deletion_kind' => 'author']]);
    expect($delayedEdit->broadcastWith())->toBe(['editedMessage' => ['id' => $reply->id, 'deletion_kind' => 'author']]);
    expect((new ChatReactionsChanged($this->building->complex->id, $reply->id))->broadcastWith()['reactions'])->toBe([]);
});
