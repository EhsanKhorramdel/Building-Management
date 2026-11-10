<?php

use App\Models\Announcement;
use App\Models\IncidentalCost;
use App\Models\UserRole;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\Support\BuildingContext;

dataset('building attachments', ['announcement', 'incidental cost']);

function buildingAttachment(BuildingContext $building, string $type): array
{
    if ($type === 'announcement') {
        $record = Announcement::factory()->for($building->complex)->create(['image_url' => 'notice.png']);
        $path = Announcement::ATTACHMENT_DIRECTORY.'/notice.png';
        $route = route('announcements.attachment', $record);
        $file = UploadedFile::fake()->image('notice.jpg');
        $mime = 'image/jpeg';
    } else {
        $record = IncidentalCost::factory()->for($building->complex)->create(['cost_invoice' => 'invoice.png']);
        $path = IncidentalCost::ATTACHMENT_DIRECTORY.'/invoice.png';
        $route = route('incidental-costs.attachment', $record);
        $file = UploadedFile::fake()->image('invoice.png');
        $mime = 'image/png';
    }

    $bytes = $file->get();
    Storage::disk('local')->put($path, $bytes);

    return compact('path', 'route', 'bytes', 'mime');
}

beforeEach(function () {
    Storage::fake('local');
    $this->building = new BuildingContext;
});

it('rejects a guest without serving image bytes', function (string $type) {
    $attachment = buildingAttachment($this->building, $type);
    $response = $this->get($attachment['route'])->assertRedirect(route('login'))->assertHeaderMissing('Content-Disposition');
    expect($response->baseResponse)->not->toBeInstanceOf(StreamedResponse::class)
        ->and($response->getContent())->not->toContain($attachment['bytes']);
})->with('building attachments');

it('rejects a foreign resident or an inactive local member without serving image bytes', function (string $type, bool $foreign) {
    $attachment = buildingAttachment($this->building, $type);
    $role = $foreign ? UserRole::factory()->create() : $this->building->resident();
    if (! $foreign) {
        $role->update(['active' => false]);
    }
    $response = $this->actingAs($role->user)->get($attachment['route'])->assertForbidden()->assertHeaderMissing('Content-Disposition');
    expect($response->baseResponse)->not->toBeInstanceOf(StreamedResponse::class)
        ->and($response->getContent())->not->toContain($attachment['bytes']);
})->with('building attachments')->with(['foreign resident' => [true], 'inactive local member' => [false]]);

it('serves the private image to the authorized viewer with its detected type', function (string $type, string $viewer) {
    $attachment = buildingAttachment($this->building, $type);
    $user = match ($viewer) {
        'resident' => $this->building->resident()->user,
        'manager' => $this->building->manager,
        'global admin' => UserRole::factory()->admin()->create()->user,
    };
    $response = $this->actingAs($user)->get($attachment['route'])->assertOk()
        ->assertHeader('Content-Type', $attachment['mime'])
        ->assertHeader('X-Content-Type-Options', 'nosniff');
    expect($response->headers->get('Cache-Control'))->toContain('private')->toContain('no-store')
        ->and($response->streamedContent())->toBe($attachment['bytes']);
})->with('building attachments')->with(['resident', 'manager', 'global admin']);

it('does not serve a missing or non-image attachment', function (string $type, bool $missing) {
    $attachment = buildingAttachment($this->building, $type);
    if ($missing) {
        Storage::disk('local')->delete($attachment['path']);
    } else {
        Storage::disk('local')->put($attachment['path'], '<!doctype html><p>Not an image</p>');
    }

    $this->actingAs($this->building->manager)->get($attachment['route'])->assertNotFound()->assertHeaderMissing('Content-Disposition');
})->with('building attachments')->with(['missing file' => [true], 'non-image file' => [false]]);
