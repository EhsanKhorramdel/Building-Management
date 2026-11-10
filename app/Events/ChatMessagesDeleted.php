<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class ChatMessagesDeleted implements ShouldBroadcast
{
    public function __construct(private int $complexId, private array $messages) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('group.'.$this->complexId);
    }

    public function broadcastAs(): string
    {
        return 'ChatMessagesDeleted';
    }

    public function broadcastWith(): array
    {
        return ['messages' => $this->messages];
    }
}
