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

    public function __construct($editedMessage)
    {
        $this->editedMessage = $editedMessage;
    }

    /**
     * @return list<PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('group.'.Message::findOrFail($this->editedMessage->id)->group->complex_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'EditChatMessage';
    }

    public function broadcastWith(): array
    {
        $presenter = app(MessagePresenter::class);
        $current = Message::findOrFail($this->editedMessage->id);

        return ['editedMessage' => $current->deleted
            ? $presenter->deletion($current)
            : $presenter->broadcastModel($this->editedMessage)];
    }
}
