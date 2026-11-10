<?php

use App\Models\Announcement;
use App\Models\MembershipRequest;
use App\Models\Message;
use Tests\Support\BuildingContext;

test('mutations reject GET and require a CSRF token', function (string $action) {
    $building = new BuildingContext;
    $resident = $building->resident();
    $message = Message::factory()->create(['group_id' => $building->group->id, 'user_id' => $building->manager->id]);
    $announcement = Announcement::factory()->create(['complex_id' => $building->complex->id]);
    $request = MembershipRequest::factory()->create();
    [$method, $url] = match ($action) {
        'delete' => ['DELETE', '/dashboard/building/group/'.$message->id],
        'upgrade' => ['POST', route('building.upgradeRole', $resident)],
        'downgrade' => ['POST', route('building.downgradeRole', $building->complex)],
        'archive' => ['POST', route('building.cancelNotification', $announcement)],
        'accept' => ['POST', route('adminPanel.accept', $request)],
        'reject' => ['POST', route('adminPanel.reject', $request)],
        'logout' => ['POST', '/logout'],
        'regenerate' => ['POST', route('building.regenerateJoinLink', $building->complex)],
    };
    $this->actingAs($building->manager)->get($url)->assertStatus(405);
    $this->app['env'] = 'local';
    try {
        $this->call($method, $url)->assertStatus(419);
    } finally {
        $this->app['env'] = 'testing';
    }
})->with(['delete', 'upgrade', 'downgrade', 'archive', 'accept', 'reject', 'logout', 'regenerate']);
