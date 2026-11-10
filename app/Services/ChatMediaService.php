<?php

namespace App\Services;

use App\Models\Group;
use App\Models\Message;

class ChatMediaService
{
    public const PAGE_SIZE = 10;

    /** @return array{images: list<array{id: int, url: string, alt: string}>, next_cursor: int|null} */
    public function page(Group $group, string $direction, ?int $cursor): array
    {
        $before = $direction === 'before';
        $images = $group->messages()->with('user')->where('deleted', false)
            ->whereNotNull('image_url')->where('image_url', '!=', '')
            ->when($cursor, fn ($query) => $query->where('id', $before ? '<' : '>', $cursor))
            ->orderBy('id', $before ? 'desc' : 'asc')->limit(self::PAGE_SIZE + 1)->get();
        $more = $images->count() > self::PAGE_SIZE;
        $images = $images->take(self::PAGE_SIZE);

        return [
            'images' => $images->map(fn (Message $message) => [
                'id' => $message->id,
                'url' => route('chat.attachments.show', $message, absolute: false),
                'alt' => 'تصویر پیام '.$message->user->name,
            ])->values()->all(),
            'next_cursor' => $more ? $images->last()->id : null,
        ];
    }
}
