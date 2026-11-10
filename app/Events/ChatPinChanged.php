<?php

namespace App\Events;

use App\Models\Message;
use App\Services\MessagePresenter;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class ChatPinChanged implements ShouldBroadcast
{
    public function __construct(private int $complexId, private int $messageId) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('group.'.$this->complexId);
    }

    public function broadcastAs(): string
    {
        return 'ChatPinChanged';
    }

    public function broadcastWith(): array
    {
        $message = Message::findOrFail($this->messageId);

        return [
            'message_id' => $message->id,
            'version' => $message->social_version,
            'pin' => app(MessagePresenter::class)->pin($message),
        ];
    }
}
