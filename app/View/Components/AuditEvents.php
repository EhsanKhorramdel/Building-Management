<?php

namespace App\View\Components;

use App\Models\AuditEvent;
use App\Models\Complex;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\Component;
use Illuminate\View\View;

class AuditEvents extends Component
{
    public function __construct(public ?Complex $complex = null) {}

    public function shouldRender(): bool
    {
        return Gate::allows('viewAny', [AuditEvent::class, $this->complex]);
    }

    public function render(): View
    {
        $events = AuditEvent::with(['actor', 'complex'])
            ->when($this->complex, fn ($query, $complex) => $query->whereBelongsTo($complex))
            ->latest('id')->limit(100)->get();

        return view('components.audit-events', compact('events'));
    }
}
