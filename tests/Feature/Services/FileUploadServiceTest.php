<?php

use App\Models\Announcement;
use App\Models\IncidentalCost;
use App\Models\Message;
use App\Services\FileUploadService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('uploads retain hashed names and use the requested directory', function (string $directory) {
    Storage::fake('local');
    $service = app(FileUploadService::class);
    $file = UploadedFile::fake()->image('original.png');
    $first = $service->store($file, $directory);
    $second = $service->store($file, $directory);

    expect($first)->toMatch('/^[a-f0-9]{40}\.png$/')->not->toBe($second);
    Storage::disk('local')->assertExists([$directory.'/'.$first, $directory.'/'.$second]);
    expect(count(Storage::disk('local')->allFiles($directory)))->toBe(2);
})->with([Message::ATTACHMENT_DIRECTORY, Announcement::ATTACHMENT_DIRECTORY, IncidentalCost::ATTACHMENT_DIRECTORY]);
