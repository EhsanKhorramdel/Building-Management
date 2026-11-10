<?php

namespace App\Services;

use App\Models\Group;
use App\Models\Message;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ChatSocialService
{
    public const BATCH_LIMIT = 50;

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

    public function delete(Message $message, int $userId, ?string $reason = null): ?Message
    {
        $actor = User::findOrFail($userId);
        [$message, $wasPinned] = DB::transaction(function () use ($message, $actor, $reason) {
            $message = $this->lock($message);
            $complex = $message->group->complex;
            $moderated = $message->user_id !== $actor->id;
            if (! $actor->can('view', $complex) || ($moderated && ! $actor->can('manage', $complex))) {
                throw new AuthorizationException;
            }
            if ($moderated && (! is_string($reason) || trim($reason) === '' || mb_strlen($reason) > 240)) {
                throw ValidationException::withMessages(['reason' => 'دلیل حذف پیام را حداکثر در ۲۴۰ نویسه وارد کنید.']);
            }
            if ($message->deleted) {
                return [null, false];
            }
            $wasPinned = $message->pinned_at !== null;
            $message->forceFill([
                'deleted' => true,
                'deletion_kind' => $moderated ? 'manager' : 'author',
                'deleted_by' => $actor->id,
                'deleted_at' => now(),
                'deletion_reason' => $moderated ? trim($reason) : null,
                'pinned_at' => null,
                'pinned_by' => null,
            ])->save();

            return [$message, $wasPinned];
        }, 3);

        if ($wasPinned) {
            $this->recordPin($message, $userId, false, ['reason' => 'message_deleted']);
        }
        if ($message?->deletion_kind === 'manager') {
            $this->audit->record('chat.message_moderated', $userId, $message->group->complex_id,
                Message::class, $message->id, ['reason' => $message->deletion_reason]);
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
