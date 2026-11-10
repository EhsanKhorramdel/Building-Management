<?php

use App\Models\Announcement;
use App\Models\IncidentalCost;
use Illuminate\Support\Facades\Storage;

it('moves both kinds of legacy building images and can be rerun', function () {
    $disk = Storage::fake('local');
    $disk->put('public/notificationFiles/notice.png', 'announcement');
    $disk->put('public/incidentalCostFiles/invoice.png', 'invoice');
    $disk->put('public/incidentalCostFiles/duplicate.png', 'identical invoice');
    $disk->put(IncidentalCost::ATTACHMENT_DIRECTORY.'/duplicate.png', 'identical invoice');
    $disk->put('public/default.png', 'placeholder');
    $disk->put('public/chats/keep.png', 'chat');

    $this->artisan('attachments:move-building-images')
        ->expectsOutput('Announcements — Found: 1. Moved: 1. Duplicate copies removed: 0. Conflicts: 0. Failed: 0.')
        ->expectsOutput('Incidental costs — Found: 2. Moved: 1. Duplicate copies removed: 1. Conflicts: 0. Failed: 0.')
        ->assertSuccessful();

    expect($disk->get(Announcement::ATTACHMENT_DIRECTORY.'/notice.png'))->toBe('announcement')
        ->and($disk->get(IncidentalCost::ATTACHMENT_DIRECTORY.'/invoice.png'))->toBe('invoice');
    $disk->assertMissing(['public/notificationFiles/notice.png', 'public/incidentalCostFiles/invoice.png', 'public/incidentalCostFiles/duplicate.png'])
        ->assertExists(['public/default.png', 'public/chats/keep.png', IncidentalCost::ATTACHMENT_DIRECTORY.'/duplicate.png']);

    $this->artisan('attachments:move-building-images')
        ->expectsOutput('Announcements — Found: 0. Moved: 0. Duplicate copies removed: 0. Conflicts: 0. Failed: 0.')
        ->expectsOutput('Incidental costs — Found: 0. Moved: 0. Duplicate copies removed: 0. Conflicts: 0. Failed: 0.')
        ->assertSuccessful();
});

it('preserves conflicting copies and still processes the other image type', function () {
    $disk = Storage::fake('local');
    $disk->put('public/notificationFiles/notice.png', 'legacy notice');
    $disk->put(Announcement::ATTACHMENT_DIRECTORY.'/notice.png', 'different private notice');
    $disk->put('public/incidentalCostFiles/invoice.png', 'invoice');

    $this->artisan('attachments:move-building-images')
        ->expectsOutput('Conflicting attachment: public/notificationFiles/notice.png')
        ->expectsOutput('Announcements — Found: 1. Moved: 0. Duplicate copies removed: 0. Conflicts: 1. Failed: 0.')
        ->expectsOutput('Incidental costs — Found: 1. Moved: 1. Duplicate copies removed: 0. Conflicts: 0. Failed: 0.')
        ->assertFailed();

    expect($disk->get('public/notificationFiles/notice.png'))->toBe('legacy notice')
        ->and($disk->get(Announcement::ATTACHMENT_DIRECTORY.'/notice.png'))->toBe('different private notice');
    $disk->assertMissing('public/incidentalCostFiles/invoice.png')->assertExists(IncidentalCost::ATTACHMENT_DIRECTORY.'/invoice.png');
});
