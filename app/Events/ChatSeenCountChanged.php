<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class ChatSeenCountChanged implements ShouldBroadcast
{
    public function __construct(private int $complexId, private int $messageId, private int $count) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('group.'.$this->complexId);
    }

    public function broadcastAs(): string
    {
        return 'ChatSeenCountChanged';
    }

    public function broadcastWith(): array
    {
        return ['message_id' => $this->messageId, 'count' => $this->count];
    }
}
