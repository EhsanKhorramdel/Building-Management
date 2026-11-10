<?php

namespace App\Http\Controllers;

use App\Models\Message;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ChatAttachmentController extends Controller
{
    public function show(Message $message): StreamedResponse
    {
        Gate::authorize('viewChatAttachment', $message->group->complex);

        $name = $message->image_url;
        abort_if($message->deleted || ! $name || str_contains($name, '\\') || basename($name) !== $name, 404);

        $disk = Storage::disk('local');
        $path = Message::ATTACHMENT_DIRECTORY.'/'.$name;
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
