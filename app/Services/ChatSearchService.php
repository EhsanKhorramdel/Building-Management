<?php

namespace App\Services;

use App\Models\Group;
use App\Models\Message;
use App\Support\TypedInput;
use Morilog\Jalali\Jalalian;

class ChatSearchService
{
    public const PAGE_SIZE = 20;

    /**
     * @return array{results: list<array{message_id: int, user_name: string, date: string, snippet: string}>, next_before_id: int|null}
     */
    public function search(Group $group, string $query, ?int $beforeId = null): array
    {
        $query = TypedInput::searchText($query);
        $expression = "COALESCE(messages.text, '')";
        $bindings = [];
        // Apply the same substitutions to stored text without changing historical message bodies.
        foreach (TypedInput::searchMap() as $from => $to) {
            $expression = 'REPLACE('.$expression.', ?, ?)';
            array_push($bindings, $from, $to);
        }
        $pattern = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $query).'%';
        $messages = $group->messages()->with('user')->where('deleted', false)
            ->whereRaw($expression." LIKE ? ESCAPE '!'", [...$bindings, $pattern])
            ->when($beforeId, fn ($builder) => $builder->where('id', '<', $beforeId))
            ->orderByDesc('id')->limit(self::PAGE_SIZE + 1)->get();
        $hasMore = $messages->count() > self::PAGE_SIZE;
        $messages = $messages->take(self::PAGE_SIZE);

        return [
            'results' => $messages->map(function (Message $message) use ($query) {
                $text = TypedInput::searchText($message->text ?? '');
                $start = max(0, (mb_stripos($text, $query) ?: 0) - 40);
                $snippet = mb_substr($text, $start, 140);

                return [
                    'message_id' => $message->id,
                    'user_name' => $message->user->name,
                    'date' => Jalalian::fromCarbon($message->created_at)->format('Y/m/d H:i'),
                    'snippet' => ($start ? '…' : '').$snippet.(mb_strlen($text) > $start + 140 ? '…' : ''),
                ];
            })->values()->all(),
            'next_before_id' => $hasMore ? $messages->last()->id : null,
        ];
    }
}
