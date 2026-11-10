<?php

namespace App\Services;

use App\Events\ChatSeenCountChanged;
use App\Models\Group;
use App\Models\Message;
use App\Models\SeenMessage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class ChatService
{
    public function __construct(private MessagePresenter $presenter, private FileUploadService $uploads) {}

    /**
     * A zero anchor starts at the latest message. One-sided pages exclude the
     * anchor and keep it as the cursor when no further messages exist.
     *
     * @return array{messages: list<array<string, mixed>>, first_message_id?: int|null, last_message_id?: int|null}
     */
    public function fetch(Group $group, string $direction, int $cursorId, int $limit, int $viewerId): array
    {
        if ($direction === 'around') {
            $query = $group->messages()->with(MessagePresenter::RELATIONS);
            $current = $cursorId === 0 ? $query->orderByDesc('id')->first() : $query->find($cursorId);
            if (! $current) {
                abort_if($group->messages()->exists(), 404);

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
        $messages = $group->messages()->with(MessagePresenter::RELATIONS)
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
        $group = Group::where('complex_id', $data['complexId'])->orderBy('id')->firstOrFail();
        $parent = $data['parent_id'] ?? null;
        if ($parent !== null && ! $group->messages()->whereKey($parent)->where('deleted', false)->exists()) {
            throw ValidationException::withMessages(['parent_id' => 'پیام پاسخ در این گفتگو در دسترس نیست.']);
        }

        return Message::create([
            'group_id' => $group->id,
            'user_id' => $userId,
            'text' => $data['messageText'] ?? null,
            'parent_id' => $parent,
            'image_url' => isset($data['messageImage']) ? $this->uploads->store($data['messageImage'], Message::ATTACHMENT_DIRECTORY) : null,
        ]);
    }

    /**
     * @return Collection<int, array{user_name: string, viewed_at: Carbon|null}>
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
        $receipt = SeenMessage::firstOrCreate(['group_id' => $message->group_id, 'user_id' => $userId, 'message_id' => $message->id]);
        if ($receipt->wasRecentlyCreated && $userId !== $message->user_id && ! $message->deleted) {
            event(new ChatSeenCountChanged($message->group->complex_id, $message->id, $this->seenCounts([$message->id])[0]['count']));
        }
    }

    /** @return list<array{message_id: int, count: int}> */
    public function seenCounts(array $messageIds): array
    {
        $counts = SeenMessage::join('messages', 'messages.id', '=', 'seen_messages.message_id')
            ->whereIn('messages.id', $messageIds)->whereColumn('seen_messages.user_id', '!=', 'messages.user_id')
            ->selectRaw('messages.id, COUNT(*) AS receipt_count')->groupBy('messages.id')->pluck('receipt_count', 'id');

        return array_map(fn ($id) => ['message_id' => (int) $id, 'count' => (int) ($counts[$id] ?? 0)], $messageIds);
    }
}
