<?php

use App\Models\IncidentalCost;
use App\Models\Message;
use App\Models\MonthlyCharge;
use App\Models\Poll;
use App\Models\PollOption;
use Tests\Support\BuildingContext;

test('a submitted complex cannot disguise a foreign resource', function (string $resource) {
    $own = new BuildingContext;
    $foreign = new BuildingContext;
    $cost = IncidentalCost::factory()->create(['complex_id' => $foreign->complex->id]);
    $charge = MonthlyCharge::factory()->create(['complex_id' => $foreign->complex->id]);
    $poll = Poll::factory()->create(['complex_id' => $foreign->complex->id]);
    $option = PollOption::factory()->create(['poll_id' => $poll->id]);
    $message = Message::factory()->create(['group_id' => $foreign->group->id]);
    [$url, $payload] = match ($resource) {
        'cost' => [route('building.incidentalCostPayment'), ['incidentalCostId' => $cost->id]],
        'charge' => [route('building.buildingChargePayment'), ['monthlyChargeId' => $charge->id]],
        'vote' => [route('building.vote'), ['voteRadio' => $option->id]],
        'results' => [route('building.voteResult'), ['poll_id' => $poll->id]],
        'edit' => ['/dashboard/building/group/edit', ['messageId' => $message->id, 'messageText' => 'Changed']],
        'viewers' => ['/dashboard/building/getMessageViewers', ['message_id' => $message->id]],
    };
    $this->actingAs($own->manager)->post($url, $payload + ['complexId' => $own->complex->id])->assertForbidden();
})->with(['cost', 'charge', 'vote', 'results', 'edit', 'viewers']);

test('a manager can edit another members unit in their complex', function () {
    $building = new BuildingContext;
    $resident = $building->resident();
    $this->actingAs($building->manager)->post(route('dashboard.unit'), [
        'complexId' => $building->complex->id, 'currentUnitNumber' => $resident->unit->unit_number,
        'newUnitNumber' => 200001, 'floorNumber' => 2,
    ])->assertSessionHas('status', 'success');
    expect($resident->unit->refresh()->unit_number)->toBe(200001);
});
