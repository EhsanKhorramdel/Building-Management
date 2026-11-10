<?php

use App\Events\DeleteChatMessage;
use App\Events\EditChatMessage;
use App\Models\Message;
use App\Services\MessagePresenter;

test('presenter preserves the HTTP and new-message shape and viewer ownership', function () {
    $message = Message::factory()->create(['text' => 'Hello', 'image_url' => 'image.png', 'edited' => true]);
    $presenter = app(MessagePresenter::class);
    expect($presenter->present($message, $message->user_id))->toBe([
        'message_id' => $message->id, 'user_name' => $message->user->name, 'user_id' => $message->user_id,
        'text' => 'Hello', 'image_url' => route('chat.attachments.show', $message, absolute: false), 'created_at' => $message->created_at->format('Y-m-d H:i:s'),
        'is_deleted' => false, 'is_edited' => true, 'is_owned_by_logged_in_user' => true,
    ])->and($presenter->present($message)['is_owned_by_logged_in_user'])->toBeFalse();
});

test('edit preserves its payload and deletion sends only its ID and tombstone kind', function () {
    $message = Message::factory()->create(['image_url' => 'image.png']);
    $attributes = $message->attributesToArray();
    $attributes['image_url'] = route('chat.attachments.show', $message, absolute: false);

    expect((new EditChatMessage($message))->broadcastWith())->toBe(['editedMessage' => $attributes])
        ->and((new DeleteChatMessage($message))->broadcastWith())->toBe([
            'deletedMessage' => ['id' => $message->id, 'deletion_kind' => 'author'],
        ]);
});
