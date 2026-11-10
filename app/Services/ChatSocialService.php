<?php

namespace App\Services;

use App\Models\Group;
use App\Models\Message;
use Illuminate\Support\Facades\DB;

class ChatSocialService
{
    public function __construct(private MessagePresenter $presenter, private AuditLogger $audit) {}

    public function toggleReaction(Message $message, int $userId, string $emoji): array
    {
        return DB::transaction(function () use ($message, $userId, $emoji) {
            $message = $this->lock($message);
            abort_if($message->deleted, 404);
            $attributes = ['user_id' => $userId, 'emoji' => $emoji];
            if (! $message->reactions()->where($attributes)->delete()) {
                $message->reactions()->create($attributes);
            }
            $message->increment('social_version');

            return [
                'message_id' => $message->id,
                'version' => $message->social_version,
                'reactions' => $this->presenter->reactions($message, $userId),
            ];
        }, 3);
    }

    public function setPin(Message $message, int $userId, bool $pinned): array
    {
        [$message, $changed] = DB::transaction(function () use ($message, $userId, $pinned) {
            $message = $this->lock($message);
            abort_if($message->deleted, 404);
            $changed = ($message->pinned_at !== null) !== $pinned;
            if ($changed) {
                $message->increment('social_version', 1, [
                    'pinned_by' => $pinned ? $userId : null,
                    'pinned_at' => $pinned ? now() : null,
                ]);
            }

            return [$message, $changed];
        }, 3);

        if ($changed) {
            $this->recordPin($message, $userId, $pinned);
        }

        return [
            'message_id' => $message->id,
            'version' => $message->social_version,
            'pin' => $this->presenter->pin($message),
        ];
    }

    public function pins(Group $group): array
    {
        return $group->messages()->with('user')->where('deleted', false)->whereNotNull('pinned_at')
            ->orderByDesc('pinned_at')->orderByDesc('id')->get()
            ->map(fn (Message $message) => $this->presenter->pin($message))->all();
    }

    public function delete(Message $message, int $userId): ?Message
    {
        [$message, $wasPinned] = DB::transaction(function () use ($message) {
            $message = $this->lock($message);
            if ($message->deleted) {
                return [null, false];
            }
            $wasPinned = $message->pinned_at !== null;
            $message->forceFill([
                'deleted' => true,
                'deletion_kind' => 'author',
                'pinned_at' => null,
                'pinned_by' => null,
            ])->save();

            return [$message, $wasPinned];
        }, 3);

        if ($wasPinned) {
            $this->recordPin($message, $userId, false, ['reason' => 'message_deleted']);
        }

        return $message;
    }

    private function lock(Message $message): Message
    {
        return Message::lockForUpdate()->findOrFail($message->id);
    }

    private function recordPin(Message $message, int $actor, bool $pinned, array $meta = []): void
    {
        $this->audit->record($pinned ? 'chat.message_pinned' : 'chat.message_unpinned',
            $actor, $message->group->complex_id, Message::class, $message->id, $meta);
    }
}
