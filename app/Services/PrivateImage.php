<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PrivateImage
{
    public function response(string $directory, ?string $name): StreamedResponse
    {
        abort_if(! $name || str_contains($name, '\\') || basename($name) !== $name, 404);

        $disk = Storage::disk('local');
        $path = $directory.'/'.$name;
        abort_unless($disk->fileExists($path), 404);

        $mime = $disk->mimeType($path);
        abort_unless(in_array($mime, ['image/gif', 'image/jpeg', 'image/png', 'image/webp'], true), 404);

        return $disk->response($path, $name, [
            'Content-Type' => $mime,
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
