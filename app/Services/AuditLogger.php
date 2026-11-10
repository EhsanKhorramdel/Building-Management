<?php

namespace App\Services;

use App\Models\AuditEvent;
use Illuminate\Support\Facades\Log;
use Throwable;

class AuditLogger
{
    final public function record(string $event, int $actorId, ?int $complexId, string $subjectType, int $subjectId, array $meta = []): void
    {
        try {
            $this->write([
                'event' => $event,
                'actor_id' => $actorId,
                'complex_id' => $complexId,
                'subject_type' => $subjectType,
                'subject_id' => $subjectId,
                'meta' => $event === 'join_link.rotated' ? null : ($meta ?: null),
            ]);
        } catch (Throwable $failure) {
            rescue(fn () => Log::warning('Audit event could not be recorded.', [
                'event' => $event,
                'actor_id' => $actorId,
                'complex_id' => $complexId,
                'error' => $failure::class,
            ]), report: false);
        }
    }

    protected function write(array $attributes): void
    {
        AuditEvent::create($attributes);
    }
}
