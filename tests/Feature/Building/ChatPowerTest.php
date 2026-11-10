<?php

use App\Events\ChatMessagesDeleted;
use App\Events\DeleteChatMessage;
use App\Models\AuditEvent;
use App\Models\Message;
use App\Services\ChatSocialService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Tests\Support\BuildingContext;

beforeEach(function () {
    $this->building = new BuildingContext;
    $this->resident = $this->building->resident();
    $this->neighbor = $this->building->resident();
    $this->message = Message::factory()->for($this->building->group)->for($this->resident->user)->create(['text' => 'Original body']);
    $this->base = '/dashboard/building/'.$this->building->complex->id;
    $this->messageUrl = '/dashboard/building/group/'.$this->message->id;
    Event::fake([DeleteChatMessage::class, ChatMessagesDeleted::class]);
});

it('denies moderation to residents foreign managers and inactive managers', function (string $actor) {
    $role = match ($actor) {
        'resident' => $this->neighbor,
        'foreign manager' => (new BuildingContext)->managerRole,
        'inactive manager' => $this->building->managerRole,
    };
    if ($actor === 'inactive manager') {
        $role->update(['active' => false]);
    }
    $this->actingAs($role->user)->deleteJson($this->messageUrl, ['reason' => 'Review'])
        ->assertForbidden();
    expect($this->message->fresh()->deleted)->toBeFalse();
    $this->assertDatabaseCount('audit_events', 0);
})->with(['resident', 'foreign manager', 'inactive manager']);

it('requires a bounded moderation reason and records the actor reason time and scoped audit', function () {
    $this->freezeSecond();
    $this->actingAs($this->building->manager)->deleteJson($this->messageUrl)->assertUnprocessable()
        ->assertJsonValidationErrors('reason');
    $this->deleteJson($this->messageUrl, ['reason' => str_repeat('x', 241)])->assertUnprocessable();
    $this->deleteJson($this->messageUrl, ['reason' => 'Off-topic content'])->assertOk()
        ->assertJsonPath('deletedMessage.deletion_kind', 'manager')
        ->assertJsonMissingPath('deletedMessage.reason');
    $message = $this->message->fresh();
    expect($message->deleted)->toBeTrue()
        ->and($message->deleted_by)->toBe($this->building->manager->id)
        ->and($message->deletion_reason)->toBe('Off-topic content')
        ->and($message->deleted_at->equalTo(now()))->toBeTrue()
        ->and($message->text)->toBe('Original body');
    $event = AuditEvent::where('event', 'chat.message_moderated')->sole();
    expect($event->actor_id)->toBe($this->building->manager->id)
        ->and($event->complex_id)->toBe($this->building->complex->id)
        ->and($event->subject_type)->toBe(Message::class)
        ->and($event->subject_id)->toBe($message->id)
        ->and($event->meta)->toBe(['reason' => 'Off-topic content']);
    $this->deleteJson($this->messageUrl, ['reason' => 'Retry reason'])->assertOk();
    expect($message->fresh()->deletion_reason)->toBe('Off-topic content');
    expect(AuditEvent::where('event', 'chat.message_moderated')->count())->toBe(1);
    Event::assertDispatchedTimes(DeleteChatMessage::class, 1);
    $this->getJson($this->base.'/group/'.$message->id)->assertJsonPath('messages.0.deletion_kind', 'manager')
        ->assertJsonPath('messages.0.text', null)->assertJsonMissingPath('messages.0.deletion_reason');
});

it('keeps author deletion distinct from moderation', function () {
    $this->actingAs($this->resident->user)->deleteJson($this->messageUrl)->assertOk();
    $message = $this->message->fresh();
    expect($message->deletion_kind)->toBe('author')->and($message->deleted_by)->toBe($this->resident->user_id)
        ->and($message->deletion_reason)->toBeNull()->and($message->deleted_at)->not->toBeNull();
    $this->assertDatabaseCount('audit_events', 0);
});

it('returns per-item bulk results and never touches a foreign group', function (string $actor) {
    $user = $actor === 'manager' ? $this->building->manager : $this->resident->user;
    $own = Message::factory()->for($this->building->group)->for($user)->create();
    $other = Message::factory()->for($this->building->group)->for($this->neighbor->user)->create();
    $foreign = Message::factory()->for((new BuildingContext)->group)->create();
    $this->actingAs($user)->postJson($this->base.'/chat-bulk-delete', [
        'message_ids' => [$own->id, $other->id, $foreign->id], 'reason' => 'Batch reason',
    ])->assertOk()->assertJsonCount(3, 'results')
        ->assertJsonPath('success_count', $actor === 'manager' ? 2 : 1)
        ->assertJsonPath('denied_count', $actor === 'manager' ? 1 : 2)
        ->assertJsonPath('results.2', ['message_id' => $foreign->id, 'status' => 'denied']);
    expect($own->fresh()->deleted)->toBeTrue()
        ->and($other->fresh()->deleted)->toBe($actor === 'manager')
        ->and($foreign->fresh()->deleted)->toBeFalse();
    Event::assertDispatched(ChatMessagesDeleted::class, function ($event) use ($actor) {
        return $event->broadcastOn()->name === 'private-group.'.$this->building->complex->id
            && count($event->broadcastWith()['messages']) === ($actor === 'manager' ? 2 : 1)
            && ! str_contains(json_encode($event->broadcastWith()), 'reason');
    });
})->with(['resident', 'manager']);

it('allows own bulk progress when another message lacks a moderation reason', function () {
    $own = Message::factory()->for($this->building->group)->for($this->building->manager)->create();
    $this->actingAs($this->building->manager)->postJson($this->base.'/chat-bulk-delete', [
        'message_ids' => [$own->id, $this->message->id],
    ])->assertOk()->assertJsonPath('success_count', 1)->assertJsonPath('denied_count', 1);
    expect($this->message->fresh()->deleted)->toBeFalse()->and($own->fresh()->deleted)->toBeTrue();
});

it('caps batches rejects duplicate IDs and makes retries report already deleted rows', function () {
    $this->actingAs($this->resident->user)->postJson($this->base.'/chat-bulk-delete', [
        'message_ids' => range(1, ChatSocialService::BATCH_LIMIT + 1),
    ])->assertUnprocessable();
    $this->postJson($this->base.'/chat-bulk-delete', ['message_ids' => [$this->message->id, $this->message->id]])->assertUnprocessable();
    $payload = ['message_ids' => [$this->message->id]];
    $this->postJson($this->base.'/chat-bulk-delete', $payload)->assertJsonPath('results.0.status', 'deleted');
    $this->postJson($this->base.'/chat-bulk-delete', $payload)->assertJsonPath('results.0.status', 'already_deleted')
        ->assertJsonPath('success_count', 1);
    Event::assertDispatchedTimes(ChatMessagesDeleted::class, 1);
});

it('accepts exactly the batch cap and broadcasts a single bounded event', function () {
    $messages = Message::factory()->count(ChatSocialService::BATCH_LIMIT)->for($this->building->group)->for($this->resident->user)->create();
    $this->actingAs($this->resident->user)->postJson($this->base.'/chat-bulk-delete', [
        'message_ids' => $messages->modelKeys(),
    ])->assertOk()->assertJsonCount(ChatSocialService::BATCH_LIMIT, 'results')
        ->assertJsonPath('success_count', ChatSocialService::BATCH_LIMIT)->assertJsonPath('denied_count', 0);
    expect(Message::whereIn('id', $messages->modelKeys())->where('deleted', true)->count())->toBe(ChatSocialService::BATCH_LIMIT);
    Event::assertDispatchedTimes(ChatMessagesDeleted::class, 1);
});

it('searches both stored text and query using the shared letter and digit variants', function (string $body, string $query) {
    $this->message->update(['text' => $body]);
    $this->actingAs($this->resident->user)->getJson($this->base.'/chat-search?'.http_build_query(['q' => $query]))
        ->assertOk()->assertJsonCount(1, 'results')->assertJsonPath('results.0.message_id', $this->message->id)
        ->assertJsonPath('results.0.user_name', $this->resident->user->name);
    expect($this->message->fresh()->text)->toBe($body);
})->with([
    ['علي', 'علی'], ['علی', 'علي'], ['كتاب', 'کتاب'], ['کتاب', 'كتاب'],
    ['واحد ١٢', '۱۲'], ['واحد ۱۲', '12'], ['واحد 12', '١٢'],
]);

it('pages search results excludes tombstones and searches current edits literally', function () {
    $this->message->update(['text' => 'old match', 'deleted' => true]);
    Message::factory()->for((new BuildingContext)->group)->create(['text' => 'match']);
    $messages = Message::factory()->count(22)->for($this->building->group)->create(['text' => 'match']);
    $this->actingAs($this->resident->user)->getJson($this->base.'/chat-search?q=match')
        ->assertJsonCount(20, 'results')->assertJsonPath('next_before_id', $messages[2]->id);
    $this->getJson($this->base.'/chat-search?q=match&before_id='.$messages[2]->id)
        ->assertJsonCount(2, 'results')->assertJsonPath('next_before_id', null);
    $this->getJson($this->base.'/group/'.$messages[0]->id)->assertOk()
        ->assertJsonPath('messages.1.message_id', $messages[0]->id);
    $edited = $messages->last();
    $edited->update(['user_id' => $this->resident->user_id]);
    $this->postJson('/dashboard/building/group/edit', ['messageId' => $edited->id, 'messageText' => 'literal 100%_new'])->assertSuccessful();
    $this->getJson($this->base.'/chat-search?q='.urlencode('100%_new'))->assertJsonCount(1, 'results')
        ->assertJsonPath('results.0.message_id', $edited->id);
    $this->getJson($this->base.'/chat-search?q=old')->assertJsonCount(0, 'results');
});

it('denies inactive and foreign members access to search and bulk', function (string $action, string $scope) {
    $role = $scope === 'foreign' ? (new BuildingContext)->managerRole : $this->building->managerRole;
    if ($scope === 'inactive') {
        $role->update(['active' => false]);
    }
    $this->actingAs($role->user);
    $response = $action === 'search'
        ? $this->getJson($this->base.'/chat-search?q=Original')
        : $this->postJson($this->base.'/chat-bulk-delete', ['message_ids' => [$this->message->id]]);
    $response->assertForbidden();
    expect($this->message->fresh()->deleted)->toBeFalse();
})->with(['search', 'bulk'])->with(['foreign', 'inactive']);

it('has no server route for typing', function () {
    $routes = collect(Route::getRoutes())->map(fn ($route) => $route->uri());
    expect($routes->filter(fn ($uri) => str_contains(strtolower($uri), 'typing')))->toBeEmpty();
});
