<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Services\PrivateImage;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ChatAttachmentController extends Controller
{
    public function show(Message $message, PrivateImage $images): StreamedResponse
    {
        Gate::authorize('viewAttachment', $message->group->complex);

        abort_if($message->deleted, 404);

        return $images->response(Message::ATTACHMENT_DIRECTORY, $message->image_url);
    }
}
