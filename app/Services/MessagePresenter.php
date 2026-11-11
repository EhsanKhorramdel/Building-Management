<?php

namespace App\Services;

use App\Models\Message;
use App\Models\UserRole;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class MessagePresenter
{
    public const RELATIONS = ['user', 'parent.user', 'reactions.user'];

    /**
     * @return array{message_id: int, user_name: string, user_id: int, text: string|null, image_url: string|null,
     *     created_at: string, is_deleted: bool, is_edited: bool, is_owned_by_logged_in_user: bool,
     *     deletion_kind?: string, quote?: array{message_id: int, deleted?: bool, user_name?: string, snippet?: string},
     *     reaction_version?: int, reactions?: list<array{emoji: string, count: int, reactors: list<array{id: int, name: string}>, reacted?: bool}>}
     */
    public function present(Message $message, ?int $viewerId = null, ?bool $showAvatar = null): array
    {
        $payload = [
            'message_id' => $message->id,
            'user_name' => $message->user->name,
            'user_id' => $message->user_id,
            'text' => $message->deleted ? null : $message->text,
            'image_url' => $message->deleted ? null : $this->attachmentUrl($message),
            'created_at' => $message->created_at->format('Y-m-d H:i:s'),
            'is_deleted' => $message->deleted,
            'is_edited' => $message->deleted ? false : $message->edited,
            'is_owned_by_logged_in_user' => $message->user_id == $viewerId,
        ];

        if ($message->deleted) {
            return $payload + ['deletion_kind' => $this->deletionKind($message)];
        }

        if ($message->user->avatar_path) {
            $showAvatar ??= UserRole::where('complex_id', $message->group->complex_id)->where('user_id', $message->user_id)
                ->where('active', true)->whereIn('role', ['resident', 'manager'])->exists();
            if ($showAvatar) {
                $payload['avatar_url'] = $message->user->avatarUrl();
            }
        }

        $message->loadMissing(['parent.user', 'reactions.user']);
        $parent = $message->parent;
        if ($parent && $parent->group_id === $message->group_id) {
            $payload['quote'] = $parent->deleted
                ? ['message_id' => $parent->id, 'deleted' => true]
                : ['message_id' => $parent->id, 'user_name' => $parent->user->name, 'snippet' => $this->snippet($parent)];
        }
        if ($message->social_version || $message->reactions->isNotEmpty()) {
            $payload['reaction_version'] = (int) $message->social_version;
            $payload['reactions'] = $this->reactions($message, $viewerId);
        }

        return $payload;
    }

    public function broadcastModel(Message $message): array
    {
        // Keep the edit payload compatible with clients that use the model's id.
        return $message->deleted
            ? $this->deletion($message)
            : array_replace(Arr::except($message->attributesToArray(), [
                'parent_id', 'pinned_at', 'pinned_by', 'deletion_kind', 'social_version',
                'deleted_by', 'deleted_at', 'deletion_reason',
            ]), ['image_url' => $this->attachmentUrl($message)]);
    }

    public function deletion(Message $message): array
    {
        return ['id' => $message->id, 'deletion_kind' => $this->deletionKind($message)];
    }

    public function reactions(Message $message, ?int $viewerId = null): array
    {
        $message->loadMissing('reactions.user');

        return $message->reactions->sortBy('id')->groupBy('emoji')->map(function ($rows, $emoji) use ($viewerId) {
            $summary = [
                'emoji' => $emoji,
                'count' => $rows->count(),
                'reactors' => $rows->map(fn ($row) => ['id' => $row->user_id, 'name' => $row->user->name])->values()->all(),
            ];
            if ($viewerId !== null) {
                $summary['reacted'] = $rows->contains('user_id', $viewerId);
            }

            return $summary;
        })->values()->all();
    }

    public function pin(Message $message): ?array
    {
        if (! $message->pinned_at || $message->deleted) {
            return null;
        }

        return [
            'message_id' => $message->id,
            'user_name' => $message->user->name,
            'snippet' => $this->snippet($message),
            'pinned_at' => $message->pinned_at->toISOString(),
            'version' => $message->social_version,
        ];
    }

    private function deletionKind(Message $message): string
    {
        return $message->deletion_kind ?? 'author';
    }

    private function snippet(Message $message): string
    {
        return Str::limit(Str::squish($message->text ?? ''), 96) ?: 'تصویر';
    }

    private function attachmentUrl(Message $message): ?string
    {
        return $message->image_url ? route('chat.attachments.show', $message, absolute: false) : null;
    }
}
