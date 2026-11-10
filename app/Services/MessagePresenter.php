<?php

namespace App\Services;

use App\Models\Message;

class MessagePresenter
{
    /**
     * @return array{message_id: int, user_name: string, user_id: int, text: string|null, image_url: string|null, created_at: string, is_deleted: bool|null, is_edited: bool|null, is_owned_by_logged_in_user: bool}
     */
    public function present(Message $message, ?int $viewerId = null): array
    {
        return [
            'message_id' => $message->id,
            'user_name' => $message->user->name,
            'user_id' => $message->user_id,
            'text' => $message->text,
            'image_url' => $this->attachmentUrl($message),
            'created_at' => $message->created_at->format('Y-m-d H:i:s'),
            'is_deleted' => $message->deleted,
            'is_edited' => $message->edited,
            'is_owned_by_logged_in_user' => $message->user_id == $viewerId,
        ];
    }

    public function broadcastModel(Message $message): array
    {
        // Edit/delete clients consume the original model-shaped payload, including id.
        return array_replace($message->attributesToArray(), ['image_url' => $this->attachmentUrl($message)]);
    }

    private function attachmentUrl(Message $message): ?string
    {
        return $message->image_url ? route('chat.attachments.show', $message, absolute: false) : null;
    }
}
