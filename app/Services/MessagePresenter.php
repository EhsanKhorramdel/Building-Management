<?php

namespace App\Services;

use App\Models\Message;

class MessagePresenter
{
    public function present(Message $message, ?int $viewerId = null): array
    {
        return [
            'message_id' => $message->id,
            'user_name' => $message->user->name,
            'user_id' => $message->user_id,
            'text' => $message->text,
            'image_url' => $message->image_url,
            'created_at' => $message->created_at->format('Y-m-d H:i:s'),
            'is_deleted' => $message->deleted,
            'is_edited' => $message->edited,
            'is_owned_by_logged_in_user' => $message->user_id == $viewerId,
        ];
    }

    public function broadcastModel(Message $message): array
    {
        // Edit/delete clients consume the original model-shaped payload, including id.
        return $message->attributesToArray();
    }
}
