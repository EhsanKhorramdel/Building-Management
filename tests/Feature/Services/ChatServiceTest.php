<?php

use App\Models\Message;
use App\Models\SeenMessage;
use App\Services\ChatService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Support\BuildingContext;

test('cursor pages retain ascending order ownership and unchanged boundary cursors', function () {
    $building = new BuildingContext;
    $messages = Message::factory()->count(25)->create(['group_id' => $building->group->id, 'user_id' => $building->manager->id]);
    $deleted = $messages[10];
    $deleted->update(['deleted' => true]);
    $other = new BuildingContext;
    Message::factory()->create(['group_id' => $other->group->id]);
    $chat = app(ChatService::class);
    $before = $chat->fetch($building->group, 'before', $messages[12]->id, 3, $building->manager->id);
    $after = $chat->fetch($building->group, 'after', $messages[12]->id, 3, $building->manager->id);
    expect(array_column($before['messages'], 'message_id'))->toBe([$messages[8]->id, $messages[9]->id, $messages[11]->id])
        ->and(array_column($after['messages'], 'message_id'))->toBe([$messages[13]->id, $messages[14]->id, $messages[15]->id])
        ->and($before['first_message_id'])->toBe($messages[8]->id)
        ->and($after['last_message_id'])->toBe($messages[15]->id)
        ->and($before['messages'][0]['is_owned_by_logged_in_user'])->toBeTrue();
    expect($chat->fetch($building->group, 'before', $messages->first()->id, 10, $building->manager->id))
        ->toBe(['messages' => [], 'first_message_id' => $messages->first()->id]);
    expect($chat->fetch($building->group, 'after', $messages->last()->id, 10, $building->manager->id))
        ->toBe(['messages' => [], 'last_message_id' => $messages->last()->id]);
    $around = $chat->fetch($building->group, 'around', $messages[12]->id, 2, $building->manager->id);
    expect(array_column($around['messages'], 'message_id'))->toBe([$messages[9]->id, $messages[11]->id, $messages[12]->id, $messages[13]->id, $messages[14]->id]);
});

test('empty chat and absent read receipts return null without losing the initial message page', function () {
    $building = new BuildingContext;
    $chat = app(ChatService::class);
    expect($chat->fetch($building->group, 'around', 0, 10, $building->manager->id))->toBe(['messages' => [], 'first_message_id' => null, 'last_message_id' => null])
        ->and($chat->lastSeen($building->group, $building->manager->id))->toBeNull();
    $message = Message::factory()->create(['group_id' => $building->group->id]);
    expect($chat->lastSeen($building->group, $building->manager->id))->toBeNull()
        ->and($chat->fetch($building->group, 'around', 0, 10, $building->manager->id)['messages'][0]['message_id'])->toBe($message->id);
});

test('last seen uses one ordered limited query and ignores deleted messages', function () {
    $building = new BuildingContext;
    $messages = Message::factory()->count(3)->create(['group_id' => $building->group->id]);
    foreach ($messages as $message) {
        SeenMessage::factory()->create(['group_id' => $building->group->id, 'message_id' => $message->id, 'user_id' => $building->manager->id]);
    }
    $messages[2]->update(['deleted' => true]);
    DB::enableQueryLog();
    DB::flushQueryLog();
    $lastSeen = app(ChatService::class)->lastSeen($building->group, $building->manager->id);
    $queries = DB::getQueryLog();
    DB::disableQueryLog();
    expect($lastSeen)->toBe($messages[1]->id)->and($queries)->toHaveCount(1)
        ->and(strtolower($queries[0]['query']))->toContain('order by', 'limit 1');
});

test('unknown chat resources fail explicitly instead of dereferencing null', function () {
    $building = new BuildingContext;
    $message = Message::factory()->create(['group_id' => $building->group->id]);
    $chat = app(ChatService::class);
    expect(fn () => $chat->viewers(999999))->toThrow(ModelNotFoundException::class)
        ->and(fn () => $chat->seen(999999, $building->manager->id))->toThrow(ModelNotFoundException::class)
        ->and(fn () => $chat->fetch($building->group, 'around', $message->id + 1, 10, $building->manager->id))->toThrow(HttpException::class);
});
