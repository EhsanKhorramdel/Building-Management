<?php

namespace App\Services;

use App\Models\Group;
use App\Models\Message;
use App\Models\SeenMessage;

class ChatService
{
    public function __construct(private MessagePresenter $presenter, private FileUploadService $uploads) {}

    /**
     * A zero anchor starts at the latest visible message. One-sided pages exclude the
     * anchor and keep it as the cursor when no further messages exist.
     *
     * @return array{messages: list<array{message_id: int, user_name: string, user_id: int, text: string|null, image_url: string|null, created_at: string, is_deleted: bool|null, is_edited: bool|null, is_owned_by_logged_in_user: bool}>, first_message_id?: int|null, last_message_id?: int|null}
     */
    public function fetch(Group $group, string $direction, int $cursorId, int $limit, int $viewerId): array
    {
        if ($direction === 'around') {
            $query = $group->messages()->where('deleted', false)->with('user');
            $current = $cursorId === 0 ? $query->orderByDesc('id')->first() : $query->find($cursorId);
            if (! $current) {
                abort_if($group->messages()->where('deleted', false)->exists(), 404);

                return ['messages' => [], 'first_message_id' => null, 'last_message_id' => null];
            }
            $before = $this->fetch($group, 'before', $current->id, $limit, $viewerId);
            $after = $this->fetch($group, 'after', $current->id, $limit, $viewerId);

            return [
                'messages' => array_merge($before['messages'], [$this->presenter->present($current, $viewerId)], $after['messages']),
                'first_message_id' => $before['first_message_id'],
                'last_message_id' => $after['last_message_id'],
            ];
        }
        $before = $direction === 'before';
        $messages = $group->messages()->where('deleted', false)->with('user')
            ->where('id', $before ? '<' : '>', $cursorId)
            ->orderBy('id', $before ? 'desc' : 'asc')->limit($limit)->get();
        if ($before) {
            $messages = $messages->reverse()->values();
        }

        return [
            'messages' => $messages->map(fn ($message) => $this->presenter->present($message, $viewerId))->all(),
            $before ? 'first_message_id' : 'last_message_id' => ($before ? $messages->first()?->id : $messages->last()?->id) ?? $cursorId,
        ];
    }

    public function lastSeen(Group $group, int $userId): ?int
    {
        return SeenMessage::join('messages', 'messages.id', '=', 'seen_messages.message_id')
            ->where('seen_messages.group_id', $group->id)->where('seen_messages.user_id', $userId)
            ->where('messages.group_id', $group->id)->where('messages.deleted', false)
            ->orderByDesc('seen_messages.id')->limit(1)->value('messages.id');
    }

    public function store(array $data, int $userId): Message
    {
        $group = Group::where('complex_id', $data['complexId'])->firstOrFail();

        return Message::create([
            'group_id' => $group->id,
            'user_id' => $userId,
            'text' => $data['messageText'] ?? null,
            'image_url' => isset($data['messageImage']) ? $this->uploads->store($data['messageImage'], 'public/chats') : null,
        ]);
    }

    /**
     * @return \Illuminate\Support\Collection<int, array{user_name: string, viewed_at: \Illuminate\Support\Carbon|null}>
     */
    public function viewers(int $messageId)
    {
        $message = Message::findOrFail($messageId);

        return SeenMessage::where('message_id', $message->id)->where('user_id', '!=', $message->user_id)
            ->with('user')->orderBy('created_at', 'desc')->get()->map(fn ($viewer) => [
                'user_name' => $viewer->user->name, 'viewed_at' => $viewer->created_at,
            ]);
    }

    public function seen(int $messageId, int $userId): void
    {
        $message = Message::findOrFail($messageId);
        SeenMessage::firstOrCreate(['group_id' => $message->group_id, 'user_id' => $userId, 'message_id' => $message->id]);
    }
}
