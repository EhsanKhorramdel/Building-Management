<?php

namespace App\Events;

use App\Models\Message;
use App\Services\MessagePresenter;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DeleteChatMessage implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $deletedMessage;

    /**
     * Create a new event instance.
     */
    public function __construct($deletedMessage)
    {
        $this->deletedMessage = $deletedMessage;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('group.'.Message::findOrFail($this->deletedMessage->id)->group->complex_id),
        ];
    }

    /**
     * Get the broadcast event name.
     */
    public function broadcastAs(): string
    {
        return 'DeleteChatMessage';
    }

    public function broadcastWith(): array
    {
        return ['deletedMessage' => app(MessagePresenter::class)->broadcastModel($this->deletedMessage)];
    }
}
