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
                'meta' => $this->safeMetadata($event, $meta),
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

    private function safeMetadata(string $event, array $meta): ?array
    {
        if (! str_starts_with($event, 'join_link.')) {
            return $meta ?: null;
        }
        $from = $meta['from'] ?? null;
        $to = $meta['to'] ?? null;

        return $event === 'join_link.rotated' && in_array($from, JoinLinks::LIFECYCLES, true) && in_array($to, JoinLinks::LIFECYCLES, true)
            ? compact('from', 'to')
            : null;
    }

    protected function write(array $attributes): void
    {
        AuditEvent::create($attributes);
    }
}
