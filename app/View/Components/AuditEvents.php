<?php

namespace App\View\Components;

use App\Models\AuditEvent;
use App\Models\Complex;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\Component;
use Illuminate\View\View;

class AuditEvents extends Component
{
    public function __construct(public ?Complex $complex = null, public bool $detail = false) {}

    public function shouldRender(): bool
    {
        return Gate::allows('viewAny', [AuditEvent::class, $this->complex]);
    }

    public function render(): View
    {
        $adminDetail = $this->detail && ! $this->complex;
        $query = AuditEvent::with(['actor', 'complex'])
            ->when($this->complex, fn ($query, $complex) => $query->whereBelongsTo($complex))
            ->latest('id');

        $complexes = $eventTypes = collect();
        if ($adminDetail) {
            $filters = request()->validate([
                'complex' => 'nullable|integer|min:1',
                'event' => 'nullable|string|max:100',
            ]);
            $query->when($filters['complex'] ?? null, fn ($query, $id) => $query->where('complex_id', $id))
                ->when($filters['event'] ?? null, fn ($query, $event) => $query->where('event', $event));
            $complexes = Complex::orderBy('name')->get(['id', 'name']);
            $eventTypes = DB::table('audit_events')->distinct()->orderBy('event')->pluck('event');
        }

        $events = $adminDetail ? $query->paginate(100)->withQueryString() : $query->limit(100)->get();
        $subjects = collect();
        if ($this->detail) {
            $rows = $adminDetail ? $events->getCollection() : $events;
            $subjects = User::whereKey($rows->where('subject_type', User::class)->pluck('subject_id')->unique()->all())->get()->keyBy('id');
            if ($adminDetail) {
                $rows->loadMissing('subject');
            }
        }

        $avatarUsers = $this->complex ? $this->complex->userRoles()->where('active', true)->whereIn('role', ['manager', 'resident'])->pluck('user_id')->flip() : null;

        return view('components.audit-events', compact('events', 'adminDetail', 'subjects', 'complexes', 'eventTypes', 'avatarUsers'));
    }
}
