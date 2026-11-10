<?php

use App\Models\Message;
use App\Models\UserRole;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Support\BuildingContext;

beforeEach(function () {
    Storage::fake('local');
    $this->building = new BuildingContext;
    $this->image = UploadedFile::fake()->image('attachment.png')->get();
    $this->message = Message::factory()->for($this->building->group)->create(['image_url' => 'attachment.png']);
    $this->path = Message::ATTACHMENT_DIRECTORY.'/attachment.png';
    Storage::disk('local')->put($this->path, $this->image);
    $this->url = route('chat.attachments.show', $this->message);
});

it('rejects an anonymous attachment request without streaming the image', function () {
    $this->get($this->url)->assertRedirect(route('login'))->assertHeaderMissing('Content-Disposition');
});

it('rejects a foreign-complex user without streaming the attachment', function () {
    $foreign = UserRole::factory()->manager()->create()->user;
    $this->actingAs($foreign)->get($this->url)->assertForbidden()->assertHeaderMissing('Content-Disposition');
});

it('streams the private PNG to an authorized viewer', function (string $role) {
    $viewer = $role === 'member' ? $this->building->manager : UserRole::factory()->admin()->create()->user;

    $response = $this->actingAs($viewer)->get($this->url)->assertOk()
        ->assertHeader('Content-Type', 'image/png')->assertHeader('X-Content-Type-Options', 'nosniff');
    expect($response->streamedContent())->toBe($this->image);
})->with(['member', 'global admin']);

it('does not serve a deleted message or a legacy non-image file', function (bool $deleted) {
    $this->message->update(['deleted' => $deleted]);
    if (! $deleted) {
        Storage::disk('local')->put($this->path, '<!doctype html><p>Not an image</p>');
    }

    $this->actingAs($this->building->manager)->get($this->url)->assertNotFound()->assertHeaderMissing('Content-Disposition');
})->with(['deleted message' => [true], 'non-image attachment' => [false]]);

it('moves legacy chat files by name and reports a clean repeat run', function () {
    $disk = Storage::disk('local');
    $disk->put('public/chats/legacy.png', $this->image);
    $disk->put('public/chats/attachment.png', $this->image);
    $disk->put('public/notificationFiles/keep.png', $this->image);
    $this->artisan('chat:move-attachments')
        ->expectsOutput('Found: 2. Moved: 1. Duplicate copies removed: 1. Conflicts: 0. Failed: 0.')->assertSuccessful();
    $disk->assertExists([Message::ATTACHMENT_DIRECTORY.'/legacy.png', $this->path, 'public/notificationFiles/keep.png']);
    $disk->assertMissing(['public/chats/legacy.png', 'public/chats/attachment.png']);

    $this->artisan('chat:move-attachments')
        ->expectsOutput('Found: 0. Moved: 0. Duplicate copies removed: 0. Conflicts: 0. Failed: 0.')->assertSuccessful();
});

it('reports a conflicting filename without overwriting either copy', function () {
    $disk = Storage::disk('local');
    $disk->put('public/chats/attachment.png', 'different bytes');

    $this->artisan('chat:move-attachments')
        ->expectsOutput('Found: 1. Moved: 0. Duplicate copies removed: 0. Conflicts: 1. Failed: 0.')->assertFailed();

    expect($disk->get($this->path))->toBe($this->image)
        ->and($disk->get('public/chats/attachment.png'))->toBe('different bytes');
});
