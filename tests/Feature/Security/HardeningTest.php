<?php

use App\Events\DeleteChatMessage;
use App\Events\EditChatMessage;
use App\Events\NewChatMessage;
use App\Models\Complex;
use App\Models\Message;
use App\Models\MonthlyCharge;
use App\Models\User;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Storage;
use Tests\Support\BuildingContext;

test('chat validates file type size and empty messages', function (string $kind) {
    Storage::fake('local');
    $building = new BuildingContext;
    $data = ['complexId' => $building->complex->id];
    if ($kind !== 'empty') {
        $data['messageImage'] = $kind === 'large' ? UploadedFile::fake()->image('large.png')->size(5121)
            : UploadedFile::fake()->create('payload.'.$kind, 1, 'application/octet-stream');
    }
    $this->actingAs($building->resident()->user)->postJson('/dashboard/building/group/send', $data)->assertUnprocessable();
    $this->assertDatabaseCount('messages', 0);
})->with(['large', 'php', 'exe', 'empty']);

test('a charge payment ignores a submitted amount', function () {
    $building = new BuildingContext;
    $resident = $building->resident();
    $charge = MonthlyCharge::factory()->create(['complex_id' => $building->complex->id]);
    $this->actingAs($resident->user)->post(route('building.buildingChargePayment'), [
        'complexId' => $building->complex->id, 'unitNumber' => $resident->unit->unit_number, 'monthlyChargeId' => $charge->id, 'Amount' => 1,
    ])->assertSessionHas('status', 'success');
    $this->assertDatabaseHas('charge_payments', ['monthly_charge_id' => $charge->id, 'role_id' => $resident->id, 'amount' => $charge->amount]);
});

test('regeneration invalidates the old join token without rotating other complexes', function () {
    $building = new BuildingContext;
    $other = Complex::factory()->create();
    $old = $building->complex->join_link;
    $otherToken = $other->join_link;
    $this->actingAs($building->manager)->post(route('building.regenerateJoinLink', $building->complex))->assertRedirect();
    $new = $building->complex->refresh()->join_link;
    expect(strlen($new))->toBe(32)->and($new)->not->toBe($old)->and($other->refresh()->join_link)->toBe($otherToken);
    $this->actingAs(User::factory()->create())->post(route('dashboard.store'), ['link' => $old])->assertSessionHas('status', 'error');
    $this->post(route('dashboard.store'), ['link' => $new])->assertSessionHas('status', 'success');
});

test('private channel and all event names are scoped to the message group', function () {
    $building = new BuildingContext;
    $resident = $building->resident();
    $other = new BuildingContext;
    $callback = Broadcast::getChannels()['group.{complexId}'];
    expect($callback($resident->user, $building->complex->id))->toBeTrue()
        ->and($callback($building->manager, $building->complex->id))->toBeTrue()
        ->and($callback(User::factory()->create(), $building->complex->id))->toBeFalse()
        ->and($callback($other->manager, $building->complex->id))->toBeFalse();
    $resident->update(['active' => false]);
    expect($callback($resident->user, $building->complex->id))->toBeFalse();
    $message = Message::factory()->create(['group_id' => $building->group->id]);
    foreach ([new NewChatMessage(['message_id' => $message->id]), new EditChatMessage($message), new DeleteChatMessage($message)] as $event) {
        expect($event->broadcastOn()[0])->toBeInstanceOf(PrivateChannel::class)
            ->and($event->broadcastOn()[0]->name)->toBe('private-group.'.$building->complex->id);
    }
});

test('a resident cannot edit another residents unit in the same complex', function () {
    $building = new BuildingContext;
    $resident = $building->resident();
    $other = $building->resident();
    $this->actingAs($resident->user)->post(route('dashboard.unit'), ['complexId' => $building->complex->id, 'currentUnitNumber' => $other->unit->unit_number, 'newUnitNumber' => 200001])->assertForbidden();
});

test('inactive roles confer no building access', function () {
    $building = new BuildingContext;
    $resident = $building->resident();
    $resident->update(['active' => false]);
    $this->actingAs($resident->user)->get(route('building', $building->complex))->assertForbidden();
});

test('remember tokens cannot be mass assigned', function () {
    expect((new User)->isFillable('remember_token'))->toBeFalse();
});
