<?php

use App\Events\ChatSeenCountChanged;
use App\Models\Message;
use App\Services\ChatService;
use Illuminate\Support\Facades\Event;
use Tests\Support\BuildingContext;

beforeEach(function () {
    $this->building = new BuildingContext;
    $this->resident = $this->building->resident();
    $this->neighbor = $this->building->resident();
    $this->base = '/dashboard/building/'.$this->building->complex->id;
    Event::fake([ChatSeenCountChanged::class]);
});

it('pages every local image in message order in both directions across boundaries', function () {
    $images = Message::factory()->count(23)->for($this->building->group)->create(['image_url' => 'photo.png']);
    Message::factory()->for($this->building->group)->create(['image_url' => null]);
    $deleted = Message::factory()->for($this->building->group)->create(['image_url' => 'gone.png', 'deleted' => true]);
    $foreign = Message::factory()->for((new BuildingContext)->group)->create(['image_url' => 'foreign.png']);
    $this->actingAs($this->resident->user);
    foreach (['after', 'before'] as $direction) {
        $cursor = null;
        $ids = [];
        do {
            $page = $this->getJson($this->base.'/chat-media?'.http_build_query(array_filter(['direction' => $direction, 'cursor' => $cursor])))
                ->assertOk()->json();
            foreach ($page['images'] as $image) {
                expect(array_keys($image))->toBe(['id', 'url', 'alt']);
                expect($image['url'])->toBe(route('chat.attachments.show', $image['id'], absolute: false));
                $ids[] = $image['id'];
            }
            $cursor = $page['next_cursor'];
        } while ($cursor !== null);
        $expected = $images->pluck('id')->all();
        expect($ids)->toBe($direction === 'after' ? $expected : array_reverse($expected))
            ->not->toContain($deleted->id, $foreign->id);
    }
});

it('rejects unauthenticated media and receipt requests', function (string $endpoint) {
    $this->get($this->base.'/'.$endpoint)->assertRedirect('/login');
})->with(['chat-media?direction=after', 'chat-seen-counts?message_ids[]=1']);

it('rejects foreign and inactive members on media and receipt endpoints', function (string $endpoint, string $scope) {
    $role = $scope === 'foreign' ? (new BuildingContext)->managerRole : $this->resident;
    if ($scope === 'inactive') {
        $role->update(['active' => false]);
    }
    $this->actingAs($role->user)->getJson($this->base.'/'.$endpoint)->assertForbidden();
})->with(['chat-media?direction=after', 'chat-seen-counts?message_ids[]=1'])->with(['foreign', 'inactive']);

it('validates media cursors and receipt batch limits', function () {
    $this->actingAs($this->resident->user)->getJson($this->base.'/chat-media?direction=wrong&cursor=-1')
        ->assertUnprocessable()->assertJsonValidationErrors(['direction', 'cursor']);
    $this->getJson($this->base.'/chat-seen-counts?'.http_build_query(['message_ids' => range(1, 51)]))
        ->assertUnprocessable()->assertJsonValidationErrors('message_ids');
});

it('counts the same viewers as the detail endpoint and excludes the author', function () {
    $message = Message::factory()->for($this->building->group)->for($this->resident->user)->create();
    $chat = app(ChatService::class);
    $chat->seen($message->id, $this->resident->user_id);
    $chat->seen($message->id, $this->neighbor->user_id);
    $chat->seen($message->id, $this->building->manager->id);
    $this->actingAs($this->resident->user)->getJson($this->base.'/chat-seen-counts?message_ids[]='.$message->id)
        ->assertOk()->assertExactJson(['counts' => [['message_id' => $message->id, 'count' => 2]]]);
    $this->postJson('/dashboard/building/getMessageViewers', ['message_id' => $message->id])->assertOk()->assertJsonCount(2);
    expect($chat->viewers($message->id))->toHaveCount(2);
    $this->actingAs($this->building->manager)->getJson($this->base.'/chat-seen-counts?message_ids[]='.$message->id)
        ->assertOk()->assertJsonPath('counts.0.count', 2);
    $this->actingAs($this->neighbor->user)->getJson($this->base.'/chat-seen-counts?message_ids[]='.$message->id)
        ->assertOk()->assertExactJson(['counts' => []]);
});

it('omits foreign and deleted receipt counts even for local managers', function () {
    $local = Message::factory()->for($this->building->group)->create();
    $deleted = Message::factory()->for($this->building->group)->create(['deleted' => true]);
    $foreign = Message::factory()->for((new BuildingContext)->group)->create();
    $this->actingAs($this->building->manager)->getJson($this->base.'/chat-seen-counts?'.http_build_query(['message_ids' => [$local->id, $deleted->id, $foreign->id]]))
        ->assertOk()->assertExactJson(['counts' => [['message_id' => $local->id, 'count' => 0]]]);
});

it('broadcasts only aggregate keys once per new non-author receipt on the private complex channel', function () {
    $message = Message::factory()->for($this->building->group)->for($this->resident->user)->create();
    $this->actingAs($this->resident->user)->postJson('/dashboard/building/group/seen', ['messageId' => $message->id])->assertSuccessful();
    Event::assertNotDispatched(ChatSeenCountChanged::class);
    $this->actingAs($this->neighbor->user)->postJson('/dashboard/building/group/seen', ['messageId' => $message->id])->assertSuccessful();
    $this->postJson('/dashboard/building/group/seen', ['messageId' => $message->id])->assertSuccessful();
    Event::assertDispatchedTimes(ChatSeenCountChanged::class, 1);
    Event::assertDispatched(ChatSeenCountChanged::class, fn ($event) => $event->broadcastWith() === ['message_id' => $message->id, 'count' => 1]
        && $event->broadcastOn()->name === 'private-group.'.$this->building->complex->id);
});
