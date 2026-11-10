<?php

use App\Models\Announcement;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Support\BuildingContext;

test('a manager can publish an announcement with an image', function () {
    Storage::fake('local');
    $building = new BuildingContext;

    $this->actingAs($building->manager)->post(route('building.NotificationRegistration', $building->complex), [
        'notifText' => 'Building meeting', 'notifPicture' => UploadedFile::fake()->image('notice.png'),
    ])->assertSessionHasNoErrors()->assertSessionHas('status', 'success');

    $announcement = Announcement::firstOrFail();
    expect($announcement->complex_id)->toBe($building->complex->id)
        ->and($announcement->manager_id)->toBe($building->manager->id)->and($announcement->text)->toBe('Building meeting');
    Storage::disk('local')->assertExists(Announcement::ATTACHMENT_DIRECTORY.'/'.$announcement->image_url)
        ->assertMissing('public/notificationFiles/'.$announcement->image_url);
    $this->get(route('building', $building->complex))->assertOk()
        ->assertSee('data-announcement-image="'.route('announcements.attachment', $announcement).'"', false)
        ->assertDontSee('/storage/notificationFiles/', false);
});

test('an empty announcement is rejected', function () {
    $building = new BuildingContext;

    $this->actingAs($building->manager)->post(route('building.NotificationRegistration', $building->complex), [])
        ->assertSessionHasErrorsIn('notifRegister', 'required_at_least_one');

    $this->assertDatabaseCount('announcements', 0);
});

test('a manager can archive an announcement', function () {
    $building = new BuildingContext;
    $announcement = Announcement::factory()->create(['complex_id' => $building->complex->id, 'manager_id' => $building->manager->id]);

    $this->actingAs($building->manager)->post(route('building.cancelNotification', $announcement))->assertSessionHas('status', 'success');

    expect((bool) $announcement->refresh()->archive)->toBeTrue();
});
