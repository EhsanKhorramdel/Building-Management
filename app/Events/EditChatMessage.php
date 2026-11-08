<?php

namespace App\Events;

use App\Models\Message;
use App\Services\MessagePresenter;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EditChatMessage implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $editedMessage;

    /**
     * Create a new event instance.
     */
    public function __construct($editedMessage)
    {
        $this->editedMessage = $editedMessage;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('group.'.Message::findOrFail($this->editedMessage->id)->group->complex_id),
        ];
    }

    /**
     * Get the broadcast event name.
     */
    public function broadcastAs(): string
    {
        return 'EditChatMessage';
    }

    public function broadcastWith(): array
    {
        return ['editedMessage' => app(MessagePresenter::class)->broadcastModel($this->editedMessage)];
    }
}
