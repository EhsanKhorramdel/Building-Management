<?php

namespace App\Services;

use App\Models\Announcement;
use App\Models\Complex;
use App\Support\TypedInput;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class AnnouncementService
{
    public function __construct(private AuditLogger $audit) {}

    public function listing(Complex $complex, int $userId): Builder
    {
        return $complex->announcements()->getQuery()->with('manager')->withCount('reads')
            ->withExists(['reads as is_read' => fn ($query) => $query->where('user_id', $userId)])
            ->orderByRaw('pinned_at IS NULL')->orderBy('pinned_at')->orderBy('id');
    }

    public function search(Complex $complex, int $userId, string $state, ?string $text): Builder
    {
        $query = $this->listing($complex, $userId);
        if ($state !== 'all') {
            $query->where('archive', $state === 'archived');
        }
        if ($text !== null && trim($text) !== '') {
            $expression = "COALESCE(text, '')";
            $bindings = [];
            foreach (TypedInput::searchMap() as $from => $to) {
                $expression = 'REPLACE('.$expression.', ?, ?)';
                array_push($bindings, $from, $to);
            }
            $pattern = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], TypedInput::searchText($text)).'%';
            $query->whereRaw($expression." LIKE ? ESCAPE '!'", [...$bindings, $pattern]);
        }

        return $query;
    }

    public function pin(Announcement $announcement, int $actorId, bool $pinned): void
    {
        $changed = DB::transaction(function () use ($announcement, $actorId, $pinned) {
            $announcement = Announcement::lockForUpdate()->findOrFail($announcement->id);
            if (($announcement->pinned_at !== null) === $pinned) {
                return false;
            }
            // Preserve pin order when two actions occur within the same second.
            Announcement::whereKey($announcement->id)->update([
                'pinned_at' => $pinned ? now()->format('Y-m-d H:i:s.u') : null,
                'pinned_by' => $pinned ? $actorId : null,
            ]);

            return true;
        });
        if ($changed) {
            $this->audit->record($pinned ? 'announcement.pinned' : 'announcement.unpinned', $actorId,
                $announcement->complex_id, Announcement::class, $announcement->id);
        }
    }
}
