<?php

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
})->with(['public/chats', 'public/notificationFiles', 'public/incidentalCostFiles']);
