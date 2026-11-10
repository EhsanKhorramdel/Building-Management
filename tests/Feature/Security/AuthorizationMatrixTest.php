<?php

use App\Events\DeleteChatMessage;
use App\Events\EditChatMessage;
use App\Events\NewChatMessage;
use App\Models\Announcement;
use App\Models\IncidentalCost;
use App\Models\MembershipRequest;
use App\Models\Message;
use App\Models\MonthlyCharge;
use App\Models\Poll;
use App\Models\PollOption;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Support\Facades\Event;
use Tests\Support\BuildingContext;

dataset('secured endpoints', [
    'dashboard', 'upgrade', 'downgrade', 'announcement', 'archive', 'cost', 'charge', 'regenerate',
    'poll', 'vote', 'results', 'costPayment', 'chargePayment', 'payments',
    'chatIndex', 'lastSeen', 'before', 'after', 'viewers', 'send', 'edit', 'seen', 'destroy',
    'unitUpdate', 'addUnit', 'adminIndex', 'adminAccept', 'adminReject',
]);

test('endpoint authorization is scoped to its complex and role', function (string $endpoint, string $actor) {
    $building = new BuildingContext;
    $resident = $building->resident();
    $other = new BuildingContext;
    $foreignResident = $other->resident();
    $admin = User::factory()->create();
    UserRole::factory()->admin()->create(['user_id' => $admin->id]);
    $user = match ($actor) {
        'resident' => $resident->user, 'manager' => $building->manager,
        'foreignManager' => $other->manager, 'foreignResident' => $foreignResident->user,
        'admin' => $admin, default => null,
    };
    if ($user) {
        $this->actingAs($user);
    }
    Event::fake([NewChatMessage::class, EditChatMessage::class, DeleteChatMessage::class]);
    $message = Message::factory()->create(['group_id' => $building->group->id, 'user_id' => $user?->id ?? $resident->user_id]);
    $announcement = Announcement::factory()->create(['complex_id' => $building->complex->id, 'manager_id' => $building->manager->id]);
    $cost = IncidentalCost::factory()->create(['complex_id' => $building->complex->id]);
    $charge = MonthlyCharge::factory()->create(['complex_id' => $building->complex->id]);
    $poll = Poll::factory()->create(['complex_id' => $building->complex->id]);
    $option = PollOption::factory()->create(['poll_id' => $poll->id]);
    $request = MembershipRequest::factory()->create();
    $id = $building->complex->id;
    $unit = $actor === 'manager' ? $building->managerRole->unit : $resident->unit;
    [$method, $uri, $payload] = match ($endpoint) {
        'dashboard' => ['GET', "/dashboard/building/$id", []],
        'upgrade' => ['POST', route('building.upgradeRole', $resident), []],
        'downgrade' => ['POST', route('building.downgradeRole', $id), []],
        'announcement' => ['POST', route('building.NotificationRegistration', $id), ['notifText' => 'Notice']],
        'archive' => ['POST', route('building.cancelNotification', $announcement), []],
        'cost' => ['POST', route('building.incidentalCost', $id), ['title' => 'Repair', 'total_amount' => 100, 'share_amount' => 10]],
        'charge' => ['POST', route('building.buildingCharge', $id), ['chargeAmount' => 10, 'yearStart' => 1405, 'monthStart' => 1, 'dayStart' => 1, 'yearEnd' => 1405, 'monthEnd' => 2, 'dayEnd' => 1]],
        'regenerate' => ['POST', route('building.regenerateJoinLink', $id), []],
        'poll' => ['POST', route('building.poll', $id), ['question' => 'When?', 'endMinutes' => 30, 'endHours' => 0, 'endDays' => 0, 'option1' => 'First', 'option2' => 'Second']],
        'vote' => ['POST', route('building.vote'), ['voteRadio' => $option->id]],
        'results' => ['POST', route('building.voteResult'), ['poll_id' => $poll->id]],
        'costPayment' => ['POST', route('building.incidentalCostPayment'), ['complexId' => $id, 'unitNumber' => $unit->unit_number, 'incidentalCostId' => $cost->id]],
        'chargePayment' => ['POST', route('building.buildingChargePayment'), ['complexId' => $id, 'unitNumber' => $unit->unit_number, 'monthlyChargeId' => $charge->id]],
        'payments' => ['POST', route('building.payments'), ['complex_id' => $id]],
        'chatIndex' => ['GET', "/dashboard/building/$id/group/$message->id", []],
        'lastSeen' => ['GET', "/dashboard/building/$id/lastMessageSeenId", []],
        'before' => ['GET', "/dashboard/building/getMessagesBeforeFrom/$id/$message->id", []],
        'after' => ['GET', "/dashboard/building/getMessagesAfterFrom/$id/$message->id", []],
        'viewers' => ['POST', '/dashboard/building/getMessageViewers', ['message_id' => $message->id]],
        'send' => ['POST', '/dashboard/building/group/send', ['complexId' => $id, 'messageText' => 'Hello']],
        'edit' => ['POST', '/dashboard/building/group/edit', ['messageId' => $message->id, 'messageText' => 'Edited']],
        'seen' => ['POST', '/dashboard/building/group/seen', ['messageId' => $message->id]],
        'destroy' => ['DELETE', "/dashboard/building/group/$message->id", []],
        'unitUpdate' => ['POST', route('dashboard.unit'), ['complexId' => $id, 'currentUnitNumber' => $unit->unit_number, 'newUnitNumber' => 200001, 'floorNumber' => 1]],
        'addUnit' => ['POST', route('dashboard.addUnit'), ['complexId' => $id, 'unitNumber' => 200002, 'floorNumber' => 1]],
        'adminIndex' => ['GET', '/adminPanel', []],
        'adminAccept' => ['POST', route('adminPanel.accept', $request), []],
        'adminReject' => ['POST', route('adminPanel.reject', $request), []],
    };
    $response = $this->call($method, $uri, $payload);
    if ($actor === 'guest') {
        $response->assertRedirect('/login');
    } else {
        $adminEndpoint = str_starts_with($endpoint, 'admin');
        $managerEndpoint = in_array($endpoint, ['upgrade', 'downgrade', 'announcement', 'archive', 'cost', 'charge', 'regenerate'], true);
        $allowed = $adminEndpoint ? $actor === 'admin' : ($actor === 'manager' || ($actor === 'resident' && ! $managerEndpoint));
        if ($allowed) {
            expect($response->status())->toBeLessThan(400);
            $response->assertSessionHasNoErrors();
        } else {
            $response->assertForbidden();
        }
    }
})->with('secured endpoints')->with(['guest', 'resident', 'manager', 'foreignManager', 'foreignResident', 'admin']);
